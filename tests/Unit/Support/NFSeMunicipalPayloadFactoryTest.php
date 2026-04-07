<?php

declare(strict_types=1);

use sabbajohn\FiscalCore\Contracts\NFSeOperationalIntrospectionInterface;
use sabbajohn\FiscalCore\Support\NFSeMunicipalPayloadFactory;
use sabbajohn\FiscalCore\Support\NFSeMunicipalPreviewSupport;
use sabbajohn\FiscalCore\Support\NFSeSchemaResolver;
use sabbajohn\FiscalCore\Support\NFSeSchemaValidator;
use sabbajohn\FiscalCore\Support\ProviderRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NFSeMunicipalPayloadFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        ProviderRegistry::getInstance()->reload();
    }

    #[DataProvider('municipioProvider')]
    public function testDemoPayloadsGenerateSchemaValidXml(string $municipio): void
    {
        $factory = new NFSeMunicipalPayloadFactory();
        $meta = $factory->providerMeta($municipio);
        $payload = $factory->demo($municipio);

        $config = ProviderRegistry::getInstance()->getConfig($meta['provider_key']);
        $config['certificate'] = NFSeMunicipalPreviewSupport::makeCertificate('Factory ' . ucfirst($municipio));
        $config['prestador'] = $payload['prestador'];
        $config['soap_transport'] = NFSeMunicipalPreviewSupport::makeTransport($municipio);

        $providerClass = $config['provider_class'];
        $provider = new $providerClass($config);
        $provider->emitir($payload);

        $this->assertInstanceOf(NFSeOperationalIntrospectionInterface::class, $provider);

        $validation = (new NFSeSchemaValidator())->validate(
            (string) $provider->getLastRequestXml(),
            (new NFSeSchemaResolver())->resolve($meta['provider_key'], 'emitir')
        );

        $this->assertTrue($validation['valid'], implode(PHP_EOL, $validation['errors']));
        $this->assertSame('success', $provider->getLastResponseData()['status'] ?? null);
    }

    public function testBuildPrestadorRequiresInscricaoMunicipal(): void
    {
        $factory = new NFSeMunicipalPayloadFactory();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FISCAL_IM');

        $factory->buildPrestador(
            'joinville',
            NFSeMunicipalPreviewSupport::makeCertificate(),
            [
                'cnpj' => '83188342000104',
                'razao_social' => 'Freeline Informatica Ltda',
                'inscricao_municipal' => '',
            ]
        );
    }

    public function testRealJoinvillePayloadUsesTwoPercentAliquota(): void
    {
        $factory = new NFSeMunicipalPayloadFactory();
        $payload = $factory->real(
            'joinville',
            [
                'cnpj' => '83188342000104',
                'inscricaoMunicipal' => '123456',
                'razao_social' => 'Freeline Informatica Ltda',
                'codigo_municipio' => '4209102',
                'simples_nacional' => true,
            ],
            [
                'documento' => '11222333000181',
                'razao_social' => 'Tomador Joinville Ltda',
                'endereco' => [
                    'logradouro' => 'Rua 1',
                    'numero' => '100',
                    'bairro' => 'Centro',
                    'codigo_municipio' => '4209102',
                    'uf' => 'SC',
                    'cep' => '89201001',
                    'municipio' => 'Joinville',
                ],
            ]
        );

        $this->assertSame(0.02, $payload['servico']['aliquota']);
        $this->assertSame('4209102', $payload['servico']['codigo_municipio']);
        $this->assertSame('11.01', $payload['servico']['item_lista_servico']);
    }

    public static function municipioProvider(): array
    {
        return [
            'belem' => ['belem'],
            'joinville' => ['joinville'],
        ];
    }
}
