<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Controller\TicketPrintController;
use App\Entity\Interface\EntrepriseOwnedInterface;
use App\Repository\TicketRepository;
use App\State\SoftDeleteProcessor;
use App\State\TicketProcessor;
use App\Validator\UniquePerEntreprise;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
#[UniquePerEntreprise(
    fields: ['voyage', 'numero'],
    message: 'L\'enregistrement existe déjà pour votre entreprise'
)]
#[ApiResource(
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    normalizationContext: ['groups' => ['read:Ticket']],
    denormalizationContext: ['groups' => ['write:Ticket']],
    paginationEnabled: false,
    order: ['createdAt' => 'DESC'],
    operations: [
        new GetCollection(
            security: "is_granted('VOIR', 'Ticket')",
            openapi: new Operation(
                summary: 'Liste des tickets',
                description: 'Permet de voir la liste des tickets',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            security: "is_granted('VOIR', object)",
            requirements: ['id' => '\d+'],
            openapi: new Operation(
                summary: 'Le ticket',
                description: 'Permet de voir un ticket',
                security: [['bearerAuth' => []]]
            )
        ),
        new Post(
            security: "is_granted('CREER', 'Tarif')",
            processor: TicketProcessor::class,
            openapi: new Operation(
                summary: 'Création du ticket',
                description: 'Permet de créer un ticket',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            security: "is_granted('VOIR', object)", // 'IMPRIMER'
            uriTemplate: '/tickets/{id}/print',
            requirements: ['id' => '\d+'],
            controller: TicketPrintController::class,
            read: true,
            openapi: new Operation(
                summary: 'Le ticket',
                description: 'Permet de voir un ticket',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('SUPPRIMER', object)",
            uriTemplate: '/tickets/{id}/remove',
            requirements: ['id' => '\d+'],
            input: false,
            processor: SoftDeleteProcessor::class,
            openapi: new Operation(
                summary: 'Mise en corbeille du ticket',
                description: 'Permet de mettre un ticket en corbeille',
                security: [['bearerAuth' => []]]
            )
        ),
    ],
    openapi: new Operation(
        security: [['bearerAuth' => []]]
    )
)]
class Ticket extends EntityBase implements EntrepriseOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)] // Sans 'onDelete: 'CASCADE' pour l'historique
    #[Groups(['read:Ticket', 'write:Ticket'])]
    private ?Voyage $voyage = null;

    #[ORM\Column(nullable: true)]
    private ?int $identreprise = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:Voyage', 'read:Ticket', 'write:Ticket'])]
    private ?string $nomclient = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:Voyage', 'read:Ticket', 'write:Ticket'])]
    private ?string $contactclient = null;

    #[ORM\Column]
    #[Groups(['read:Voyage', 'read:Ticket', 'write:Ticket'])]
    private ?int $numero = null;

    #[ORM\Column]
    #[Groups(['read:Voyage', 'read:Ticket', 'write:Ticket'])]
    private ?int $prix = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = 'VALIDE'; // 'enum'

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVoyage(): ?Voyage
    {
        return $this->voyage;
    }

    public function setVoyage(?Voyage $voyage): static
    {
        $this->voyage = $voyage;

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

    public function getNomclient(): ?string
    {
        return $this->nomclient;
    }

    public function setNomclient(string $nomclient): static
    {
        $this->nomclient = $nomclient;

        return $this;
    }

    public function getContactclient(): ?string
    {
        return $this->contactclient;
    }

    public function setContactclient(string $contactclient): static
    {
        $this->contactclient = $contactclient;

        return $this;
    }

    public function getNumero(): ?int
    {
        return $this->numero;
    }

    public function setNumero(int $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    public function getPrix(): ?int
    {
        return $this->prix;
    }

    public function setPrix(int $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }
}
