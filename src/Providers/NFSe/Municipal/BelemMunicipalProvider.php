<?php

declare(strict_types=1);

namespace freeline\FiscalCore\Providers\NFSe\Municipal;

use freeline\FiscalCore\Contracts\NFSeOperationalIntrospectionInterface;
use freeline\FiscalCore\Providers\NFSe\AbstractNFSeProvider;
use freeline\FiscalCore\Support\CertificateManager;
use freeline\FiscalCore\Support\NFSeSchemaResolver;
use freeline\FiscalCore\Support\NFSeSchemaValidator;
use freeline\FiscalCore\Support\NFSeSoapCurlTransport;
use freeline\FiscalCore\Support\NFSeSoapTransportInterface;
use NFePHP\Common\Certificate;
use NFePHP\Common\Signer;

class BelemMunicipalProvider extends AbstractNFSeProvider implements NFSeOperationalIntrospectionInterface
{
    private const NFSE_NS = 'http://www.abrasf.org.br/nfse.xsd';
    private const DSIG_NS = 'http://www.w3.org/2000/09/xmldsig#';
    private const SERVICE_NS = 'http://nfse.abrasf.org.br';

    private ?string $lastRequestXml = null;
    private ?string $lastSoapEnvelope = null;
    private ?string $lastResponseXml = null;
    private array $lastResponseData = [];
    private array $lastTransportData = [];
    private ?string $lastOperation = null;
    private array $lastOperationArtifacts = [];
    private array $lastPrestadorContext = [];

    private NFSeSoapTransportInterface $transport;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->transport = $config['soap_transport'] ?? new NFSeSoapCurlTransport();
    }

    public function emitir(array $dados): string
    {
        $this->validarDados($dados);
        $this->lastPrestadorContext = $this->extractPrestadorContext($dados['prestador'] ?? []);

        $requestXml = $this->montarXmlRps($dados);
        if ($this->shouldSignOperation('emitir')) {
            $requestXml = $this->assinarXml($requestXml, 'emitir');
        }

        return $this->dispatchSoapOperation(
            'emitir',
            'RecepcionarLoteRpsSincrono',
            $requestXml,
            'emitir'
        );
    }

    public function consultarPorRps(array $identificacaoRps): string
    {
        $this->validarIdentificacaoRps($identificacaoRps);

        return $this->dispatchSoapOperation(
            'consultar_nfse_rps',
            'ConsultarNfsePorRps',
            $this->montarXmlConsultarNfsePorRps($identificacaoRps),
            'consultar_nfse_rps'
        );
    }

    public function consultarLote(string $protocolo): string
    {
        if (trim($protocolo) === '') {
            throw new \InvalidArgumentException('Protocolo do lote é obrigatório para consulta em Belém.');
        }

        return $this->dispatchSoapOperation(
            'consultar_lote',
            'ConsultarLoteRps',
            $this->montarXmlConsultarLote($protocolo),
            'consultar_lote'
        );
    }

    public function cancelar(string $chave, string $motivo, ?string $protocolo = null): bool
    {
        if (trim($chave) === '') {
            throw new \InvalidArgumentException('Número da NFSe é obrigatório para cancelamento em Belém.');
        }

        if (trim($motivo) === '') {
            throw new \InvalidArgumentException('Motivo do cancelamento é obrigatório para Belém.');
        }

        $requestXml = $this->montarXmlCancelarNfse($chave, $motivo, $protocolo);
        if ($this->shouldSignOperation('cancelar_nfse')) {
            $requestXml = $this->assinarXml($requestXml, 'cancelar_nfse');
        }

        $this->dispatchSoapOperation(
            'cancelar_nfse',
            'CancelarNfse',
            $requestXml,
            'cancelar_nfse'
        );

        return ($this->lastResponseData['status'] ?? 'unknown') === 'success';
    }

    protected function montarXmlRps(array $dados): string
    {
        $prestador = $dados['prestador'];
        $servico = $this->resolveServicoData($dados);
        $tomador = $dados['tomador'];
        $rps = $dados['rps'] ?? [];
        $lote = $dados['lote'] ?? [];

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        $root = $dom->createElementNS(self::NFSE_NS, 'EnviarLoteRpsSincronoEnvio');
        $dom->appendChild($root);

        $loteRps = $this->appendXmlNode($dom, $root, 'LoteRps');
        $loteRps->setAttribute('Id', (string) ($lote['id'] ?? 'LoteBelem1'));
        $loteRps->setAttribute('versao', (string) ($this->config['versao'] ?? '2.03'));

        $this->appendXmlNode($dom, $loteRps, 'NumeroLote', (string) ($lote['numero'] ?? '1'));
        $cpfCnpjPrestador = $this->appendXmlNode($dom, $loteRps, 'CpfCnpj');
        $this->appendDocumentoNode($dom, $cpfCnpjPrestador, $this->normalizeDigits((string) $prestador['cnpj']));
        $this->appendXmlNode($dom, $loteRps, 'InscricaoMunicipal', (string) $prestador['inscricaoMunicipal']);
        $this->appendXmlNode($dom, $loteRps, 'QuantidadeRps', '1');

        $listaRps = $this->appendXmlNode($dom, $loteRps, 'ListaRps');
        $rpsNode = $this->appendXmlNode($dom, $listaRps, 'Rps');
        $infDeclaracao = $this->appendXmlNode($dom, $rpsNode, 'InfDeclaracaoPrestacaoServico');
        $infDeclaracao->setAttribute('Id', (string) ($dados['id'] ?? 'BelemRps1'));

        $infRps = $this->appendXmlNode($dom, $infDeclaracao, 'Rps');
        $infRps->setAttribute('Id', (string) ($rps['id'] ?? (($dados['id'] ?? 'BelemRps1') . '-rps')));

        $identificacaoRps = $this->appendXmlNode($dom, $infRps, 'IdentificacaoRps');
        $this->appendXmlNode($dom, $identificacaoRps, 'Numero', (string) ($rps['numero'] ?? '1'));
        $this->appendXmlNode($dom, $identificacaoRps, 'Serie', (string) ($rps['serie'] ?? 'RPS'));
        $this->appendXmlNode($dom, $identificacaoRps, 'Tipo', (string) ($rps['tipo'] ?? '1'));
        $this->appendXmlNode($dom, $infRps, 'DataEmissao', $this->xmlDate((string) ($rps['data_emissao'] ?? null)));
        $this->appendXmlNode($dom, $infRps, 'Status', (string) ($rps['status'] ?? '1'));

        $this->appendXmlNode(
            $dom,
            $infDeclaracao,
            'Competencia',
            $this->xmlDate((string) ($dados['competencia'] ?? $rps['data_emissao'] ?? null))
        );

        $servicoNode = $this->appendXmlNode($dom, $infDeclaracao, 'Servico');
        $valoresNode = $this->appendXmlNode($dom, $servicoNode, 'Valores');
        $this->appendXmlNode($dom, $valoresNode, 'ValorServicos', $this->decimal((float) $servico['valor_servicos']));
        $this->appendOptionalDecimal($dom, $valoresNode, 'ValorDeducoes', $servico['valor_deducoes'] ?? 0.0);
        $this->appendOptionalDecimal($dom, $valoresNode, 'ValorPis', $servico['valor_pis'] ?? 0.0);
        $this->appendOptionalDecimal($dom, $valoresNode, 'ValorCofins', $servico['valor_cofins'] ?? 0.0);
        $this->appendOptionalDecimal($dom, $valoresNode, 'ValorInss', $servico['valor_inss'] ?? 0.0);
        $this->appendOptionalDecimal($dom, $valoresNode, 'ValorIr', $servico['valor_ir'] ?? 0.0);
        $this->appendOptionalDecimal($dom, $valoresNode, 'ValorCsll', $servico['valor_csll'] ?? 0.0);
        $this->appendOptionalDecimal($dom, $valoresNode, 'OutrasRetencoes', $servico['outras_retencoes'] ?? 0.0);
        $this->appendOptionalDecimal($dom, $valoresNode, 'ValorIss', $servico['valor_iss'] ?? null);
        $this->appendOptionalDecimal($dom, $valoresNode, 'Aliquota', $servico['aliquota'] ?? null, 4);
        $this->appendOptionalDecimal($dom, $valoresNode, 'DescontoIncondicionado', $servico['desconto_incondicionado'] ?? 0.0);
        $this->appendOptionalDecimal($dom, $valoresNode, 'DescontoCondicionado', $servico['desconto_condicionado'] ?? 0.0);

        $this->appendXmlNode($dom, $servicoNode, 'IssRetido', $this->booleanCode((bool) ($servico['iss_retido'] ?? false)));
        $this->appendXmlNode($dom, $servicoNode, 'ItemListaServico', (string) $servico['item_lista_servico']);
        $this->appendXmlNode($dom, $servicoNode, 'CodigoCnae', (string) $servico['codigo_cnae']);
        if (!empty($servico['codigo_tributacao_municipio'])) {
            $this->appendXmlNode($dom, $servicoNode, 'CodigoTributacaoMunicipio', (string) $servico['codigo_tributacao_municipio']);
        }
        $this->appendXmlNode($dom, $servicoNode, 'Discriminacao', (string) $servico['discriminacao']);
        $this->appendXmlNode($dom, $servicoNode, 'CodigoMunicipio', (string) $servico['codigo_municipio']);
        $this->appendXmlNode($dom, $servicoNode, 'ExigibilidadeISS', (string) ($servico['exigibilidade_iss'] ?? '1'));

        $prestadorNode = $this->appendXmlNode($dom, $infDeclaracao, 'Prestador');
        $cpfCnpjPrestadorInterno = $this->appendXmlNode($dom, $prestadorNode, 'CpfCnpj');
        $this->appendDocumentoNode($dom, $cpfCnpjPrestadorInterno, $this->normalizeDigits((string) $prestador['cnpj']));
        $this->appendXmlNode($dom, $prestadorNode, 'InscricaoMunicipal', (string) $prestador['inscricaoMunicipal']);

        $tomadorNode = $this->appendXmlNode($dom, $infDeclaracao, 'Tomador');
        $identificacaoTomador = $this->appendXmlNode($dom, $tomadorNode, 'IdentificacaoTomador');
        $cpfCnpjTomador = $this->appendXmlNode($dom, $identificacaoTomador, 'CpfCnpj');
        $this->appendDocumentoNode($dom, $cpfCnpjTomador, $this->normalizeDigits((string) $tomador['documento']));
        if (!empty($tomador['inscricaoMunicipal'])) {
            $this->appendXmlNode($dom, $identificacaoTomador, 'InscricaoMunicipal', (string) $tomador['inscricaoMunicipal']);
        }
        $this->appendXmlNode($dom, $tomadorNode, 'RazaoSocial', (string) $tomador['razao_social']);
        $enderecoNode = $this->appendXmlNode($dom, $tomadorNode, 'Endereco');
        $this->appendXmlNode($dom, $enderecoNode, 'Endereco', (string) $tomador['endereco']['logradouro']);
        $this->appendXmlNode($dom, $enderecoNode, 'Numero', (string) ($tomador['endereco']['numero'] ?? 'S/N'));
        if (!empty($tomador['endereco']['complemento'])) {
            $this->appendXmlNode($dom, $enderecoNode, 'Complemento', (string) $tomador['endereco']['complemento']);
        }
        $this->appendXmlNode($dom, $enderecoNode, 'Bairro', (string) $tomador['endereco']['bairro']);
        $this->appendXmlNode(
            $dom,
            $enderecoNode,
            'CodigoMunicipio',
            (string) ($tomador['endereco']['codigo_municipio'] ?? $servico['codigo_municipio'])
        );
        $this->appendXmlNode($dom, $enderecoNode, 'Uf', (string) ($tomador['endereco']['uf'] ?? 'PA'));
        $this->appendXmlNode($dom, $enderecoNode, 'Cep', $this->normalizeDigits((string) $tomador['endereco']['cep']));

        if (!empty($tomador['telefone']) || !empty($tomador['email'])) {
            $contatoNode = $this->appendXmlNode($dom, $tomadorNode, 'Contato');
            if (!empty($tomador['telefone'])) {
                $this->appendXmlNode($dom, $contatoNode, 'Telefone', $this->normalizeDigits((string) $tomador['telefone']));
            }
            if (!empty($tomador['email'])) {
                $this->appendXmlNode($dom, $contatoNode, 'Email', (string) $tomador['email']);
            }
        }

        if (!empty($prestador['regime_especial_tributacao'])) {
            $this->appendXmlNode(
                $dom,
                $infDeclaracao,
                'RegimeEspecialTributacao',
                (string) $prestador['regime_especial_tributacao']
            );
        }
        $this->appendXmlNode(
            $dom,
            $infDeclaracao,
            'OptanteSimplesNacional',
            $this->booleanCode((bool) ($prestador['simples_nacional'] ?? false))
        );
        $this->appendXmlNode(
            $dom,
            $infDeclaracao,
            'IncentivoFiscal',
            $this->booleanCode((bool) ($prestador['incentivo_fiscal'] ?? false))
        );

        return $dom->saveXML($dom->documentElement) ?: '';
    }

    public function validarDados(array $dados): bool
    {
        parent::validarDados($dados);

        $required = [
            'prestador.cnpj' => $this->normalizeDigits((string) ($dados['prestador']['cnpj'] ?? '')),
            'prestador.inscricaoMunicipal' => (string) ($dados['prestador']['inscricaoMunicipal'] ?? ''),
            'prestador.razao_social' => (string) ($dados['prestador']['razao_social'] ?? ''),
            'tomador.documento' => $this->normalizeDigits((string) ($dados['tomador']['documento'] ?? '')),
            'tomador.razao_social' => (string) ($dados['tomador']['razao_social'] ?? ''),
            'tomador.endereco.logradouro' => (string) ($dados['tomador']['endereco']['logradouro'] ?? ''),
            'tomador.endereco.bairro' => (string) ($dados['tomador']['endereco']['bairro'] ?? ''),
            'tomador.endereco.cep' => $this->normalizeDigits((string) ($dados['tomador']['endereco']['cep'] ?? '')),
            'servico.item_lista_servico' => (string) ($dados['servico']['item_lista_servico'] ?? $dados['servico']['codigo'] ?? ''),
            'servico.codigo_cnae' => (string) ($dados['servico']['codigo_cnae'] ?? $dados['servico']['codigo_atividade'] ?? ''),
            'servico.codigo_municipio' => (string) ($dados['servico']['codigo_municipio'] ?? ''),
            'servico.discriminacao' => (string) ($dados['servico']['discriminacao'] ?? $dados['servico']['descricao'] ?? ''),
            'servico.aliquota' => $dados['servico']['aliquota'] ?? null,
        ];

        foreach ($required as $field => $value) {
            if (trim((string) $value) === '') {
                throw new \InvalidArgumentException("Campo obrigatório ausente: {$field}");
            }
        }

        if (!isset($dados['prestador']['mei']) && !isset($dados['prestador']['regime_tributario'])) {
            throw new \InvalidArgumentException(
                'Belém exige classificação explícita do emitente para distinguir MEI do fluxo municipal.'
            );
        }

        if (($dados['prestador']['mei'] ?? false) === true) {
            throw new \InvalidArgumentException('Emitente MEI deve usar o provider nacional para Belém.');
        }

        $this->assertItensCompativeis($dados);

        return true;
    }

    protected function processarResposta(string $xmlResposta): array
    {
        if (trim($xmlResposta) === '') {
            return [
                'status' => 'empty',
                'mensagens' => ['Resposta vazia do webservice de Belém.'],
            ];
        }

        $dom = new \DOMDocument();
        if (!@$dom->loadXML($xmlResposta)) {
            return [
                'status' => 'invalid_xml',
                'mensagens' => ['Resposta XML inválida do webservice de Belém.'],
                'raw_xml' => $xmlResposta,
            ];
        }

        $xpath = new \DOMXPath($dom);

        $mensagens = [];
        foreach ($xpath->query("//*[local-name()='MensagemRetorno']") as $messageNode) {
            $codigo = trim((string) $xpath->evaluate("string(./*[local-name()='Codigo'])", $messageNode));
            $mensagem = trim((string) $xpath->evaluate("string(./*[local-name()='Mensagem'])", $messageNode));
            $correcao = trim((string) $xpath->evaluate("string(./*[local-name()='Correcao'])", $messageNode));

            $parts = array_values(array_filter([$codigo, $mensagem, $correcao]));
            if ($parts !== []) {
                $mensagens[] = implode(' ', $parts);
            }
        }

        $protocol = $this->firstNodeValue($xpath, [
            "//*[local-name()='Protocolo']",
        ]);
        $numeroLote = $this->firstNodeValue($xpath, [
            "//*[local-name()='NumeroLote']",
        ]);
        $dataRecebimento = $this->firstNodeValue($xpath, [
            "//*[local-name()='DataRecebimento']",
        ]);
        $rootName = $dom->documentElement?->localName;

        $nfse = null;
        $compNfse = $xpath->query("//*[local-name()='CompNfse']")->item(0);
        if ($compNfse instanceof \DOMNode) {
            $nfse = [
                'numero' => $this->firstNodeValue($xpath, [".//*[local-name()='Numero']"], $compNfse),
                'codigo_verificacao' => $this->firstNodeValue($xpath, [".//*[local-name()='CodigoVerificacao']"], $compNfse),
                'data_emissao' => $this->firstNodeValue($xpath, [".//*[local-name()='DataEmissao']"], $compNfse),
                'valor_servicos' => $this->firstNodeValue($xpath, [".//*[local-name()='ValorServicos']"], $compNfse),
                'valor_liquido' => $this->firstNodeValue($xpath, [".//*[local-name()='ValorLiquidoNfse']"], $compNfse),
            ];
        }

        $listaNfse = [];
        foreach ($xpath->query("//*[local-name()='ListaNfse']/*[local-name()='CompNfse']") as $nfseNode) {
            $listaNfse[] = [
                'numero' => $this->firstNodeValue($xpath, [".//*[local-name()='Numero']"], $nfseNode),
                'codigo_verificacao' => $this->firstNodeValue($xpath, [".//*[local-name()='CodigoVerificacao']"], $nfseNode),
                'data_emissao' => $this->firstNodeValue($xpath, [".//*[local-name()='DataEmissao']"], $nfseNode),
                'tomador' => $this->firstNodeValue($xpath, [".//*[local-name()='RazaoSocial']"], $nfseNode),
                'valor_servicos' => $this->firstNodeValue($xpath, [".//*[local-name()='ValorServicos']"], $nfseNode),
            ];
        }

        foreach ($xpath->query("//*[local-name()='ListaNotaFiscal']/*[local-name()='Nfse']") as $nfseNode) {
            $listaNfse[] = [
                'numero' => $this->firstNodeValue($xpath, [".//*[local-name()='Numero']"], $nfseNode),
                'codigo_verificacao' => $this->firstNodeValue($xpath, [".//*[local-name()='CodigoVerificacao']"], $nfseNode),
                'data_emissao' => $this->firstNodeValue($xpath, [".//*[local-name()='DataEmissao']"], $nfseNode),
                'tomador' => $this->firstNodeValue($xpath, [".//*[local-name()='TomadorServico']/*[local-name()='RazaoSocial']"], $nfseNode),
                'valor_servicos' => $this->firstNodeValue($xpath, [".//*[local-name()='ValorServicos']"], $nfseNode),
            ];
        }

        $cancelamento = null;
        $cancelamentoNode = $xpath->query("//*[local-name()='InfPedidoCancelamento']")->item(0);
        if ($cancelamentoNode instanceof \DOMNode || str_contains((string) $rootName, 'CancelarNfseResponse')) {
            $cancelamento = [
                'numero' => $this->firstNodeValue($xpath, [
                    "//*[local-name()='InfPedidoCancelamento']//*[local-name()='Numero']",
                    "//*[local-name()='IdentificacaoNfse']/*[local-name()='Numero']",
                ]),
                'codigo_cancelamento' => $this->firstNodeValue($xpath, [
                    "//*[local-name()='CodigoCancelamento']",
                ]),
                'sucesso' => $mensagens === [],
            ];
        }

        $hasSuccessPayload = $nfse !== null
            || $listaNfse !== []
            || $cancelamento !== null
            || $protocol !== null
            || $numeroLote !== null;

        return [
            'status' => $mensagens !== [] ? 'error' : ($hasSuccessPayload ? 'success' : 'unknown'),
            'operation_response' => $rootName,
            'protocolo' => $protocol,
            'numero_lote' => $numeroLote,
            'data_recebimento' => $dataRecebimento,
            'nfse' => $nfse,
            'lista_nfse' => $listaNfse,
            'cancelamento' => $cancelamento,
            'mensagens' => array_values(array_filter($mensagens)),
            'raw_xml' => $xmlResposta,
        ];
    }

    public function getLastRequestXml(): ?string
    {
        return $this->lastRequestXml;
    }

    public function getLastSoapEnvelope(): ?string
    {
        return $this->lastSoapEnvelope;
    }

    public function getLastResponseXml(): ?string
    {
        return $this->lastResponseXml;
    }

    public function getLastResponseData(): array
    {
        return $this->lastResponseData;
    }

    public function getLastTransportData(): array
    {
        return $this->lastTransportData;
    }

    public function getLastOperation(): ?string
    {
        return $this->lastOperation;
    }

    public function getLastOperationArtifacts(): array
    {
        return $this->lastOperationArtifacts;
    }

    public function getSupportedOperations(): array
    {
        return [
            'emitir_sincrono',
            'consultar_lote',
            'consultar_nfse_rps',
            'cancelar_nfse',
        ];
    }

    private function resolveServicoData(array $dados): array
    {
        $servico = $dados['servico'];

        if (!empty($dados['itens']) && is_array($dados['itens'])) {
            $descricao = [];
            $valorTotal = 0.0;
            foreach ($dados['itens'] as $item) {
                $descricao[] = trim((string) ($item['descricao'] ?? $servico['descricao'] ?? $servico['discriminacao'] ?? 'Servico'));
                $valorTotal += (float) ($item['valor_servicos'] ?? $item['valor'] ?? 0);
            }

            $servico['discriminacao'] = implode(' | ', array_filter($descricao));
            $servico['valor_servicos'] = $valorTotal;
        } else {
            $servico['discriminacao'] = (string) ($servico['discriminacao'] ?? $servico['descricao'] ?? '');
            $servico['valor_servicos'] = (float) ($dados['valor_servicos'] ?? $servico['valor_servicos'] ?? 0.0);
        }

        $servico['item_lista_servico'] = (string) ($servico['item_lista_servico'] ?? $servico['codigo'] ?? '');
        $servico['codigo_cnae'] = $this->normalizeDigits((string) ($servico['codigo_cnae'] ?? $servico['codigo_atividade'] ?? ''));
        $servico['codigo_municipio'] = $this->normalizeDigits((string) ($servico['codigo_municipio'] ?? $this->getCodigoMunicipio()));
        $servico['aliquota'] = $this->normalizeAliquota($servico['aliquota'] ?? null);

        return $servico;
    }

    private function normalizeAliquota(mixed $aliquota): float
    {
        if ($aliquota === null || $aliquota === '') {
            return 0.0;
        }

        $value = (float) $aliquota;

        return $value > 1 ? $value / 100 : $value;
    }

    private function validarIdentificacaoRps(array $identificacaoRps): void
    {
        foreach (['numero', 'serie', 'tipo'] as $campo) {
            if (trim((string) ($identificacaoRps[$campo] ?? '')) === '') {
                throw new \InvalidArgumentException("Identificação RPS inválida para Belém: campo {$campo} é obrigatório.");
            }
        }
    }

    private function montarXmlConsultarLote(string $protocolo): string
    {
        $prestador = $this->resolvePrestadorContext();

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $root = $dom->createElementNS(self::NFSE_NS, 'ConsultarLoteRpsEnvio');
        $dom->appendChild($root);

        $prestadorNode = $this->appendXmlNode($dom, $root, 'Prestador');
        $cpfCnpjNode = $this->appendXmlNode($dom, $prestadorNode, 'CpfCnpj');
        $this->appendDocumentoNode($dom, $cpfCnpjNode, $prestador['cnpj']);
        $this->appendXmlNode($dom, $prestadorNode, 'InscricaoMunicipal', $prestador['inscricao_municipal']);
        $this->appendXmlNode($dom, $root, 'Protocolo', trim($protocolo));

        return $dom->saveXML($dom->documentElement) ?: '';
    }

    private function montarXmlConsultarNfsePorRps(array $identificacaoRps): string
    {
        $prestador = $this->resolvePrestadorContext($identificacaoRps['prestador'] ?? []);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $root = $dom->createElementNS(self::NFSE_NS, 'ConsultarNfseRpsEnvio');
        $dom->appendChild($root);

        $rpsNode = $this->appendXmlNode($dom, $root, 'IdentificacaoRps');
        $this->appendXmlNode($dom, $rpsNode, 'Numero', trim((string) $identificacaoRps['numero']));
        $this->appendXmlNode($dom, $rpsNode, 'Serie', trim((string) $identificacaoRps['serie']));
        $this->appendXmlNode($dom, $rpsNode, 'Tipo', trim((string) $identificacaoRps['tipo']));

        $prestadorNode = $this->appendXmlNode($dom, $root, 'Prestador');
        $cpfCnpjNode = $this->appendXmlNode($dom, $prestadorNode, 'CpfCnpj');
        $this->appendDocumentoNode($dom, $cpfCnpjNode, $prestador['cnpj']);
        $this->appendXmlNode($dom, $prestadorNode, 'InscricaoMunicipal', $prestador['inscricao_municipal']);

        return $dom->saveXML($dom->documentElement) ?: '';
    }

    private function montarXmlCancelarNfse(string $numeroNfse, string $motivo, ?string $protocolo): string
    {
        $prestador = $this->resolvePrestadorContext();
        $codigoCancelamento = $this->resolveCodigoCancelamento($protocolo);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $root = $dom->createElementNS(self::NFSE_NS, 'CancelarNfseEnvio');
        $dom->appendChild($root);

        $pedido = $this->appendXmlNode($dom, $root, 'Pedido');
        $infPedido = $this->appendXmlNode($dom, $pedido, 'InfPedidoCancelamento');
        $infPedido->setAttribute(
            'Id',
            sprintf(
                'Cancelamento_%s_%s',
                $prestador['cnpj'],
                $this->normalizeDigits($numeroNfse)
            )
        );

        $identificacao = $this->appendXmlNode($dom, $infPedido, 'IdentificacaoNfse');
        $this->appendXmlNode($dom, $identificacao, 'Numero', trim($numeroNfse));
        $cpfCnpjNode = $this->appendXmlNode($dom, $identificacao, 'CpfCnpj');
        $this->appendDocumentoNode($dom, $cpfCnpjNode, $prestador['cnpj']);
        $this->appendXmlNode($dom, $identificacao, 'InscricaoMunicipal', $prestador['inscricao_municipal']);
        $this->appendXmlNode($dom, $identificacao, 'CodigoMunicipio', $prestador['codigo_municipio']);
        $this->appendXmlNode($dom, $infPedido, 'CodigoCancelamento', $codigoCancelamento);

        return $dom->saveXML($dom->documentElement) ?: '';
    }

    private function dispatchSoapOperation(
        string $operationKey,
        string $soapOperation,
        string $requestXml,
        string $schemaOperation
    ): string {
        $this->assertRequestSchema($requestXml, $schemaOperation);

        $soapEnvelope = $this->montarSoapEnvelope($requestXml, $soapOperation);
        $transportData = $this->transport->send(
            $this->resolveSoapEndpoint(),
            $soapEnvelope,
            [
                'soap_action' => '',
                'timeout' => $this->getTimeout(),
                'soap_operation' => $soapOperation,
                'operation' => $operationKey,
            ]
        );

        $responseXml = (string) ($transportData['response_xml'] ?? '');
        $parsedResponse = $this->processarResposta($responseXml);

        $this->persistArtifacts(
            $operationKey,
            $requestXml,
            $soapEnvelope,
            $responseXml,
            $transportData,
            $parsedResponse
        );

        return $responseXml;
    }

    private function persistArtifacts(
        string $operationKey,
        string $requestXml,
        string $soapEnvelope,
        string $responseXml,
        array $transportData,
        array $parsedResponse
    ): void {
        $this->lastOperation = $operationKey;
        $this->lastRequestXml = $requestXml;
        $this->lastSoapEnvelope = $soapEnvelope;
        $this->lastResponseXml = $responseXml;
        $this->lastTransportData = $transportData;
        $this->lastResponseData = $parsedResponse;
        $this->lastOperationArtifacts = [
            'operation' => $operationKey,
            'request_xml' => $requestXml,
            'soap_envelope' => $soapEnvelope,
            'response_xml' => $responseXml,
            'parsed_response' => $parsedResponse,
            'transport' => $transportData,
        ];

        $this->logSoapDebug($this->lastOperationArtifacts);
    }

    private function assertItensCompativeis(array $dados): void
    {
        if (empty($dados['itens']) || !is_array($dados['itens'])) {
            return;
        }

        $first = null;
        foreach ($dados['itens'] as $index => $item) {
            if (!is_array($item)) {
                throw new \InvalidArgumentException("Item {$index} inválido para emissão de Belém.");
            }

            $normalized = [
                'classificacao' => (string) ($item['codigo_cnae'] ?? $item['codigo_cbo'] ?? $dados['servico']['codigo_cnae'] ?? $dados['servico']['codigo_atividade'] ?? ''),
                'aliquota' => (string) ($item['aliquota'] ?? $dados['servico']['aliquota'] ?? ''),
                'incidencia' => (string) ($item['exigibilidade_iss'] ?? $dados['servico']['exigibilidade_iss'] ?? '1'),
                'iss_retido' => (string) ($item['iss_retido'] ?? $dados['servico']['iss_retido'] ?? '0'),
            ];

            if ($first === null) {
                $first = $normalized;
                continue;
            }

            if ($normalized !== $first) {
                throw new \InvalidArgumentException(
                    'Belém exige itens compatíveis com o mesmo CNAE/CBO, alíquota e regra de incidência na mesma nota.'
                );
            }
        }
    }

    private function appendDocumentoNode(\DOMDocument $dom, \DOMElement $parent, string $documento): void
    {
        if (strlen($documento) === 11) {
            $this->appendXmlNode($dom, $parent, 'Cpf', $documento);
            return;
        }

        $this->appendXmlNode($dom, $parent, 'Cnpj', $documento);
    }

    private function appendOptionalDecimal(
        \DOMDocument $dom,
        \DOMElement $parent,
        string $name,
        mixed $value,
        int $precision = 2
    ): void {
        if ($value === null || $value === '') {
            return;
        }

        $this->appendXmlNode($dom, $parent, $name, $this->decimal((float) $value, $precision));
    }

    private function assinarXml(string $xml, string $operationKey): string
    {
        $certificate = $this->resolveCertificate();
        if ($certificate === null) {
            throw new \RuntimeException('Certificado digital obrigatório para o provider municipal de Belém.');
        }

        return match ($operationKey) {
            'emitir' => Signer::sign(
                $certificate,
                $xml,
                'LoteRps',
                'Id',
                OPENSSL_ALGO_SHA256,
                Signer::CANONICAL,
                'EnviarLoteRpsSincronoEnvio'
            ),
            'cancelar_nfse' => Signer::sign(
                $certificate,
                $xml,
                'InfPedidoCancelamento',
                'Id',
                OPENSSL_ALGO_SHA256,
                Signer::CANONICAL,
                'CancelarNfseEnvio'
            ),
            default => $xml,
        };
    }

    private function shouldSignOperation(string $operationKey): bool
    {
        $configured = $this->config['sign_operations'] ?? ['emitir'];
        if (!is_array($configured)) {
            $configured = ['emitir'];
        }

        return in_array($operationKey, $configured, true);
    }

    private function resolveCertificate(): ?Certificate
    {
        $configCertificate = $this->config['certificate'] ?? null;
        if ($configCertificate instanceof Certificate) {
            return $configCertificate;
        }

        $pfxContent = $this->config['certificate_pfx_content'] ?? null;
        $pfxPassword = $this->config['certificate_password'] ?? null;
        if (is_string($pfxContent) && $pfxContent !== '' && is_string($pfxPassword) && $pfxPassword !== '') {
            return Certificate::readPfx($pfxContent, $pfxPassword);
        }

        return CertificateManager::getInstance()->getCertificate();
    }

    private function assertRequestSchema(string $requestXml, string $operation): void
    {
        $resolver = new NFSeSchemaResolver();
        $validator = new NFSeSchemaValidator();
        $schemaPath = $resolver->resolve('BELEM_MUNICIPAL_2025', $operation);
        $validation = $validator->validate($requestXml, $schemaPath);

        if ($validation['valid']) {
            return;
        }

        throw new \RuntimeException(
            'XML de Belém inválido para o schema da operação '
            . $operation
            . ': '
            . implode('; ', $validation['errors'])
        );
    }

    private function montarSoapEnvelope(string $requestXml, string $soapOperation): string
    {
        $soap = new \DOMDocument('1.0', 'UTF-8');
        $soap->preserveWhiteSpace = false;
        $soap->formatOutput = false;

        $envelope = $soap->createElementNS('http://schemas.xmlsoap.org/soap/envelope/', 'soapenv:Envelope');
        $envelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:svc', self::SERVICE_NS);
        $envelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:nfse', self::NFSE_NS);
        $soap->appendChild($envelope);

        $header = $soap->createElementNS('http://schemas.xmlsoap.org/soap/envelope/', 'soapenv:Header');
        $envelope->appendChild($header);
        $cabecalho = $soap->createElementNS(self::NFSE_NS, 'nfse:cabecalho');
        $header->appendChild($cabecalho);
        $cabecalho->appendChild($soap->createElementNS(self::NFSE_NS, 'nfse:versaoDados', (string) $this->getVersao()));

        $body = $soap->createElementNS('http://schemas.xmlsoap.org/soap/envelope/', 'soapenv:Body');
        $envelope->appendChild($body);
        $operation = $soap->createElementNS(self::SERVICE_NS, 'svc:' . $soapOperation);
        $body->appendChild($operation);

        $request = new \DOMDocument('1.0', 'UTF-8');
        $request->loadXML($requestXml);
        $operation->appendChild($soap->importNode($request->documentElement, true));

        return $soap->saveXML() ?: '';
    }

    private function resolveSoapEndpoint(): string
    {
        return preg_replace('/\?wsdl$/i', '', $this->getWsdlUrl()) ?: $this->getWsdlUrl();
    }

    private function firstNodeValue(\DOMXPath $xpath, array $queries, ?\DOMNode $context = null): ?string
    {
        foreach ($queries as $query) {
            $nodes = $xpath->query($query, $context);
            if ($nodes instanceof \DOMNodeList && $nodes->length > 0) {
                $value = trim((string) $nodes->item(0)?->textContent);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    private function extractPrestadorContext(array $prestador): array
    {
        return [
            'cnpj' => $this->normalizeDigits((string) ($prestador['cnpj'] ?? '')),
            'inscricao_municipal' => trim((string) ($prestador['inscricaoMunicipal'] ?? $prestador['inscricao_municipal'] ?? '')),
            'codigo_municipio' => trim((string) ($prestador['codigo_municipio'] ?? $this->getCodigoMunicipio())),
        ];
    }

    private function resolvePrestadorContext(array $override = []): array
    {
        $candidates = [
            $this->extractPrestadorContext($override),
            $this->lastPrestadorContext,
            $this->extractPrestadorContext((array) ($this->config['prestador'] ?? [])),
            [
                'cnpj' => $this->normalizeDigits((string) ($this->config['prestador_cnpj'] ?? '')),
                'inscricao_municipal' => trim((string) ($this->config['prestador_inscricao_municipal'] ?? '')),
                'codigo_municipio' => trim((string) ($this->config['prestador_codigo_municipio'] ?? $this->getCodigoMunicipio())),
            ],
        ];

        foreach ($candidates as $candidate) {
            $cnpj = $this->normalizeDigits((string) ($candidate['cnpj'] ?? ''));
            $inscricao = trim((string) ($candidate['inscricao_municipal'] ?? ''));
            $codigoMunicipio = trim((string) ($candidate['codigo_municipio'] ?? $this->getCodigoMunicipio()));

            if ($cnpj !== '' && $inscricao !== '') {
                return [
                    'cnpj' => $cnpj,
                    'inscricao_municipal' => $inscricao,
                    'codigo_municipio' => $codigoMunicipio !== '' ? $codigoMunicipio : $this->getCodigoMunicipio(),
                ];
            }
        }

        throw new \InvalidArgumentException(
            'Belém requer CNPJ e inscrição municipal do prestador para consulta/cancelamento. '
            . 'Informe no payload da emissão anterior ou na configuração do provider.'
        );
    }

    private function resolveCodigoCancelamento(?string $protocolo): string
    {
        $configured = trim((string) ($this->config['cancelamento_codigo'] ?? '1'));
        $candidate = trim((string) ($protocolo ?? ''));

        if ($candidate !== '' && preg_match('/^\d+$/', $candidate) === 1) {
            return $candidate;
        }

        return $configured !== '' ? $configured : '1';
    }

    private function isSoapDebugEnabled(): bool
    {
        $configFlag = (bool) ($this->config['debug_http'] ?? false);
        $envRaw = $_ENV['FISCAL_NFSE_DEBUG'] ?? getenv('FISCAL_NFSE_DEBUG') ?: '';
        $envFlag = in_array(strtolower((string) $envRaw), ['1', 'true', 'yes', 'on'], true);

        return $configFlag || $envFlag;
    }

    private function getSoapDebugLogPath(): string
    {
        $configured = (string) ($this->config['debug_log_file'] ?? '');
        if ($configured !== '') {
            return $configured;
        }

        return sys_get_temp_dir() . '/nfse-belem-soap-debug.log';
    }

    private function logSoapDebug(array $artifacts): void
    {
        if (!$this->isSoapDebugEnabled()) {
            return;
        }

        $payload = [
            'ts' => date(DATE_ATOM),
            'provider' => 'BelemMunicipalProvider',
            'ambiente' => $this->getAmbiente(),
            'endpoint' => $this->resolveSoapEndpoint(),
            'operation' => $artifacts['operation'] ?? null,
            'request_xml' => $this->maskSensitiveData($artifacts['request_xml'] ?? null),
            'soap_envelope' => $this->maskSensitiveData($artifacts['soap_envelope'] ?? null),
            'response_xml' => $this->maskSensitiveData($artifacts['response_xml'] ?? null),
            'parsed_response' => $this->maskSensitiveData($artifacts['parsed_response'] ?? []),
            'transport' => $this->maskSensitiveData($artifacts['transport'] ?? []),
        ];

        $line = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($line === false) {
            return;
        }

        @file_put_contents($this->getSoapDebugLogPath(), $line . PHP_EOL, FILE_APPEND);
    }

    private function maskSensitiveData(mixed $value): mixed
    {
        if (is_array($value)) {
            $masked = [];
            foreach ($value as $key => $item) {
                if (is_string($key) && preg_match('/(cnpj|cpf|documento|email|telefone|protocolo|codigo_verificacao|inscricao|id)/i', $key) === 1) {
                    $masked[$key] = $this->maskSensitiveString((string) $item);
                    continue;
                }

                $masked[$key] = $this->maskSensitiveData($item);
            }

            return $masked;
        }

        if (is_string($value)) {
            return $this->maskSensitiveString($value);
        }

        return $value;
    }

    private function maskSensitiveString(string $value): string
    {
        $patterns = [
            '/(<(?:\w+:)?(?:Cpf|Cnpj|Documento|InscricaoMunicipal|Telefone|Email|Protocolo|CodigoVerificacao|Numero)\b[^>]*>)(.*?)(<\/(?:\w+:)?(?:Cpf|Cnpj|Documento|InscricaoMunicipal|Telefone|Email|Protocolo|CodigoVerificacao|Numero)>)/si',
            '/([A-Z0-9._%+-]+)@([A-Z0-9.-]+\.[A-Z]{2,})/i',
            '/\b\d{11,14}\b/',
        ];

        $value = preg_replace_callback(
            $patterns[0],
            static fn (array $matches): string => $matches[1] . str_repeat('*', max(4, strlen(trim($matches[2])))) . $matches[3],
            $value
        ) ?? $value;

        $value = preg_replace(
            $patterns[1],
            '***@$2',
            $value
        ) ?? $value;

        $value = preg_replace_callback(
            $patterns[2],
            static function (array $matches): string {
                $digits = $matches[0];
                return str_repeat('*', max(0, strlen($digits) - 4)) . substr($digits, -4);
            },
            $value
        ) ?? $value;

        $value = preg_replace('/\(\d{2}\)\s*\d{4,5}-?\d{4}/', '(**) *****-****', $value) ?? $value;

        return $value;
    }
}
