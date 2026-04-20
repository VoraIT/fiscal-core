<?php

declare(strict_types=1);

namespace sabbajohn\FiscalCore\Contracts;

interface NFSeImpressaoResultInterface
{
    public function getImpressao(): array;

    public function getProvider(): array;

    public function getRaw(): array;

    public function toArray(): array;
}
