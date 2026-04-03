# NFSe Rio Preto da Eva - ISSWEB

Este guia cobre a preparacao do municipio de Rio Preto da Eva/AM no `fiscal-core` via familia municipal compartilhada `ISSWEB_AM`.

## Status

- municipio catalogado com `provider_family = ISSWEB_AM`
- provider concreto: `IsswebProvider`
- schemas locais usados:
  - `XSDNFEletronica.xsd`
  - `XSDISSEConsultaNota.xsd`
  - `XSDISSECancelaNFe.xsd`
  - `XSDRetorno.xsd`
- premissa atual: Rio Preto da Eva usa ISSWEB, mas isso ainda precisa de confirmacao oficial
- homologacao real ainda depende de credenciais e endpoints oficiais

## Variaveis obrigatorias

- `FISCAL_CNPJ`
- `FISCAL_RAZAO_SOCIAL`
- `FISCAL_IM`
- `FISCAL_CERT_PATH`
- `FISCAL_CERT_PASSWORD`
- `NFSE_ISSWEB_CHAVE`

`NFSE_ISSWEB_CHAVE` deve conter exatamente 48 caracteres.

## Payload minimo

- `prestador.cnpj`
- `prestador.inscricaoMunicipal`
- `tomador.documento`
- `tomador.razao_social` ou `tomador.razaoSocial`
- `tomador.endereco.codigo_municipio`
- `tomador.endereco.cep`
- `servico.codigo`
- `servico.descricao` ou `servico.discriminacao`
- `valor_servicos`

## Operacoes suportadas

- `emitir`
- `consultar`
- `cancelar`

Convencoes atuais do provider:

- `consultar(string $chave)` interpreta `chave` como numero da nota fiscal
- `cancelar(string $chave, string $motivo, ?string $protocolo)` interpreta:
  - `chave` como numero da nota fiscal
  - `protocolo` como chave de validacao ISSWEB no formato `9999-AAAAA`

## Impressao

O provider nao gera PDF local nesta etapa.

Quando `official_validation_url_template` estiver configurado e a resposta do ISSWEB retornar `numero` e `chave_validacao`, o parser expora `nfse_url` para consulta/validacao oficial.

## Pendencias operacionais

Antes de homologar de verdade, confirmar com a prefeitura ou fornecedor:

- se Rio Preto da Eva realmente usa ISSWEB
- endpoint de homologacao
- endpoint de producao
- host oficial do webservice
- operacao SOAP exata de emissao
- operacao SOAP exata de consulta
- operacao SOAP exata de cancelamento
- se a mesma `NFSE_ISSWEB_CHAVE` pode ser reutilizada entre municipios ou se a credencial e exclusiva
- se o certificado cliente TLS e obrigatorio no transporte
- se existe URL oficial deterministica para impressao/validacao

Se a confirmacao futura apontar ambiente nacional, o catalogo deste municipio deve migrar para `nfse_nacional`.

## Exemplo

Preview local:

```bash
php examples/homologacao/07-rio-preto-da-eva-issweb.php
```

Envio real:

```bash
php examples/homologacao/07-rio-preto-da-eva-issweb.php --send
```
