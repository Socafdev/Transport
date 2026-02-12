<?php

namespace App\Domain\Service;

use Symfony\Component\Finder\Finder;

class EntityDiscoveryService
{
    public function __construct(
        private string $entityPath
    )
    {
    }

    /**
     * Permet de cacher les entités sur lesquelles on ne peut pas définir des permissions
     * @return array
     */
    public function getEntityList(): array
    {
        $entities = [];
        $finder = new Finder();
        $finder->files()->in($this->entityPath)->name('*.php');
        foreach ($finder as $file) {
            $entityName = $file->getBasename('.php');
            if (!in_array($entityName, [
                'UserRole',
                'Entreprise',
                'EntityBase',
                'Permission' # !!
            ])) {
                $entities[] = $entityName;
            }
        }
        sort($entities);
        return $entities;
    }
}