<?php

namespace Tests\Unit\DTO;

use PHPUnit\Framework\TestCase;
use sabbajohn\FiscalCore\Adapters\NF\DTO\IcmsDTO;

class IcmsDTOTest extends TestCase
{
    public function testSimplesNacionalSemCredito()
    {
        $dto = IcmsDTO::simplesNacionalSemCredito();

        $this->assertEquals('102', $dto->cst);
        $this->assertEquals(0, $dto->orig);
        $this->assertNull($dto->pCredSN);
        $this->assertNull($dto->vCredICMSSN);
    }

    public function testSimplesNacionalComCredito()
    {
        $dto = IcmsDTO::simplesNacionalComCredito(1.86, 18.60);

        $this->assertEquals('101', $dto->cst);
        $this->assertEquals(0, $dto->orig);
        $this->assertEquals(1.86, $dto->pCredSN);
        $this->assertEquals(18.60, $dto->vCredICMSSN);
    }

    public function testIcms00()
    {
        $dto = IcmsDTO::icms00(
            vBC: 1000.00,
            pICMS: 18.00,
            vICMS: 180.00
        );

        $this->assertEquals('00', $dto->cst);
        $this->assertEquals(1000.00, $dto->vBC);
        $this->assertEquals(18.00, $dto->pICMS);
        $this->assertEquals(180.00, $dto->vICMS);
        $this->assertEquals(3, $dto->modBC);
    }

    public function testIcmsIsento()
    {
        $dto = IcmsDTO::icmsIsento();

        $this->assertEquals('40', $dto->cst);
        $this->assertEquals(0, $dto->orig);
        $this->assertNull($dto->vBC);
        $this->assertNull($dto->pICMS);
    }

    public function testOrigemPersonalizada()
    {
        $dto = IcmsDTO::simplesNacionalSemCredito(orig: 1);

        $this->assertEquals(1, $dto->orig);
    }
}
