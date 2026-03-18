<?php

declare(strict_types=1);

namespace freeline\FiscalCore\Support;

interface NFSeSoapTransportInterface
{
    /**
     * @param array{
     *   soap_action?:string,
     *   timeout?:int,
     *   headers?:string[]
     * } $options
     * @return array{
     *   request_xml:string,
     *   response_xml:string,
     *   status_code:int,
     *   headers:string[]
     * }
     */
    public function send(string $endpoint, string $envelope, array $options = []): array;
}
