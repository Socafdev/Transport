<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Ticket;
use App\Entity\User;
use App\Entity\Voyage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TicketProcessor implements ProcessorInterface
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
        /** @var Ticket $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();
        $entrepriseId = $user->getEntrepriseid();

        $data
            ->setIdentreprise($entrepriseId)
            ->setCreatedBy($user->getId());

        $voyage = $data->getVoyage();
        if($voyage->getPlacesOccupees() >= $voyage->getPlacesTotal()) {
            throw new BadRequestHttpException('Voyage complet');
        }
        /*
            if($voyage->getStatut() === StatutVoyage::TERMINE) {
                throw new BadRequestHttpException('Voyage terminé');
            }
        */
        if(!$voyage->getCar()) {
            throw new BadRequestHttpException('Aucun véhicule affecté');
        }

        $exist = $this->em->getRepository(Ticket::class)->findOneBy([ # On vérifie que la place est unique
            'voyage' => $voyage,
            'numero' => $data->getNumero(),
            'identreprise' => $entrepriseId,
            'deletedAt' => null
            // 'statut' => 'RESERVE'
        ]);

        if($exist) {
            throw new BadRequestHttpException('Place déjà occupée');
        }

        $trajet = $voyage->getTrajet();
        $tarif = $trajet->getTarif();
        if(!$tarif) {
            throw new BadRequestHttpException('Tarif introuvable');
        }
        $prix =  $tarif->getMontant(); // Le prix '=' au tarif du trajet

        $data
            ->setVoyage($voyage)
            ->setNomclient($data->getNomclient())
            ->setContactclient($data->getContactclient())
            ->setNumero($data->getNumero())
            ->setPrix($prix); /*
                - ->setStatut('RESERVE')
            */
        $voyage->setPlacesOccupees($voyage->getPlacesOccupees() + 1);
        /*
            $ticket->setNumero($this->generateNumero($entrepriseId)); -- Prendre en compte le numéro du voyage
            private function generateNumero(int $entrepriseId): string
            {
                do {
                    $numero = 'TCK-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
                    $exists = $this->em->getRepository(Ticket::class)->findOneBy([
                        'numero' => $numero,
                        'identreprise' => $entrepriseId
                    ]);
                } while ($exists);
                return $numero;
            }
        */
        return $this->processor->process($data, $operation, $uriVariables, $context);
    }

    private function generateCode(int $entrepriseId): string
    {
        return 'TCK-' . $entrepriseId . '-' . strtoupper(uniqid());
    }
}
