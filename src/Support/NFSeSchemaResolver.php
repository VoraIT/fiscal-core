<?php 
namespace freeline\FiscalCore\Support;

final class NFSeSchemaResolver
{
    public function resolve(string $providerFamily, string $operation): string
    {
        $families = json_decode(
            file_get_contents(__DIR__ . '/../../config/nfse-provider-families.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $family = $families[$providerFamily] ?? null;

        if (!$family) {
            throw new \RuntimeException("Família '{$providerFamily}' não configurada.");
        }

        $root = dirname(__DIR__, 2) . '/' . $family['schema_root'];
        $entry = $family['xsd_entrypoints'][$operation] ?? null;

        if (!$entry) {
            throw new \RuntimeException("Operação '{$operation}' sem schema mapeado.");
        }

        return $root . '/' . $entry;
    }
}
