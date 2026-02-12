<?php

namespace App\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class UniquePerEntrepriseValidator extends ConstraintValidator
{
    /*
    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var UniquePerEntreprise $constraint *

     
        // TODO: implement the validation here
        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->addViolation()
        ;
    } */



      public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {}


       public function validate(mixed $entity, Constraint $constraint): void
    {
        if (!$constraint instanceof \App\Validator\UniquePerEntreprise || !$entity) {
            return;
        }

        /**
         * @var User
         */
        $user = $this->security->getUser();
        if (!$user || !method_exists($user, 'getEntreprise')) {
            return;
        }

        $entrepriseId = $user->getEntrepriseid();

        // 🔎 Construire dynamiquement les critères
        $criteria = ['identreprise' => $entrepriseId];

        foreach ($constraint->fields as $field) {
            $getter = 'get' . ucfirst($field);
            if (!method_exists($entity, $getter)) {
                return; // le champ n’existe pas, on stoppe
            }

            $value = $entity->$getter();

            if ($value === null || $value === '') {
                return; // champ vide => pas de validation
            }

            $criteria[$field] = $value;
        }

        // 🧹 Soft delete
        $metadata = $this->entityManager->getClassMetadata($entity::class);
        if ($metadata->hasField('deletedAt')) {
            $criteria['deletedAt'] = null;
        }

        $repo = $this->entityManager->getRepository($entity::class);
        $existing = $repo->findOneBy($criteria);

        // ✏️ Cas EDIT : ignorer l'entité courante
        if ($existing && method_exists($entity, 'getId') && $entity->getId() === $existing->getId()) {
            return;
        }

        // ❌ Violation attachée au premier champ
        if ($existing) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath($constraint->fields[0])
                ->addViolation();
        }
    }

}
