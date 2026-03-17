<?php

namespace freeline\FiscalCore\Support;

use freeline\FiscalCore\Contracts\NFSeProviderConfigInterface;
use PhpParser\Node\Expr\Cast\Object_;
use stdClass;

/**
 * Registry para carregar Providers de NFSe baseado em configuração externa
 * 
 * Permite centralizar o carregamento de providers sem duplicar código
 * quando múltiplos municípios compartilham a mesma implementação.
 * 
 * Uso:
 * ```php
 * $registry = ProviderRegistry::getInstance();
 * $provider = $registry->get('curitiba');
 * $provider->emitir($dados);
 * ```
 */
class ProviderRegistry
{
    private const NFSE_NATIONAL_KEY = 'nfse_nacional';

    private static ?self $instance = null;
    private array $config = [];
    private array $providers = [];
    
    private function __construct()
    {
        $this->loadConfig();
    }
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Carrega configurações de providers de arquivo JSON
     */
    private function loadConfig(): void
    {
        $configFile = __DIR__ . '/../../config/nfse-municipios.json';
        
        if (file_exists($configFile)) {
            $json = file_get_contents($configFile);
            $this->config = json_decode($json, true) ?? [];
        }
    }
    
    /**
     * Obtém um provider configurado para o município
     * 
     * @param string $municipio Nome ou código IBGE do município
     * @return NFSeProviderConfigInterface
     * @throws \RuntimeException Se município não configurado
     */
    public function get(string $municipio): NFSeProviderConfigInterface
    {
        $providerKey = $this->resolveProviderKey($municipio);

        // Retornar provider em cache se já instanciado
        if (isset($this->providers[$providerKey])) {
            return $this->providers[$providerKey];
        }

        // Verificar se provider está configurado
        if (!isset($this->config[$providerKey])) {
            throw new \RuntimeException(
                "Provider '{$providerKey}' não configurado. " .
                "Defina '" . self::NFSE_NATIONAL_KEY . "' em config/nfse-municipios.json"
            );
        }

        $config = $this->config[$providerKey];
        
        // Verificar se provider class está especificado
        $providerName = $config['provider'] ?? $config['provider_class'] ?? null;
        if ($providerName === null) {
            throw new \RuntimeException(
                "Provider class não especificado para chave '{$providerKey}'"
            );
        }
        
        // Montar nome completo da classe
        $providerClass = $this->resolveProviderClass($providerName);
        
        // Verificar se classe existe
        if (!class_exists($providerClass)) {
            throw new \RuntimeException(
                "Provider class não encontrado: {$providerClass}"
            );
        }
        
        // Instanciar provider com configuração
        $provider = new $providerClass($config);
        
        // Cachear para reutilização
        $this->providers[$providerKey] = $provider;
        
        return $provider;
    }

    public function getNfseNacional(): NFSeProviderConfigInterface
    {
        return $this->get(self::NFSE_NATIONAL_KEY);
    }
    
    public function getByMunicipio(string $municipio): NFSeProviderConfigInterface
    {
        $catalog = new NFSeMunicipalCatalog();
        $resolved = $catalog->resolveMunicipio($municipio);

        if ($resolved === null) {
            return $this->get(self::NFSE_NATIONAL_KEY);
        }

        return $this->getFamilyProvider($resolved['provider_family_key'], $resolved);
    }

    private function getFamilyProvider(string $key, array $municipio): NFSeProviderConfigInterface
    {
        return new NFSeProviderConfigInterface();
    }
    /**
     * Resolve o nome completo da classe do provider
     * 
     * @param string $providerName Nome curto (ex: "AbrasfV2Provider")
     * @return string Nome completo com namespace
     */
    private function resolveProviderClass(string $providerName): string
    {
        // Se já tem namespace completo, retornar como está
        if (str_contains($providerName, '\\')) {
            return $providerName;
        }
        
        // Adicionar namespace padrão
        return "freeline\\FiscalCore\\Providers\\NFSe\\{$providerName}";
    }
    
    /**
     * Lista todos os municípios configurados
     * 
     * @return array
     */
    public function listMunicipios(): array
    {
        return array_keys($this->config);
    }

    /**
     * Verifica se um município está configurado
     * 
     * @param string $municipio Nome do município
     * @return bool
     */
    public function has(string $municipio): bool
    {
        return isset($this->config[$municipio]);
    }

    /**
     * Obtém a configuração bruta de um município
     * 
     * @param string $municipio Nome do município
     * @return array
     * @throws \RuntimeException Se município não configurado
     */
    public function getConfig(string $municipio): array
    {
        $providerKey = $this->resolveProviderKey($municipio);

        if (!isset($this->config[$providerKey])) {
            throw new \RuntimeException(
                "Provider '{$providerKey}' não configurado. " .
                "Defina '" . self::NFSE_NATIONAL_KEY . "' em config/nfse-municipios.json"
            );
        }

        return $this->config[$providerKey];
    }
    
    /**
     * Adiciona ou atualiza configuração de um município em runtime
     * 
     * @param string $municipio
     * @param array $config
     */
    public function register(string $municipio, array $config): void
    {
        $this->config[$municipio] = $config;
        
        // Limpar cache se existir
        unset($this->providers[$municipio]);
    }
    
    /**
     * Recarrega configurações do arquivo
     */
    public function reload(): void
    {
        $this->config = [];
        $this->providers = [];
        $this->loadConfig();
    }

    /**
     * Valida configuração de um provider
     */
    public function validarConfiguracao(string $municipio): FiscalResponse
    {
        try {
            $providerKey = $this->resolveProviderKey($municipio);

            if (!$this->has($providerKey)) {
                return FiscalResponse::error(
                    "Provider '{$providerKey}' não configurado",
                    'PROVIDER_NOT_FOUND',
                    'validacao_provider_config'
                );
            }
            
            $config = $this->getConfig($providerKey);
            $erros = [];
            
            // Validações básicas
            $providerClass = $config['provider_class'] ?? $config['provider'] ?? null;
            if (empty($providerClass)) {
                $erros[] = 'provider/provider_class não definido';
            }
            if (empty($config['url_producao']) && empty($config['wsdl_producao'])) {
                $erros[] = 'url_producao/wsdl_producao não definida';
            }
            if (empty($config['url_homologacao']) && empty($config['wsdl_homologacao'])) {
                $erros[] = 'url_homologacao/wsdl_homologacao não definida';
            }
            
            if (!empty($erros)) {
                return FiscalResponse::error(
                    'Configuração inválida: ' . implode(', ', $erros),
                    'INVALID_CONFIG',
                    'validacao_provider_config'
                );
            }
            
            return FiscalResponse::success([
                'municipio' => $municipio,
                'provider_key' => $providerKey,
                'municipio_ignored' => $providerKey !== $municipio,
                'config_valida' => true,
                'provider_class' => $providerClass
            ], 'validacao_provider_config');
            
        } catch (\Exception $e) {
            return FiscalResponse::fromException($e, 'validacao_provider_config');
        }
    }

    /**
     * Determina ambiente (produção/homologação) baseado na configuração
     */
    public function determinarAmbiente(?string $ambiente = null): string
    {
        if ($ambiente !== null) {
            return strtolower($ambiente) === 'producao' ? 'producao' : 'homologacao';
        }
        
        // Verifica variável de ambiente
        $env = $_ENV['NFSE_AMBIENTE'] ?? $_ENV['APP_ENV'] ?? 'homologacao';
        
        return in_array(strtolower($env), ['prod', 'production', 'producao']) ? 'producao' : 'homologacao';
    }

    /**
     * Obtém regras específicas de um município
     */
    public function obterRegrasEspecificas(string $municipio): array
    {
        if (!$this->has($municipio) && !$this->has(self::NFSE_NATIONAL_KEY)) {
            return [];
        }
        
        $config = $this->getConfig($municipio);
        return $config['regras_especificas'] ?? [];
    }

    /**
     * Busca provider de fallback quando o principal falha
     */
    public function buscarFallback(string $municipio): ?string
    {
        if (!$this->has($municipio) && !$this->has(self::NFSE_NATIONAL_KEY)) {
            return null;
        }
        
        $config = $this->getConfig($municipio);
        return $config['fallback_provider'] ?? null;
    }

    /**
     * Obtém versão do schema XML suportada
     */
    public function obterVersaoSchema(string $municipio): string
    {
        if (!$this->has($municipio) && !$this->has(self::NFSE_NATIONAL_KEY)) {
            return '1.0'; // versão padrão
        }
        
        $config = $this->getConfig($municipio);
        return $config['versao_schema'] ?? '1.0';
    }
    
    // Prevenir clonagem
    private function __clone() {}
    
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }

    private function resolveProviderKey(string $input): string
    {
        if (isset($this->config[$input])) {
            $alias = $this->config[$input]['alias_of'] ?? null;
            return is_string($alias) && $alias !== '' ? $alias : $input;
        }

        if (isset($this->config[self::NFSE_NATIONAL_KEY])) {
            return self::NFSE_NATIONAL_KEY;
        }

        return $input;
    }
}
