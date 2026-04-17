<?php

declare(strict_types=1);

namespace sabbajohn\FiscalCore\Renderers\NFSe;

use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;
use sabbajohn\FiscalCore\Contracts\MunicipalDanfseRendererInterface;

final class NacionalDanfseRenderer implements MunicipalDanfseRendererInterface
{
    public function render(string $xmlNfse): string
    {
        $data = $this->extractDocumentData($xmlNfse);
        $html = $this->buildHtml($data);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        return $dompdf->output();
    }

    private function extractDocumentData(string $xmlNfse): array
    {
        $dom = new \DOMDocument();
        if (!@$dom->loadXML($xmlNfse)) {
            throw new RuntimeException('XML final da NFSe invalido para gerar o DANFSe nacional.');
        }

        $xpath = new \DOMXPath($dom);

        return [
            'numero' => $this->firstNodeValue($xpath, [
                "//*[local-name()='nNFSe']",
                "//*[local-name()='nDFSe']",
            ]),
            'chave_acesso' => $this->firstNodeValue($xpath, [
                "//*[local-name()='infNFSe']/@Id",
                "//*[local-name()='chaveAcesso']",
            ]),
            'data_emissao' => $this->firstNodeValue($xpath, [
                "//*[local-name()='dhProc']",
                "//*[local-name()='dhEmi']",
            ]),
            'municipio' => $this->firstNodeValue($xpath, [
                "//*[local-name()='xLocEmi']",
                "//*[local-name()='xLocPrestacao']",
            ]),
            'prestador_razao_social' => $this->firstNodeValue($xpath, [
                "//*[local-name()='emit']/*[local-name()='xNome']",
            ]),
            'prestador_documento' => $this->firstNodeValue($xpath, [
                "//*[local-name()='emit']/*[local-name()='CNPJ']",
                "//*[local-name()='prest']/*[local-name()='CNPJ']",
            ]),
            'prestador_im' => $this->firstNodeValue($xpath, [
                "//*[local-name()='emit']/*[local-name()='IM']",
                "//*[local-name()='prest']/*[local-name()='IM']",
            ]),
            'tomador_nome' => $this->firstNodeValue($xpath, [
                "//*[local-name()='toma']/*[local-name()='xNome']",
            ]),
            'tomador_documento' => $this->firstNodeValue($xpath, [
                "//*[local-name()='toma']/*[local-name()='CNPJ']",
                "//*[local-name()='toma']/*[local-name()='CPF']",
            ]),
            'servico_descricao' => $this->firstNodeValue($xpath, [
                "//*[local-name()='cServ']/*[local-name()='xDescServ']",
            ]),
            'tributacao_nacional' => $this->firstNodeValue($xpath, [
                "//*[local-name()='cServ']/*[local-name()='cTribNac']",
                "//*[local-name()='xTribNac']",
            ]),
            'tributacao_municipal' => $this->firstNodeValue($xpath, [
                "//*[local-name()='cServ']/*[local-name()='cTribMun']",
                "//*[local-name()='xTribMun']",
            ]),
            'nbs' => $this->firstNodeValue($xpath, [
                "//*[local-name()='cServ']/*[local-name()='cNBS']",
                "//*[local-name()='xNBS']",
            ]),
            'valor_servicos' => $this->firstNodeValue($xpath, [
                "//*[local-name()='vServPrest']/*[local-name()='vServ']",
                "//*[local-name()='valores']/*[local-name()='vBC']",
            ]),
            'aliquota' => $this->firstNodeValue($xpath, [
                "//*[local-name()='pAliqAplic']",
            ]),
            'valor_iss' => $this->firstNodeValue($xpath, [
                "//*[local-name()='vISSQN']",
            ]),
            'valor_liquido' => $this->firstNodeValue($xpath, [
                "//*[local-name()='vLiq']",
            ]),
        ];
    }

    private function buildHtml(array $data): string
    {
        $fields = array_map(
            static fn (?string $value): string => htmlspecialchars(trim((string) $value) !== '' ? (string) $value : '-', ENT_QUOTES, 'UTF-8'),
            $data
        );

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
    .wrap { border: 1px solid #111827; padding: 24px; }
    h1 { margin: 0 0 12px; font-size: 20px; }
    h2 { margin: 20px 0 8px; font-size: 14px; border-bottom: 1px solid #d1d5db; padding-bottom: 4px; }
    .grid { width: 100%; border-collapse: collapse; }
    .grid td { padding: 6px 8px; vertical-align: top; border: 1px solid #e5e7eb; }
    .label { font-size: 10px; text-transform: uppercase; color: #6b7280; display: block; margin-bottom: 4px; }
    .box { min-height: 42px; }
    .mono { font-family: DejaVu Sans Mono, monospace; }
  </style>
</head>
<body>
  <div class="wrap">
    <h1>DANFSe - Padrão Nacional</h1>
    <table class="grid">
      <tr>
        <td><span class="label">Número</span><div class="box mono">{$fields['numero']}</div></td>
        <td><span class="label">Data de emissão</span><div class="box">{$fields['data_emissao']}</div></td>
        <td><span class="label">Município</span><div class="box">{$fields['municipio']}</div></td>
      </tr>
      <tr>
        <td colspan="3"><span class="label">Chave de acesso</span><div class="box mono">{$fields['chave_acesso']}</div></td>
      </tr>
    </table>
    <h2>Prestador</h2>
    <table class="grid">
      <tr>
        <td><span class="label">Razão social</span><div class="box">{$fields['prestador_razao_social']}</div></td>
        <td><span class="label">Documento</span><div class="box mono">{$fields['prestador_documento']}</div></td>
        <td><span class="label">IM</span><div class="box mono">{$fields['prestador_im']}</div></td>
      </tr>
    </table>
    <h2>Tomador</h2>
    <table class="grid">
      <tr>
        <td><span class="label">Nome</span><div class="box">{$fields['tomador_nome']}</div></td>
        <td><span class="label">Documento</span><div class="box mono">{$fields['tomador_documento']}</div></td>
      </tr>
    </table>
    <h2>Serviço</h2>
    <table class="grid">
      <tr>
        <td colspan="3"><span class="label">Descrição</span><div class="box">{$fields['servico_descricao']}</div></td>
      </tr>
      <tr>
        <td><span class="label">Tributação nacional</span><div class="box mono">{$fields['tributacao_nacional']}</div></td>
        <td><span class="label">Tributação municipal</span><div class="box mono">{$fields['tributacao_municipal']}</div></td>
        <td><span class="label">NBS</span><div class="box mono">{$fields['nbs']}</div></td>
      </tr>
    </table>
    <h2>Valores</h2>
    <table class="grid">
      <tr>
        <td><span class="label">Valor do serviço</span><div class="box mono">{$fields['valor_servicos']}</div></td>
        <td><span class="label">Alíquota</span><div class="box mono">{$fields['aliquota']}</div></td>
        <td><span class="label">ISS</span><div class="box mono">{$fields['valor_iss']}</div></td>
        <td><span class="label">Valor líquido</span><div class="box mono">{$fields['valor_liquido']}</div></td>
      </tr>
    </table>
  </div>
</body>
</html>
HTML;
    }

    private function firstNodeValue(\DOMXPath $xpath, array $queries): ?string
    {
        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            if ($nodes instanceof \DOMNodeList && $nodes->length > 0) {
                $value = trim((string) $nodes->item(0)?->textContent);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }
}
