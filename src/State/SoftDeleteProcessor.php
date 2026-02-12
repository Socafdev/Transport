<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

class SoftDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /**
         * @var User
         */
        $user = $this->security->getUser();
        if(method_exists($data, 'setDeletedAt')) {
            $data
                ->setDeletedAt(new \DateTimeImmutable())
                ->setDeletedBy($user->getId())
                ->setIsEtatdelete(true);
            // - On a pas besoin de 'flush' il le fait automatiquement
        }
        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
