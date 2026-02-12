<?php

namespace App\Domain\Service;

use App\Entity\Depannage;

class DepannageCostService
{
    public function recalculate(Depannage $depannage): void
    {
        $total = 0;
        foreach($depannage->getDetaildepannages() as $detail) {
            $piece = $detail->getPiece();
            $quantite = $detail->getQuantite();
            $total += $piece->getPrixUnitaire() * $quantite;
        }
        $depannage->setCoutTotal($total);
    }

}