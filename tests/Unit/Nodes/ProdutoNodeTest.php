<?php

namespace Tests\Unit\Nodes;

use PHPUnit\Framework\TestCase;
use sabbajohn\FiscalCore\Adapters\NF\Nodes\ProdutoNode;
use sabbajohn\FiscalCore\Adapters\NF\DTO\ProdutoDTO;

class ProdutoNodeTest extends TestCase
{
    public function testGetNodeType()
    {
        $dto = ProdutoDTO::simple(1, 'PROD001', 'PRODUTO', '12345678', '5102', 1, 10.00);
        $node = new ProdutoNode($dto);

        $this->assertEquals('produto', $node->getNodeType());
    }

    public function testValidateComDadosValidos()
    {
        $dto = ProdutoDTO::simple(1, 'PROD001', 'PRODUTO', '12345678', '5102', 1, 10.00);
        $node = new ProdutoNode($dto);

        $this->assertTrue($node->validate());
    }

    public function testValidateCodigoVazio()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Código do produto é obrigatório');

        $dto = new ProdutoDTO(
            1, '', 'SEM GTIN', 'DESC', '12345678', '5102',
            'UN', 1, 10, 10, 'SEM GTIN', 'UN', 1, 10
        );
        $node = new ProdutoNode($dto);
        $node->validate();
    }

    public function testValidateDescricaoVazia()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Descrição do produto é obrigatória');

        $dto = new ProdutoDTO(
            1, 'PROD', 'SEM GTIN', '', '12345678', '5102',
            'UN', 1, 10, 10, 'SEM GTIN', 'UN', 1, 10
        );
        $node = new ProdutoNode($dto);
        $node->validate();
    }

    public function testValidateQuantidadeZero()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantidade deve ser maior que zero');

        $dto = new ProdutoDTO(
            1, 'PROD', 'SEM GTIN', 'DESC', '12345678', '5102',
            'UN', 0, 10, 10, 'SEM GTIN', 'UN', 1, 10
        );
        $node = new ProdutoNode($dto);
        $node->validate();
    }

    public function testValidateValorUnitarioZero()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Valor unitário deve ser maior que zero');

        $dto = new ProdutoDTO(
            1, 'PROD', 'SEM GTIN', 'DESC', '12345678', '5102',
            'UN', 1, 0, 10, 'SEM GTIN', 'UN', 1, 10
        );
        $node = new ProdutoNode($dto);
        $node->validate();
    }
}
