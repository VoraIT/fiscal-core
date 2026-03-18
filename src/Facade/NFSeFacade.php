<?php

namespace freeline\FiscalCore\Facade;

use freeline\FiscalCore\Adapters\NF\NFSeAdapter;
use freeline\FiscalCore\Support\NFSeProviderResolver;
use freeline\FiscalCore\Support\ResponseHandler;
use freeline\FiscalCore\Support\FiscalResponse;
use freeline\FiscalCore\Support\ProviderRegistry;
use freeline\FiscalCore\Support\CertificateManager;

/**
 * Facade para NFSe - Interface simplificada e com tratamento de erros
 * Evita que aplicações recebam erros 500 fornecendo responses padronizados
 */
class NFSeFacade
{
    private ?NFSeAdapter $nfse = null;
    private ResponseHandler $responseHandler;
    private ?FiscalResponse $initializationError = null;
    private string $municipio;
    private string $providerKey;
    private bool $municipioIgnored = false;
    private array $deprecationWarnings = [];

    public function __construct(string $municipio = 'curitiba', ?NFSeAdapter $nfse = null)
    {
        $this->municipio = $municipio;
        $this->responseHandler = new ResponseHandler();
        $resolver = new NFSeProviderResolver();
        $compat = $resolver->buildMetadata($municipio);
        $this->providerKey = $compat['provider_key'];
        $this->municipioIgnored = $compat['municipio_ignored'];
        $this->deprecationWarnings = $compat['warnings'];

        if ($nfse !== null) {
            $this->nfse = $nfse;
        } else {
            try {
                $registry = ProviderRegistry::getInstance();
                if (!$registry->has($this->providerKey)) {
                    $this->initializationError = FiscalResponse::error(
                        "Provider NFSe nacional '{$this->providerKey}' não encontrado",
                        'PROVIDER_NOT_FOUND',
                        'nfse_initialization',
                        [
                            'available_municipios' => $registry->listMunicipios(),
                            'provider_key' => $this->providerKey,
                            'municipio_input' => $municipio,
                            'municipio_ignored' => $this->municipioIgnored,
                            'warnings' => $this->deprecationWarnings,
                            'suggestions' => [
                                "Configure '{$this->providerKey}' em config/nfse-municipios.json",
                                "O parâmetro 'municipio' foi deprecado para NFSe e não define roteamento",
                            ]
                        ]
                    );
                    return;
                }

                $this->nfse = new NFSeAdapter($municipio);
            } catch (\Exception $e) {
                $this->initializationError = $this->responseHandler->handle($e, 'nfse_initialization');
            }
        }
    }

    /**
     * Verifica se o NFSe está inicializado corretamente
     */
    private function checkNFSeInitialization(): ?FiscalResponse
    {
        if ($this->initializationError !== null) {
            return $this->initializationError;
        }
        return null;
    }

    /**
     * Emite uma NFSe
     */
    public function emitir(array $dados): FiscalResponse
    {
        if ($check = $this->checkNFSeInitialization()) {
            return $check;
        }

        try {
            $resultado = $this->nfse->emitir($dados);
            $lastEmission = method_exists($this->nfse, 'getLastEmissionInfo')
                ? $this->nfse->getLastEmissionInfo()
                : [];

            return FiscalResponse::success([
                'resultado' => $resultado,
                'type' => 'nfse_xml',
                'municipio' => $this->municipio,
                'emissao' => $lastEmission,
            ], 'nfse_emission', [
                'municipio' => $this->municipio,
                'provider_key' => $this->providerKey,
                'municipio_ignored' => $this->municipioIgnored,
                'warnings' => $this->deprecationWarnings,
                'provider_info' => $this->nfse->getProviderInfo()
            ]);
        } catch (\Exception $e) {
            return $this->responseHandler->handle($e, 'nfse_emission');
        }
    }

    /**
     * Consulta uma NFSe
     */
    public function consultar(string $chave): FiscalResponse
    {
        if ($check = $this->checkNFSeInitialization()) {
            return $check;
        }

        try {
            $resultado = $this->nfse->consultar($chave);
            return FiscalResponse::success([
                'resultado' => $resultado,
                'type' => 'nfse_consulta',
                'chave' => $chave,
                'municipio' => $this->municipio
            ], 'nfse_query', [
                'chave' => $chave,
                'municipio' => $this->municipio,
                'provider_key' => $this->providerKey,
                'municipio_ignored' => $this->municipioIgnored,
                'warnings' => $this->deprecationWarnings,
            ]);
        } catch (\Exception $e) {
            return $this->responseHandler->handle($e, 'nfse_query');
        }
    }

    /**
     * Cancela uma NFSe
     */
    public function cancelar(string $chave, string $motivo, string $protocolo = ''): FiscalResponse
    {
        if ($check = $this->checkNFSeInitialization()) {
            return $check;
        }

        try {
            $resultado = $this->nfse->cancelar($chave, $motivo, $protocolo);
            $operation = method_exists($this->nfse, 'getLastOperationInfo')
                ? $this->nfse->getLastOperationInfo()
                : [];
            return FiscalResponse::success([
                'canceled' => $resultado,
                'type' => 'nfse_cancelamento',
                'chave' => $chave,
                'motivo' => $motivo,
                'municipio' => $this->municipio,
                'cancelamento' => $operation,
            ], 'nfse_cancellation', [
                'chave' => $chave,
                'motivo' => $motivo,
                'municipio' => $this->municipio,
                'provider_key' => $this->providerKey,
                'municipio_ignored' => $this->municipioIgnored,
                'warnings' => $this->deprecationWarnings,
            ]);
        } catch (\Exception $e) {
            return $this->responseHandler->handle($e, 'nfse_cancellation');
        }
    }

    /**
     * Substitui uma NFSe
     */
    public function substituir(string $chave, array $dados): FiscalResponse
    {
        if ($check = $this->checkNFSeInitialization()) {
            return $check;
        }

        try {
            $resultado = $this->nfse->substituir($chave, $dados);
            return FiscalResponse::success([
                'resultado' => $resultado,
                'type' => 'nfse_substituicao',
                'chave' => $chave,
                'municipio' => $this->municipio,
            ], 'nfse_substitution', $this->buildCompatibilityMetadata());
        } catch (\Exception $e) {
            return $this->responseHandler->handle($e, 'nfse_substitution');
        }
    }

    public function consultarPorRps(array $identificacaoRps): FiscalResponse
    {
        if ($check = $this->checkNFSeInitialization()) {
            return $check;
        }

        try {
            $resultado = $this->nfse->consultarPorRps($identificacaoRps);
            $operation = method_exists($this->nfse, 'getLastOperationInfo')
                ? $this->nfse->getLastOperationInfo()
                : [];
            return FiscalResponse::success([
                'resultado' => $resultado,
                'type' => 'nfse_consulta_rps',
                'municipio' => $this->municipio,
                'consulta' => $operation,
            ], 'nfse_query_by_rps', $this->buildCompatibilityMetadata());
        } catch (\Exception $e) {
            return $this->responseHandler->handle($e, 'nfse_query_by_rps');
        }
    }

    public function consultarLote(string $protocolo): FiscalResponse
    {
        if ($check = $this->checkNFSeInitialization()) {
            return $check;
        }

        try {
            $resultado = $this->nfse->consultarLote($protocolo);
            $operation = method_exists($this->nfse, 'getLastOperationInfo')
                ? $this->nfse->getLastOperationInfo()
                : [];
            return FiscalResponse::success([
                'resultado' => $resultado,
                'type' => 'nfse_consulta_lote',
                'protocolo' => $protocolo,
                'municipio' => $this->municipio,
                'consulta' => $operation,
            ], 'nfse_query_lote', $this->buildCompatibilityMetadata());
        } catch (\Exception $e) {
            return $this->responseHandler->handle($e, 'nfse_query_lote');
        }
    }

    public function baixarXml(string $chave): FiscalResponse
    {
        if ($check = $this->checkNFSeInitialization()) {
            return $check;
        }

        try {
            $resultado = $this->nfse->baixarXml($chave);
            return FiscalResponse::success([
                'resultado' => $resultado,
                'type' => 'nfse_xml_download',
                'chave' => $chave,
                'municipio' => $this->municipio,
            ], 'nfse_download_xml', $this->buildCompatibilityMetadata());
        } catch (\Exception $e) {
            return $this->responseHandler->handle($e, 'nfse_download_xml');
        }
    }

    public function baixarDanfse(string $chave): FiscalResponse
    {
        if ($check = $this->checkNFSeInitialization()) {
            return $check;
        }

        try {
            $resultado = $this->nfse->baixarDanfse($chave);
            return FiscalResponse::success([
                'resultado' => $resultado,
                'type' => 'nfse_danfse_download',
                'chave' => $chave,
                'municipio' => $this->municipio,
            ], 'nfse_download_danfse', $this->buildCompatibilityMetadata());
        } catch (\Exception $e) {
            return $this->responseHandler->handle($e, 'nfse_download_danfse');
        }
    }

    public function listarMunicipiosNacionais(bool $forceRefresh = false): FiscalResponse
    {
        if ($check = $this->checkNFSeInitialization()) {
            return $check;
        }

        try {
            $resultado = $this->nfse->listarMunicipiosNacionais($forceRefresh);
            return FiscalResponse::success($resultado['data'] ?? [], 'nfse_nacional_municipios', [
                'source' => $resultado['metadata']['source'] ?? null,
                'stale' => $resultado['metadata']['stale'] ?? null,
                'force_refresh' => $forceRefresh,
                'municipio' => $this->municipio,
                'provider_key' => $this->providerKey,
                'municipio_ignored' => $this->municipioIgnored,
                'warnings' => $this->deprecationWarnings,
            ]);
        } catch (\Exception $e) {
            return $this->responseHandler->handle($e, 'nfse_nacional_municipios');
        }
    }

    public function consultarAliquotasMunicipio(
        string $codigoMunicipio,
        ?string $codigoServico = null,
        ?string $competencia = null,
        bool $forceRefresh = false
    ): FiscalResponse {
        if ($check = $this->checkNFSeInitialization()) {
            return $check;
        }

        try {
            $resultado = $this->nfse->consultarAliquotasMunicipio(
                $codigoMunicipio,
                $codigoServico,
                $competencia,
                $forceRefresh
            );
            return FiscalResponse::success($resultado['data'] ?? [], 'nfse_nacional_aliquotas', [
                'source' => $resultado['metadata']['source'] ?? null,
                'stale' => $resultado['metadata']['stale'] ?? null,
                'force_refresh' => $forceRefresh,
                'codigo_municipio' => $codigoMunicipio,
                'codigo_servico' => $codigoServico,
                'competencia' => $competencia,
                'municipio' => $this->municipio,
                'provider_key' => $this->providerKey,
                'municipio_ignored' => $this->municipioIgnored,
                'warnings' => $this->deprecationWarnings,
            ]);
        } catch (\Exception $e) {
            return $this->responseHandler->handle($e, 'nfse_nacional_aliquotas');
        }
    }

    public function consultarConvenioMunicipio(string $codigoMunicipio, bool $forceRefresh = false): FiscalResponse
    {
        if ($check = $this->checkNFSeInitialization()) {
            return $check;
        }

        try {
            $resultado = $this->nfse->consultarConvenioMunicipio($codigoMunicipio, $forceRefresh);
            return FiscalResponse::success($resultado['data'] ?? [], 'nfse_nacional_convenio', [
                'source' => $resultado['metadata']['source'] ?? null,
                'stale' => $resultado['metadata']['stale'] ?? null,
                'force_refresh' => $forceRefresh,
                'codigo_municipio' => $codigoMunicipio,
                'municipio' => $this->municipio,
                'provider_key' => $this->providerKey,
                'municipio_ignored' => $this->municipioIgnored,
                'warnings' => $this->deprecationWarnings,
            ]);
        } catch (\Exception $e) {
            return $this->responseHandler->handle($e, 'nfse_nacional_convenio');
        }
    }

    public static function consultarContribuinteCnc(string $municipio, string $cnc): FiscalResponse
    {
        try {
            $adapter = new NFSeAdapter($municipio);
            $data = $adapter->consultarContribuinteCnc($cnc);

            if (!is_array($data)) {
                $data = ['resultado' => $data];
            }

            $data['codigoMunicipio'] = $municipio;

            return FiscalResponse::success($data, 'nfse_consultar_contribuinte_cnc');
        } catch (\Throwable $e) {
            return FiscalResponse::fromException($e, 'nfse_consultar_contribuinte_cnc');
        }
    }

    public static function verificarHabilitacaoCnc(string $municipio, string $cnc): FiscalResponse
    {
        try {
            $adapter = new NFSeAdapter($municipio);
            $habilitado = $adapter->verificarHabilitacaoCnc($cnc);

            return FiscalResponse::success([
                'habilitado' => $habilitado,
                'cnc' => $cnc,
                'codigoMunicipio' => $municipio,
            ], 'nfse_verificar_habilitacao_cnc');
        } catch (\Throwable $e) {
            return FiscalResponse::fromException($e, 'nfse_verificar_habilitacao_cnc');
        }
    }

    public function validarLayoutDps(array $payload, bool $checkCatalog = true): FiscalResponse
    {
        if ($check = $this->checkNFSeInitialization()) {
            return $check;
        }

        try {
            $resultado = $this->nfse->validarLayoutDps($payload, $checkCatalog);
            return FiscalResponse::success($resultado, 'nfse_nacional_layout_check', [
                'municipio' => $this->municipio,
                'provider_key' => $this->providerKey,
                'municipio_ignored' => $this->municipioIgnored,
                'warnings' => $this->deprecationWarnings,
                'check_catalog' => $checkCatalog,
            ]);
        } catch (\Exception $e) {
            return $this->responseHandler->handle($e, 'nfse_nacional_layout_check');
        }
    }

    public function gerarXmlDpsPreview(array $payload): FiscalResponse
    {
        if ($check = $this->checkNFSeInitialization()) {
            return $check;
        }

        try {
            $xml = $this->nfse->gerarXmlDpsPreview($payload);
            return FiscalResponse::success([
                'xml' => $xml,
            ], 'nfse_nacional_layout_preview', [
                'municipio' => $this->municipio,
                'provider_key' => $this->providerKey,
                'municipio_ignored' => $this->municipioIgnored,
                'warnings' => $this->deprecationWarnings,
            ]);
        } catch (\Exception $e) {
            return $this->responseHandler->handle($e, 'nfse_nacional_layout_preview');
        }
    }

    public function validarXmlDps(array $payload): FiscalResponse
    {
        if ($check = $this->checkNFSeInitialization()) {
            return $check;
        }

        try {
            $resultado = $this->nfse->validarXmlDps($payload);
            return FiscalResponse::success($resultado, 'nfse_nacional_xml_check', [
                'municipio' => $this->municipio,
                'provider_key' => $this->providerKey,
                'municipio_ignored' => $this->municipioIgnored,
                'warnings' => $this->deprecationWarnings,
            ]);
        } catch (\Exception $e) {
            return $this->responseHandler->handle($e, 'nfse_nacional_xml_check');
        }
    }

    /**
     * Lista municípios disponíveis
     */
    public function listarMunicipios(): FiscalResponse
    {
        try {
            $registry = ProviderRegistry::getInstance();
            $municipios = $registry->listMunicipios();

            return FiscalResponse::success(['municipios' => $municipios], 'nfse_list_municipios', [
                'total' => count($municipios),
                'current_municipio' => $this->municipio,
                'provider_key' => $this->providerKey,
                'municipio_ignored' => $this->municipioIgnored,
                'warnings' => $this->deprecationWarnings,
            ]);
        } catch (\Exception $e) {
            return $this->responseHandler->handle($e, 'nfse_list_municipios');
        }
    }

    /**
     * Obtém informações do provider atual
     */
    public function getProviderInfo(): FiscalResponse
    {
        if ($check = $this->checkNFSeInitialization()) {
            return $check;
        }

        try {
            $info = $this->nfse->getProviderInfo();
            $info['provider_key'] = $this->providerKey;
            $info['municipio_ignored'] = $this->municipioIgnored;
            $info['warnings'] = $this->deprecationWarnings;
            return FiscalResponse::success($info, 'nfse_provider_info');
        } catch (\Exception $e) {
            return $this->responseHandler->handle($e, 'nfse_provider_info');
        }
    }

    /**
     * Valida XML NFSe
     */
    public function validarXML(string $xml): FiscalResponse
    {
        return $this->responseHandler->handle(function () use ($xml) {
            if (empty($xml)) {
                throw new \InvalidArgumentException("XML não pode estar vazio");
            }

            // Validação básica de XML
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);

            if (!$dom->loadXML($xml)) {
                $errors = libxml_get_errors();
                $errorMsg = "XML inválido: " . $errors[0]->message ?? 'Erro desconhecido';
                libxml_clear_errors();
                throw new \InvalidArgumentException($errorMsg);
            }

            // Tenta identificar tipo de NFSe
            $tiposNFSe = ['CompNfse', 'Nfse', 'InfNfse', 'GerarNfseEnvio'];
            $tipoDetectado = null;

            foreach ($tiposNFSe as $tipo) {
                if ($dom->getElementsByTagName($tipo)->length > 0) {
                    $tipoDetectado = $tipo;
                    break;
                }
            }

            if (!$tipoDetectado) {
                throw new \InvalidArgumentException("XML não é uma NFSe válida");
            }

            return [
                'xml_valido' => true,
                'tipo_nfse' => $tipoDetectado,
                'municipio_esperado' => $this->municipio,
                'tamanho_xml' => strlen($xml)
            ];
        }, 'validacao_xml_nfse');
    }

    /**
     * Valida dados do prestador NFSe
     */
    public function validarPrestador(array $prestador): FiscalResponse
    {
        return $this->responseHandler->handle(function () use ($prestador) {
            $erros = [];

            // Valida campos obrigatórios
            if (empty($prestador['cnpj'])) {
                $erros[] = 'CNPJ do prestador é obrigatório';
            } else {
                // Valida formato CNPJ
                $cnpj = preg_replace('/\D/', '', $prestador['cnpj']);
                if (strlen($cnpj) !== 14) {
                    $erros[] = 'CNPJ deve ter 14 dígitos';
                }
            }

            if (empty($prestador['inscricaoMunicipal'])) {
                $erros[] = 'Inscrição Municipal é obrigatória';
            }

            if (empty($prestador['razaoSocial'])) {
                $erros[] = 'Razão Social é obrigatória';
            }

            if (!empty($erros)) {
                throw new \InvalidArgumentException("Prestador inválido: " . implode(', ', $erros));
            }

            return [
                'prestador_valido' => true,
                'cnpj_formatado' => isset($prestador['cnpj']) ? preg_replace('/\D/', '', $prestador['cnpj']) : null,
                'validacoes' => [
                    'cnpj' => !empty($prestador['cnpj']),
                    'inscricao_municipal' => !empty($prestador['inscricaoMunicipal']),
                    'razao_social' => !empty($prestador['razaoSocial'])
                ]
            ];
        }, 'validacao_prestador_nfse');
    }

    /**
     * Valida configuração do município
     */
    public function validarMunicipio(?string $municipio = null): FiscalResponse
    {
        $municipioToValidate = $municipio ?? $this->municipio;

        try {
            $registry = ProviderRegistry::getInstance();

            if (!$registry->has($this->providerKey)) {
                return FiscalResponse::error(
                    "Provider NFSe nacional '{$this->providerKey}' não está configurado",
                    'MUNICIPALITY_NOT_CONFIGURED',
                    'nfse_municipality_validation',
                    [
                        'available_municipios' => $registry->listMunicipios(),
                        'provider_key' => $this->providerKey,
                        'municipio_input' => $municipioToValidate,
                        'municipio_ignored' => true,
                        'warnings' => $this->deprecationWarnings,
                        'suggestions' => [
                            "Configure '{$this->providerKey}' em config/nfse-municipios.json",
                            "Não é mais necessário configurar provider por município",
                        ]
                    ]
                );
            }

            $config = $registry->getConfig($this->providerKey);
            return FiscalResponse::success([
                'municipio' => $municipioToValidate,
                'provider_key' => $this->providerKey,
                'municipio_ignored' => true,
                'configured' => true,
                'config_keys' => array_keys($config)
            ], 'nfse_municipality_validation', [
                'warnings' => $this->deprecationWarnings
            ]);

        } catch (\Exception $e) {
            return $this->responseHandler->handle($e, 'nfse_municipality_validation');
        }
    }

    public static function nacional(?NFSeAdapter $nfse = null): self
    {
        return new self('nfse_nacional', $nfse);
    }

    private function buildCompatibilityMetadata(): array
    {
        return [
            'municipio' => $this->municipio,
            'provider_key' => $this->providerKey,
            'municipio_ignored' => $this->municipioIgnored,
            'warnings' => $this->deprecationWarnings,
        ];
    }

    /**
     * Verifica prontidão para homologação NFSe Nacional.
     */
    public function verificarProntidaoHomologacao(): FiscalResponse
    {
        try {
            $registry = ProviderRegistry::getInstance();
            $config = $registry->getConfig($this->providerKey);

            $required = ['provider', 'api_base_url', 'timeout', 'endpoints'];
            $missing = [];
            foreach ($required as $key) {
                if (!array_key_exists($key, $config) || $config[$key] === '' || $config[$key] === []) {
                    $missing[] = $key;
                }
            }

            $signatureMode = (string) ($config['signature_mode'] ?? 'optional');
            $certManager = CertificateManager::getInstance();
            $certLoaded = $certManager->getCertificate() !== null;
            $certValid = $certManager->isValid();

            if ($signatureMode === 'required' && (!$certLoaded || !$certValid)) {
                $missing[] = 'certificado_digital_valido';
            }

            return FiscalResponse::success([
                'ready' => empty($missing),
                'provider_key' => $this->providerKey,
                'signature_mode' => $signatureMode,
                'certificado_carregado' => $certLoaded,
                'certificado_valido' => $certValid,
                'missing_requirements' => $missing,
            ], 'nfse_homologation_readiness', $this->buildCompatibilityMetadata());
        } catch (\Exception $e) {
            return $this->responseHandler->handle($e, 'nfse_homologation_readiness');
        }
    }
}
