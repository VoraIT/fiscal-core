<?php

use PHPUnit\Framework\TestCase;
use sabbajohn\FiscalCore\NFSe\ProviderRegistry;
use sabbajohn\FiscalCore\NFSe\ProviderResolver;
use sabbajohn\FiscalCore\Contracts\NFSeProviderInterface;

class ProviderResolverTest extends TestCase
{
    public function test_resolves_registered_provider_by_key(): void
    {
        $registry = new ProviderRegistry();
        $registry->register('dummy', function (array $cfg): NFSeProviderInterface {
            return new class($cfg) implements NFSeProviderInterface {
                public function __construct(private array $cfg) {}
                public function emitir(array $dados): string { return 'ok'; }
                public function consultar(string $chave): string { return 'ok'; }
                public function cancelar(string $chave, string $motivo, ?string $protocolo = null): bool { return true; }
            };
        });

        $resolver = new ProviderResolver(['provider' => 'dummy'], $registry);
        $provider = $resolver->resolve();

        $this->assertInstanceOf(NFSeProviderInterface::class, $provider);
        $this->assertSame('ok', $provider->emitir([]));
        $this->assertSame('ok', $provider->consultar('x'));
        $this->assertTrue($provider->cancelar('x', 'y'));
    }
}
