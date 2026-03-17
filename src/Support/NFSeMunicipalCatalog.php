<?php

namespace freeline\FiscalCore\Support;


final class NFSeMunicipalCatalog
{
    private array $catalog;

    public function __construct(?string $path = null)
    {
        $path ??= __DIR__ . '/../../config/nfse-catalog.json';
        $this->catalog = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    public function resolveMunicipio(string $input): ?array
    {
        $input = trim(mb_strtolower($input));

        if (isset($this->catalog['municipios'][$input])) {
            return $this->normalizeMunicipio($input);
        }

        if (isset($this->catalog['aliases'][$input])) {
            return $this->normalizeMunicipio($this->catalog['aliases'][$input]);
        }

        foreach ($this->catalog['municipios'] as $ibge => $municipio) {
            if (mb_strtolower($municipio['slug']) === $input) {
                return $this->normalizeMunicipio($ibge);
            }
        }

        return null;
    }

    private function normalizeMunicipio(string $ibge): array
    {
        $m = $this->catalog['municipios'][$ibge];

        return [
            'ibge' => $ibge,
            'slug' => $m['slug'],
            'nome' => $m['nome'],
            'uf' => $m['uf'],
            'provider_family_key' => $m['provider_family'],
            'schema_package' => $m['schema_package'],
            'active' => (bool) ($m['active'] ?? true),
        ];
    }
}
