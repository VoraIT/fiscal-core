<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/common.php';

$projectRoot = dirname(__DIR__, 2);

exit(nfseMunicipalRunScript('joinville', $argv, [
    'FISCAL_ENVIRONMENT' => 'homologacao',
    'FISCAL_CERT_PATH' => $projectRoot . '/certs/...',
    'FISCAL_CERT_PASSWORD' => '...',
    'FISCAL_CNPJ' => '',
    'FISCAL_RAZAO_SOCIAL' => 'FREELINE INFORMATICA LTDA',
    'FISCAL_UF' => 'SC',
]));
