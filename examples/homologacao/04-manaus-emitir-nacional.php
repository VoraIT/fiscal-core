<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/manaus_nacional_common.php';

$projectRoot = dirname(__DIR__, 2);
$options = manausNacionalParseOptions($argv);
if (($options['help'] ?? false) === true) {
    echo manausNacionalUsage(basename((string) $argv[0])) . PHP_EOL;
    exit(0);
}

manausNacionalApplyEnvOverrides($projectRoot);

$nfse = manausNacionalFacade();
$providerInfo = $nfse->getProviderInfo();
$payload = manausNacionalBuildPayload($options);

if (($options['send'] ?? false) !== true) {
    $layout = $nfse->validarLayoutDps($payload, false);
    $xml = $nfse->gerarXmlDpsPreview($payload);

    echo json_encode([
        'mode' => 'preview',
        'provider' => $providerInfo->toArray(),
        'layout' => $layout->toArray(),
        'payload' => $payload,
        'xml_preview' => $xml,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
}

$resultado = $nfse->emitirCompleto($payload);
echo $resultado->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
