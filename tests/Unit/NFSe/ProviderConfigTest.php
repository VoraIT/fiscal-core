<?php

declare(strict_types=1);

use freeline\FiscalCore\Facade\NFSeFacade;
use PHPUnit\Framework\TestCase;

final class ProviderConfigTest extends TestCase
{
    public function testFacadeLoadsPilotProviderInfoForBelem(): void
    {
        $facade = new NFSeFacade('belem');
        $response = $facade->getProviderInfo();

        $this->assertTrue($response->isSuccess());

        $data = $response->getData();
        $this->assertSame('BELEM_MUNICIPAL_2025', $data['provider_key']);
        $this->assertSame('1501402', $data['codigo_municipio']);
        $this->assertStringContainsString('BelemMunicipalProvider', $data['provider_class']);
        $this->assertContains('consultar_nfse_rps', $data['supported_operations']);
    }

    public function testFacadeListsOnlyPilotMunicipios(): void
    {
        $facade = new NFSeFacade('belem');
        $response = $facade->listarMunicipios();

        $this->assertTrue($response->isSuccess());
        $this->assertSame(
            ['belem', 'joinville', 'manaus', 'nacional'],
            $response->getData('municipios')
        );
    }

    public function testFacadeMapsJoinvilleToPublica(): void
    {
        $facade = new NFSeFacade('joinville');
        $response = $facade->getProviderInfo();

        $this->assertTrue($response->isSuccess());

        $data = $response->getData();
        $this->assertSame('PUBLICA', $data['provider_key']);
        $this->assertSame('4209102', $data['codigo_municipio']);
        $this->assertStringContainsString('PublicaProvider', $data['provider_class']);
    }

    public function testFacadeFallsBackToNationalForUnknownMunicipio(): void
    {
        $facade = new NFSeFacade('municipio-inexistente');
        $response = $facade->getProviderInfo();

        $this->assertTrue($response->isSuccess());

        $data = $response->getData();
        $this->assertSame('nfse_nacional', $data['provider_key']);
        $this->assertTrue($data['municipio_ignored']);
        $this->assertStringContainsString('NacionalProvider', $data['provider_class']);
    }
}
