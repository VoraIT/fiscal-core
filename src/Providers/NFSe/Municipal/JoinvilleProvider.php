<?php

declare(strict_types=1);

namespace sabbajohn\FiscalCore\Providers\NFSe\Municipal;

/**
 * Alias de compatibilidade para Joinville/SC.
 * O roteamento oficial do catálogo aponta para a família PUBLICA.
 */
class JoinvilleProvider extends PublicaProvider
{
    public function getCodigoMunicipio(): string
    {
        return '4209102';
    }
}
