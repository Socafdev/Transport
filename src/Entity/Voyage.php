<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Entity\Dto\AffectcarInput;
use App\Entity\Dto\AffectpersonnelInput;
use App\Entity\Interface\EntrepriseOwnedInterface;
use App\Repository\VoyageRepository;
use App\State\AffectcarProcessor;
use App\State\AffectpersonnelProcessor;
use App\State\SoftDeleteProcessor;
use App\State\UpdatedbyProcessor;
use App\State\VoyageProcessor;
use App\Validator\UniquePerEntreprise;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: VoyageRepository::class)]
#[UniquePerEntreprise(
    fields: ['provenance', 'destination', 'datedebut', 'trajet'],
    message: 'L\'enregistrement existe déjà pour votre entreprise'
)]
#[ORM\UniqueConstraint(columns: ['codevoyage', 'identreprise'])]
#[ApiResource(
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    normalizationContext: ['groups' => ['read:Voyage']],
    denormalizationContext: ['groups' => ['write:Voyage']],
    order: ['createdAt' => 'DESC'],
    operations: [
        new GetCollection(
            security: "is_granted('VOIR', 'Voyage')",
            openapi: new Operation(
                summary: 'La liste des voyages',
                description: 'Permet de voir la liste des voyages',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            security: "is_granted('VOIR', object)",
            requirements: ['id' => '\d+'],
            openapi: new Operation(
                summary: 'Le voyage',
                description: 'Permet de voir un voyage',
                security: [['bearerAuth' => []]]
            )
        ),
        new Post(
            security: "is_granted('CREER', 'Voyage')",
            processor: VoyageProcessor::class,
            openapi: new Operation(
                summary: 'Permet de créer un voyage',
                description: 'Création du voyage',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('MODIFIER', object)",
            requirements: ['id' => '\d+'],
            processor: VoyageProcessor::class,
            denormalizationContext: ['groups' => ['write:Voyage:update']],
            openapi: new Operation(
                summary: 'Modification du voyage',
                description: 'Permet de modifier un voyage',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('SUPPRIMER', object)",
            uriTemplate: '/voyages/{id}/remove',
            requirements: ['id' => '\d+'],
            input: false,
            processor: SoftDeleteProcessor::class,
            openapi: new Operation(
                summary: 'Mise en corbeille du voyage',
                description: 'Permet de mettre un voyage en corbeille',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('MODIFIER', object)",
            requirements: ['id' => '\d+'],
            uriTemplate: '/voyages/{id}/affect/car',
            input: AffectcarInput::class,
            processor: AffectcarProcessor::class,
            denormalizationContext: ['groups' => ['write:AffectcarInput']],
            openapi: new Operation(
                summary: 'Affectation d\'un car à un voyage',
                description: 'Permet d\'affecter un car à un voyage',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('MODIFIER', object)",
            requirements: ['id' => '\d+'],
            uriTemplate: '/voyages/{id}/affect/personnel',
            input: AffectpersonnelInput::class,
            processor: AffectpersonnelProcessor::class,
            name: 'Affect-voyage',
            denormalizationContext: ['groups' => ['write:AffectpersonnelInput']],
            openapi: new Operation(
                summary: 'Affectation d\'un personnel à un voyage',
                description: 'Permet d\'affecter un personnel à un voyage',
                security: [['bearerAuth' => []]]
            )
        )
    ],
    openapi: new Operation(
        security: [['bearerAuth' => []]]
    )
)]
class Voyage extends EntityBase implements EntrepriseOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:Voyage'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:Voyage'])]
    private ?string $codevoyage = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:Voyage', 'write:Voyage', 'read:Trajet'])]
    private ?string $provenance = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:Voyage', 'write:Voyage', 'read:Trajet'])]
    private ?string $destination = null;

    /*
        #[ORM\Column]
        private ?int $idprovenance = null;

        #[ORM\Column]
        private ?int $iddestination = null;
    */

    #[ORM\Column]
    #[Groups(['read:Voyage', 'read:Trajet', 'write:Voyage'])]
    private ?\DateTimeImmutable $datedebut = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['read:Voyage', 'read:Trajet', 'write:Voyage:update'])]
    private ?\DateTimeImmutable $datefin = null;

    #[ORM\ManyToOne(inversedBy: 'voyages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] // --
    #[Groups(['read:Voyage', 'write:Voyage'])]
    private ?Trajet $trajet = null;

    #[ORM\ManyToOne(inversedBy: 'voyages')]
    // #[ORM\JoinColumn(nullable: false)]  -- onDelete: 'RESTRICT'
    #[Groups(['read:Voyage', 'read:Trajet', 'write:Voyage', 'write:Voyage:update'])] // 'optionel' ou l'ajouter à partir d'un 'input' et '..update' car au cours d'un voyage on peut changer un car
    private ?Car $car = null;

    #[ORM\Column(nullable: true)]
    private ?int $identreprise = null;

    /**
     * @var Collection<int, Detailpersonnel>
     */
    #[ORM\OneToMany(targetEntity: Detailpersonnel::class, mappedBy: 'voyage')]
    #[Groups(['read:Voyage', 'read:Trajet'])]
    private Collection $detailpersonnels;

    /**
     * @var Collection<int, Ticket>
     */
    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'voyage')]
    #[Groups(['read:Voyage'])]
    private Collection $tickets;

    #[ORM\Column(nullable: true)]
    #[Groups(['read:Voyage'])]
    private ?int $places_total = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['read:Voyage'])]
    private ?int $places_occupees = null;

    public function __construct()
    {
        $this->detailpersonnels = new ArrayCollection();
        $this->tickets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodevoyage(): ?string
    {
        return $this->codevoyage;
    }

    public function setCodevoyage(string $codevoyage): static
    {
        $this->codevoyage = $codevoyage;

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

    public function getTrajet(): ?Trajet
    {
        return $this->trajet;
    }

    public function setTrajet(?Trajet $trajet): static
    {
        $this->trajet = $trajet;

        return $this;
    }

    public function getCar(): ?Car
    {
        return $this->car;
    }

    public function setCar(?Car $car): static
    {
        $this->car = $car;

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
     * @return Collection<int, Detailpersonnel>
     */
    public function getDetailpersonnels(): Collection
    {
        return $this->detailpersonnels;
    }

    public function addDetailpersonnel(Detailpersonnel $detailpersonnel): static
    {
        if (!$this->detailpersonnels->contains($detailpersonnel)) {
            $this->detailpersonnels->add($detailpersonnel);
            $detailpersonnel->setVoyage($this);
        }

        return $this;
    }

    public function removeDetailpersonnel(Detailpersonnel $detailpersonnel): static
    {
        if ($this->detailpersonnels->removeElement($detailpersonnel)) {
            // set the owning side to null (unless already changed)
            if ($detailpersonnel->getVoyage() === $this) {
                $detailpersonnel->setVoyage(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Ticket>
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function addTicket(Ticket $ticket): static
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets->add($ticket);
            $ticket->setVoyage($this);
        }

        return $this;
    }

    public function removeTicket(Ticket $ticket): static
    {
        if ($this->tickets->removeElement($ticket)) {
            // set the owning side to null (unless already changed)
            if ($ticket->getVoyage() === $this) {
                $ticket->setVoyage(null);
            }
        }

        return $this;
    }

    public function getPlacesTotal(): ?int
    {
        return $this->places_total;
    }

    public function setPlacesTotal(?int $places_total): static
    {
        $this->places_total = $places_total;

        return $this;
    }

    public function getPlacesOccupees(): ?int
    {
        return $this->places_occupees;
    }

    public function setPlacesOccupees(?int $places_occupees): static
    {
        $this->places_occupees = $places_occupees;

        return $this;
    }
}
