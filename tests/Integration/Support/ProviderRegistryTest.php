<?php

declare(strict_types=1);

use freeline\FiscalCore\Providers\NFSe\Municipal\BelemMunicipalProvider;
use freeline\FiscalCore\Providers\NFSe\Municipal\IsswebProvider;
use freeline\FiscalCore\Providers\NFSe\Municipal\PublicaProvider;
use freeline\FiscalCore\Providers\NFSe\NacionalProvider;
use freeline\FiscalCore\Support\ProviderRegistry;
use PHPUnit\Framework\TestCase;

final class ProviderRegistryTest extends TestCase
{
    public function testGetByMunicipioJoinvilleReturnsPublicaProvider(): void
    {
        $registry = ProviderRegistry::getInstance();

        $provider = $registry->getByMunicipio('joinville');

        $this->assertInstanceOf(PublicaProvider::class, $provider);
    }

    public function testGetByMunicipioBelemReturnsCurrentMunicipalProvider(): void
    {
        $registry = ProviderRegistry::getInstance();

        $provider = $registry->getByMunicipio('belem');

        $this->assertInstanceOf(BelemMunicipalProvider::class, $provider);
    }

    public function testGetByMunicipioManausReturnsNacionalProvider(): void
    {
        $registry = ProviderRegistry::getInstance();

        $provider = $registry->getByMunicipio('manaus');

        $this->assertInstanceOf(NacionalProvider::class, $provider);
    }

    public function testGetByMunicipioPresidenteFigueiredoReturnsIsswebProvider(): void
    {
        $registry = ProviderRegistry::getInstance();

        $provider = $registry->getByMunicipio('presidente-figueiredo');

        $this->assertInstanceOf(IsswebProvider::class, $provider);
    }

    public function testGetByMunicipioRioPretoDaEvaReturnsIsswebProvider(): void
    {
        $registry = ProviderRegistry::getInstance();

        $provider = $registry->getByMunicipio('rio-preto-da-eva');

        $this->assertInstanceOf(IsswebProvider::class, $provider);
        $this->assertSame('1303569', $provider->getCodigoMunicipio());
    }

    public function testGetByUnknownMunicipioReturnsNacionalProvider(): void
    {
        $registry = ProviderRegistry::getInstance();

        $provider = $registry->getByMunicipio('nao-existe');

        $this->assertInstanceOf(NacionalProvider::class, $provider);
    }
}
