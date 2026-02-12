<?php

namespace App\Entity\Data;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use App\Controller\StatistiqueController;

#[ApiResource(
    operations: [
        new GetCollection(
            name: '',
            uriTemplate: '/statistiques',
            read: false,
            write: false,
            input: false,
            controller: StatistiqueController::class,
            openapi: new Operation(
                summary: 'Statistiques',
                description: 'Permet de voir les statistiques',
                security: [['bearerAuth' => []]],
                parameters: []
            ) 
        )
    ]
)]
class Statistique
{
}