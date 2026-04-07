<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sabbajohn\FiscalCore\Adapters\NF\Core\NotaFiscal;
use sabbajohn\FiscalCore\Adapters\NF\DTO\{IdentificacaoDTO, EmitenteDTO, DestinatarioDTO};
use sabbajohn\FiscalCore\Adapters\NF\Nodes\{IdentificacaoNode, EmitenteNode, DestinatarioNode};

class NotaFiscalTest extends TestCase
{
    public function testAddNode()
    {
        $nota = new NotaFiscal();
        $dto = IdentificacaoDTO::forNFCe(41, 'VENDA', 123, 4106902);
        $node = new IdentificacaoNode($dto);

        $nota->addNode($node);

        $this->assertTrue($nota->hasNode('identificacao'));
    }

    public function testHasNode()
    {
        $nota = new NotaFiscal();
        
        $this->assertFalse($nota->hasNode('identificacao'));
        
        $dto = IdentificacaoDTO::forNFCe(41, 'VENDA', 123, 4106902);
        $nota->addNode(new IdentificacaoNode($dto));
        
        $this->assertTrue($nota->hasNode('identificacao'));
    }

    public function testGetNodes()
    {
        $nota = new NotaFiscal();
        $dto = IdentificacaoDTO::forNFCe(41, 'VENDA', 123, 4106902);
        $nota->addNode(new IdentificacaoNode($dto));

        $nodes = $nota->getNodes();

        $this->assertIsArray($nodes);
        $this->assertCount(1, $nodes);
        $this->assertArrayHasKey('identificacao', $nodes);
    }

    public function testValidateIdentificacaoObrigatoria()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Identificação é obrigatória');

        $nota = new NotaFiscal();
        $nota->validate();
    }

    public function testValidateEmitenteObrigatorio()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Emitente é obrigatório');

        $nota = new NotaFiscal();
        $dto = IdentificacaoDTO::forNFCe(41, 'VENDA', 123, 4106902);
        $nota->addNode(new IdentificacaoNode($dto));
        $nota->validate();
    }

    public function testValidateComDadosMinimos()
    {
        $nota = new NotaFiscal();

        // Identificação
        $id = IdentificacaoDTO::forNFCe(41, 'VENDA', 123, 4106902);
        $nota->addNode(new IdentificacaoNode($id));

        // Emitente
        $emit = new EmitenteDTO(
            '12345678000190', 'EMPRESA', '', '123',
            'RUA', '1', 'BAIRRO', '123', 'CIDADE', 'UF', '12345'
        );
        $nota->addNode(new EmitenteNode($emit));

        $this->assertTrue($nota->validate());
    }

    public function testAddNodeFluente()
    {
        $nota = new NotaFiscal();

        $id = IdentificacaoDTO::forNFCe(41, 'VENDA', 123, 4106902);
        $emit = new EmitenteDTO(
            '12345678000190', 'EMPRESA', '', '123',
            'RUA', '1', 'BAIRRO', '123', 'CIDADE', 'UF', '12345'
        );

        $resultado = $nota
            ->addNode(new IdentificacaoNode($id))
            ->addNode(new EmitenteNode($emit));

        $this->assertInstanceOf(NotaFiscal::class, $resultado);
        $this->assertCount(2, $nota->getNodes());
    }
}
