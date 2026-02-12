<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Enum\Referencetype;
use App\Domain\Enum\Typemouvement;
use App\Domain\Service\StockmouvementService;
use App\Entity\Approvisionnement;
use App\Entity\Detailapprovisionnement;
use App\Entity\Dto\ApprovisionnementInput;
use App\Entity\User;
use App\Repository\FournisseurRepository;
use App\Repository\PieceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApprovisionnementProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private EntityManagerInterface $em,
        private FournisseurRepository $fournisseurRepository,
        private PieceRepository $pieceRepository,
        private StockmouvementService $stockmouvementService
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var ApprovisionnementInput $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();
        $entrepriseId = $user->getEntrepriseid();
        $fournisseur = $this->fournisseurRepository->findOneBy([
            'id' => $data->fournisseur,
            'identreprise' => $entrepriseId,
            'deletedAt' => null
        ]);

        if(!$fournisseur) {
            throw new NotFoundHttpException('Référence invalide');
        }

        $approvisonnement = new Approvisionnement();
        $approvisonnement
            ->setFournisseur($fournisseur)
            ->setIdentreprise($entrepriseId)
            ->setCreatedBy($user->getId())
            ->setDateappro(new \DateTimeImmutable());
        $this->em->persist($approvisonnement);
        $this->em->flush(); /*
            - Va être nécessaire pour avoir l'id
        */

        # $this->em->wrapInTransaction(function () use ($fournisseur, $entrepriseId, $user, $data, $approvisonnement) -- Au lieu de '->beginTransaction()' pour éviter les incohérences {

        $ids = array_map(fn($d) => $d['piece'], $data->details);
        if(count($ids) !== count(array_unique($ids))) { // Une validation anti-doublon de pièce
            throw new BadRequestHttpException('Une pièce est en doublon dans le dépannage');
        }
        /* -- Ou
            $pieceIds = [];
            foreach($data->details as $detailInput) {
                if(in_array($detailInput['piece'], $pieceIds, true)) {
                    throw new BadRequestHttpException(
                        sprintf('La pièce %d est en doublon dans ce dépannage.', $detailInput['piece'])
                    );
                }
                $pieceIds[] = $detailInput['piece'];
            }
        */
        foreach($data->details as $detailInput) {
            $piece = $this->pieceRepository->findOneBy([
                'id' => $detailInput['piece'],
                'identreprise' => $entrepriseId,
                'deletedAt' => null
            ]); /*
                - '$detailInput->piece' si on utilise 'DetailapprovisionnementInput'
            */
            if(!$piece) {
                throw new NotFoundHttpException('Référence invalide');
            }

            $quantite = (int)$detailInput['quantite'];
            $prixUnitaire = (int)$detailInput['prixunitaire'];

            if($quantite <= 0) {
                throw new BadRequestHttpException('Quantité invalide');
            }
            if ($prixUnitaire <= 0) {
                throw new BadRequestHttpException('Prix unitaire invalide');
            }
            $montantTotal = $quantite * $prixUnitaire;

            $detailapprovisonnement = new Detailapprovisionnement();
            $detailapprovisonnement
                ->setApprovisionnement($approvisonnement)
                ->setPiece($piece)
                ->setQuantite($quantite)
                ->setPrixunitaire($prixUnitaire)
                ->setCouttotal($montantTotal);

            $this->em->persist($detailapprovisonnement);

            # On crée un mouvement stock
            $this->stockmouvementService->createMovement(
                $piece,
                Typemouvement::ENTREE->value,
                $quantite,
                Referencetype::APPROVISIONNEMENT->value,
                $approvisonnement->getId(),
                $entrepriseId
            );
        }

        return $this->processor->process($approvisonnement, $operation, $uriVariables, $context); /*
            - Pas de '->flush()' vu qu'on a le 'process'
        */
    }
}
