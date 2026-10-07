<?php

namespace App\Entity;

use App\Repository\BuildingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BuildingRepository::class)]
class Building
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    /**
     * @var Collection<int, AccessDevice>
     */
    #[ORM\OneToMany(targetEntity: AccessDevice::class, mappedBy: 'building')]
    private Collection $accessDevice;

    #[ORM\Column(length: 255)]
    private ?string $addressLine = null;

    #[ORM\Column(length: 255)]
    private ?string $addressCity = null;

    #[ORM\Column(length: 255)]
    private ?string $addressPostCode = null;

    #[ORM\Column(length: 255)]
    private ?string $addressCountry = null;

    public function __construct()
    {
        $this->accessDevice = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->label ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return Collection<int, AccessDevice>
     */
    public function getAccessDevice(): Collection
    {
        return $this->accessDevice;
    }

    public function addAccessDevice(AccessDevice $accessDevice): static
    {
        if (!$this->accessDevice->contains($accessDevice)) {
            $this->accessDevice->add($accessDevice);
            $accessDevice->setBuilding($this);
        }

        return $this;
    }

    public function removeAccessDevice(AccessDevice $accessDevice): static
    {
        if ($this->accessDevice->removeElement($accessDevice)) {
            // set the owning side to null (unless already changed)
            if ($accessDevice->getBuilding() === $this) {
                $accessDevice->setBuilding(null);
            }
        }

        return $this;
    }

    public function getAddressLine(): ?string
    {
        return $this->addressLine;
    }

    public function setAddressLine(string $addressLine): static
    {
        $this->addressLine = $addressLine;

        return $this;
    }

    public function getAddressCity(): ?string
    {
        return $this->addressCity;
    }

    public function setAddressCity(string $addressCity): static
    {
        $this->addressCity = $addressCity;

        return $this;
    }

    public function getAddressPostCode(): ?string
    {
        return $this->addressPostCode;
    }

    public function setAddressPostCode(string $addressPostCode): static
    {
        $this->addressPostCode = $addressPostCode;

        return $this;
    }

    public function getAddressCountry(): ?string
    {
        return $this->addressCountry;
    }

    public function setAddressCountry(string $addressCountry): static
    {
        $this->addressCountry = $addressCountry;

        return $this;
    }
}
