<?php

declare(strict_types=1);

/**
 * Importa pacotes de schemas NFSe do repositório Uninfe para o fiscal-core.
 *
 * Uso:
 *   php scripts/nfse/import-uninfe-schemas.php
 *   php scripts/nfse/import-uninfe-schemas.php --dry-run
 *   php scripts/nfse/import-uninfe-schemas.php --force
 *   php scripts/nfse/import-uninfe-schemas.php --families=BELEM_MUNICIPAL_2025,MANAUS_AM,PUBLICA
 *   php scripts/nfse/import-uninfe-schemas.php --all
 *   php scripts/nfse/import-uninfe-schemas.php --all --force
 *
 * Origem esperada:
 *   Uninfe/source/NFe.Components.Wsdl/NFse/schemas/NFSe
 *
 * Destino:
 *   resources/nfse/schemas
 */

const DEFAULT_FAMILIES = [
    'BELEM_MUNICIPAL_2025',
    'MANAUS_AM',
    'PUBLICA',
];

const CUSTOM_SCHEMA_FAMILIES = [
    'BELEM_MUNICIPAL_2025' => 'scripts/nfse/schema-overrides/BELEM_MUNICIPAL_2025',
    'PUBLICA' => 'scripts/nfse/schema-overrides/PUBLICA',
];

const FAMILY_FILE_OVERLAYS = [];

main($argv);

/**
 * @param array<int, string> $argv
 */
function main(array $argv): void
{
    $options = parseOptions($argv);

    $projectRoot = realpath(__DIR__ . '/../../');
    if ($projectRoot === false) {
        fail('Não foi possível resolver a raiz do projeto.');
    }

    $sourceBase = $projectRoot . '/Uninfe/source/NFe.Components.Wsdl/NFse/schemas/NFSe';
    $targetBase = $projectRoot . '/resources/nfse/schemas';
    $manifestPath = $targetBase . '/manifest.json';

    if (!is_dir($sourceBase) && array_diff($options['families'], array_keys(CUSTOM_SCHEMA_FAMILIES)) !== []) {
        fail("Diretório de origem não encontrado: {$sourceBase}");
    }

    if (!is_dir($targetBase) && !$options['dry-run']) {
        mkdirRecursive($targetBase);
    }

    $families = $options['all']
        ? discoverSchemaFamilies($sourceBase)
        : $options['families'];

    if ($families === []) {
        fail('Nenhuma família encontrada para importação.');
    }

    echo PHP_EOL;
    echo '== Importação de Schemas NFSe do Uninfe ==' . PHP_EOL;
    echo 'Origem  : ' . $sourceBase . PHP_EOL;
    echo 'Destino : ' . $targetBase . PHP_EOL;
    echo 'Famílias: ' . implode(', ', $families) . PHP_EOL;
    echo 'Dry-run : ' . ($options['dry-run'] ? 'sim' : 'não') . PHP_EOL;
    echo 'Force   : ' . ($options['force'] ? 'sim' : 'não') . PHP_EOL;
    echo 'All     : ' . ($options['all'] ? 'sim' : 'não') . PHP_EOL;
    echo PHP_EOL;

    $summary = [
        'copied_families' => 0,
        'copied_files' => 0,
        'skipped_families' => 0,
        'warnings' => [],
        'families' => [],
        'sources' => [],
    ];

    foreach ($families as $family) {
        [$sourceDir, $sourceKind] = resolveFamilySource($projectRoot, $sourceBase, $family);
        $targetDir = $targetBase . '/' . $family;

        if (!is_dir($sourceDir)) {
            $summary['warnings'][] = "Família '{$family}' não encontrada na origem.";
            echo "[WARN] Família '{$family}' não encontrada em {$sourceDir}" . PHP_EOL;
            continue;
        }

        $sourceFileCount = countFiles($sourceDir);

        if (is_dir($targetDir) && !$options['force']) {
            $summary['skipped_families']++;
            $summary['families'][$family] = [
                'status' => 'skipped',
                'files' => $sourceFileCount,
                'source_kind' => $sourceKind,
                'source' => relativeToProjectRoot($projectRoot, $sourceDir),
                'target' => relativeToProjectRoot($projectRoot, $targetDir),
            ];

            echo "[SKIP] Destino já existe para '{$family}'. Use --force para sobrescrever." . PHP_EOL;
            continue;
        }

        if ($options['dry-run']) {
            $summary['copied_families']++;
            $summary['copied_files'] += $sourceFileCount;
            $summary['families'][$family] = [
                'status' => 'dry-run',
                'files' => $sourceFileCount,
                'source_kind' => $sourceKind,
                'source' => relativeToProjectRoot($projectRoot, $sourceDir),
                'target' => relativeToProjectRoot($projectRoot, $targetDir),
            ];

            echo "[DRY ] {$family}: {$sourceFileCount} arquivo(s) seriam copiados para {$targetDir}" . PHP_EOL;
            continue;
        }

        if (is_dir($targetDir) && $options['force']) {
            deleteDirectoryRecursive($targetDir);
        }

        mkdirRecursive($targetDir);

        $copied = copyDirectoryRecursive($sourceDir, $targetDir);
        $copied += applyFamilyOverlays($projectRoot, $family, $targetDir);

        $summary['copied_families']++;
        $summary['copied_files'] += $copied;
        $summary['families'][$family] = [
            'status' => 'imported',
            'files' => $copied,
            'source_kind' => $sourceKind,
            'source' => relativeToProjectRoot($projectRoot, $sourceDir),
            'target' => relativeToProjectRoot($projectRoot, $targetDir),
        ];

        echo "[ OK ] {$family}: {$copied} arquivo(s) copiados para {$targetDir}" . PHP_EOL;
    }

    if (!$options['dry-run']) {
        $manifest = [
            'generated_at' => date(DATE_ATOM),
            'source_base' => relativeToProjectRoot($projectRoot, $sourceBase),
            'target_base' => relativeToProjectRoot($projectRoot, $targetBase),
            'mode' => $options['all'] ? 'all' : 'selected',
            'custom_schema_families' => CUSTOM_SCHEMA_FAMILIES,
            'families' => $summary['families'],
            'warnings' => $summary['warnings'],
        ];

        file_put_contents(
            $manifestPath,
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        echo PHP_EOL;
        echo '[ OK ] Manifest gerado em ' . $manifestPath . PHP_EOL;
    }

    echo PHP_EOL;
    echo '== Resumo ==' . PHP_EOL;
    echo 'Famílias processadas: ' . count($families) . PHP_EOL;
    echo 'Famílias copiadas   : ' . $summary['copied_families'] . PHP_EOL;
    echo 'Famílias puladas    : ' . $summary['skipped_families'] . PHP_EOL;
    echo 'Arquivos copiados   : ' . $summary['copied_files'] . PHP_EOL;

    if ($summary['warnings'] !== []) {
        echo 'Avisos:' . PHP_EOL;
        foreach ($summary['warnings'] as $warning) {
            echo ' - ' . $warning . PHP_EOL;
        }
    }

    echo PHP_EOL;
}

/**
 * @return array{0:string,1:string}
 */
function resolveFamilySource(string $projectRoot, string $uninfeSourceBase, string $family): array
{
    if (isset(CUSTOM_SCHEMA_FAMILIES[$family])) {
        return [$projectRoot . '/' . CUSTOM_SCHEMA_FAMILIES[$family], 'custom_override'];
    }

    return [$uninfeSourceBase . '/' . $family, 'uninfe'];
}

function applyFamilyOverlays(string $projectRoot, string $family, string $targetDir): int
{
    $overlays = FAMILY_FILE_OVERLAYS[$family] ?? [];
    $copied = 0;

    foreach ($overlays as $overlay) {
        $sourcePath = $projectRoot . '/' . ltrim((string) $overlay['source'], '/');
        $targetPath = $targetDir . '/' . ltrim((string) $overlay['target'], '/');

        if (!is_file($sourcePath)) {
            fail("Overlay de schema não encontrado para '{$family}': {$sourcePath}");
        }

        $targetPathDir = dirname($targetPath);
        mkdirRecursive($targetPathDir);

        if (!copy($sourcePath, $targetPath)) {
            fail("Falha ao copiar overlay '{$sourcePath}' para '{$targetPath}'");
        }

        $copied++;
    }

    return $copied;
}

/**
 * @param array<int, string> $argv
 * @return array{
 *   dry-run: bool,
 *   force: bool,
 *   all: bool,
 *   families: array<int, string>
 * }
 */
function parseOptions(array $argv): array
{
    $dryRun = false;
    $force = false;
    $all = false;
    $families = DEFAULT_FAMILIES;

    foreach ($argv as $arg) {
        if ($arg === '--dry-run') {
            $dryRun = true;
            continue;
        }

        if ($arg === '--force') {
            $force = true;
            continue;
        }

        if ($arg === '--all') {
            $all = true;
            continue;
        }

        if (str_starts_with($arg, '--families=')) {
            $raw = trim(substr($arg, strlen('--families=')));
            if ($raw !== '') {
                $families = array_values(array_filter(array_map(
                    static fn (string $value): string => trim($value),
                    explode(',', $raw)
                )));
            }
        }
    }

    if (!$all && $families === []) {
        fail('Nenhuma família informada em --families.');
    }

    return [
        'dry-run' => $dryRun,
        'force' => $force,
        'all' => $all,
        'families' => $families,
    ];
}

/**
 * @return array<int, string>
 */
function discoverSchemaFamilies(string $sourceBase): array
{
    $families = [];

    $items = scandir($sourceBase);
    if ($items === false) {
        fail("Falha ao listar diretório: {$sourceBase}");
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $fullPath = $sourceBase . '/' . $item;

        if (is_dir($fullPath)) {
            $families[] = $item;
        }
    }

    sort($families);

    return $families;
}

function mkdirRecursive(string $path): void
{
    if (is_dir($path)) {
        return;
    }

    if (!mkdir($path, 0777, true) && !is_dir($path)) {
        fail("Falha ao criar diretório: {$path}");
    }
}

function countFiles(string $directory): int
{
    $count = 0;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
    );

    /** @var SplFileInfo $item */
    foreach ($iterator as $item) {
        if ($item->isFile()) {
            $count++;
        }
    }

    return $count;
}

function copyDirectoryRecursive(string $source, string $target): int
{
    $copied = 0;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    /** @var SplFileInfo $item */
    foreach ($iterator as $item) {
        $relativePath = substr($item->getPathname(), strlen($source) + 1);
        $destinationPath = $target . '/' . $relativePath;

        if ($item->isDir()) {
            mkdirRecursive($destinationPath);
            continue;
        }

        $destinationDir = dirname($destinationPath);
        mkdirRecursive($destinationDir);

        if (!copy($item->getPathname(), $destinationPath)) {
            fail("Falha ao copiar arquivo '{$item->getPathname()}' para '{$destinationPath}'");
        }

        $copied++;
    }

    return $copied;
}

function deleteDirectoryRecursive(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    /** @var SplFileInfo $item */
    foreach ($iterator as $item) {
        if ($item->isDir()) {
            if (!rmdir($item->getPathname())) {
                fail("Falha ao remover diretório '{$item->getPathname()}'");
            }
            continue;
        }

        if (!unlink($item->getPathname())) {
            fail("Falha ao remover arquivo '{$item->getPathname()}'");
        }
    }

    if (!rmdir($directory)) {
        fail("Falha ao remover diretório '{$directory}'");
    }
}

function relativeToProjectRoot(string $projectRoot, string $path): string
{
    $normalizedRoot = rtrim(str_replace('\\', '/', $projectRoot), '/');
    $normalizedPath = str_replace('\\', '/', $path);

    if (str_starts_with($normalizedPath, $normalizedRoot . '/')) {
        return substr($normalizedPath, strlen($normalizedRoot) + 1);
    }

    return $normalizedPath;
}

function fail(string $message): never
{
    fwrite(STDERR, '[ERROR] ' . $message . PHP_EOL);
    exit(1);
}
