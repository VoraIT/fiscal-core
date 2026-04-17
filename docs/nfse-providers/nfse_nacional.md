# nfse_nacional

## Identificação

- Provider: `NacionalProvider`
- Transporte: REST
- Layout: NACIONAL
- Municípios atuais relevantes: nacional, manaus, emissões MEI

## Requisitos

- certificado válido e mTLS quando exigido
- configuração completa em `config/nfse/nfse-provider-families.json`
- payload DPS aderente ao layout nacional

## Operações

- emitir
- consultar
- cancelar
- consultar por RPS
- consultar lote
- baixar XML
- baixar DANFSe
- CNC e parametrização municipal

## Overrides conhecidos

- Manaus roteia para o nacional no catálogo ativo
- MEI sempre emite pelo nacional, independentemente do município de origem

## Limitações

- aceitação final depende da parametrização municipal e do `cTribNac`
