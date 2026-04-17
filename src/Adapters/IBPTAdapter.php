<?php

namespace sabbajohn\FiscalCore\Adapters;

use sabbajohn\FiscalCore\Contracts\TributacaoInterface;
use NFePHP\Ibpt\Ibpt;

class IBPTAdapter implements TributacaoInterface
{
    private Ibpt $client;
    private string $ufDefault;

    public function __construct(string $cnpj, string $token, string $ufDefault = 'SP')
    {
        $this->client = new Ibpt($cnpj, $token);
        $this->ufDefault = $ufDefault;
    }

    /**
     * Espera campos em $produto:
     * - uf (opcional, default construtor)
     * - ncm (string)
     * - extarif (int|0)
     * - descricao (string)
     * - unidade (string)
     * - valor (float|int)
     * - gtin (string|"")
     * - codigoInterno (string|"")
     */
    public function calcularImpostos(array $produto): array
    {
        $uf = $produto['uf'] ?? $this->ufDefault;
        $ncm = (string) ($produto['ncm'] ?? '');
        $ext = (int) ($produto['extarif'] ?? 0);
        $descricao = (string) ($produto['descricao'] ?? 'Produto');
        $unidade = (string) ($produto['unidade'] ?? 'UN');
        $valor = (float) ($produto['valor'] ?? 0.0);
        $gtin = (string) ($produto['gtin'] ?? '');
        $codigoInterno = (string) ($produto['codigoInterno'] ?? '');

        try {
            $resp = $this->client->productTaxes(
                $uf,
                $ncm,
                $ext,
                $descricao,
                $unidade,
                $valor,
                $gtin,
                $codigoInterno
            );
            return is_object($resp) ? get_object_vars($resp) : (array) $resp;
        } catch (\Throwable $e) {
            throw new \RuntimeException('Falha ao consultar IBPT (produto): ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Consulta de alíquota por NCM.
     * Como a API requer parâmetros adicionais, usamos defaults razoáveis.
     */
    public function consultarAliquota(string $ncm): array
    {
        try {
            $resp = $this->client->productTaxes(
                $this->ufDefault,
                $ncm,
                0,
                'CONSULTA',
                'UN',
                0.01,
                ''
            );
            return is_object($resp) ? get_object_vars($resp) : (array) $resp;
        } catch (\Throwable $e) {
            throw new \RuntimeException('Falha ao consultar IBPT por NCM: ' . $e->getMessage(), 0, $e);
        }
    }
}
