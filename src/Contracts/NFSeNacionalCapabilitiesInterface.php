<?php

namespace sabbajohn\FiscalCore\Contracts;

/**
 * Capacidades opcionais para o provider NFSe Nacional.
 *
 * Providers legados municipais não precisam implementar esta interface.
 */
interface NFSeNacionalCapabilitiesInterface
{
    public function consultarPorRps(array $identificacaoRps): NFSeConsultaResultInterface;

    public function consultarLote(string $protocolo): NFSeConsultaResultInterface;

    public function substituir(string $nfseOriginal, array $dadosSubstituicao): string;

    public function baixarXml(string $chave): string;

    public function baixarDanfse(string $chave): NFSeImpressaoResultInterface;

    /**
     * @return array{data: array, metadata?: array}
     */
    public function listarMunicipiosNacionais(bool $forceRefresh = false): array;

    /**
     * @return array{data: array, metadata?: array}
     */
    public function consultarAliquotasMunicipio(
        string $codigoMunicipio,
        ?string $codigoServico = null,
        ?string $competencia = null,
        bool $forceRefresh = false
    ): array;

    /**
     * @return array{data: array, metadata?: array}
     */
    public function consultarConvenioMunicipio(string $codigoMunicipio, bool $forceRefresh = false): array;

    /**
     * Valida o layout DPS nacional e, opcionalmente, consulta catálogo municipal.
     *
     * @return array{
     *   valid: bool,
     *   errors: array<int, string>,
     *   warnings: array<int, string>,
     *   catalog: array<string, mixed>|null
     * }
     */
    public function validarLayoutDps(array $payload, bool $checkCatalog = true): array;

    /**
     * Gera XML DPS para preview sem transmissão.
     */
    public function gerarXmlDpsPreview(array $payload): ?string;

    /**
     * Gera e valida estruturalmente o XML DPS antes do envio.
     *
     * @return array{
     *   valid: bool,
     *   xml: string|null,
     *   errors: array<int, string>,
     *   missingTags: array<int, array{tag: string, xpath: string}>
     * }
     */
    public function validarXmlDps(array $payload): array;
}
