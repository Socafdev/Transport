<?php

namespace App\Domain\Service;

use Doctrine\ORM\EntityManagerInterface;

class EntrepriseUniqueChecker
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Vérifie l'unicité d'un champ pour une entité et une entreprise
     */
    public function exists(
        string $entityClass,
        string $field,
        mixed $value,
        int $entrepriseId,
        ?int $excludeId = null
    ): bool {
        $repo = $this->entityManager->getRepository($entityClass);

        $criteria = [
            $field => $value,
            'identreprise' => $entrepriseId,
            'deletedAt' => NULL,
        ];

        $entity = $repo->findOneBy($criteria);

        if (!$entity) {
            return false;
        }

        if ($excludeId !== null && method_exists($entity, 'getId')) {
            return $entity->getId() !== $excludeId;
        }

        return true;
    }
}