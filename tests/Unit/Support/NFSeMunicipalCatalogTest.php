<?php

declare(strict_types=1);

use freeline\FiscalCore\Support\NFSeMunicipalCatalog;
use PHPUnit\Framework\TestCase;

final class NFSeMunicipalCatalogTest extends TestCase
{
    private function fixturePath(): string
    {
        return dirname(__DIR__, 3) . '/config/nfse/providers-catalog.json';
    }

    public function testResolveJoinvilleBySlug(): void
    {
        $catalog = new NFSeMunicipalCatalog($this->fixturePath());

        $result = $catalog->resolveMunicipio('joinville');

        $this->assertNotNull($result);
        $this->assertSame('4209102', $result['ibge']);
        $this->assertSame('PUBLICA', $result['provider_family_key']);
    }

    public function testResolveBelemByAccentedName(): void
    {
        $catalog = new NFSeMunicipalCatalog($this->fixturePath());

        $result = $catalog->resolveMunicipio('Belém');

        $this->assertNotNull($result);
        $this->assertSame('1501402', $result['ibge']);
        $this->assertSame('BELEM_MUNICIPAL_2025', $result['provider_family_key']);
    }

    public function testResolveManausByIbge(): void
    {
        $catalog = new NFSeMunicipalCatalog($this->fixturePath());

        $result = $catalog->resolveMunicipio('1302603');

        $this->assertNotNull($result);
        $this->assertSame('MANAUS_AM', $result['provider_family_key']);
    }

    public function testUnknownMunicipioReturnsNull(): void
    {
        $catalog = new NFSeMunicipalCatalog($this->fixturePath());

        $this->assertNull($catalog->resolveMunicipio('nao-existe'));
    }
}
