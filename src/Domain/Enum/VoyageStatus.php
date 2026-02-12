<?php

namespace App\Domain\Enum;

enum VoyageStatus: string
{
    case PREVU = 'PREVU';

    case EN_COURS = 'EN_COURS';

    case TERMINE = 'TERMINE';

    case ANNULE = 'ANNULE';
}