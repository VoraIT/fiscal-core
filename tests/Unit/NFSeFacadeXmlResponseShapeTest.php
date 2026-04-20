<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sabbajohn\FiscalCore\Adapters\NF\NFSeAdapter;
use sabbajohn\FiscalCore\Facade\NFSeFacade;

class NFSeFacadeXmlResponseShapeTest extends TestCase
{
    public function test_baixar_xml_returns_canonical_document_shape(): void
    {
        $adapter = $this->createMock(NFSeAdapter::class);
        $adapter->expects($this->once())
            ->method('baixarXml')
            ->with('NFSE123')
            ->willReturn(json_encode([
                'status' => 'success',
                'numero' => '123',
                'raw_xml' => '<CompNfse><Nfse><InfNfse><Numero>123</Numero></InfNfse></Nfse></CompNfse>',
            ]));

        $facade = new NFSeFacade('nacional', $adapter);
        $response = $facade->baixarXml('NFSE123');

        $this->assertTrue($response->isSuccess());
        $this->assertSame('NFSE123', $response->getData('documento')['chave_consulta']);
        $this->assertSame('<CompNfse><Nfse><InfNfse><Numero>123</Numero></InfNfse></Nfse></CompNfse>', $response->getData('documento')['xml']);
        $this->assertSame('indisponivel', $response->getData('impressao')['modo']);
        $this->assertSame('success', $response->getData('raw')['parsed_response']['status']);
    }
}
