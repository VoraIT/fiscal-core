# ISSWEB_AM

## Identificação

- Provider: `IsswebProvider`
- Transporte: SOAP
- Layout: ISSWEB
- Municípios atuais: Presidente Figueiredo/AM, Rio Preto da Eva/AM

## Requisitos

- validar endpoints reais por município
- confirmar chave/autenticação do ISSWEB
- revisar requisitos de item de serviço e retorno oficial

## Operações

- emitir
- consultar
- cancelar

## Overrides conhecidos

- Presidente Figueiredo usa `official_validation_url_template`
- Presidente Figueiredo e Rio Preto da Eva usam `payload_defaults` diferentes de homologação

## Limitações

- família compartilhada, mas payload e URL oficial não são necessariamente idênticos
- evidências reais continuam obrigatórias por município
