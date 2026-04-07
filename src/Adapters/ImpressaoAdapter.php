<?php

namespace sabbajohn\FiscalCore\Adapters;

use sabbajohn\FiscalCore\Contracts\ImpressaoInterface;
use NFePHP\DA\NFe\Danfe as DanfeNFe;
use NFePHP\DA\NFe\Danfce as DanfeNFCe;
use NFePHP\DA\MDFe\Damdfe as DanfeMdfe;
use NFePHP\DA\CTe\Dacte as DanfeCte;

class ImpressaoAdapter implements ImpressaoInterface
{
    public function gerarDanfe(string $xml): string
    {
        $danfe = new DanfeNFe($xml);
        return $danfe->render();
    }

    public function gerarDanfce(string $xml): string
    {
        $danfe = new DanfeNFCe($xml);
        return $danfe->render();
    }

    public function gerarMdfe(string $xml): string
    {
        $danfe = new DanfeMdfe($xml);
        return $danfe->render();
    }

    public function gerarCte(string $xml): string
    {
        $danfe = new DanfeCte($xml);
        return $danfe->render();
    }
}