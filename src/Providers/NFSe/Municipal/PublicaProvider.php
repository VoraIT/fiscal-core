<?php

declare(strict_types=1);

namespace freeline\FiscalCore\Providers\NFSe\Municipal;

use freeline\FiscalCore\Providers\NFSe\AbstractNFSeProvider;

final class PublicaProvider extends AbstractNFSeProvider
{
    private const XMLNS = 'http://www.publica.inf.br';

    protected function montarXmlRps(array $dados): string
    {
        $prestadorCnpj = $this->normalizeDigits((string) ($dados['prestador']['cnpj'] ?? ''));
        $prestadorIm = (string) ($dados['prestador']['inscricaoMunicipal'] ?? '');
        $tomadorDocumento = $this->normalizeDigits((string) ($dados['tomador']['documento'] ?? ''));
        $itemListaServico = $this->normalizeDigits((string) ($dados['servico']['codigo'] ?? ''));
        $codigoMunicipio = $this->normalizeDigits(
            (string) ($dados['servico']['codigo_municipio'] ?? $this->getCodigoMunicipio())
        );
        $dataEmissao = $this->xmlDateTime((string) ($dados['rps']['data_emissao'] ?? ''));
        $competencia = $this->gYearMonth((string) ($dados['competencia'] ?? $dados['rps']['data_emissao'] ?? ''));
        $valorServicos = (float) ($dados['valor_servicos'] ?? 0);
        $aliquota = (float) ($dados['servico']['aliquota'] ?? 0);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = false;

        $root = $dom->createElementNS(self::XMLNS, 'GerarNfseEnvio');
        $dom->appendChild($root);

        $rps = $this->appendXmlNode($dom, $root, 'Rps', null, self::XMLNS);
        $infRps = $this->appendXmlNode($dom, $rps, 'InfRps', null, self::XMLNS);
        $infRps->setAttribute('id', (string) ($dados['id'] ?? 'RPS-PUBLICA-1'));

        $identificacaoRps = $this->appendXmlNode($dom, $infRps, 'IdentificacaoRps', null, self::XMLNS);
        $this->appendXmlNode(
            $dom,
            $identificacaoRps,
            'Numero',
            (string) ($dados['rps']['numero'] ?? '1'),
            self::XMLNS
        );
        $this->appendXmlNode(
            $dom,
            $identificacaoRps,
            'Serie',
            (string) ($dados['rps']['serie'] ?? 'NF'),
            self::XMLNS
        );
        $this->appendXmlNode(
            $dom,
            $identificacaoRps,
            'Tipo',
            (string) ($dados['rps']['tipo'] ?? '1'),
            self::XMLNS
        );

        $this->appendXmlNode($dom, $infRps, 'DataEmissao', $dataEmissao, self::XMLNS);
        $this->appendXmlNode($dom, $infRps, 'NaturezaOperacao', '1', self::XMLNS);
        $this->appendXmlNode(
            $dom,
            $infRps,
            'OptanteSimplesNacional',
            $this->booleanCode((bool) ($dados['prestador']['simples_nacional'] ?? false)),
            self::XMLNS
        );
        $this->appendXmlNode($dom, $infRps, 'IncentivadorCultural', '2', self::XMLNS);
        $this->appendXmlNode($dom, $infRps, 'Competencia', $competencia, self::XMLNS);
        $this->appendXmlNode($dom, $infRps, 'Status', '1', self::XMLNS);

        $servico = $this->appendXmlNode($dom, $infRps, 'Servico', null, self::XMLNS);
        $valores = $this->appendXmlNode($dom, $servico, 'Valores', null, self::XMLNS);
        $this->appendXmlNode($dom, $valores, 'ValorServicos', $this->decimal($valorServicos), self::XMLNS);
        $this->appendXmlNode($dom, $valores, 'IssRetido', '2', self::XMLNS);
        $this->appendXmlNode($dom, $valores, 'BaseCalculo', $this->decimal($valorServicos), self::XMLNS);
        $this->appendXmlNode(
            $dom,
            $valores,
            'Aliquota',
            $this->decimal((float) $this->formatarAliquota($aliquota), 4),
            self::XMLNS
        );
        $this->appendXmlNode($dom, $servico, 'ItemListaServico', $itemListaServico, self::XMLNS);
        $this->appendXmlNode(
            $dom,
            $servico,
            'Discriminacao',
            (string) ($dados['servico']['discriminacao'] ?? $dados['servico']['descricao'] ?? 'Servico'),
            self::XMLNS
        );
        $this->appendXmlNode($dom, $servico, 'CodigoMunicipio', $codigoMunicipio, self::XMLNS);

        $prestador = $this->appendXmlNode($dom, $infRps, 'Prestador', null, self::XMLNS);
        $this->appendXmlNode($dom, $prestador, 'Cnpj', $prestadorCnpj, self::XMLNS);
        $this->appendXmlNode($dom, $prestador, 'InscricaoMunicipal', $prestadorIm, self::XMLNS);

        $tomador = $this->appendXmlNode($dom, $infRps, 'Tomador', null, self::XMLNS);
        $identificacaoTomador = $this->appendXmlNode($dom, $tomador, 'IdentificacaoTomador', null, self::XMLNS);
        $cpfCnpj = $this->appendXmlNode($dom, $identificacaoTomador, 'CpfCnpj', null, self::XMLNS);
        $documentNode = strlen($tomadorDocumento) === 11 ? 'Cpf' : 'Cnpj';
        $this->appendXmlNode($dom, $cpfCnpj, $documentNode, $tomadorDocumento, self::XMLNS);
        $this->appendXmlNode(
            $dom,
            $tomador,
            'RazaoSocial',
            (string) ($dados['tomador']['razao_social'] ?? 'Tomador de Teste'),
            self::XMLNS
        );

        return $dom->saveXML() ?: '';
    }

    public function validarDados(array $dados): bool
    {
        parent::validarDados($dados);

        $required = [
            'prestador.cnpj' => $this->normalizeDigits((string) ($dados['prestador']['cnpj'] ?? '')),
            'prestador.inscricaoMunicipal' => (string) ($dados['prestador']['inscricaoMunicipal'] ?? ''),
            'servico.codigo' => $this->normalizeDigits((string) ($dados['servico']['codigo'] ?? '')),
            'tomador.documento' => $this->normalizeDigits((string) ($dados['tomador']['documento'] ?? '')),
            'tomador.razao_social' => (string) ($dados['tomador']['razao_social'] ?? ''),
        ];

        foreach ($required as $field => $value) {
            if (trim((string) $value) === '') {
                throw new \InvalidArgumentException("Campo obrigatório ausente: {$field}");
            }
        }

        return true;
    }

    protected function processarResposta(string $xmlResposta): array
    {
        return [
            'provider' => 'PUBLICA',
            'raw_xml' => $xmlResposta,
        ];
    }
}
