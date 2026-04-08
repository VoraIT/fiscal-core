<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use sabbajohn\FiscalCore\Support\NFSeMunicipalHomologationService;

$service = new NFSeMunicipalHomologationService(dirname(__DIR__, 2));

$result = $service->preview('{{PROVIDER_SLUG}}', '12345678901', [
    'allow_production' => false,
]);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
