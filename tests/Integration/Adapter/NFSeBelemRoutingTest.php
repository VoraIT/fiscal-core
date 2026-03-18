<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Fixtures/NFSeBelemMunicipalFixtures.php';
require_once dirname(__DIR__, 2) . '/Fakes/RecordingNfseProvider.php';

use freeline\FiscalCore\Adapters\NF\NFSeAdapter;
use freeline\FiscalCore\Facade\NFSeFacade;
use freeline\FiscalCore\Providers\NFSe\Municipal\BelemMunicipalProvider;
use freeline\FiscalCore\Support\NFSeSoapTransportInterface;
use freeline\FiscalCore\Support\ProviderRegistry;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\RecordingNfseProvider;

final class NFSeBelemRoutingTest extends TestCase
{
    protected function tearDown(): void
    {
        ProviderRegistry::getInstance()->reload();
        RecordingNfseProvider::reset();
    }

    public function testBelemMeiRoutesAutomaticallyToNationalProvider(): void
    {
        $registry = ProviderRegistry::getInstance();
        $registry->register('nfse_nacional', [
            'provider_class' => RecordingNfseProvider::class,
            'codigo_municipio' => '1001058',
            'aliquota_format' => 'decimal',
            'wsdl' => '',
            'api_base_url' => 'https://example.test/api',
            'ambiente' => 'homologacao',
        ]);

        $adapter = new NFSeAdapter('belem');
        $result = $adapter->emitir(NFSeBelemMunicipalFixtures::meiPayload());

        $this->assertSame('<nacional-gravado />', $result);
        $this->assertNotNull(RecordingNfseProvider::$lastPayload);
        $this->assertSame(
            'nfse_nacional',
            $adapter->getLastEmissionInfo()['effective_provider_key'] ?? null
        );
        $this->assertSame(
            'belem_mei_nacional',
            $adapter->getLastEmissionInfo()['routing_mode'] ?? null
        );
    }

    public function testBelemRejectsEmissionWhenMeiClassificationIsMissing(): void
    {
        $adapter = new NFSeAdapter('belem');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('identificação explícita do emitente');

        $adapter->emitir(NFSeBelemMunicipalFixtures::payloadWithoutClassification());
    }

    public function testBelemMunicipalConsultAndCancelUseMunicipalProviderCapabilities(): void
    {
        $transport = new class implements NFSeSoapTransportInterface {
            private int $call = 0;

            public function send(string $endpoint, string $envelope, array $options = []): array
            {
                $this->call++;
                $response = match ($this->call) {
                    1 => NFSeBelemMunicipalFixtures::consultarNfseRpsSoapResponse(),
                    2 => NFSeBelemMunicipalFixtures::consultarLoteSoapResponse(),
                    default => NFSeBelemMunicipalFixtures::cancelarSoapSuccessResponse(),
                };

                return [
                    'request_xml' => $envelope,
                    'response_xml' => $response,
                    'status_code' => 200,
                    'headers' => [],
                ];
            }
        };

        $provider = new BelemMunicipalProvider(NFSeBelemMunicipalFixtures::belemConfig([
            'soap_transport' => $transport,
        ]));
        $adapter = new NFSeAdapter('belem', $provider);

        $consultaRps = $adapter->consultarPorRps(NFSeBelemMunicipalFixtures::consultaRps());
        $this->assertStringContainsString('ConsultarNfsePorRpsResponse', $consultaRps);
        $this->assertSame('consultar_por_rps', $adapter->getLastOperationInfo()['operation']);

        $consultaLote = $adapter->consultarLote(NFSeBelemMunicipalFixtures::loteProtocolo());
        $this->assertStringContainsString('ConsultarLoteRpsResponse', $consultaLote);
        $this->assertSame('consultar_lote', $adapter->getLastOperationInfo()['operation']);

        $cancelado = $adapter->cancelar(
            NFSeBelemMunicipalFixtures::cancelamentoNumeroNfse(),
            'Cancelamento de homologacao'
        );
        $this->assertTrue($cancelado);
        $this->assertSame('cancelar', $adapter->getLastOperationInfo()['operation']);
        $this->assertSame('success', $adapter->getLastOperationInfo()['parsed_response']['status']);
    }

    public function testBelemFacadeIncludesMunicipalOperationMetadata(): void
    {
        $transport = new class implements NFSeSoapTransportInterface {
            private int $call = 0;

            public function send(string $endpoint, string $envelope, array $options = []): array
            {
                $this->call++;
                $response = $this->call === 1
                    ? NFSeBelemMunicipalFixtures::consultarLoteSoapResponse()
                    : NFSeBelemMunicipalFixtures::cancelarSoapSuccessResponse();

                return [
                    'request_xml' => $envelope,
                    'response_xml' => $response,
                    'status_code' => 200,
                    'headers' => [],
                ];
            }
        };

        $provider = new BelemMunicipalProvider(NFSeBelemMunicipalFixtures::belemConfig([
            'soap_transport' => $transport,
        ]));
        $adapter = new NFSeAdapter('belem', $provider);
        $facade = new NFSeFacade('belem', $adapter);

        $consulta = $facade->consultarLote(NFSeBelemMunicipalFixtures::loteProtocolo());
        $this->assertTrue($consulta->isSuccess());
        $this->assertSame('consultar_lote', $consulta->getData('consulta')['operation']);

        $cancelamento = $facade->cancelar(
            NFSeBelemMunicipalFixtures::cancelamentoNumeroNfse(),
            'Cancelamento de homologacao'
        );
        $this->assertTrue($cancelamento->isSuccess());
        $this->assertSame('cancelar', $cancelamento->getData('cancelamento')['operation']);
    }
}
