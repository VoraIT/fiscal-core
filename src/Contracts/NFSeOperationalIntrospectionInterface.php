<?php

namespace sabbajohn\FiscalCore\Contracts;

/**
 * Capacidades opcionais para providers que expõem telemetria e artefatos
 * da última operação executada.
 */
interface NFSeOperationalIntrospectionInterface
{
    public function getLastResponseData(): array;

    public function getLastOperationArtifacts(): array;

    public function getSupportedOperations(): array;
}
