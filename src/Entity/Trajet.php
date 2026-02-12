<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Entity\Dto\TrajetInput;
use App\Entity\Interface\EntrepriseOwnedInterface;
use App\Repository\TrajetRepository;
use App\State\SoftDeleteProcessor;
use App\State\TrajetProcessor;
use App\State\UpdatedbyProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: TrajetRepository::class)]
#[UniqueEntity(
    fields: ['provenance', 'destination', 'identreprise'],
    message: 'L\'enregistrement existe déjà pour votre entreprise'
)]
#[ORM\UniqueConstraint(columns: ['provenance', 'destination', 'identreprise'])]
#[ApiResource(
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    normalizationContext: ['groups' => ['read:Trajet']],
    denormalizationContext: ['groups' => ['write:Trajet']],
    paginationEnabled: false,
    order: ['createdAt' => 'DESC'],
    operations: [
        new GetCollection(
            security: "is_granted('VOIR', 'Trajet')",
            openapi: new Operation(
                summary: 'Liste des trajets',
                description: 'Permet de voir la liste des trajets',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            security: "is_granted('VOIR', object)",
            requirements: ['id' => '\d+'],
            openapi: new Operation(
                summary: 'Le trajet',
                description: 'Permet de voir un trajet',
                security: [['bearerAuth' => []]]
            )
        ),
        new Post(
            security: "is_granted('CREER', 'Trajet')",
            input: TrajetInput::class,
            processor: TrajetProcessor::class, // On l'a fais à cause du 'codetrajet'
            denormalizationContext: ['groups' => ['write:TrajetInput']],
            openapi: new Operation(
                summary: 'Permet de créer un tarif',
                description: 'Création du tarif',
                security: [['bearerAuth' => []]]
            )
        ),
        /*
            new Patch(
                security: "is_granted('MODIFIER', object)",
                requirements: ['id' => '\d+'],
                processor: UpdatedbyProcessor::class,
                openapi: new Operation(
                    summary: 'Modification du trajet',
                    description: 'Permet de modifier un trajet',
                    security: [['bearerAuth' => []]]
                )
            ),
        */
        new Patch(
            security: "is_granted('SUPPRIMER', object)",
            uriTemplate: '/trajets/{id}/remove',
            requirements: ['id' => '\d+'],
            input: false,
            processor: SoftDeleteProcessor::class,
            openapi: new Operation(
                summary: 'Mise en corbeille du trajet',
                description: 'Permet de mettre un trajet en corbeille',
                security: [['bearerAuth' => []]]
            )
        ),
    ],
    openapi: new Operation(
        security: [['bearerAuth' => []]]
    )
)]
class Trajet extends EntityBase implements EntrepriseOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:Trajet'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:Trajet'])]
    private ?string $codetrajet = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:Trajet'])]
    private ?string $provenance = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:Trajet'])]
    private ?string $destination = null;

    /*
        #[ORM\Column]
        private ?int $idprovenance = null;

        #[ORM\Column]
        private ?int $iddestination = null;
    */

    #[ORM\Column]
    #[Groups(['read:Trajet', 'write:Trajet'])]
    private ?\DateTimeImmutable $datedebut = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['read:Trajet', 'write:Trajet'])]
    private ?\DateTimeImmutable $datefin = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['read:Trajet'])]
    private ?int $orderindex = null;

    #[ORM\ManyToOne(inversedBy: 'trajets')]
    #[ORM\JoinColumn(nullable: false)] // onDelete: 'RESTRICT'
    #[Groups(['read:Trajet'])]
    private ?Tarif $tarif = null;

    #[ORM\Column(nullable: true)]
    private ?int $identreprise = null;

    /**
     * @var Collection<int, Voyage>
     */
    #[ORM\OneToMany(targetEntity: Voyage::class, mappedBy: 'trajet')]
    #[Groups(['read:Trajet'])]
    private Collection $voyages;

    public function __construct()
    {
        $this->voyages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodetrajet(): ?string
    {
        return $this->codetrajet;
    }

    public function setCodetrajet(string $codetrajet): static
    {
        $this->codetrajet = $codetrajet;

        return $this;
    }

    public function getProvenance(): ?string
    {
        return $this->provenance;
    }

    public function setProvenance(string $provenance): static
    {
        $this->provenance = $provenance;

        return $this;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(string $destination): static
    {
        $this->destination = $destination;

        return $this;
    }

    /*
        public function getIdprovenance(): ?int
        {
            return $this->idprovenance;
        }

        public function setIdprovenance(int $idprovenance): static
        {
            $this->idprovenance = $idprovenance;

            return $this;
        }

        public function getIddestination(): ?int
        {
            return $this->iddestination;
        }

        public function setIddestination(int $iddestination): static
        {
            $this->iddestination = $iddestination;

            return $this;
        }
    */

    public function getDatedebut(): ?\DateTimeImmutable
    {
        return $this->datedebut;
    }

    public function setDatedebut(\DateTimeImmutable $datedebut): static
    {
        $this->datedebut = $datedebut;

        return $this;
    }

    public function getDatefin(): ?\DateTimeImmutable
    {
        return $this->datefin;
    }

    public function setDatefin(\DateTimeImmutable $datefin): static
    {
        $this->datefin = $datefin;

        return $this;
    }

    public function getOrderindex(): ?int
    {
        return $this->orderindex;
    }

    public function setOrderindex(int $orderindex): static
    {
        $this->orderindex = $orderindex;

        return $this;
    }

    public function getTarif(): ?Tarif
    {
        return $this->tarif;
    }

    public function setTarif(?Tarif $tarif): static
    {
        $this->tarif = $tarif;

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

    /**
     * @return Collection<int, Voyage>
     */
    public function getVoyages(): Collection
    {
        return $this->voyages;
    }

    public function addVoyage(Voyage $voyage): static
    {
        if (!$this->voyages->contains($voyage)) {
            $this->voyages->add($voyage);
            $voyage->setTrajet($this);
        }

        return $this;
    }

    public function removeVoyage(Voyage $voyage): static
    {
        if ($this->voyages->removeElement($voyage)) {
            // set the owning side to null (unless already changed)
            if ($voyage->getTrajet() === $this) {
                $voyage->setTrajet(null);
            }
        }

        return $this;
    }
}
