<?php

declare(strict_types=1);

use freeline\FiscalCore\Adapters\NF\NFSeAdapter;
use PHPUnit\Framework\TestCase;

final class NFSeAdapterTest extends TestCase
{
    public function testJoinvilleUsesPublicaProvider(): void
    {
        $adapter = new NFSeAdapter('joinville');

        $info = $adapter->getProviderInfo();

        $this->assertSame('PUBLICA', $info['provider_key']);
        $this->assertStringContainsString('PublicaProvider', $info['provider_class']);
    }

    public function testBelemUsesCurrentMunicipalProvider(): void
    {
        $adapter = new NFSeAdapter('belem');

        $info = $adapter->getProviderInfo();

        $this->assertSame('BELEM_MUNICIPAL_2025', $info['provider_key']);
        $this->assertStringContainsString('BelemMunicipalProvider', $info['provider_class']);
        $this->assertContains('consultar_lote', $info['supported_operations']);
        $this->assertContains('cancelar_nfse', $info['supported_operations']);
    }

    public function testManausUsesManausAmProvider(): void
    {
        $adapter = new NFSeAdapter('manaus');

        $info = $adapter->getProviderInfo();

        $this->assertSame('MANAUS_AM', $info['provider_key']);
        $this->assertStringContainsString('ManausAmProvider', $info['provider_class']);
    }

    public function testUnknownUsesNacionalProvider(): void
    {
        $adapter = new NFSeAdapter('nao-existe');

        $info = $adapter->getProviderInfo();

        $this->assertSame('nfse_nacional', $info['provider_key']);
        $this->assertStringContainsString('NacionalProvider', $info['provider_class']);
    }
}
