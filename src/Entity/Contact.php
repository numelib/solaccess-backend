<?php

namespace App\Entity;

use App\Repository\ContactRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
class Contact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    private ?string $lastname = null;

    #[ORM\ManyToOne(inversedBy: "contacts")]
    #[ORM\JoinColumn]
    private ?User $manager = null;

    /**
     * @var Collection<int, AccessCode>
     */
    #[ORM\OneToMany(targetEntity: AccessCode::class, mappedBy: "contact")]
    private Collection $accessCodes;

    /**
     * @var Collection<int, AccessBadge>
     */
    #[ORM\OneToMany(targetEntity: AccessBadge::class, mappedBy: "contact")]
    private Collection $accessBadges;

    public function __construct()
    {
        $this->accessCodes = new ArrayCollection();
        $this->accessBadges = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->firstname . " " . $this->lastname;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getManager(): ?User
    {
        return $this->manager;
    }

    public function setManager(?User $manager): static
    {
        $this->manager = $manager;

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
            $accessCode->setContact($this);
        }

        return $this;
    }

    public function removeAccessCode(AccessCode $accessCode): static
    {
        if ($this->accessCodes->removeElement($accessCode)) {
            // set the owning side to null (unless already changed)
            if ($accessCode->getContact() === $this) {
                $accessCode->setContact(null);
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
            $accessBadge->setContact($this);
        }

        return $this;
    }

    public function removeAccessBadge(AccessBadge $accessBadge): static
    {
        if ($this->accessBadges->removeElement($accessBadge)) {
            // set the owning side to null (unless already changed)
            if ($accessBadge->getContact() === $this) {
                $accessBadge->setContact(null);
            }
        }

        return $this;
    }
}
