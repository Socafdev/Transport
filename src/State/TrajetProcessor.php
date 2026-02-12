<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Dto\TrajetInput;
use App\Entity\Trajet;
use App\Entity\User;
use App\Entity\Voyage;
use App\Repository\CarRepository;
use App\Repository\TarifRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TrajetProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private EntityManagerInterface $em,
        private TarifRepository $tarifRepository,
        private CarRepository $carRepository
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var TrajetInput $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();
        $entrepriseId = $user->getEntrepriseid();
        $tarif = $this->tarifRepository->findOneBy([
            'id' => $data->tarifId,
            'identreprise' => $entrepriseId,
            'deletedAt' => null
        ]);
        if(!$tarif) {
            throw new BadRequestHttpException('Tarif invalide pour cette entreprise');
        }

        $dateDebut = new \DateTimeImmutable($data->datedebut);
        $dateFin = new \DateTimeImmutable($data->datefin);
        if($dateDebut >= $dateFin) {
            throw new BadRequestHttpException('La date de fin doit être supérieure à la date de début');
        }

        if($data->provenance === $data->destination) {
            throw new BadRequestHttpException('Provenance et destination identiques');
        }

        $trajet = new Trajet();
        $trajet
            ->setProvenance($data->provenance)
            ->setDestination($data->destination)
            ->setDatedebut($dateDebut)
            ->setDatefin($dateFin)
            ->setIdentreprise($entrepriseId)
            ->setTarif($tarif)
            ->setCreatedBy($user->getId())
            ->setCodeTrajet($this->generateCodeTrajet($entrepriseId)); // Pour moi 'TR' . uniqid() . date('YmdHis')
            /*
            - ->setOrderindex(0); -- Je ne sais pas quoi mettre
        */
        $this->em->persist($trajet);
        // $this->em->flush(); -- Pour avoir l'id du trajet mais on n'a 'process'

        $voyage = new Voyage();
        $voyage
            ->setTrajet($trajet)
            ->setCodevoyage($trajet->getCodetrajet() . '-V1')
            ->setProvenance($trajet->getProvenance())
            ->setDestination($trajet->getDestination())
            ->setDatedebut($trajet->getDatedebut())
            ->setIdentreprise($entrepriseId)
            ->setCreatedBy($user->getId()); /*
                - '->setDatefin()' au 'patch'
            */

        if($data->carId !== null) {
            $car = $this->carRepository->findOneBy([
                'id' => $data->carId,
                'identreprise' => $entrepriseId,
                'deletedAt' => null
            ]);
            if(!$car) {
                throw new BadRequestHttpException('Car invalide pour cette entreprise');
            }
            $voyage
                ->setCar($car)
                ->setPlacesTotal($car->getNbrSiege());
        } else {
            $voyage->setPlacesTotal(0); /*
                - Pour l'affecter plus tard
            */
        }
        $voyage->setPlacesOccupees(0);

        $this->em->persist($voyage);

        return $this->processor->process($trajet, $operation, $uriVariables, $context);
    }

    private function generateCodeTrajet(int $entrepriseId): string
    {
        $count = $this->em->getRepository(Trajet::class)->count([
            'identreprise' => $entrepriseId,
            'deletedAt' => null
        ]);
        return sprintf('TR-%d-%04d', $entrepriseId, $count + 1);
    }
}
