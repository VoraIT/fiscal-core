<?php

namespace Tests\Unit\NFSe;

use sabbajohn\FiscalCore\Services\NFSe\NacionalCatalogService;
use sabbajohn\FiscalCore\Support\Cache\FileCacheStore;
use PHPUnit\Framework\TestCase;

class NacionalCatalogServiceTest extends TestCase
{
    public function test_lista_municipios_com_cache_hit_e_miss(): void
    {
        $cacheDir = sys_get_temp_dir() . '/fiscal-core-test-cache-' . uniqid();
        $calls = 0;

        $service = new NacionalCatalogService(
            'https://api.local',
            30,
            new FileCacheStore($cacheDir),
            86400,
            function (string $path) use (&$calls) {
                $calls++;
                $this->assertSame('/catalogos/municipios', $path);

                return [
                    'data' => [
                        ['codigo_municipio' => '4106902', 'nome' => 'Curitiba'],
                    ],
                ];
            }
        );

        $first = $service->listarMunicipios();
        $second = $service->listarMunicipios();

        $this->assertSame(1, $calls);
        $this->assertSame('remote', $first['metadata']['source']);
        $this->assertSame('cache', $second['metadata']['source']);
        $this->assertFalse($second['metadata']['stale']);
        $this->assertSame('4106902', $second['data'][0]['codigo_municipio']);
    }

    public function test_retorna_cache_stale_quando_falha_remota(): void
    {
        $cacheDir = sys_get_temp_dir() . '/fiscal-core-test-cache-' . uniqid();

        $seedService = new NacionalCatalogService(
            'https://api.local',
            30,
            new FileCacheStore($cacheDir),
            1,
            fn (string $path) => ['data' => [['codigo_municipio' => '3550308']]]
        );
        $seedService->listarMunicipios(true);
        sleep(2);

        $failingService = new NacionalCatalogService(
            'https://api.local',
            30,
            new FileCacheStore($cacheDir),
            1,
            function (string $path) {
                throw new \RuntimeException('API indisponível');
            }
        );

        $result = $failingService->listarMunicipios();

        $this->assertSame('cache', $result['metadata']['source']);
        $this->assertTrue($result['metadata']['stale']);
        $this->assertSame('3550308', $result['data'][0]['codigo_municipio']);
    }
}
