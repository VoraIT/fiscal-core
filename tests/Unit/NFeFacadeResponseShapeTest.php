<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sabbajohn\FiscalCore\Adapters\ImpressaoAdapter;
use sabbajohn\FiscalCore\Adapters\NF\NFeAdapter;
use sabbajohn\FiscalCore\Facade\NFeFacade;

class NFeFacadeResponseShapeTest extends TestCase
{
    public function test_consultar_returns_canonical_document_shape(): void
    {
        $xml = '<retConsSitNFe><xMotivo>Autorizado o uso da NF-e</xMotivo></retConsSitNFe>';

        $adapter = $this->createMock(NFeAdapter::class);
        $adapter->expects($this->once())
            ->method('consultar')
            ->with('35123456789012345678901234567890123456789012')
            ->willReturn($xml);

        $facade = new NFeFacade($adapter, $this->createMock(ImpressaoAdapter::class));
        $response = $facade->consultar('35123456789012345678901234567890123456789012');

        $this->assertTrue($response->isSuccess());
        $this->assertSame($xml, $response->getData('documento')['xml']);
        $this->assertSame('35123456789012345678901234567890123456789012', $response->getData('documento')['chave_acesso']);
        $this->assertSame('Autorizado o uso da NF-e', $response->getData('documento')['situacao']);
        $this->assertSame('indisponivel', $response->getData('impressao')['modo']);
        $this->assertSame($xml, $response->getData('raw')['response_xml']);
    }

    public function test_gerar_danfe_returns_xml_and_pdf_in_canonical_shape(): void
    {
        $xml = '<NFe><infNFe Id="NFe123" /></NFe>';
        $pdf = '%PDF-1.4 test';

        $impressao = $this->createMock(ImpressaoAdapter::class);
        $impressao->expects($this->once())
            ->method('gerarDanfe')
            ->with($xml)
            ->willReturn($pdf);

        $facade = new NFeFacade($this->createMock(NFeAdapter::class), $impressao);
        $response = $facade->gerarDanfe($xml);

        $this->assertTrue($response->isSuccess());
        $this->assertSame($xml, $response->getData('documento')['xml']);
        $this->assertSame('pdf_base64', $response->getData('impressao')['modo']);
        $this->assertSame(base64_encode($pdf), $response->getData('impressao')['pdf_base64']);
        $this->assertSame('application/pdf', $response->getData('impressao')['content_type']);
        $this->assertTrue(str_starts_with($response->getData('impressao')['filename'], 'danfe_'));
    }

    public function test_baixar_xml_returns_canonical_document_shape(): void
    {
        $documentXml = '<resNFe><chNFe>35123456789012345678901234567890123456789012</chNFe></resNFe>';
        $docZip = base64_encode(gzencode($documentXml));
        $distXml = <<<XML
<retDistDFeInt>
    <cStat>138</cStat>
    <xMotivo>Documento localizado</xMotivo>
    <ultNSU>12</ultNSU>
    <maxNSU>12</maxNSU>
    <loteDistDFeInt>
        <docZip NSU="12" schema="resNFe_v1.01">{$docZip}</docZip>
    </loteDistDFeInt>
</retDistDFeInt>
XML;

        $adapter = $this->createMock(NFeAdapter::class);
        $adapter->expects($this->once())
            ->method('downloadNFe')
            ->with('35123456789012345678901234567890123456789012')
            ->willReturn($distXml);

        $facade = new NFeFacade($adapter, $this->createMock(ImpressaoAdapter::class));
        $response = $facade->baixarXml('35123456789012345678901234567890123456789012');

        $this->assertTrue($response->isSuccess());
        $this->assertSame($documentXml, $response->getData('documento')['xml']);
        $this->assertSame('35123456789012345678901234567890123456789012', $response->getData('documento')['chave_acesso']);
        $this->assertSame('indisponivel', $response->getData('impressao')['modo']);
        $this->assertSame($distXml, $response->getData('raw')['response_xml']);
        $this->assertCount(1, $response->getData('documents'));
    }
}
