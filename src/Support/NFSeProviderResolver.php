<?php

namespace freeline\FiscalCore\Support;

final class NFSeProviderResolver
{
    public const NATIONAL_KEY = 'nfse_nacional';

    public function __construct(
        private ?NFSeMunicipalCatalog $catalog = null
    ) {
        $this->catalog ??= new NFSeMunicipalCatalog();
    }

    public function resolveKey(?string $input): string
    {
        if ($input === null || trim($input) === '') {
            return self::NATIONAL_KEY;
        }

        $resolved = $this->catalog->resolveMunicipio($input);

        if ($resolved !== null) {
            return $resolved['provider_family_key'];
        }

        return self::NATIONAL_KEY;
    }

    public function buildMetadata(?string $input): array
    {
        $resolved = $this->catalog->resolveMunicipio($input);

        if ($resolved !== null) {
            return [
                'provider_key' => $resolved['provider_family_key'],
                'municipio_input' => $input,
                'municipio_ignored' => false,
                'municipio_resolved' => $resolved,
                'routing_mode' => 'municipal',
                'warnings' => [],
            ];
        }

        return [
            'provider_key' => self::NATIONAL_KEY,
            'municipio_input' => $input,
            'municipio_ignored' => $input !== null && $input !== '',
            'routing_mode' => 'nacional_fallback',
            'warnings' => $input ? [
                "Município '{$input}' não encontrado no catálogo municipal. Aplicado fallback nacional."
            ] : [],
        ];
    }
}
