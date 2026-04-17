# Arquitetura - Padrão Composite + Builder

## Visão Geral

A arquitetura do módulo de Notas Fiscais (NFe/NFCe) utiliza o **Padrão Composite** para estruturar a nota como uma árvore de nós, e o **Padrão Builder** para construí-la a partir de dados externos (arrays, JSON, XML).

## Estrutura de Diretórios

```
src/Adapters/NF/
├── Core/                           # Núcleo do Composite Pattern
│   ├── NotaFiscal.php             # Composite Root - Agrega todos os nós
│   └── NotaNodeInterface.php      # Interface base para todos os nós
│
├── Builder/                        # Padrão Builder
│   └── NotaFiscalBuilder.php      # Constrói NotaFiscal a partir de dados externos
│
├── DTO/                            # Data Transfer Objects
│   ├── IdentificacaoDTO.php       # Dados de identificação da nota
│   ├── EmitenteDTO.php            # Dados do emitente
│   ├── DestinatarioDTO.php        # Dados do destinatário
│   ├── ProdutoDTO.php             # Dados do produto/item
│   ├── IcmsDTO.php                # Dados do ICMS
│   ├── PisDTO.php                 # Dados do PIS
│   ├── CofinsDTO.php              # Dados do COFINS
│   └── PagamentoDTO.php           # Dados de pagamento
│
├── Nodes/                          # Implementações de NotaNodeInterface
│   ├── IdentificacaoNode.php      # Nó de identificação
│   ├── EmitenteNode.php           # Nó do emitente
│   ├── DestinatarioNode.php       # Nó do destinatário
│   ├── ProdutoNode.php            # Nó de produto
│   ├── ImpostoNode.php            # Nó de impostos
│   └── PagamentoNode.php          # Nó de pagamento
│
└── XmlParser.php                   # Parser para importar XML
```

## Camadas da Arquitetura

### 1. DTOs (Data Transfer Objects)

**Responsabilidade:** Transportar dados entre camadas sem lógica de negócio.

```php
class EmitenteDTO {
    public function __construct(
        public string $cnpj,
        public string $razaoSocial,
        public string $inscricaoEstadual,
        // ... outros campos
    ) {}
}
```

**Características:**
- Imutáveis (readonly properties)
- Apenas validação de tipos
- Factory methods para casos comuns

### 2. Nodes (Composite Pattern)

**Responsabilidade:** Cada nó sabe se renderizar no formato NFePHP.

```php
interface NotaNodeInterface {
    public function getNodeType(): string;
    public function validate(): bool;
    public function addToMake(Make $make): void;
}

class EmitenteNode implements NotaNodeInterface {
    public function __construct(private EmitenteDTO $dto) {}

    public function addToMake(Make $make): void {
        $make->tagenderEmit(
            xLgr: $this->dto->logradouro,
            nro: $this->dto->numero,
            // ...
        );
    }
}
```

**Características:**
- Encapsulam um DTO
- Sabem validar seus dados
- Sabem se adicionar ao objeto Make do NFePHP
- Retornam tipo de nó para organização no Composite

### 3. NotaFiscal (Composite Root)

**Responsabilidade:** Agregar todos os nós e gerar o XML completo.

```php
class NotaFiscal {
    private array $nodes = [];

    public function addNode(NotaNodeInterface $node): void {
        $this->nodes[$node->getNodeType()] = $node;
    }

    public function validate(): bool {
        foreach ($this->nodes as $node) {
            $node->validate();
        }
        return true;
    }

    public function getMake(): Make {
        $make = new Make();
        foreach ($this->nodes as $node) {
            $node->addToMake($make);
        }
        return $make;
    }

    public function toXml(): string {
        return $this->getMake()->getXML();
    }
}
```

**Características:**
- Armazena nodes em um array associativo por tipo
- Valida a estrutura completa
- Gera o objeto Make do NFePHP
- Exporta para XML

### 4. Builder

**Responsabilidade:** Construir NotaFiscal a partir de dados externos.

```php
class NotaFiscalBuilder {
    private NotaFiscal $nota;

    public function __construct() {
        $this->nota = new NotaFiscal();
    }

    public static function fromArray(array $dados): self {
        $builder = new self();
        
        // Identificação
        if (isset($dados['identificacao'])) {
            $dto = new IdentificacaoDTO(...$dados['identificacao']);
            $builder->nota->addNode(new IdentificacaoNode($dto));
        }

        // Emitente
        if (isset($dados['emitente'])) {
            $dto = new EmitenteDTO(...$dados['emitente']);
            $builder->nota->addNode(new EmitenteNode($dto));
        }

        // ... outros nós

        return $builder;
    }

    public static function fromXml(string $xml): self {
        $parser = new XmlParser($xml);
        return self::fromArray($parser->toArray());
    }

    public function build(): NotaFiscal {
        return $this->nota;
    }
}
```

**Características:**
- Factory methods: `fromArray()`, `fromXml()`
- Métodos fluentes: `setIdentificacao()`, `setEmitente()`, etc.
- Traduz dados externos em DTOs e Nodes
- Retorna NotaFiscal pronto para uso

### 5. XmlParser

**Responsabilidade:** Parsear XML de NFe/NFCe e converter para array.

```php
class XmlParser {
    public function __construct(private string $xmlContent) {
        // Parse XML e remove namespaces
    }

    public function parseEmitente(): EmitenteDTO { }
    public function parseDestinatario(): ?DestinatarioDTO { }
    public function parseProdutos(): array { }
    public function parseImpostos(int $item): array { }
    
    public function toArray(): array {
        return [
            'identificacao' => $this->parseIdentificacao(),
            'emitente' => $this->parseEmitente(),
            'destinatario' => $this->parseDestinatario(),
            'itens' => $this->parseProdutos(),
            'pagamentos' => $this->parsePagamentos(),
        ];
    }
}
```

## Fluxo de Dados

### Criação Manual (Array → NotaFiscal)

```
Array de dados
    ↓
NotaFiscalBuilder::fromArray()
    ↓
DTOs criados
    ↓
Nodes criados com DTOs
    ↓
Nodes adicionados à NotaFiscal
    ↓
NotaFiscal.build()
```

### Importação XML (XML → NotaFiscal)

```
XML (string ou arquivo)
    ↓
NotaFiscalBuilder::fromXml()
    ↓
XmlParser extrai dados
    ↓
XmlParser.toArray()
    ↓
NotaFiscalBuilder::fromArray()
    ↓
NotaFiscal
```

### Exportação (NotaFiscal → XML)

```
NotaFiscal
    ↓
NotaFiscal.validate()
    ↓
NotaFiscal.getMake()
    ↓
Cada Node se adiciona ao Make
    ↓
Make.getXML()
    ↓
XML da NFe/NFCe
```

## Exemplos de Uso

### Criando NFCe do Zero

```php
use sabbajohn\FiscalCore\Adapters\NF\Builder\NotaFiscalBuilder;

$builder = NotaFiscalBuilder::fromArray([
    'identificacao' => [
        'modelo' => 65,
        'serie' => 1,
        'numeroNota' => 123,
        'naturezaOperacao' => 'VENDA',
        'cMunFG' => '4106902',
        // ...
    ],
    'emitente' => [
        'cnpj' => '12345678000190',
        'razaoSocial' => 'EMPRESA LTDA',
        'logradouro' => 'RUA TESTE',
        // ...
    ],
    'itens' => [
        [
            'produto' => [
                'codigo' => '001',
                'descricao' => 'PRODUTO TESTE',
                'unidade' => 'UN',
                'quantidade' => 1,
                'valorUnitario' => 100.00,
                'valorTotal' => 100.00,
                // ...
            ],
            'impostos' => [
                'icms' => ['cst' => '102', 'orig' => 0],
                'pis' => ['cst' => '07'],
                'cofins' => ['cst' => '07'],
            ],
        ],
    ],
    'pagamentos' => [
        ['tPag' => '01', 'vPag' => 100.00],
    ],
]);

$nota = $builder->build();
$xml = $nota->toXml();
```

### Importando de XML

```php
use sabbajohn\FiscalCore\Adapters\NF\Builder\NotaFiscalBuilder;

// De string
$builder = NotaFiscalBuilder::fromXml($xmlString);

// De arquivo
$builder = NotaFiscalBuilder::fromXml('/caminho/nfe.xml', true);

$nota = $builder->build();
$nota->validate();
```

## Vantagens da Arquitetura

1. **Separação de Responsabilidades**
   - DTOs: apenas dados
   - Nodes: lógica de renderização
   - Builder: tradução de formatos
   - NotaFiscal: coordenação

2. **Extensibilidade**
   - Novos nodes podem ser adicionados facilmente
   - Novos formatos de entrada (JSON, API, etc.) = novo método no Builder

3. **Testabilidade**
   - Cada componente pode ser testado isoladamente
   - Mocks fáceis com interfaces

4. **Compatibilidade NFePHP**
   - Nodes encapsulam toda integração com NFePHP
   - Mudanças na lib NFePHP ficam isoladas nos Nodes

5. **Bidirecoinal**
   - Array/XML → NotaFiscal (importação)
   - NotaFiscal → XML (exportação)

## Próximos Passos

- [ ] Suporte para NFe 3.10 (legado)
- [ ] Validação contra schema XSD
- [ ] Comparação de XMLs (diff)
- [ ] Processamento em lote
- [ ] Cache de parsing
- [ ] Eventos de validação
