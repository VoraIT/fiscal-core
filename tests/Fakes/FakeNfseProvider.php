<?php

declare(strict_types=1);

namespace Tests\Fakes;

use sabbajohn\FiscalCore\Contracts\NFSeNacionalCapabilitiesInterface;
use sabbajohn\FiscalCore\Contracts\NFSeProviderConfigInterface;

final class FakeNfseProvider implements NFSeProviderConfigInterface, NFSeNacionalCapabilitiesInterface
{
    public function emitir(array $dados): string
    {
        return '<ok />';
    }
    public function consultar(string $chave): string
    {
        return '<consulta />';
    }
    public function cancelar(string $chave, string $motivo, ?string $protocolo = null): bool
    {
        return true;
    }
    public function substituir(string $chave, array $dados): string
    {
        return '<substituicao />';
    }
    public function getWsdlUrl(): string
    {
        return 'https://example.test/wsdl';
    }
    public function getVersao(): string
    {
        return '1.00';
    }
    public function getAliquotaFormat(): string
    {
        return 'decimal';
    }
    public function getCodigoMunicipio(): string
    {
        return '0000000';
    }
    public function getAmbiente(): string
    {
        return 'homologacao';
    }
    public function getTimeout(): int
    {
        return 30;
    }
    public function getAuthConfig(): array
    {
        return [];
    }
    public function getNationalApiBaseUrl(): string
    {
        return 'https://example.test/api';
    }
    public function validarDados(array $dados): bool
    {
        return true;
    }
    public function consultarContribuinteCnc(string $cnc): array
    {
        return ['suportado' => false, 'cnc' => $cnc];
    }
    public function verificarHabilitacaoCnc(string $cnc): bool
    {
        return false;
    }
    public function getConfig(): array
    {
        return [];
    }
    public function consultarPorRps(array $identificacaoRps): string
    {
        return '<rps />';
    }
    public function consultarLote(string $protocolo): string
    {
        return '<lote />';
    }
    public function baixarXml(string $chave): string
    {
        return '<xml />';
    }
    public function baixarDanfse(string $chave): string
    {
        return '<danfse />';
    }
    public function listarMunicipiosNacionais(bool $forceRefresh = false): array
    {
        return [];
    }
    public function consultarAliquotasMunicipio(string $codigoMunicipio, ?string $codigoServico = null, ?string $competencia = null, bool $forceRefresh = false): array
    {
        return [];
    }
    public function consultarConvenioMunicipio(string $codigoMunicipio, bool $forceRefresh = false): array
    {
        return [];
    }
    public function validarLayoutDps(array $payload, bool $checkCatalog = true): array
    {
        return ['valid' => true, 'errors' => [], 'warnings' => []];
    }
    public function gerarXmlDpsPreview(array $payload): ?string
    {
        return '<xml />';
    }
    public function validarXmlDps(array $payload): array
    {
        return ['valid' => true, 'xml' => '<xml />', 'errors' => [], 'missingTags' => []];
    }
}
