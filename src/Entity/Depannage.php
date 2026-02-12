<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\RequestBody;
use App\Entity\Dto\AffectpersonnelInput;
use App\Entity\Dto\DepannageInput;
use App\Entity\Interface\EntrepriseOwnedInterface;
use App\Repository\DepannageRepository;
use App\State\AffectpersonnelProcessor;
use App\State\DepannageProcessor;
use ArrayObject;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: DepannageRepository::class)]
#[ApiResource(
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    normalizationContext: ['groups' => ['read:Depannage']],
    order: ['createdAt' => 'DESC'],
    operations: [
        new GetCollection(
            security: "is_granted('VOIR', 'Depannage')",
            openapi: new Operation(
                summary: 'La liste des dépannages',
                description: 'Permet de voir la liste des dépannages',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            security: "is_granted('VOIR', object)",
            requirements: ['id' => '\d+'],
            openapi: new Operation(
                summary: 'Le dépannage',
                description: 'Permet de voir un dépannage',
                security: [['bearerAuth' => []]]
            )
        ),
        new Post(
            security: "is_granted('CREER', 'Depannage')",
            input: DepannageInput::class,
            processor: DepannageProcessor::class,
            denormalizationContext: ['groups' => ['write:DepannageInput']],
            openapi: new Operation(
                summary: 'Créer un dépannage',
                description: 'Permet de créer un dépannage',
                security: [['bearerAuth' => []]],
                requestBody: new RequestBody(
                    required: true,
                    content: new ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'lieudepannage' => [
                                        'type' => 'string',
                                        'example' => 'Abidjan'
                                    ],
                                    'description' => [
                                        'type' => 'string'
                                    ],
                                    'details' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'piece' => [
                                                    'type' => 'int',
                                                    'example' => '2'
                                                ],
                                                'quantite' => [
                                                    'type' => 'int',
                                                    'example' => '10'
                                                ]
                                            ]
                                        ]
                                    ],
                                    'car' => [
                                        'type' => 'int',
                                        'example' => 1
                                    ]
                                ]
                            ]
                        ]
                    ])
                )
            )
        ),
        new Patch(
            security: "is_granted('MODIFIER', object)",
            requirements: ['id' => '\d+'],
            uriTemplate: '/depannages/{id}/affect/personnel',
            input: AffectpersonnelInput::class,
            processor: AffectpersonnelProcessor::class,
            name: 'Affect-depannage',
            denormalizationContext: ['groups' => ['write:AffectpersonnelInput']],
            openapi: new Operation(
                summary: 'Affectation d\'un personnel à un dépannage',
                description: 'Permet d\'affecter un personnel à un dépannage',
                security: [['bearerAuth' => []]]
            )
        )
    ],
    openapi: new Operation(
        security: [['bearerAuth' => []]]
    )
)]
/*
    #[ApiFilter(SearchFilter::class, properties: [
        'car.id' => 'exact',
        'statut' => 'exact'
    ])]
*/
class Depannage extends EntityBase implements EntrepriseOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['read:Depannage'])]
    private ?\DateTimeImmutable $datedepannage = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:Depannage'])]
    private ?string $lieudepannage = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['read:Depannage'])]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?int $identreprise = null;

    /**
     * @var Collection<int, Detaildepannage>
     */
    #[ORM\OneToMany(targetEntity: Detaildepannage::class, mappedBy: 'depannage')]
    #[Groups(['read:Depannage'])]
    private Collection $detaildepannages;

    /**
     * @var Collection<int, Detailpersonnel>
     */
    #[ORM\OneToMany(targetEntity: Detailpersonnel::class, mappedBy: 'depannage')]
    #[Groups(['read:Depannage'])]
    private Collection $detailpersonnels;

    #[ORM\ManyToOne(inversedBy: 'depannages')]
    #[ORM\JoinColumn(nullable: false)] // onDelete: 'RESTRICT'
    #[Groups(['read:Depannage'])]
    private ?Car $car = null;

    #[ORM\Column(nullable: true)]
    private ?int $couttotal = null;

    public function __construct()
    {
        $this->detaildepannages = new ArrayCollection();
        $this->detailpersonnels = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDatedepannage(): ?\DateTimeImmutable
    {
        return $this->datedepannage;
    }

    public function setDatedepannage(\DateTimeImmutable $datedepannage): static
    {
        $this->datedepannage = $datedepannage;

        return $this;
    }

    public function getLieudepannage(): ?string
    {
        return $this->lieudepannage;
    }

    public function setLieudepannage(string $lieudepannage): static
    {
        $this->lieudepannage = $lieudepannage;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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
     * @return Collection<int, Detaildepannage>
     */
    public function getDetaildepannages(): Collection
    {
        return $this->detaildepannages;
    }

    public function addDetaildepannage(Detaildepannage $detaildepannage): static
    {
        if (!$this->detaildepannages->contains($detaildepannage)) {
            $this->detaildepannages->add($detaildepannage);
            $detaildepannage->setDepannage($this);
        }

        return $this;
    }

    public function removeDetaildepannage(Detaildepannage $detaildepannage): static
    {
        if ($this->detaildepannages->removeElement($detaildepannage)) {
            // set the owning side to null (unless already changed)
            if ($detaildepannage->getDepannage() === $this) {
                $detaildepannage->setDepannage(null);
            }
        }

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
            $detailpersonnel->setDepannage($this);
        }

        return $this;
    }

    public function removeDetailpersonnel(Detailpersonnel $detailpersonnel): static
    {
        if ($this->detailpersonnels->removeElement($detailpersonnel)) {
            // set the owning side to null (unless already changed)
            if ($detailpersonnel->getDepannage() === $this) {
                $detailpersonnel->setDepannage(null);
            }
        }

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

    public function getCouttotal(): ?int
    {
        return $this->couttotal;
    }

    public function setCouttotal(?int $couttotal): static
    {
        $this->couttotal = $couttotal;

        return $this;
    }
}
