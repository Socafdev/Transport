<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Entity\Dto\RegisterInput;
use App\Repository\UserRepository;
use App\State\RegisterProcessor;
use App\State\UserProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ApiResource(
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    normalizationContext: ['groups' => ['read:User']],
    denormalizationContext: ['groups' => ['write:User']],
    paginationEnabled: false,
    operations: [
        new Post(
            name: 'Register',
            uriTemplate: '/register',
            input: RegisterInput::class,
            processor: RegisterProcessor::class, /*
                - Va contenir la logique de l'inscription et ne fonctionne pas sur 'getCollection' et 'get', va traiter l'objet avant persistance
            */
            denormalizationContext: ['groups' => ['write:Register']], /*
                - Pour éviter qu'il utilise 'write:User' sinon il ne vas pas remplir mon 'input' ou utiliser un groupe de denormalization sur le 'RegisterInput' ce qui va le permettre de documenté
            */
            status: Response::HTTP_CREATED,
            openapi: new Operation(
                summary: 'Permet à un utilisateur de créer une entreprise et devenir administrateur',
                description: 'Crée un nouvel utilisateur et son entreprise'
            )
        ),
        new GetCollection(
            security: "is_granted('VOIR', 'User') or is_granted('ROLE_SUPER_ADMIN')", /*
                - Pour le filtre du 'entreprise' on l'a fais dans 'UserEntrepriseExtension'
            */
            openapi: new Operation(
                summary: 'La liste des utilisateurs',
                description: 'Permet de voir la liste des utilisateurs',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            security: "is_granted('VOIR', object) or is_granted('ROLE_SUPER_ADMIN')",
            requirements: ['id' => '\d+'], /*
                - Pour le filtre du 'entreprise' on l'a fais dans 'UserEntrepriseExtension'
            */
            openapi: new Operation(
                summary: 'L\'utilisateur',
                description: 'Permet de voir un utilisateur',
                security: [['bearerAuth' => []]]
            )
        ),
        new Post(
            security: "is_granted('CREER', object)",
            processor: UserProcessor::class,
            openapi: new Operation(
                summary: 'Créer un utilisateur',
                description: 'Permet de créer un utilisateur',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('MODIFIER', object)", /*
                - On bloque l'accès au super admin car c'est à l'admin de modifier son utilisateur par cause de 'userRoles'
            */
            requirements: ['id' => '\d+'], /*
                - Pour le filtre du 'entreprise' on l'a fais dans 'UserEntrepriseExtension'
            */
            processor: UserProcessor::class,
            openapi: new Operation(
                summary: 'Modifier un utilisateur',
                description: 'Permet de modifier un utilisateur',
                security: [['bearerAuth' => []]]
            )
        )
        // Les autres routes sont dans 'OpenApiFactory' --
    ]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface, JWTUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:User'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank()]
    #[Assert\Email()]
    #[Groups(['read:User', 'write:User'])]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[Groups(['read:User'])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank()]
    #[Assert\Length(minMessage: 'Le nom est obligatoire', min: 1)]
    #[Groups(['read:User', 'write:User'])]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:User', 'write:User'])]
    #[Assert\NotBlank()]
    #[Assert\Length(minMessage: 'Le prenom est obligatoire', min: 1)] // 'message'..
    private ?string $prenom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $etat = true;

    #[ORM\ManyToOne(inversedBy: 'users')]
    # #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")] -- !!
    private ?Entreprise $entreprise = null;

    /**
     * @var Collection<int, UserRole>
     */
    #[ORM\OneToMany(targetEntity: UserRole::class, mappedBy: 'usere', cascade: ['persist'])] // 'persist' pour persister les 'userRoles'
    #[Groups(['write:User'])]
    private Collection $userRoles;

    /*
        #[Assert\NotBlank(groups: ['write:User'])]
        #[Assert\Length(
            min: 2,
            max: 4096,
            minMessage: "Le mot de passe doit faire au moins {{ limit }} caractères",
            groups: ['write:User']
        )]
        #[Assert\Regex(
            pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
            message: 'Le mot de passe doit contenir au moins une minuscule, une majuscule, un chiffre et un caractère spécial',
            groups: ['write:User']
        )]
    */
    #[Groups(['write:User'])]
    private ?string $plainPassword = null;

    #[Groups(['read:User'])]
    private ?int $entrepriseid = null;

    public function __construct()
    {
        $this->userRoles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function isEtat(): ?bool
    {
        return $this->etat;
    }

    public function setEtat(bool $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): static
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    /**
     * @return Collection<int, UserRole>
     */
    public function getUserRoles(): Collection
    {
        return $this->userRoles;
    }

    public function addUserRole(UserRole $userRole): static
    {
        if (!$this->userRoles->contains($userRole)) {
            $this->userRoles->add($userRole);
            $userRole->setUsere($this);
        }

        return $this;
    }

    public function removeUserRole(UserRole $userRole): static
    {
        if ($this->userRoles->removeElement($userRole)) {
            // set the owning side to null (unless already changed)
            if ($userRole->getUsere() === $this) {
                $userRole->setUsere(null);
            }
        }

        return $this;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public static function createFromPayload($username, array $payload): User
    {
        $user = new User();
        return $user
            ->setEmail($username)
            ->setRoles($payload['roles'] ?? []) // Le 'payload' contient le jwt
            ->setNom($payload['nom'] ?? '') // On l'a rajouter grâce au 'JWTSubscriber'
            ->setId($payload['id'] ?? null) // Ou utiliser un repository pour le trouver par 'email'
            ->setEntrepriseid($payload['entrepriseId'] ?? null)
        ; // '??' à cause du système de refresh
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getEntrepriseid(): ?int
    {
        return $this->entrepriseid;
    }

    public function setEntrepriseid(?int $entrepriseid): static
    {
        $this->entrepriseid = $entrepriseid;

        return $this;
    }

}
