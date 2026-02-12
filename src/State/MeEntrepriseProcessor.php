<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Dto\EntrepriseInput;
use App\Entity\Entreprise;
use App\Entity\User;
use App\Repository\EntrepriseRepository;
use Symfony\Bundle\SecurityBundle\Security;

class MeEntrepriseProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private EntrepriseRepository $entrepriseRepository
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var EntrepriseInput $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();
        /**
         * @var Entreprise
         */
        $entreprise = $this->entrepriseRepository->find($user->getEntrepriseid()); /*
            - On ne vérifie pas vu que le '->find()' retourne une exception en cas d'erreur sinon throw 'RuntimeException'
        */
        $entreprise
            ->setLibelle($data->libelle)
            ->setContact1($data->contact1)
            ->setContact2($data->contact2)
            ->setAdresse($data->adresse)
            ->setEmail($data->email)
            ->setAnneecreation(new \DateTimeImmutable($data->anneecreation))
            ->setSigle($data->sigle)
            ->setSiteweb($data->siteweb)
            ->setRccm($data->rccm)
            ->setBanque($data->banque)
            ->setType($data->type)
            ->setCentreimpot($data->centreimpot)
            ->setTauxtva($data->tauxtva)
            ->setUpdatedAt(new \DateTimeImmutable())
            ->setUpdatedBy($user->getId())
        ;

        return $this->processor->process($entreprise, $operation, $uriVariables, $context); /*
            - Pas de '->flush()' vu qu'on a le 'process'
        */
    }
}
