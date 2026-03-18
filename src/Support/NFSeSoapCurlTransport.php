<?php

declare(strict_types=1);

namespace freeline\FiscalCore\Support;

use RuntimeException;

final class NFSeSoapCurlTransport implements NFSeSoapTransportInterface
{
    public function send(string $endpoint, string $envelope, array $options = []): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('Extensão cURL é obrigatória para transporte SOAP municipal.');
        }

        $headers = array_merge(
            [
                'Content-Type: text/xml; charset=utf-8',
                'SOAPAction: "' . ($options['soap_action'] ?? '') . '"',
                'Content-Length: ' . strlen($envelope),
            ],
            $options['headers'] ?? []
        );

        $timeout = max(1, (int) ($options['timeout'] ?? 30));

        $handle = curl_init($endpoint);
        if ($handle === false) {
            throw new RuntimeException("Falha ao inicializar cURL para '{$endpoint}'.");
        }

        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $envelope,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        $response = curl_exec($handle);
        $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if ($response === false) {
            throw new RuntimeException(
                $error !== '' ? "Falha no transporte SOAP municipal: {$error}" : 'Falha desconhecida no transporte SOAP municipal.'
            );
        }

        return [
            'request_xml' => $envelope,
            'response_xml' => (string) $response,
            'status_code' => $statusCode,
            'headers' => $headers,
        ];
    }
}
