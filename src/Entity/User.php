<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: "UNIQ_IDENTIFIER_EMAIL", fields: ["email"])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[
        Assert\Regex(
            pattern: '/^\S*(?=\S{7,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$/',
            message: "Merci de saisir un mot de passe plus sécurisé",
        ),
    ]
    private ?string $password = null;

    #[
        Assert\Regex(
            pattern: '/^\S*(?=\S{7,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$/',
            message: "Merci de saisir un mot de passe plus sécurisé",
        ),
    ]
    private $plainPassword;

    #[ORM\Column(length: 255)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    private ?string $lastname = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $last_login = null;

    /**
     * @var Collection<int, Badge>
     */
    #[ORM\OneToMany(targetEntity: AccessDevice::class, mappedBy: "user")]
    private Collection $devices;

    /**
     * @var Collection<int, AccessBadge>
     */
    #[ORM\OneToMany(targetEntity: AccessBadge::class, mappedBy: "user")]
    private Collection $accessBadges;

    /**
     * @var Collection<int, AccessCode>
     */
    #[ORM\OneToMany(targetEntity: AccessCode::class, mappedBy: "user")]
    private Collection $accessCodes;

    /**
     * @var Collection<int, Contact>
     */
    #[
        ORM\OneToMany(
            targetEntity: Contact::class,
            mappedBy: "manager",
            orphanRemoval: true,
        ),
    ]
    private Collection $contacts;

    public function __construct()
    {
        $this->devices = new ArrayCollection();
        $this->accessBadges = new ArrayCollection();
        $this->accessCodes = new ArrayCollection();
        $this->contacts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString()
    {
        return $this->firstname . " " . $this->lastname;
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
        $roles[] = "ROLE_USER";

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

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $password): self
    {
        $this->plainPassword = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0" . self::class . "\0password"] = hash(
            "crc32c",
            $this->password,
        );

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getLastLogin(): ?\DateTimeImmutable
    {
        return $this->last_login;
    }

    public function setLastLogin(?\DateTimeImmutable $last_login): static
    {
        $this->last_login = $last_login;

        return $this;
    }

    /**
     * @return Collection<int, AccessDevice>
     */
    public function getDevices(): Collection
    {
        return $this->devices;
    }

    public function addDevice(AccessDevice $device): static
    {
        if (!$this->devices->contains($device)) {
            $this->devices->add($device);
            $device->setUser($this);
        }

        return $this;
    }

    public function removeDevice(AccessDevice $device): static
    {
        if ($this->devices->removeElement($device)) {
            // set the owning side to null (unless already changed)
            if ($device->getUser() === $this) {
                $device->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AccessBadge>
     */
    public function getAccessBadges(): Collection
    {
        return $this->accessBadges;
    }

    public function addAccessBadge(AccessBadge $accessBadge): static
    {
        if (!$this->accessBadges->contains($accessBadge)) {
            $this->accessBadges->add($accessBadge);
            $accessBadge->setUser($this);
        }

        return $this;
    }

    public function removeAccessBadge(AccessBadge $accessBadge): static
    {
        if ($this->accessBadges->removeElement($accessBadge)) {
            // set the owning side to null (unless already changed)
            if ($accessBadge->getUser() === $this) {
                $accessBadge->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AccessCode>
     */
    public function getAccessCodes(): Collection
    {
        return $this->accessCodes;
    }

    public function addAccessCode(AccessCode $accessCode): static
    {
        if (!$this->accessCodes->contains($accessCode)) {
            $this->accessCodes->add($accessCode);
            $accessCode->setUser($this);
        }

        return $this;
    }

    public function removeAccessCode(AccessCode $accessCode): static
    {
        if ($this->accessCodes->removeElement($accessCode)) {
            // set the owning side to null (unless already changed)
            if ($accessCode->getUser() === $this) {
                $accessCode->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Contact>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addContact(Contact $contact): static
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts->add($contact);
            $contact->setManager($this);
        }

        return $this;
    }

    public function removeContact(Contact $contact): static
    {
        if ($this->contacts->removeElement($contact)) {
            // set the owning side to null (unless already changed)
            if ($contact->getManager() === $this) {
                $contact->setManager(null);
            }
        }

        return $this;
    }
}
