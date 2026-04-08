<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use sabbajohn\FiscalCore\Support\NFSeScaffoldGenerator;

$options = [
    'family' => null,
    'provider_class' => null,
    'layout_family' => null,
    'schema_package' => null,
    'transport' => 'soap',
    'output_dir' => null,
    'dry_run' => false,
];

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--dry-run') {
        $options['dry_run'] = true;
        continue;
    }

    if ($arg === '--help' || $arg === '-h') {
        echo "Uso: php scripts/nfse/scaffold-family.php --family=CHAVE [--provider-class=NomeProvider] [--layout-family=LAYOUT] [--schema-package=SCHEMAS] [--transport=soap|rest] [--output-dir=DIR] [--dry-run]\n";
        exit(0);
    }

    if (!str_starts_with($arg, '--') || !str_contains($arg, '=')) {
        continue;
    }

    [$key, $value] = explode('=', substr($arg, 2), 2);
    $normalized = str_replace('-', '_', $key);
    if (array_key_exists($normalized, $options)) {
        $options[$normalized] = $value;
    }
}

$generator = new NFSeScaffoldGenerator(dirname(__DIR__, 2));
$result = $generator->scaffoldFamily($options);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
