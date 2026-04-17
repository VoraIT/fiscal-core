<?php

declare(strict_types=1);

namespace sabbajohn\FiscalCore\Providers\NFSe\Municipal;

use sabbajohn\FiscalCore\Providers\NFSe\AbstractNFSeProvider;

final class ManausAmProvider extends AbstractNFSeProvider
{
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
        $valorServicos = (float) ($dados['valor_servicos'] ?? 0);
        $aliquota = (float) ($dados['servico']['aliquota'] ?? 0);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = false;

        $root = $dom->createElement('EnviarLoteRpsEnvio');
        $dom->appendChild($root);

        $loteRps = $this->appendXmlNode($dom, $root, 'LoteRps');
        $loteRps->setAttribute('id', (string) ($dados['lote']['id'] ?? 'LOTE-MANAUS-1'));
        $this->appendXmlNode($dom, $loteRps, 'NumeroLote', (string) ($dados['lote']['numero'] ?? '1'));
        $this->appendXmlNode($dom, $loteRps, 'Cnpj', $prestadorCnpj);
        $this->appendXmlNode($dom, $loteRps, 'InscricaoMunicipal', $prestadorIm);
        $this->appendXmlNode($dom, $loteRps, 'QuantidadeRps', '1');

        $listaRps = $this->appendXmlNode($dom, $loteRps, 'ListaRps');
        $rps = $this->appendXmlNode($dom, $listaRps, 'Rps');
        $infRps = $this->appendXmlNode($dom, $rps, 'InfRps');
        $infRps->setAttribute('id', (string) ($dados['id'] ?? 'RPS-MANAUS-1'));

        $identificacaoRps = $this->appendXmlNode($dom, $infRps, 'IdentificacaoRps');
        $this->appendXmlNode($dom, $identificacaoRps, 'Numero', (string) ($dados['rps']['numero'] ?? '1'));
        $this->appendXmlNode($dom, $identificacaoRps, 'Serie', (string) ($dados['rps']['serie'] ?? 'NF'));
        $this->appendXmlNode($dom, $identificacaoRps, 'Tipo', (string) ($dados['rps']['tipo'] ?? '1'));

        $this->appendXmlNode($dom, $infRps, 'DataEmissao', $dataEmissao);
        $this->appendXmlNode($dom, $infRps, 'NaturezaOperacao', '1');
        $this->appendXmlNode(
            $dom,
            $infRps,
            'OptanteSimplesNacional',
            $this->booleanCode((bool) ($dados['prestador']['simples_nacional'] ?? false))
        );
        $this->appendXmlNode($dom, $infRps, 'IncentivadorCultural', '2');
        $this->appendXmlNode($dom, $infRps, 'Status', '1');

        $servico = $this->appendXmlNode($dom, $infRps, 'Servico');
        $valores = $this->appendXmlNode($dom, $servico, 'Valores');
        $this->appendXmlNode($dom, $valores, 'ValorServicos', $this->decimal($valorServicos));
        $this->appendXmlNode($dom, $valores, 'IssRetido', '2');
        $this->appendXmlNode($dom, $valores, 'BaseCalculo', $this->decimal($valorServicos));
        $this->appendXmlNode(
            $dom,
            $valores,
            'Aliquota',
            $this->decimal((float) $this->formatarAliquota($aliquota), 4)
        );
        $this->appendXmlNode($dom, $servico, 'ItemListaServico', $itemListaServico);
        $this->appendXmlNode(
            $dom,
            $servico,
            'Discriminacao',
            (string) ($dados['servico']['discriminacao'] ?? $dados['servico']['descricao'] ?? 'Servico')
        );
        $this->appendXmlNode($dom, $servico, 'CodigoMunicipio', $codigoMunicipio);

        $prestador = $this->appendXmlNode($dom, $infRps, 'Prestador');
        $this->appendXmlNode($dom, $prestador, 'Cnpj', $prestadorCnpj);
        $this->appendXmlNode($dom, $prestador, 'InscricaoMunicipal', $prestadorIm);

        $tomador = $this->appendXmlNode($dom, $infRps, 'Tomador');
        $identificacaoTomador = $this->appendXmlNode($dom, $tomador, 'IdentificacaoTomador');
        $cpfCnpj = $this->appendXmlNode($dom, $identificacaoTomador, 'CpfCnpj');
        $documentNode = strlen($tomadorDocumento) === 11 ? 'Cpf' : 'Cnpj';
        $this->appendXmlNode($dom, $cpfCnpj, $documentNode, $tomadorDocumento);
        $this->appendXmlNode(
            $dom,
            $tomador,
            'RazaoSocial',
            (string) ($dados['tomador']['razao_social'] ?? 'Tomador de Teste')
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
            'tomador.razao_social' => (string) ($dados['tomador']['razaoSocial'] ?? ''),
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
            'provider' => 'MANAUS_AM',
            'raw_xml' => $xmlResposta,
        ];
    }
}
