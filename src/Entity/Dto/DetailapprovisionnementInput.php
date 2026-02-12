<?php

namespace App\Entity\Dto;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class DetailapprovisionnementInput
{
    #[Assert\NotNull]
    public int $piece; // Chaque 'Detailapprovisionnement' est lié à une pièce

    #[Assert\NotNull]
    #[Assert\Positive]
    public int $quantite;

    #[Assert\NotNull]
    #[Assert\Positive]
    public int $prixunitaire;

}