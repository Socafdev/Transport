<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Enum\Referencetype;
use App\Domain\Enum\Typemouvement;
use App\Domain\Service\StockmouvementService;
use App\Entity\Depannage;
use App\Entity\Detaildepannage;
use App\Entity\Dto\DepannageInput;
use App\Entity\User;
use App\Repository\CarRepository;
use App\Repository\PieceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DepannageProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private CarRepository $carRepository,
        private PieceRepository $pieceRepository,
        private EntityManagerInterface $em,
        private StockmouvementService $stockmouvementService
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var DepannageInput $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();
        $entrepriseId = $user->getEntrepriseid();

        $car = $this->carRepository->findOneBy([
            'id' => $data->car,
            'identreprise' => $entrepriseId,
            'deletedAt' => null
        ]);

        if(!$car) {
            throw new NotFoundHttpException('Référence invalide');
        }

        $depannage = new Depannage();
        $depannage
            ->setLieudepannage($data->lieudepannage)
            ->setDescription($data->description)
            ->setIdentreprise($entrepriseId)
            ->setCar($car)
            ->setCreatedBy($user->getId())
            ->setDatedepannage(new \DateTimeImmutable()); // Ou le reçevoir via le 'input'
        $this->em->persist($depannage);
        $this->em->flush(); /*
            - Va être nécessaire pour avoir l'id
        */

        $ids = array_map(fn($d) => $d['piece'], $data->details);
        if(count($ids) !== count(array_unique($ids))) {
            throw new BadRequestHttpException('Une pièce est en doublon dans le dépannage');
        }

        $total = 0;
        foreach($data->details as $detailInput) {
            $piece = $this->pieceRepository->findOneBy([
                'id' => $detailInput['piece'],
                'identreprise' => $entrepriseId,
                'deletedAt' => null
            ]);

            if(!$piece) {
                throw new NotFoundHttpException('Référence invalide');
            }

            $prixunitaire = $detailInput['prixunitaire'] ?? $piece->getPrixUnitaire();
            $quantite = $detailInput['quantite'];

            if($quantite <= 0) { // Vu qu'on n'a de pas règle dessus
                throw new BadRequestHttpException('Quantité invalide');
            }
            if ($prixunitaire <= 0) {
                throw new BadRequestHttpException('Prix unitaire invalide');
            }

            $detail = new Detaildepannage();
            $detail
                ->setPiece($piece)
                ->setQuantite($quantite)
                ->setDepannage($depannage)
                ->setPrixunitaire($prixunitaire);
            $this->em->persist($detail);

            # On crée un mouvement stock
            $this->stockmouvementService->createMovement(
                $piece,
                Typemouvement::SORTIE->value,
                $quantite,
                Referencetype::DEPANNAGE->value,
                $depannage->getId(),
                $entrepriseId
            );

            $total += $prixunitaire * $quantite; // Le cout total du dépannage
        }
        $depannage->setCoutTotal($total);

        return $this->processor->process($depannage, $operation, $uriVariables, $context);
    }
}
