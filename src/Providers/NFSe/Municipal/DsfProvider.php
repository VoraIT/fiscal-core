<?php

declare(strict_types=1);

namespace freeline\FiscalCore\Providers\NFSe\Municipal;

/**
 * Alias legado para a antiga família DSF.
 *
 * Belém migrou para o provider municipal atual, mas o alias é preservado
 * para compatibilidade interna com código que ainda referencia DsfProvider.
 */
final class DsfProvider extends BelemMunicipalProvider
{
}
