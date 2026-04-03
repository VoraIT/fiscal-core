<?php

declare(strict_types=1);

final class NFSePilotPayloads
{
    public static function all(): array
    {
        return [
            'belem' => self::belem(),
            'manaus' => self::manaus(),
            'joinville' => self::joinville(),
        ];
    }

    public static function belem(): array
    {
        return [
            'id' => 'RPS-BELEM-1',
            'lote' => [
                'id' => 'LOTE-BELEM-1',
                'numero' => '1',
            ],
            'rps' => [
                'numero' => '1',
                'data_emissao' => '2026-03-18T10:00:00-03:00',
            ],
            'prestador' => [
                'cnpj' => '12345678000195',
                'inscricaoMunicipal' => '4007197',
                'razao_social' => 'Freeline Belem Servicos Ltda',
                'regime_tributario' => 'simples nacional',
                'mei' => false,
                'simples_nacional' => false,
            ],
            'tomador' => [
                'documento' => '98765432000199',
                'razao_social' => 'Cliente Belem Ltda',
                'email' => 'cliente.belem@example.com',
                'endereco' => [
                    'tipo_logradouro' => 'RUA',
                    'logradouro' => 'Rua das Mangueiras',
                    'numero' => '100',
                    'complemento' => 'Sala 2',
                    'tipo_bairro' => 'BAIRRO',
                    'bairro' => 'Nazare',
                    'cidade' => 'Belem',
                    'codigo_municipio' => '1501402',
                    'cep' => '66000000',
                ],
            ],
            'servico' => [
                'codigo' => '0107',
                'item_lista_servico' => '0107',
                'codigo_atividade' => '620910000',
                'codigo_cnae' => '620910000',
                'descricao' => 'Desenvolvimento de software sob encomenda',
                'discriminacao' => 'Desenvolvimento de software sob encomenda',
                'codigo_municipio' => '1501402',
                'cidade_descricao' => 'Belem',
                'aliquota' => 0.02,
            ],
            'valor_servicos' => 100.00,
        ];
    }

    public static function manaus(): array
    {
        return [
            'id' => 'DPS130260312345678000195000010000000000001',
            'tpAmb' => '2',
            'dhEmi' => '2026-03-18T10:00:00-04:00',
            'verAplic' => 'fiscal-core',
            'serie' => '1',
            'nDPS' => '1',
            'dCompet' => '2026-03-18',
            'tpEmit' => '1',
            'cLocEmi' => '1302603',
            'prestador' => [
                'cnpj' => '12345678000195',
                'inscricaoMunicipal' => '123456',
                'razao_social' => 'Freeline Manaus Servicos Ltda',
                'opSimpNac' => '1',
                'regEspTrib' => '0',
            ],
            'tomador' => [
                'documento' => '12345678901',
                'razaoSocial' => 'Cliente Manaus',
            ],
            'servico' => [
                'codigo' => '010101',
                'cTribNac' => '010101',
                'descricao' => 'Consultoria tecnica em sistemas',
                'cLocPrestacao' => '1302603',
                'codigo_municipio' => '1302603',
                'tribISSQN' => '1',
                'tpRetISSQN' => '1',
                'aliquota' => 0.02,
            ],
            'valor_servicos' => 150.00,
        ];
    }

    public static function joinville(): array
    {
        return [
            'id' => 'RPS-JOINVILLE-1',
            'rps' => [
                'numero' => '1',
                'serie' => 'NF',
                'tipo' => '1',
                'data_emissao' => '2026-03-18T10:00:00-03:00',
            ],
            'competencia' => '2026-03',
            'prestador' => [
                'cnpj' => '12345678000195',
                'inscricaoMunicipal' => '123456',
                'razao_social' => 'Freeline Joinville Servicos Ltda',
                'simples_nacional' => false,
            ],
            'tomador' => [
                'documento' => '98765432000199',
                'razao_social' => 'Cliente Joinville Ltda',
            ],
            'servico' => [
                'codigo' => '1401',
                'descricao' => 'Licenciamento de software',
                'codigo_municipio' => '4209102',
                'aliquota' => 0.02,
            ],
            'valor_servicos' => 200.00,
        ];
    }
}
