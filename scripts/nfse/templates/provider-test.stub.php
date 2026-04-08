<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use sabbajohn\FiscalCore\Providers\NFSe\Municipal\{{PROVIDER_SHORT_CLASS}};

final class {{PROVIDER_SHORT_CLASS}}Test extends TestCase
{
    public function testScaffoldProviderEmitsPendingPayload(): void
    {
        $provider = new {{PROVIDER_SHORT_CLASS}}([
            'municipio_nome' => 'Município Scaffold',
            'codigo_municipio' => '0000000',
            'ambiente' => 'homologacao',
        ]);

        $xml = $provider->emitir([
            'prestador' => ['cnpj' => '12345678000195'],
            'tomador' => ['documento' => '98765432000199'],
            'servico' => ['codigo' => '101'],
            'valor_servicos' => 100.00,
        ]);

        $this->assertStringContainsString('{{PROVIDER_SLUG}}', $xml);
        $this->assertSame('pending', $provider->getLastResponseData()['status'] ?? null);
    }
}
