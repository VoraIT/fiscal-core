<?php

declare(strict_types=1);

namespace sabbajohn\FiscalCore\Providers\NFSe\Municipal;

use BadMethodCallException;
use sabbajohn\FiscalCore\Contracts\NFSeOperationalIntrospectionInterface;
use sabbajohn\FiscalCore\Providers\NFSe\AbstractNFSeProvider;

final class {{PROVIDER_SHORT_CLASS}} extends AbstractNFSeProvider implements NFSeOperationalIntrospectionInterface
{
    private array $lastResponseData = [];
    private array $lastOperationArtifacts = [];

    public function emitir(array $dados): string
    {
        $this->validarDados($dados);

        $xml = $this->montarXmlRps($dados);
        $this->lastOperationArtifacts = [
            'request_xml' => $xml,
        ];
        $this->lastResponseData = [
            'status' => 'pending',
            'provider' => '{{FAMILY_KEY}}',
            'message' => 'Scaffold inicial gerado. Implementar transporte, assinatura e parser.',
        ];

        return $xml;
    }

    public function consultar(string $chave): string
    {
        throw new BadMethodCallException('{{PROVIDER_SHORT_CLASS}} ainda não implementa consultar.');
    }

    public function cancelar(string $chave, string $motivo, ?string $protocolo = null): bool
    {
        throw new BadMethodCallException('{{PROVIDER_SHORT_CLASS}} ainda não implementa cancelar.');
    }

    public function substituir(string $chave, array $dados): string
    {
        throw new BadMethodCallException('{{PROVIDER_SHORT_CLASS}} ainda não implementa substituir.');
    }

    protected function montarXmlRps(array $dados): string
    {
        return sprintf(
            '<{{PROVIDER_SLUG}} status="scaffold"><municipio>%s</municipio></{{PROVIDER_SLUG}}>',
            htmlspecialchars((string) ($this->config['municipio_nome'] ?? ''), ENT_XML1)
        );
    }

    protected function processarResposta(string $xmlResposta): array
    {
        return [
            'status' => 'pending',
            'raw_response' => $xmlResposta,
        ];
    }

    public function getLastResponseData(): array
    {
        return $this->lastResponseData;
    }

    public function getLastOperationArtifacts(): array
    {
        return $this->lastOperationArtifacts;
    }

    public function getSupportedOperations(): array
    {
        return ['emitir'];
    }
}
