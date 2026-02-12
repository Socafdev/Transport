<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use App\Entity\Interface\EntrepriseOwnedInterface;
use App\Repository\InventaireRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: InventaireRepository::class)]
#[ApiResource(
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    normalizationContext: ['groups' => ['read:Inventaire']],
    order: ['createdAt' => 'DESC'],
    operations: [
        new GetCollection(
            security: "is_granted('VOIR', 'Inventaire')",
            openapi: new Operation(
                summary: 'La liste des inventaires',
                description: 'Permet de voir la liste des inventaires',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            security: "is_granted('VOIR', object)",
            requirements: ['id' => '\d+'],
            openapi: new Operation(
                summary: 'L\'inventaire',
                description: 'Permet de voir un inventaire',
                security: [['bearerAuth' => []]]
            )
        ) /*
        - On n'a pas de 'post' vu qu'on ne doit pas manipuler le stock    
    */
    ],
    openapi: new Operation(
        security: [['bearerAuth' => []]]
    )
)]
class Inventaire extends EntityBase implements EntrepriseOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'inventaires')]
    #[ORM\JoinColumn(nullable: false)] // onDelete: 'RESTRICT'
    #[Groups(['read:Inventaire'])]
    private ?Piece $piece = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:Inventaire'])]
    private ?string $typemouvement = null; // ENTREE, SORTIE, AJUSTEMENT - enum 'Typemouvement'

    #[ORM\Column]
    #[Groups(['read:Inventaire'])]
    private ?int $quantite = null;

    #[ORM\Column]
    #[Groups(['read:Inventaire'])]
    private ?\DateTimeImmutable $datemouvement = null;

    #[ORM\Column(nullable: true)]
    private ?int $identreprise = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:Inventaire'])]
    private ?string $reference_type = null; // APPROVISIONNEMENT, DEPANNAGE, AJUSTEMENT - enum 'Referencetype'

    #[ORM\Column(nullable: true)]
    #[Groups(['read:Inventaire'])]
    private ?int $referenceid = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPiece(): ?Piece
    {
        return $this->piece;
    }

    public function setPiece(?Piece $piece): static
    {
        $this->piece = $piece;

        return $this;
    }

    public function getTypemouvement(): ?string
    {
        return $this->typemouvement;
    }

    public function setTypemouvement(string $typemouvement): static
    {
        $this->typemouvement = $typemouvement;

        return $this;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getDatemouvement(): ?\DateTimeImmutable
    {
        return $this->datemouvement;
    }

    public function setDatemouvement(\DateTimeImmutable $datemouvement): static
    {
        $this->datemouvement = $datemouvement;

        return $this;
    }

    public function getIdentreprise(): ?int
    {
        return $this->identreprise;
    }

    public function setIdentreprise(?int $identreprise): static
    {
        $this->identreprise = $identreprise;

        return $this;
    }

    public function getReferenceType(): ?string
    {
        return $this->reference_type;
    }

    public function setReferenceType(string $reference_type): static
    {
        $this->reference_type = $reference_type;

        return $this;
    }

    public function getReferenceid(): ?int
    {
        return $this->referenceid;
    }

    public function setReferenceid(?int $referenceid): static
    {
        $this->referenceid = $referenceid;

        return $this;
    }
}
