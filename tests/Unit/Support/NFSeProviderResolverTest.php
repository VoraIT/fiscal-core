<?php

declare(strict_types=1);

use freeline\FiscalCore\Support\NFSeMunicipalCatalog;
use freeline\FiscalCore\Support\NFSeProviderResolver;
use PHPUnit\Framework\TestCase;

final class NFSeProviderResolverTest extends TestCase
{
    private function makeResolver(): NFSeProviderResolver
    {
        $catalog = new NFSeMunicipalCatalog(dirname(__DIR__, 3) . '/config/nfse/providers-catalog.json');

        return new NFSeProviderResolver($catalog);
    }

    public function testResolveJoinvilleToPublica(): void
    {
        $resolver = $this->makeResolver();

        $this->assertSame('PUBLICA', $resolver->resolveKey('joinville'));
    }

    public function testResolveBelemToCurrentMunicipalFamily(): void
    {
        $resolver = $this->makeResolver();

        $this->assertSame('BELEM_MUNICIPAL_2025', $resolver->resolveKey('belem'));
    }

    public function testResolveManausToManausAm(): void
    {
        $resolver = $this->makeResolver();

        $this->assertSame('MANAUS_AM', $resolver->resolveKey('manaus'));
    }

    public function testUnknownFallsBackToNational(): void
    {
        $resolver = $this->makeResolver();

        $this->assertSame(NFSeProviderResolver::NATIONAL_KEY, $resolver->resolveKey('nao-existe'));
    }

    public function testNullFallsBackToNational(): void
    {
        $resolver = $this->makeResolver();

        $this->assertSame(NFSeProviderResolver::NATIONAL_KEY, $resolver->resolveKey(null));
    }
}
