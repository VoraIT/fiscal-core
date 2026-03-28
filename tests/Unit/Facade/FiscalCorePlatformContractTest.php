<?php

use freeline\FiscalCore\Facade\ImpressaoFacade;
use freeline\FiscalCore\Facade\NFCeFacade;
use freeline\FiscalCore\Facade\NFeFacade;
use freeline\FiscalCore\Facade\NFSeFacade;
use freeline\FiscalCore\Support\SafeCertificateManager;
use PHPUnit\Framework\TestCase;

class FiscalCorePlatformContractTest extends TestCase
{
    public function test_nfe_facade_keeps_platform_required_contract_methods(): void
    {
        $reflection = new ReflectionClass(NFeFacade::class);

        $this->assertTrue($reflection->hasMethod('emitir'));
        $this->assertTrue($reflection->hasMethod('consultar'));
        $this->assertTrue($reflection->hasMethod('cancelar'));
        $this->assertTrue($reflection->hasMethod('validarXML'));
        $this->assertTrue($reflection->hasMethod('gerarDanfe'));
    }

    public function test_nfce_facade_keeps_platform_required_contract_methods(): void
    {
        $reflection = new ReflectionClass(NFCeFacade::class);

        $this->assertTrue($reflection->hasMethod('emitir'));
        $this->assertTrue($reflection->hasMethod('consultar'));
        $this->assertTrue($reflection->hasMethod('cancelar'));
        $this->assertTrue($reflection->hasMethod('gerarDanfce'));
    }

    public function test_nfse_facade_keeps_platform_required_contract_methods(): void
    {
        $reflection = new ReflectionClass(NFSeFacade::class);

        $this->assertTrue($reflection->hasMethod('emitir'));
        $this->assertTrue($reflection->hasMethod('consultar'));
        $this->assertTrue($reflection->hasMethod('cancelar'));
        $this->assertTrue($reflection->hasMethod('consultarLote'));
        $this->assertTrue($reflection->hasMethod('gerarDanfse'));
        $this->assertTrue($reflection->hasMethod('getProviderInfo'));
    }

    public function test_support_components_keep_platform_required_entrypoints(): void
    {
        $impressaoReflection = new ReflectionClass(ImpressaoFacade::class);
        $certificateReflection = new ReflectionClass(SafeCertificateManager::class);

        $this->assertTrue($impressaoReflection->hasMethod('gerarDanfe'));
        $this->assertTrue($impressaoReflection->hasMethod('gerarDanfce'));
        $this->assertTrue($certificateReflection->hasMethod('loadFromContentSafe'));
        $this->assertTrue($certificateReflection->hasMethod('validateSafe'));
    }
}
