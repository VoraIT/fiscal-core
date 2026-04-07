<?php

declare(strict_types=1);

namespace sabbajohn\FiscalCore\Support;

use InvalidArgumentException;
use NFePHP\Common\Certificate;

final class NFSeMunicipalPayloadFactory
{
    public function demo(string $municipio): array
    {
        return match ($this->normalizeMunicipio($municipio)) {
            'belem' => [
                'id' => 'RPS-BELEM-2026-1',
                'lote' => [
                    'id' => 'LOTE-BELEM-2026-1',
                    'numero' => '1001',
                ],
                'rps' => [
                    'id' => 'RPS-BELEM-2026-1-RAW',
                    'numero' => '1001',
                    'serie' => 'RPS',
                    'tipo' => '1',
                    'data_emissao' => '2026-03-18',
                    'status' => '1',
                ],
                'competencia' => '2026-03-18',
                'prestador' => [
                    'cnpj' => '12345678000195',
                    'inscricaoMunicipal' => '4007197',
                    'razao_social' => 'Freeline Tecnologia Ltda',
                    'simples_nacional' => true,
                    'regime_tributario' => 'simples nacional',
                    'mei' => false,
                    'incentivo_fiscal' => false,
                ],
                'tomador' => [
                    'documento' => '98765432000199',
                    'razao_social' => 'Cliente de Belem Ltda',
                    'email' => 'financeiro@example.com',
                    'telefone' => '(91) 99999-0000',
                    'endereco' => [
                        'logradouro' => 'Rua das Mangueiras',
                        'numero' => '100',
                        'complemento' => 'Sala 2',
                        'bairro' => 'Nazare',
                        'codigo_municipio' => '1501402',
                        'uf' => 'PA',
                        'cep' => '66000000',
                    ],
                ],
                'servico' => [
                    'codigo' => '0107',
                    'item_lista_servico' => '0107',
                    'codigo_cnae' => '620910000',
                    'descricao' => 'Servicos de tecnologia da informacao prestados em Belem.',
                    'discriminacao' => 'Servicos de tecnologia da informacao prestados em Belem.',
                    'codigo_municipio' => '1501402',
                    'aliquota' => 0.02,
                    'iss_retido' => false,
                    'exigibilidade_iss' => '1',
                ],
                'valor_servicos' => 3000.00,
            ],
            'joinville' => [
                'id' => 'JOINVILLE-RPS-2026-1',
                'rps' => [
                    'numero' => '1001',
                    'serie' => 'A1',
                    'tipo' => '1',
                    'data_emissao' => '2026-03-19 09:15:00',
                    'status' => '1',
                ],
                'competencia' => '2026-03',
                'prestador' => [
                    'cnpj' => '12345678000195',
                    'inscricaoMunicipal' => '123456',
                    'razao_social' => 'Freeline Joinville Servicos Ltda',
                    'simples_nacional' => true,
                    'incentivador_cultural' => false,
                ],
                'tomador' => [
                    'documento' => '98765432000199',
                    'razao_social' => 'Cliente Joinville Ltda',
                    'email' => 'financeiro.joinville@example.com',
                    'telefone' => '(47) 99999-1234',
                    'endereco' => [
                        'logradouro' => 'Rua do Principe',
                        'numero' => '100',
                        'complemento' => 'Sala 401',
                        'bairro' => 'Centro',
                        'codigo_municipio' => '4209102',
                        'uf' => 'SC',
                        'cep' => '89201001',
                        'municipio' => 'Joinville',
                    ],
                ],
                'servico' => [
                    'codigo' => '11.01',
                    'item_lista_servico' => '11.01',
                    'descricao' => 'Desenvolvimento e licenciamento de software.',
                    'discriminacao' => 'Desenvolvimento e licenciamento de software.',
                    'codigo_municipio' => '4209102',
                    'natureza_operacao' => '16',
                    'aliquota' => 0.02,
                    'iss_retido' => false,
                ],
                'valor_servicos' => 1500.00,
            ],
            default => throw new InvalidArgumentException("Município '{$municipio}' não suportado para payload demo."),
        };
    }

    public function buildPrestador(string $municipio, Certificate $certificate, array $empresaConfig, array $options = []): array
    {
        $meta = $this->providerMeta($municipio);
        $cnpj = $this->normalizeDigits((string) ($empresaConfig['cnpj'] ?? $certificate->getCnpj() ?? ''));
        $razaoSocial = trim((string) ($empresaConfig['razao_social'] ?? $certificate->getCompanyName() ?? ''));
        $inscricaoMunicipal = trim((string) ($empresaConfig['inscricao_municipal'] ?? ''));
        $simples = $this->toBool($options['simples_nacional'] ?? true);

        if ($cnpj === '') {
            throw new InvalidArgumentException('Não foi possível determinar o CNPJ do prestador a partir do certificado/.env.');
        }

        if ($inscricaoMunicipal === '') {
            throw new InvalidArgumentException('FISCAL_IM é obrigatório para emissão NFSe municipal.');
        }

        if ($razaoSocial === '') {
            throw new InvalidArgumentException('FISCAL_RAZAO_SOCIAL é obrigatório quando o certificado não informa a razão social.');
        }

        $base = [
            'cnpj' => $cnpj,
            'inscricaoMunicipal' => $inscricaoMunicipal,
            'razao_social' => $razaoSocial,
            'codigo_municipio' => $meta['codigo_municipio'],
        ];

        return match ($this->normalizeMunicipio($municipio)) {
            'belem' => $base + [
                'simples_nacional' => $simples,
                'regime_tributario' => $simples ? 'simples nacional' : 'normal',
                'mei' => false,
                'incentivo_fiscal' => false,
            ],
            'joinville' => $base + [
                'simples_nacional' => $simples,
                'incentivador_cultural' => false,
            ],
            default => throw new InvalidArgumentException("Município '{$municipio}' não suportado para prestador."),
        };
    }

    public function buildTomadorFromLookup(string $municipio, string $documento, array $lookup): array
    {
        $meta = $this->providerMeta($municipio);
        $endereco = is_array($lookup['endereco'] ?? null) ? $lookup['endereco'] : [];

        return [
            'documento' => $this->normalizeDigits($documento),
            'razao_social' => trim((string) ($lookup['razao_social'] ?? '')),
            'nome_fantasia' => trim((string) ($lookup['nome_fantasia'] ?? '')),
            'email' => trim((string) ($lookup['email'] ?? '')),
            'telefone' => trim((string) ($lookup['telefone'] ?? '')),
            'endereco' => [
                'logradouro' => trim((string) ($endereco['logradouro'] ?? '')),
                'numero' => trim((string) ($endereco['numero'] ?? 'S/N')),
                'complemento' => trim((string) ($endereco['complemento'] ?? '')),
                'bairro' => trim((string) ($endereco['bairro'] ?? '')),
                'codigo_municipio' => trim((string) ($endereco['codigo_municipio'] ?? $meta['codigo_municipio'])),
                'uf' => trim((string) ($endereco['uf'] ?? $meta['uf'])),
                'cep' => $this->normalizeDigits((string) ($endereco['cep'] ?? '')),
                'municipio' => trim((string) ($endereco['municipio'] ?? $meta['nome'])),
            ],
        ];
    }

    public function real(string $municipio, array $prestador, array $tomador, array $overrides = []): array
    {
        $meta = $this->providerMeta($municipio);
        $today = new \DateTimeImmutable('now');

        $base = match ($this->normalizeMunicipio($municipio)) {
            'belem' => [
                'id' => sprintf('RPS-BELEM-%s-1', $today->format('YmdHis')),
                'lote' => [
                    'id' => sprintf('LOTE-BELEM-%s', $today->format('YmdHis')),
                    'numero' => $today->format('His'),
                ],
                'rps' => [
                    'id' => sprintf('RPS-BELEM-%s-RAW', $today->format('YmdHis')),
                    'numero' => $today->format('His'),
                    'serie' => 'RPS',
                    'tipo' => '1',
                    'data_emissao' => $today->format('Y-m-d'),
                    'status' => '1',
                ],
                'competencia' => $today->format('Y-m-d'),
                'prestador' => $prestador,
                'tomador' => $tomador,
                'servico' => [
                    'codigo' => '0107',
                    'item_lista_servico' => '0107',
                    'codigo_cnae' => '620910000',
                    'descricao' => 'Servicos de tecnologia da informacao em homologacao.',
                    'discriminacao' => 'Servicos de tecnologia da informacao em homologacao.',
                    'codigo_municipio' => $meta['codigo_municipio'],
                    'aliquota' => 0.02,
                    'iss_retido' => false,
                    'exigibilidade_iss' => '1',
                ],
                'valor_servicos' => 3000.00,
            ],
            'joinville' => [
                'id' => sprintf('JOINVILLE-RPS-%s', $today->format('YmdHis')),
                'rps' => [
                    'numero' => $today->format('His'),
                    'serie' => 'A1',
                    'tipo' => '1',
                    'data_emissao' => $today->format('Y-m-d H:i:s'),
                    'status' => '1',
                ],
                'competencia' => $today->format('Y-m'),
                'prestador' => $prestador,
                'tomador' => $tomador,
                'servico' => [
                    'codigo' => '11.01',
                    'item_lista_servico' => '11.01',
                    'descricao' => 'Desenvolvimento e licenciamento de software em homologacao.',
                    'discriminacao' => 'Desenvolvimento e licenciamento de software em homologacao.',
                    'codigo_municipio' => $meta['codigo_municipio'],
                    'natureza_operacao' => '16',
                    'aliquota' => 0.02,
                    'iss_retido' => false,
                ],
                'valor_servicos' => 1500.00,
            ],
            default => throw new InvalidArgumentException("Município '{$municipio}' não suportado para payload real."),
        };

        return $this->mergeRecursiveDistinct($base, $overrides);
    }

    public function providerMeta(string $municipio): array
    {
        return match ($this->normalizeMunicipio($municipio)) {
            'belem' => [
                'slug' => 'belem',
                'nome' => 'Belem',
                'codigo_municipio' => '1501402',
                'uf' => 'PA',
                'provider_key' => 'BELEM_MUNICIPAL_2025',
            ],
            'joinville' => [
                'slug' => 'joinville',
                'nome' => 'Joinville',
                'codigo_municipio' => '4209102',
                'uf' => 'SC',
                'provider_key' => 'PUBLICA',
            ],
            default => throw new InvalidArgumentException("Município '{$municipio}' não suportado."),
        };
    }

    private function mergeRecursiveDistinct(array $base, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = $this->mergeRecursiveDistinct($base[$key], $value);
                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }

    private function normalizeMunicipio(string $municipio): string
    {
        return strtolower(trim($municipio));
    }

    private function normalizeDigits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on', 'sim'], true);
    }
}
