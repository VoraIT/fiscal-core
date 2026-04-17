# PUBLICA

## Identificação

- Provider: `PublicaProvider`
- Transporte: SOAP RPC/literal
- Layout: PUBLICA 3.00
- Município atual: Joinville/SC

## Requisitos

- Prestador com CNPJ, IM e configuração de assinatura válida
- Código do município e alíquota coerentes no serviço
- WSDL principal e WSDL de consultas por ambiente

## Operações

- emitir
- consultar lote
- consultar por RPS
- cancelar

## Overrides conhecidos

- Joinville usa `payload_defaults` para descrição/discriminação padrão de homologação

## Limitações

- novos municípios da família devem preferir override leve no catálogo antes de subclass
