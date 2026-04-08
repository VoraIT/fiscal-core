# Migração de Município NFSe Municipal para Nacional

Este guia define o fluxo canônico para migrar um município do provider municipal para `nfse_nacional` sem perder rastreabilidade histórica.

## Quando migrar

Migre quando o município:

- aderir formalmente ao ambiente nacional
- disponibilizar emissão e operações principais pelo fluxo nacional
- possuir janela de vigência definida para fatos geradores

## Passos obrigatórios

1. Atualize `config/nfse/providers-catalog.json` para apontar `provider_family = nfse_nacional`.
2. Sincronize `config/nfse/nfse-catalog-manifest.json` com a mesma decisão e a nota de vigência.
3. Preserve o provider municipal histórico fora da rota ativa.
4. Atualize exemplos e scripts de homologação para o fluxo nacional.
5. Valide emissão, consulta, cancelamento e download no provider nacional.
6. Documente a janela de vigência e qualquer limitação de retroativos.

## Regras de compatibilidade

- O resolver não deve fazer roteamento híbrido por data.
- A vigência deve ficar documentada, não embutida implicitamente no runtime.
- Consultas e cancelamentos seguem o provider efetivo da nota já emitida.
- MEI continua seguindo a política global: emissão sempre no nacional.

## Evidências mínimas

- request/response de emissão nacional
- consulta por chave ou RPS
- cancelamento ou limitação formalizada
- prova de DANFSe/XML final
- nota documental sobre o provider histórico mantido apenas para referência
