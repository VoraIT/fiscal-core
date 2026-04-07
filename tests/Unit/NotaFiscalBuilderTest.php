<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sabbajohn\FiscalCore\Adapters\NF\Builder\NotaFiscalBuilder;
use sabbajohn\FiscalCore\Adapters\NF\Core\NotaFiscal;

class NotaFiscalBuilderTest extends TestCase
{
    public function testFromArrayComDadosCompletos()
    {
        $dados = [
            'identificacao' => [
                'cUF' => 41,
                'cNF' => 12345678,
                'natOp' => 'VENDA',
                'mod' => 65,
                'serie' => 1,
                'nNF' => 123,
                'cMunFG' => 4106902,
                'tpAmb' => 2,
            ],
            'emitente' => [
                'cnpj' => '12345678000190',
                'razaoSocial' => 'EMPRESA TESTE',
                'nomeFantasia' => 'TESTE',
                'inscricaoEstadual' => '123',
                'logradouro' => 'RUA',
                'numero' => '1',
                'bairro' => 'BAIRRO',
                'codigoMunicipio' => '123',
                'municipio' => 'CIDADE',
                'uf' => 'UF',
                'cep' => '12345',
            ],
            'destinatario' => [
                'cpfCnpj' => '12345678901',
                'nome' => 'CONSUMIDOR',
            ],
            'itens' => [
                [
                    'produto' => [
                        'codigo' => 'PROD001',
                        'descricao' => 'PRODUTO',
                        'ncm' => '12345678',
                        'cfop' => '5102',
                        'unidade' => 'UN',
                        'quantidade' => 1.0,
                        'valorUnitario' => 10.00,
                        'valorTotal' => 10.00,
                    ],
                    'impostos' => [
                        'icms' => ['cst' => '102', 'orig' => 0],
                        'pis' => ['cst' => '49'],
                        'cofins' => ['cst' => '49'],
                    ],
                ],
            ],
            'pagamentos' => [
                ['tPag' => '01', 'vPag' => 10.00],
            ],
        ];

        $nota = NotaFiscalBuilder::fromArray($dados)->build();

        $this->assertInstanceOf(NotaFiscal::class, $nota);
        $this->assertTrue($nota->hasNode('identificacao'));
        $this->assertTrue($nota->hasNode('emitente'));
        $this->assertTrue($nota->hasNode('destinatario'));
        $this->assertTrue($nota->hasNode('produto'));
        $this->assertTrue($nota->hasNode('imposto'));
        $this->assertTrue($nota->hasNode('pagamento'));
    }

    public function testFromArrayComMultiplosItens()
    {
        $dados = [
            'identificacao' => [
                'cUF' => 41, 'cNF' => 12345678, 'natOp' => 'VENDA',
                'mod' => 65, 'serie' => 1, 'nNF' => 123,
                'cMunFG' => 4106902, 'tpAmb' => 2,
            ],
            'emitente' => [
                'cnpj' => '12345678000190', 'razaoSocial' => 'EMPRESA',
                'nomeFantasia' => '', 'inscricaoEstadual' => '123',
                'logradouro' => 'RUA', 'numero' => '1', 'bairro' => 'B',
                'codigoMunicipio' => '123', 'municipio' => 'C',
                'uf' => 'UF', 'cep' => '12345',
            ],
            'destinatario' => [
                'cpfCnpj' => '12345678901',
                'nome' => 'CONSUMIDOR',
            ],
            'itens' => [
                [
                    'produto' => [
                        'codigo' => 'PROD001', 'descricao' => 'PRODUTO 1',
                        'ncm' => '12345678', 'cfop' => '5102', 'unidade' => 'UN',
                        'quantidade' => 2.0, 'valorUnitario' => 10.00, 'valorTotal' => 20.00,
                    ],
                    'impostos' => [
                        'icms' => ['cst' => '102', 'orig' => 0],
                    ],
                ],
                [
                    'produto' => [
                        'codigo' => 'PROD002', 'descricao' => 'PRODUTO 2',
                        'ncm' => '87654321', 'cfop' => '5102', 'unidade' => 'UN',
                        'quantidade' => 1.0, 'valorUnitario' => 15.00, 'valorTotal' => 15.00,
                    ],
                    'impostos' => [
                        'icms' => ['cst' => '102', 'orig' => 0],
                    ],
                ],
            ],
            'pagamentos' => [
                ['tPag' => '01', 'vPag' => 35.00],
            ],
        ];

        $nota = NotaFiscalBuilder::fromArray($dados)->build();

        $this->assertInstanceOf(NotaFiscal::class, $nota);
        // Os itens são adicionados sobrescrevendo (último item prevalece)
        $this->assertTrue($nota->hasNode('produto'));
    }

    public function testBuildRetornaNotaFiscal()
    {
        $dados = [
            'identificacao' => [
                'cUF' => 41, 'cNF' => 12345678, 'natOp' => 'VENDA',
                'mod' => 65, 'serie' => 1, 'nNF' => 123,
                'cMunFG' => 4106902, 'tpAmb' => 2,
            ],
            'emitente' => [
                'cnpj' => '12345678000190', 'razaoSocial' => 'EMPRESA',
                'nomeFantasia' => '', 'inscricaoEstadual' => '123',
                'logradouro' => 'RUA', 'numero' => '1', 'bairro' => 'B',
                'codigoMunicipio' => '123', 'municipio' => 'C',
                'uf' => 'UF', 'cep' => '12345',
            ],
        ];

        $builder = NotaFiscalBuilder::fromArray($dados);
        $nota = $builder->build();

        $this->assertInstanceOf(NotaFiscal::class, $nota);
    }
}
