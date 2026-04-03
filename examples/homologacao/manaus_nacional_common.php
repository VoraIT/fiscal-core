<?php

declare(strict_types=1);

use freeline\FiscalCore\Facade\FiscalFacade;

function manausNacionalApplyEnvOverrides(string $projectRoot): void
{
    $envOverrides = array_merge(
        nfseMunicipalBuildEnvOverrides('manaus', 'homologacao', $projectRoot),
        [
            'FISCAL_CNPJ' => nfseMunicipalRequiredEnvValue('FISCAL_CNPJ'),
            'FISCAL_RAZAO_SOCIAL' => nfseMunicipalRequiredEnvValue('FISCAL_RAZAO_SOCIAL'),
            'FISCAL_UF' => nfseMunicipalEnvValue('FISCAL_UF') ?? 'AM',
        ]
    );

    nfseMunicipalApplyEnvOverrides($envOverrides);
}

function manausNacionalUsage(string $scriptName): string
{
    return <<<TXT
Uso:
  php {$scriptName} [--send] [--tomador-doc=12345678909] [--tomador-nome="TOMADOR TESTE"] [--valor=10.00]
                     [--competencia=2026-04-03] [--c-trib-nac=010101] [--aliquota=0.02]

Comportamento:
  --send            Envia de verdade para a API nacional em homologacao
  --tomador-doc     CPF ou CNPJ do tomador
  --tomador-nome    Razao social ou nome do tomador
  --valor           Valor do servico
  --competencia     Data no formato YYYY-MM-DD
  --c-trib-nac      Codigo tributario nacional com 6 digitos
  --aliquota        Aliquota ISS em formato decimal, ex.: 0.02

Sem --send, o script executa somente preview seguro do XML DPS.
TXT;
}

function manausNacionalOperationsUsage(string $scriptName): string
{
    return <<<TXT
Uso:
  php {$scriptName} --consultar-chave=CHAVE
  php {$scriptName} --consultar-rps-numero=1 [--consultar-rps-serie=1] [--consultar-rps-tipo=1]
  php {$scriptName} --consultar-lote=PROTOCOLO
  php {$scriptName} --baixar-xml=CHAVE
  php {$scriptName} --baixar-danfse=CHAVE
  php {$scriptName} --cancelar-chave=CHAVE --motivo="Motivo do cancelamento" [--protocolo=PROTOCOLO]
  php {$scriptName} --aliquotas [--c-trib-nac=010101] [--competencia=2026-04-03]
  php {$scriptName} --convenio

Todas as operacoes usam o municipio manaus no fluxo nacional.
TXT;
}

function manausNacionalParseOptions(array $argv): array
{
    $options = [
        'send' => false,
        'tomador_doc' => '12345678909',
        'tomador_nome' => 'TOMADOR DE TESTE MANAUS',
        'valor' => '10.00',
        'competencia' => date('Y-m-d'),
        'c_trib_nac' => '010101',
        'aliquota' => '0.02',
        'consultar_rps_serie' => '1',
        'consultar_rps_tipo' => '1',
        'motivo' => 'Cancelamento de teste em homologacao',
    ];

    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--send') {
            $options['send'] = true;
            continue;
        }

        if ($arg === '--help' || $arg === '-h') {
            $options['help'] = true;
            continue;
        }

        if ($arg === '--aliquotas') {
            $options['aliquotas'] = true;
            continue;
        }

        if ($arg === '--convenio') {
            $options['convenio'] = true;
            continue;
        }

        foreach ([
            '--tomador-doc=' => 'tomador_doc',
            '--tomador-nome=' => 'tomador_nome',
            '--valor=' => 'valor',
            '--competencia=' => 'competencia',
            '--c-trib-nac=' => 'c_trib_nac',
            '--aliquota=' => 'aliquota',
            '--consultar-chave=' => 'consultar_chave',
            '--consultar-rps-numero=' => 'consultar_rps_numero',
            '--consultar-rps-serie=' => 'consultar_rps_serie',
            '--consultar-rps-tipo=' => 'consultar_rps_tipo',
            '--consultar-lote=' => 'consultar_lote',
            '--baixar-xml=' => 'baixar_xml',
            '--baixar-danfse=' => 'baixar_danfse',
            '--cancelar-chave=' => 'cancelar_chave',
            '--motivo=' => 'motivo',
            '--protocolo=' => 'protocolo',
        ] as $prefix => $key) {
            if (str_starts_with($arg, $prefix)) {
                $options[$key] = substr($arg, strlen($prefix));
                continue 2;
            }
        }
    }

    return $options;
}

function manausNacionalBuildPayload(array $options): array
{
    $cnpj = preg_replace('/\D+/', '', nfseMunicipalRequiredEnvValue('FISCAL_CNPJ')) ?? '';
    $inscricaoMunicipal = nfseMunicipalRequiredEnvValue('FISCAL_IM');
    $razaoSocial = nfseMunicipalRequiredEnvValue('FISCAL_RAZAO_SOCIAL');
    $competencia = (string) ($options['competencia'] ?? date('Y-m-d'));
    $dhEmi = $competencia . 'T10:00:00-04:00';
    $serie = '1';
    $numero = date('His');
    $dpsId = sprintf('DPS1302603%s%s%s', $cnpj, str_pad($serie, 5, '0', STR_PAD_LEFT), str_pad($numero, 15, '0', STR_PAD_LEFT));

    return [
        'id' => $dpsId,
        'tpAmb' => '2',
        'dhEmi' => $dhEmi,
        'verAplic' => 'fiscal-core-examples',
        'serie' => $serie,
        'nDPS' => $numero,
        'dCompet' => $competencia,
        'tpEmit' => '1',
        'cLocEmi' => '1302603',
        'prestador' => [
            'cnpj' => $cnpj,
            'inscricaoMunicipal' => $inscricaoMunicipal,
            'razaoSocial' => $razaoSocial,
            'opSimpNac' => '1',
            'regEspTrib' => '0',
        ],
        'tomador' => [
            'documento' => (string) ($options['tomador_doc'] ?? '12345678909'),
            'razaoSocial' => (string) ($options['tomador_nome'] ?? 'TOMADOR DE TESTE MANAUS'),
        ],
        'servico' => [
            'codigo' => (string) ($options['c_trib_nac'] ?? '010101'),
            'cTribNac' => (string) ($options['c_trib_nac'] ?? '010101'),
            'descricao' => 'Servico de homologacao NFSe nacional para Manaus.',
            'cLocPrestacao' => '1302603',
            'codigo_municipio' => '1302603',
            'tribISSQN' => '1',
            'tpRetISSQN' => '1',
            'aliquota' => (float) ($options['aliquota'] ?? '0.02'),
        ],
        'valor_servicos' => (float) ($options['valor'] ?? '10.00'),
    ];
}

function manausNacionalFacade(): \freeline\FiscalCore\Facade\NFSeFacade
{
    $fiscal = new FiscalFacade();
    return $fiscal->nfse('manaus');
}
