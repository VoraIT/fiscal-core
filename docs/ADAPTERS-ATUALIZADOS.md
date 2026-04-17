# Atualização dos Adapters NFe/NFCe

## ✅ Implementação Concluída

### Arquivos Atualizados

1. **src/Adapters/NFeAdapter.php**
   - Integrado com NotaFiscalBuilder
   - Método `emitir()` usa Composite + Builder
   - Método `criarNota()` para criação sem envio
   - Método estático `builder()` para construção fluente

2. **src/Adapters/NFCeAdapter.php**
   - Integrado com NotaFiscalBuilder
   - Garante modelo 65 (NFCe)
   - Suporte a múltiplas formas de pagamento
   - Validação automática antes do envio

### Novos Métodos nos Adapters

```php
// Emitir NFe/NFCe via array (com validação automática)
$adapter->emitir($dadosArray);

// Criar nota sem emitir (para testes/manipulação)
$nota = $adapter->criarNota($dadosArray);

// Builder fluente (método estático)
$builder = NFeAdapter::builder();
```

### Fluxo de Emissão

1. **Array de dados** → Builder
2. **Builder** → NotaFiscal (Composite)
3. **NotaFiscal** → Validação automática
4. **NotaFiscal** → NFePHP Make
5. **Make** → XML
6. **XML** → Assinatura
7. **XML Assinado** → SEFAZ

### Vantagens

✅ **Type-safe**: Validação em tempo de compilação  
✅ **Validação automática**: Antes do envio  
✅ **Testável**: Criar notas sem enviar  
✅ **Flexível**: Array ou construção manual  
✅ **Backward compatible**: Mantém interface original  

### Exemplo de Uso

```php
use sabbajohn\FiscalCore\Support\ConfigManager;
use sabbajohn\FiscalCore\Adapters\NFCeAdapter;
use NFePHP\NFe\Tools;

// 1. Configurar
$config = ConfigManager::getInstance();
$certManager = $config->getCertificateManager();
$tools = new Tools($configJson, $certManager->getCertificateObject());

// 2. Criar adapter
$adapter = new NFCeAdapter($tools);

// 3. Preparar dados
$dados = [
    'identificacao' => [...],
    'emitente' => [...],
    'destinatario' => [...],
    'itens' => [[...]],
    'pagamentos' => [[...]],
];

// 4. Emitir
try {
    $resposta = $adapter->emitir($dados);
    echo "NFCe emitida! Protocolo: $resposta";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
```

### Testes

Executar: `php scripts/exemplo-adapters-integrados.php`

**Resultados:**
- ✅ NFCe validada com 2 itens e 1 pagamento
- ✅ NFe validada com impostos completos (ICMS, PIS, COFINS)
- ✅ Todos os nodes criados corretamente
- ✅ Validações funcionando

### Próximos Passos

- [ ] Adicionar suporte a XML de retorno (parsing)
- [ ] Criar helper para cálculo de totais
- [ ] Adicionar TotalNode (tag `<total>`)
- [ ] Adicionar TransporteNode (tag `<transp>`)
- [ ] Testes unitários com PHPUnit
- [ ] Documentação de API completa
