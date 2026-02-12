<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Entity\Voyage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class VoyageProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private EntityManagerInterface $em
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var Voyage $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();
        $entrepriseId = $user->getEntrepriseid();

        if($operation instanceof Post) {
            $data
                ->setIdentreprise($entrepriseId)
                ->setCreatedBy($user->getId());

            $numero = $this->em->getRepository(Voyage::class)->count([
                'trajet' => $data->getTrajet(),
                'identreprise' => $entrepriseId,
                'deletedAt' => null
            ]) + 1;

            $data->setCodevoyage($data->getTrajet()->getCodetrajet() . '-V' . $numero);
        }

        if($operation instanceof Patch) {
            $data->setUpdatedBy($user->getId());
        }

        if($data->getCar()) {
            $data->setCar($data->getCar());
            $places = $data->getCar()->getNbrSiege();

            if($data->getPlacesOccupees() > $data->getCar()->getNbrSiege()) { // On vérifie que les places déjà occupées ne dépassent celui du nouveau car en cas de 'patch'
                throw new BadRequestHttpException(
                    'Impossible de changer de Car : les places déjà occupées dépassent la capacité du nouveau véhicule'
                );
            }

            $data->setPlacesTotal($places); /*
                - Le '->setPlacesOccupees' sera incrémenté à chaque ticket
            */
        } else {
            $data
                ->setPlacesTotal(0)
                ->setPlacesOccupees(0);
        }

        return $this->processor->process($data, $operation, $uriVariables, $context);
    }

}
