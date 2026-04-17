<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Fixtures/NFSeIsswebFixtures.php';

use sabbajohn\FiscalCore\Providers\NFSe\Municipal\IsswebProvider;
use sabbajohn\FiscalCore\Support\NFSeSchemaResolver;
use sabbajohn\FiscalCore\Support\NFSeSchemaValidator;
use PHPUnit\Framework\TestCase;

final class IsswebProviderTest extends TestCase
{
    public function testEmitirGeneratesSchemaValidXmlAndParsesSuccess(): void
    {
        $provider = new IsswebProvider(NFSeIsswebFixtures::config([
            'soap_transport' => NFSeIsswebFixtures::makeTransport(NFSeIsswebFixtures::successResponse()),
        ]));

        $provider->emitir(NFSeIsswebFixtures::payload());
        $artifacts = $provider->getLastOperationArtifacts();

        $schema = (new NFSeSchemaResolver())->resolve('ISSWEB_AM', 'emitir');
        $validation = (new NFSeSchemaValidator())->validate((string) $artifacts['request_xml'], $schema);

        $this->assertTrue($validation['valid'], implode(PHP_EOL, $validation['errors']));
        $this->assertSame('success', $provider->getLastResponseData()['status']);
        $this->assertSame('4567', $provider->getLastResponseData()['numero']);
        $this->assertSame('AB12-C3456', $provider->getLastResponseData()['chave_validacao']);
        $this->assertStringContainsString('servicosweb.pmpf.am.gov.br', (string) $provider->getLastResponseData()['nfse_url']);
    }

    public function testConsultarGeneratesSchemaValidXml(): void
    {
        $provider = new IsswebProvider(NFSeIsswebFixtures::config([
            'soap_transport' => NFSeIsswebFixtures::makeTransport(NFSeIsswebFixtures::consultaResponse()),
        ]));

        $provider->consultar('4567');
        $artifacts = $provider->getLastOperationArtifacts();

        $schema = (new NFSeSchemaResolver())->resolve('ISSWEB_AM', 'consultar');
        $validation = (new NFSeSchemaValidator())->validate((string) $artifacts['request_xml'], $schema);

        $this->assertTrue($validation['valid'], implode(PHP_EOL, $validation['errors']));
        $this->assertSame('4567', $provider->getLastResponseData()['numero']);
    }

    public function testCancelarGeneratesSchemaValidXml(): void
    {
        $provider = new IsswebProvider(NFSeIsswebFixtures::config([
            'soap_transport' => NFSeIsswebFixtures::makeTransport(NFSeIsswebFixtures::cancelResponse()),
        ]));

        $result = $provider->cancelar('4567', 'Cancelamento de teste em homologacao', 'AB12-C3456');
        $artifacts = $provider->getLastOperationArtifacts();

        $schema = (new NFSeSchemaResolver())->resolve('ISSWEB_AM', 'cancelar_nfse');
        $validation = (new NFSeSchemaValidator())->validate((string) $artifacts['request_xml'], $schema);

        $this->assertTrue($result);
        $this->assertTrue($validation['valid'], implode(PHP_EOL, $validation['errors']));
    }

    public function testParsesRejectionResponse(): void
    {
        $provider = new IsswebProvider(NFSeIsswebFixtures::config([
            'soap_transport' => NFSeIsswebFixtures::makeTransport(NFSeIsswebFixtures::rejectionResponse()),
        ]));

        $provider->emitir(NFSeIsswebFixtures::payload());

        $this->assertSame('error', $provider->getLastResponseData()['status']);
        $this->assertSame(['[123] Item de atividade invalido para o prestador.'], $provider->getLastResponseData()['mensagens']);
    }

    public function testThrowsWhenAuthKeyIsMissing(): void
    {
        $provider = new IsswebProvider(NFSeIsswebFixtures::config([
            'auth' => ['chave' => ''],
        ]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('NFSE_ISSWEB_CHAVE');

        $provider->emitir(NFSeIsswebFixtures::payload());
    }

    public function testThrowsWhenEndpointIsMissing(): void
    {
        $provider = new IsswebProvider(NFSeIsswebFixtures::config([
            'wsdl_homologacao' => '',
            'service_base_homologacao' => '',
        ]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Endpoint ISSWEB de homologacao');

        $provider->emitir(NFSeIsswebFixtures::payload());
    }

    public function testRejectsMissingPrestadorInscricaoMunicipal(): void
    {
        $provider = new IsswebProvider(NFSeIsswebFixtures::config());
        $payload = NFSeIsswebFixtures::payload();
        unset($payload['prestador']['inscricaoMunicipal']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('prestador.inscricaoMunicipal');

        $provider->emitir($payload);
    }

    public function testRioPretoDaEvaUsesSharedIsswebFamilyWithMunicipalCode(): void
    {
        $provider = new IsswebProvider(NFSeIsswebFixtures::config([
            'municipio_slug' => 'rio-preto-da-eva',
            'soap_transport' => NFSeIsswebFixtures::makeTransport(NFSeIsswebFixtures::successResponse()),
        ]));

        $provider->emitir(NFSeIsswebFixtures::payload([
            'municipio_slug' => 'rio-preto-da-eva',
        ]));
        $artifacts = $provider->getLastOperationArtifacts();

        $schema = (new NFSeSchemaResolver())->resolve('ISSWEB_AM', 'emitir');
        $validation = (new NFSeSchemaValidator())->validate((string) $artifacts['request_xml'], $schema);

        $this->assertTrue($validation['valid'], implode(PHP_EOL, $validation['errors']));
        $this->assertSame('1303569', $provider->getCodigoMunicipio());
        $this->assertNull($provider->getLastResponseData()['nfse_url']);
    }
}
