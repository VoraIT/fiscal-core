<?php

declare(strict_types=1);

/**
 * Importa catálogo NFSe a partir do Webservice.xml do Uninfe.
 *
 * Gera:
 *   - config/nfse-catalog.json
 *   - config/nfse-provider-families.json
 *   - config/nfse-catalog-manifest.json
 *
 * Uso:
 *   php scripts/nfse/import-uninfe-catalog.php
 *   php scripts/nfse/import-uninfe-catalog.php --dry-run
 *   php scripts/nfse/import-uninfe-catalog.php --force
 *   php scripts/nfse/import-uninfe-catalog.php --all
 *   php scripts/nfse/import-uninfe-catalog.php --ibges=1501402,1302603,4209102
 */

const DEFAULT_IBGES = [
    '1501402', // Belém
    '1302603', // Manaus
    '1303536', // Presidente Figueiredo
    '1303569', // Rio Preto da Eva
    '4209102', // Joinville
];

const PROVIDER_CLASS_MAP = [
    'BELEM_MUNICIPAL_2025' => 'sabbajohn\\FiscalCore\\Providers\\NFSe\\Municipal\\BelemMunicipalProvider',
    'DSF' => 'sabbajohn\\FiscalCore\\Providers\\NFSe\\Municipal\\DsfProvider',
    'MANAUS_AM' => 'sabbajohn\\FiscalCore\\Providers\\NFSe\\Municipal\\ManausAmProvider',
    'ISSWEB_AM' => 'sabbajohn\\FiscalCore\\Providers\\NFSe\\Municipal\\IsswebProvider',
    'PUBLICA' => 'sabbajohn\\FiscalCore\\Providers\\NFSe\\Municipal\\PublicaProvider',
    'nfse_nacional' => 'sabbajohn\\FiscalCore\\Providers\\NFSe\\NacionalProvider',
];

const DEFAULT_PROVIDER_METADATA = [
    'BELEM_MUNICIPAL_2025' => [
        'layout_family' => 'ABRASF_203',
        'schema_root' => 'resources/nfse/schemas/BELEM_MUNICIPAL_2025',
        'xsd_entrypoints' => [
            'emitir' => 'enviar_lote_rps_sincrono_envio.xsd',
            'enviar_lote_rps' => 'enviar_lote_rps_sincrono_envio.xsd',
            'consultar_lote' => 'consultar_lote_rps_envio.xsd',
            'consultar_nfse_rps' => 'consultar_nfse_rps_envio.xsd',
            'cancelar_nfse' => 'cancelar_nfse_envio.xsd',
        ],
        'aliquota_format' => 'decimal',
        'transport' => 'soap',
        'versao' => '2.03',
        'codigo_municipio' => '1501402',
        'wsdl_homologacao' => 'https://sefin-hml.belem.pa.gov.br/notafiscal-abrasfv203-ws/NotaFiscalSoap?wsdl',
        'wsdl_producao' => 'https://notafiscal.belem.pa.gov.br/notafiscal-abrasfv203-ws/NotaFiscalSoap?wsdl',
        'signature_mode' => 'required',
        'soap_operation' => 'RecepcionarLoteRpsSincrono',
        'supported_operations' => [
            'emitir_sincrono',
            'consultar_lote',
            'consultar_nfse_rps',
            'cancelar_nfse',
        ],
        'municipio_nome' => 'Belem',
        'source' => 'custom_override',
    ],
    'DSF' => [
        'layout_family' => 'LEGACY_ALIAS',
        'schema_root' => 'resources/nfse/schemas/BELEM_MUNICIPAL_2025',
        'xsd_entrypoints' => [
            'emitir' => 'enviar_lote_rps_sincrono_envio.xsd',
            'consultar_lote' => 'consultar_lote_rps_envio.xsd',
            'consultar_nfse_rps' => 'consultar_nfse_rps_envio.xsd',
            'cancelar_nfse' => 'cancelar_nfse_envio.xsd',
        ],
        'aliquota_format' => 'decimal',
        'transport' => 'soap',
        'versao' => '2.03',
        'codigo_municipio' => '1501402',
        'wsdl_homologacao' => 'https://sefin-hml.belem.pa.gov.br/notafiscal-abrasfv203-ws/NotaFiscalSoap?wsdl',
        'wsdl_producao' => 'https://notafiscal.belem.pa.gov.br/notafiscal-abrasfv203-ws/NotaFiscalSoap?wsdl',
        'signature_mode' => 'required',
        'soap_operation' => 'RecepcionarLoteRpsSincrono',
        'supported_operations' => [
            'emitir_sincrono',
            'consultar_lote',
            'consultar_nfse_rps',
            'cancelar_nfse',
        ],
        'municipio_nome' => 'Belem',
        'source' => 'legacy_alias',
    ],
    'MANAUS_AM' => [
        'layout_family' => 'MANAUS_AM',
        'schema_root' => 'resources/nfse/schemas/MANAUS_AM',
        'xsd_entrypoints' => [
            'emitir' => 'nfse_v2010.xsd',
            'enviar_lote_rps' => 'nfse_v2010.xsd',
            'consultar_lote' => 'nfse_v2010.xsd',
            'consultar_nfse_rps' => 'nfse_v2010.xsd',
        ],
        'aliquota_format' => 'decimal',
        'transport' => 'soap',
        'versao' => '2010',
        'codigo_municipio' => '1302603',
        'municipio_nome' => 'Manaus',
        'wsdl' => '',
    ],
    'ISSWEB_AM' => [
        'layout_family' => 'ISSWEB',
        'schema_root' => 'resources/nfse/schemas/ISSWEB',
        'xsd_entrypoints' => [
            'emitir' => 'XSDNFEletronica.xsd',
            'consultar' => 'XSDISSEConsultaNota.xsd',
            'cancelar_nfse' => 'XSDISSECancelaNFe.xsd',
            'retorno' => 'XSDRetorno.xsd',
        ],
        'aliquota_format' => 'decimal',
        'transport' => 'soap',
        'versao' => 'ISSWEB',
        'municipio_uf' => 'AM',
        'wsdl_homologacao' => '',
        'wsdl_producao' => '',
        'service_base_homologacao' => '',
        'service_base_producao' => '',
        'signature_mode' => 'optional',
        'supported_operations' => [
            'emitir',
            'consultar',
            'cancelar',
        ],
        'printing_mode' => 'official_url',
        'official_validation_url_template' => '',
        'auth' => [
            'chave' => '',
        ],
        'source' => 'custom_override',
    ],
    'PUBLICA' => [
        'layout_family' => 'PUBLICA',
        'schema_root' => 'resources/nfse/schemas/PUBLICA',
        'xsd_entrypoints' => [
            'emitir' => 'schema_nfse_v03.xsd',
            'enviar_lote_rps' => 'schema_nfse_v03.xsd',
            'consultar_lote' => 'schema_nfse_v03.xsd',
            'consultar_nfse_rps' => 'schema_nfse_v03.xsd',
            'cancelar_nfse' => 'schema_nfse_v03.xsd',
        ],
        'aliquota_format' => 'percentual',
        'transport' => 'soap',
        'versao' => '3.00',
        'codigo_municipio' => '4209102',
        'municipio_nome' => 'Joinville',
        'wsdl_homologacao' => 'https://nfsehomologacao.joinville.sc.gov.br/nfse_integracao/Services?wsdl',
        'wsdl_producao' => 'https://nfem.joinville.sc.gov.br/nfse_integracao/Services?wsdl',
        'consultas_wsdl_homologacao' => 'https://nfsehomologacao.joinville.sc.gov.br/nfse_integracao/Consultas?wsdl',
        'consultas_wsdl_producao' => 'https://nfem.joinville.sc.gov.br/nfse_integracao/Consultas?wsdl',
        'signature_mode' => 'required',
        'signature_algorithm' => 'sha1',
        'soap_style' => 'rpc_literal',
        'soap_namespace' => 'http://service.nfse.integracao.ws.publica/',
        'supported_operations' => [
            'emitir',
            'consultar_lote',
            'consultar_nfse_rps',
            'cancelar_nfse',
        ],
        'source' => 'custom_override',
    ],
    'nfse_nacional' => [
        'layout_family' => 'NACIONAL',
        'transport' => 'rest',
        'codigo_municipio' => '1001058',
        'wsdl' => '',
        'api_base_url' => '',
    ],
];

const MUNICIPIO_OVERRIDES = [
    '1303536' => [
        'provider_family' => 'ISSWEB_AM',
        'schema_package' => 'ISSWEB',
        'slug' => 'presidente-figueiredo',
        'nome' => 'Presidente Figueiredo',
        'uf' => 'AM',
        'provider_note' => 'Município preparado para integração municipal via família compartilhada ISSWEB_AM.',
        'provider_config_overrides' => [
            'official_validation_url_template' => 'https://servicosweb.pmpf.am.gov.br/issweb/validacao?numero={numero}&chave={chave_validacao}',
        ],
        'payload_defaults' => [
            'rps' => [
                'numero' => '123',
            ],
            'tomador' => [
                'razao_social' => 'Cliente Presidente Figueiredo Ltda',
                'email' => 'financeiro@example.com',
                'endereco' => [
                    'logradouro' => 'Avenida Amazonas',
                    'bairro' => 'Centro',
                    'codigo_municipio' => '1303536',
                    'uf' => 'AM',
                    'cep' => '69735-000',
                    'municipio' => 'Presidente Figueiredo',
                ],
            ],
            'servico' => [
                'codigo' => '101',
                'descricao' => 'Servico de homologacao ISSWEB para Presidente Figueiredo.',
                'discriminacao' => 'Servico de homologacao ISSWEB para Presidente Figueiredo.',
                'tipo_documento' => '001',
                'local_prestacao' => [
                    'tipo' => '1',
                    'uf' => 'AM',
                    'codigo_municipio' => '1303536',
                    'cep' => '69735-000',
                ],
            ],
        ],
    ],
    '1303569' => [
        'provider_family' => 'ISSWEB_AM',
        'schema_package' => 'ISSWEB',
        'slug' => 'rio-preto-da-eva',
        'nome' => 'Rio Preto da Eva',
        'uf' => 'AM',
        'provider_note' => 'Hipótese atual de integração municipal via ISSWEB, pendente de validação oficial.',
        'payload_defaults' => [
            'rps' => [
                'numero' => '123',
            ],
            'tomador' => [
                'razao_social' => 'Cliente Rio Preto da Eva Ltda',
                'email' => 'financeiro@example.com',
                'endereco' => [
                    'logradouro' => 'Avenida Governador Gilberto Mestrinho',
                    'bairro' => 'Centro',
                    'codigo_municipio' => '1303569',
                    'uf' => 'AM',
                    'cep' => '69117-000',
                    'municipio' => 'Rio Preto da Eva',
                ],
            ],
            'servico' => [
                'codigo' => '101',
                'descricao' => 'Servico de homologacao ISSWEB para Rio Preto da Eva.',
                'discriminacao' => 'Servico de homologacao ISSWEB para Rio Preto da Eva.',
                'tipo_documento' => '001',
                'local_prestacao' => [
                    'tipo' => '1',
                    'uf' => 'AM',
                    'codigo_municipio' => '1303569',
                    'cep' => '69117-000',
                ],
            ],
        ],
    ],
    '1501402' => [
        'provider_family' => 'BELEM_MUNICIPAL_2025',
        'schema_package' => 'BELEM_MUNICIPAL_2025',
        'slug' => 'belem',
        'nome' => 'Belem',
        'uf' => 'PA',
        'provider_note' => 'Belém migrado para provider municipal atual; override local sobre catálogo UniNFe.',
        'provider_config_overrides' => [
            'requires_explicit_mei_classification' => true,
        ],
    ],
    '4209102' => [
        'provider_family' => 'PUBLICA',
        'schema_package' => 'PUBLICA',
        'slug' => 'joinville',
        'nome' => 'Joinville',
        'uf' => 'SC',
        'payload_defaults' => [
            'servico' => [
                'descricao' => 'Desenvolvimento e licenciamento de software para homologacao de Joinville.',
                'discriminacao' => 'Desenvolvimento e licenciamento de software para homologacao de Joinville.',
            ],
        ],
    ],
];

const EXTRA_PROVIDER_FAMILIES = [
    'DSF',
];

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

    $sourceXmlPath = $projectRoot . '/Uninfe/source/NFe.Components.Wsdl/NFse/WSDL/Webservice.xml';    
    $catalogPath = $projectRoot . '/config/nfse/providers-catalog.json';
    $familiesPath = $projectRoot . '/config/nfse/nfse-provider-families.json';
    $manifestPath = $projectRoot . '/config/nfse/nfse-catalog-manifest.json';


    if (!is_file($sourceXmlPath)) {
        fail("Arquivo de origem não encontrado: {$sourceXmlPath}");
    }

    $xml = simplexml_load_file($sourceXmlPath);
    if ($xml === false) {
        fail("Falha ao carregar XML: {$sourceXmlPath}");
    }

    $targets = $options['all'] ? null : $options['ibges'];

    echo PHP_EOL;
    echo '== Importação de Catálogo NFSe do Uninfe ==' . PHP_EOL;
    echo 'Origem : ' . $sourceXmlPath . PHP_EOL;
    echo 'Dry-run: ' . ($options['dry-run'] ? 'sim' : 'não') . PHP_EOL;
    echo 'Force  : ' . ($options['force'] ? 'sim' : 'não') . PHP_EOL;
    echo 'All    : ' . ($options['all'] ? 'sim' : 'não') . PHP_EOL;
    echo 'IBGEs  : ' . ($targets === null ? '[todos]' : implode(', ', $targets)) . PHP_EOL;
    echo PHP_EOL;

    $catalog = [
        'version' => 1,
        'generated_at' => date(DATE_ATOM),
        'municipios' => [],
        'aliases' => [],
    ];

    $catalog['municipios']['1001058'] = [
        'slug' => 'nacional',
        'nome' => 'Nacional',
        'uf' => 'AN',
        'provider_family' => 'nfse_nacional',
        'schema_package' => 'NACIONAL',
        'ibge' => '1001058',
        'homologado' => false,
        'active' => true,
    ];

    foreach (buildMunicipioAliases($catalog['municipios']['1001058']) as $alias) {
        $catalog['aliases'][$alias] = '1001058';
    }

    $families = [];
    $processed = 0;
    $skipped = 0;

    foreach ($xml->Estado as $estado) {
        $ibge = trim((string) $estado['ID']);

        if ($ibge === '') {
            $skipped++;
            continue;
        }

        if ($targets !== null && !in_array($ibge, $targets, true)) {
            continue;
        }

        $nomeCompleto = trim((string) $estado['Nome']);
        $nome = normalizeMunicipioNome($nomeCompleto);
        $uf = strtoupper(trim((string) $estado['UF']));
        $providerFamily = trim((string) $estado['Padrao']);

        if ($providerFamily === '') {
            $providerFamily = 'UNMAPPED';
        }

        $slug = slugifyMunicipio($nome, $ibge);

        $municipio = [
            'slug' => $slug,
            'nome' => $nome,
            'uf' => $uf,
            'provider_family' => $providerFamily,
            'schema_package' => $providerFamily,
            'ibge' => $ibge,
            'homologado' => false,
            'active' => true,
        ];

        $municipio = applyMunicipioOverride($ibge, $municipio);

        $catalog['municipios'][$ibge] = $municipio;

        foreach (buildMunicipioAliases($municipio) as $alias) {
            $catalog['aliases'][$alias] = $ibge;
        }

        $effectiveFamily = (string) $municipio['provider_family'];
        $families[$effectiveFamily] = buildProviderFamilyConfig($effectiveFamily);

        $processed++;
    }

    $families['nfse_nacional'] = buildProviderFamilyConfig('nfse_nacional');

    foreach (EXTRA_PROVIDER_FAMILIES as $familyKey) {
        $families[$familyKey] = buildProviderFamilyConfig($familyKey);
    }

    ksort($catalog['municipios']);
    ksort($catalog['aliases']);
    ksort($families);

    if ($processed === 0) {
        fail('Nenhum município foi processado. Verifique os filtros informados.');
    }

    $manifest = [
        'generated_at' => date(DATE_ATOM),
        'source_xml' => relativeToProjectRoot($projectRoot, $sourceXmlPath),
        'mode' => $options['all'] ? 'all' : 'selected',
        'processed_municipios' => $processed,
        'skipped_entries' => $skipped,
        'families_count' => count($families),
        'selected_ibges' => $targets ?? '[todos]',
        'municipio_overrides' => MUNICIPIO_OVERRIDES,
        'extra_provider_families' => EXTRA_PROVIDER_FAMILIES,
    ];

    if ($options['dry-run']) {
        echo "[DRY ] Municípios processados: {$processed}" . PHP_EOL;
        echo "[DRY ] Famílias encontradas : " . count($families) . PHP_EOL;
        echo PHP_EOL;
        echo 'Prévia das famílias:' . PHP_EOL;
        foreach (array_keys($families) as $familyKey) {
            echo " - {$familyKey} => " . ($families[$familyKey]['provider_class'] ?? '[sem classe]') . PHP_EOL;
        }
        echo PHP_EOL;
        return;
    }

    ensureDirectory(dirname($catalogPath));
    ensureDirectory(dirname($familiesPath));

    if (!$options['force']) {
        foreach ([$catalogPath, $familiesPath, $manifestPath] as $path) {
            if (is_file($path)) {
                fail("Arquivo já existe: {$path}. Use --force para sobrescrever.");
            }
        }
    }

    file_put_contents(
        $catalogPath,
        json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );

    file_put_contents(
        $familiesPath,
        json_encode($families, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );

    file_put_contents(
        $manifestPath,
        json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );

    echo "[ OK ] Catálogo gerado em {$catalogPath}" . PHP_EOL;
    echo "[ OK ] Famílias geradas em {$familiesPath}" . PHP_EOL;
    echo "[ OK ] Manifest gerado em {$manifestPath}" . PHP_EOL;
    echo PHP_EOL;
    echo "Municípios processados: {$processed}" . PHP_EOL;
    echo "Famílias encontradas : " . count($families) . PHP_EOL;
    echo PHP_EOL;
}

function applyMunicipioOverride(string $ibge, array $municipio): array
{
    if (!isset(MUNICIPIO_OVERRIDES[$ibge])) {
        return $municipio;
    }

    return [
        ...$municipio,
        ...MUNICIPIO_OVERRIDES[$ibge],
    ];
}

/**
 * @return array<int,string>
 */
function buildMunicipioAliases(array $municipio): array
{
    $slug = (string) ($municipio['slug'] ?? '');
    $nome = (string) ($municipio['nome'] ?? '');
    $uf = strtolower((string) ($municipio['uf'] ?? ''));
    $ibge = (string) ($municipio['ibge'] ?? '');

    $aliases = array_filter([
        $slug,
        $slug !== '' && $uf !== '' ? "{$slug}-{$uf}" : '',
        $slug !== '' && $uf !== '' ? "{$slug}/{$uf}" : '',
        $slug !== '' && $uf !== '' ? "{$slug} {$uf}" : '',
        $ibge,
    ]);

    return array_values(array_unique($aliases));
}

/**
 * @param array<int, string> $argv
 * @return array{
 *   dry-run: bool,
 *   force: bool,
 *   all: bool,
 *   ibges: array<int, string>
 * }
 */
function parseOptions(array $argv): array
{
    $dryRun = false;
    $force = false;
    $all = false;
    $ibges = DEFAULT_IBGES;

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

        if (str_starts_with($arg, '--ibges=')) {
            $raw = trim(substr($arg, strlen('--ibges=')));
            if ($raw !== '') {
                $ibges = array_values(array_filter(array_map(
                    static fn (string $value): string => trim($value),
                    explode(',', $raw)
                )));
            }
        }
    }

    if (!$all && $ibges === []) {
        fail('Nenhum IBGE informado em --ibges.');
    }

    return [
        'dry-run' => $dryRun,
        'force' => $force,
        'all' => $all,
        'ibges' => $ibges,
    ];
}

function buildProviderFamilyConfig(string $providerFamily): array
{
    $providerClass = PROVIDER_CLASS_MAP[$providerFamily] ?? buildFallbackProviderClass($providerFamily);

    $base = DEFAULT_PROVIDER_METADATA[$providerFamily] ?? [
        'layout_family' => $providerFamily,
        'schema_root' => "resources/nfse/schemas/{$providerFamily}",
        'xsd_entrypoints' => new \stdClass(),
        'transport' => 'soap',
    ];

    return [
        'provider_class' => $providerClass,
        ...$base,
    ];
}

function buildFallbackProviderClass(string $providerFamily): string
{
    $normalized = preg_replace('/[^a-zA-Z0-9]+/', ' ', $providerFamily) ?? $providerFamily;
    $normalized = str_replace(' ', '', ucwords(strtolower($normalized)));

    return 'sabbajohn\\FiscalCore\\Providers\\NFSe\\Municipal\\' . $normalized . 'Provider';
}

function normalizeMunicipioNome(string $nomeCompleto): string
{
    $nomeCompleto = trim($nomeCompleto);

    if (str_contains($nomeCompleto, '-')) {
        [$nome] = explode('-', $nomeCompleto, 2);
        return trim($nome);
    }

    return $nomeCompleto;
}

function slugifyMunicipio(string $nome, string $fallback): string
{
    if (function_exists('transliterator_transliterate')) {
        $nome = transliterator_transliterate('Any-Latin; Latin-ASCII;', $nome) ?: $nome;
    } else {
        $iconv = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nome);
        if (is_string($iconv) && $iconv !== '') {
            $nome = $iconv;
        }
    }

    $value = strtolower($nome);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    $value = trim($value, '-');

    return $value !== '' ? $value : strtolower($fallback);
}

function ensureDirectory(string $path): void
{
    if (is_dir($path)) {
        return;
    }

    if (!mkdir($path, 0777, true) && !is_dir($path)) {
        fail("Falha ao criar diretório: {$path}");
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
