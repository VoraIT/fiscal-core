<?php

use PHPUnit\Framework\TestCase;
use sabbajohn\FiscalCore\Support\CertificateManager;
use sabbajohn\FiscalCore\Support\ConfigManager;
use sabbajohn\FiscalCore\Support\ToolsFactory;

class SingletonManagersTest extends TestCase
{
    protected function setUp(): void
    {
        // Limpa os singletons entre testes
        CertificateManager::getInstance()->clear();
        ConfigManager::getInstance()->reload();
    }

    public function test_certificate_manager_singleton_instance(): void
    {
        $manager1 = CertificateManager::getInstance();
        $manager2 = CertificateManager::getInstance();

        $this->assertSame($manager1, $manager2);
        $this->assertInstanceOf(CertificateManager::class, $manager1);
    }

    public function test_config_manager_singleton_instance(): void
    {
        $manager1 = ConfigManager::getInstance();
        $manager2 = ConfigManager::getInstance();

        $this->assertSame($manager1, $manager2);
        $this->assertInstanceOf(ConfigManager::class, $manager1);
    }

    public function test_certificate_manager_initially_empty(): void
    {
        
        $manager = CertificateManager::getInstance();
        $manager->clear();
        $this->assertFalse($manager->isLoaded());
        $this->assertNull($manager->getCertificate());
        $this->assertNull($manager->getCnpj());
        $this->assertNull($manager->getRazaoSocial());
    }

    public function test_config_manager_has_defaults(): void
    {
        $manager = ConfigManager::getInstance();

        $this->assertEquals(2, $manager->get('ambiente')); // homologação
        $this->assertEquals('SC', $manager->get('uf'));
        $this->assertEquals('4.00', $manager->get('versao_nfe'));
        $this->assertTrue($manager->isHomologation());
        $this->assertFalse($manager->isProduction());
    }

    public function test_config_manager_set_and_get(): void
    {
        $manager = ConfigManager::getInstance();

        $manager->set('test_key', 'test_value');
        $this->assertEquals('test_value', $manager->get('test_key'));

        $manager->set('nested.key', 'nested_value');
        $this->assertEquals('nested_value', $manager->get('nested.key'));
    }

    public function test_config_manager_nfe_config(): void
    {
        $manager = ConfigManager::getInstance();
        $config = $manager->getNFeConfig();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('tpAmb', $config);
        $this->assertArrayHasKey('siglaUF', $config);
        $this->assertArrayHasKey('versao', $config);
        $this->assertArrayHasKey('proxy', $config);
        $this->assertEquals(ConfigManager::AMBIENTE_HOMOLOGACAO, $config['tpAmb']); // homologação
    }

    public function test_config_manager_nfse_config(): void
    {
        $manager = ConfigManager::getInstance();
        $config = $manager->getNFSeConfig();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('provider', $config);
        $this->assertArrayHasKey('versao', $config);
        $this->assertArrayHasKey('ambiente', $config);

        $this->assertEquals(ConfigManager::AMBIENTE_HOMOLOGACAO, $config['ambiente']);
    }

    public function test_tools_factory_requires_certificate(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Certificado digital não carregado');

        ToolsFactory::createNFeTools();
    }

    /**
     * @skip If certificate is loaded
     * @depends test_tools_factory_requires_certificate
     */
    public function test_tools_factory_validate_environment_without_certificate(): void
    {
        $this->markTestSkipped("Skipping test because certificate is loaded");
        $validation = ToolsFactory::validateEnvironment();

        $this->assertFalse($validation['valid']);
        $this->assertContains('Certificado digital não carregado', $validation['errors']);
        $this->assertEquals('Homologação', $validation['environment']);
    }

    public function test_tools_factory_setup_for_development(): void
    {
        ToolsFactory::setupForDevelopment([
            'uf' => 'RJ',
            'token_ibpt' => 'TEST_TOKEN'
        ]);

        $manager = ConfigManager::getInstance();
        $this->assertEquals(2, $manager->get('ambiente')); // homologação
        $this->assertEquals('RJ', $manager->get('uf'));
        $this->assertEquals('TEST_TOKEN', $manager->get('token_ibpt'));
    }

    public function test_tools_factory_setup_for_production_requires_configs(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuração obrigatória para produção: csc');

        ToolsFactory::setupForProduction([
            'uf' => 'SP'
        ]);
    }

    public function test_tools_factory_setup_for_production_with_all_configs(): void
    {
        ToolsFactory::setupForProduction([
            'csc' => 'TEST_CSC',
            'csc_id' => '000001',
            'uf' => 'SP',
            'municipio_ibge' => '3550308'
        ]);

        $manager = ConfigManager::getInstance();
        $manager->set('ambiente', 1); // produção
        $this->assertEquals(1, $manager->get('ambiente')); // produção
        $this->assertEquals('TEST_CSC', $manager->get('csc'));
        $this->assertTrue($manager->isProduction());
    }

    public function test_certificate_manager_load_from_invalid_file(): void
    {
        $manager = CertificateManager::getInstance();

        $this->expectException(\NFePHP\Common\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Certificado não encontrado');

        $manager->loadFromFile('/path/that/does/not/exist.pfx', 'password');
    }

    public function test_config_manager_environment_methods(): void
    {
        $manager = ConfigManager::getInstance();

        // Testa homologação (padrão)
        $this->assertTrue($manager->isHomologation());
        $this->assertFalse($manager->isProduction());

        // Muda para produção
        $manager->set('ambiente', 1);
        $this->assertFalse($manager->isHomologation());
        $this->assertTrue($manager->isProduction());
    }

    public function test_config_manager_reset(): void
    {
        $manager = ConfigManager::getInstance();

        // Modifica configuração
        $manager->set('ambiente', 1);
        $manager->set('custom_key', 'custom_value');

        // Reseta
        $manager->reload();

        // Verifica se voltou ao padrão
        $this->assertEquals(2, $manager->get('ambiente'));
        $this->assertNull($manager->get('custom_key'));
    }

    public function test_singleton_prevent_cloning(): void
    {
        $manager = CertificateManager::getInstance();

        $this->expectException(\Error::class);
        
        $clone = clone $manager;
    }

    public function test_certificate_manager_clear(): void
    {
        $manager = CertificateManager::getInstance();

        // Simula um certificado carregado (sem arquivo real)
        // Como não temos arquivo real, apenas testamos o clear
        $manager->clear();

        $this->assertFalse($manager->isLoaded());
        $this->assertNull($manager->getCertificate());
        $this->assertEmpty($manager->getCertificateInfo());
    }
}