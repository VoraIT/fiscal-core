<?php

declare(strict_types=1);

namespace sabbajohn\FiscalCore\Contracts;

interface NFSeConsultaResultInterface
{
    public function getConsulta(): array;

    public function getDocumento(): array;

    public function getImpressao(): array;

    public function getProvider(): array;

    public function getRaw(): array;

    public function toArray(): array;
}
