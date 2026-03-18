<?php

declare(strict_types=1);

use freeline\FiscalCore\Providers\NFSe\Municipal\BelemMunicipalProvider;
use freeline\FiscalCore\Providers\NFSe\Municipal\ManausAmProvider;
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

    public function testGetByMunicipioManausReturnsManausAmProvider(): void
    {
        $registry = ProviderRegistry::getInstance();

        $provider = $registry->getByMunicipio('manaus');

        $this->assertInstanceOf(ManausAmProvider::class, $provider);
    }

    public function testGetByUnknownMunicipioReturnsNacionalProvider(): void
    {
        $registry = ProviderRegistry::getInstance();

        $provider = $registry->getByMunicipio('nao-existe');

        $this->assertInstanceOf(NacionalProvider::class, $provider);
    }
}
