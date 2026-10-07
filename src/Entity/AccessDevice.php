<?php

namespace App\Entity;

use App\Config\LockStates;
use App\Entity\Lock;
use App\Repository\AccessDeviceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: AccessDeviceRepository::class)]
class AccessDevice implements UserInterface
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    private ?string $tokenPublicId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tokenHash = null;

    #[ORM\Column(options: ["default" => true])]
    private bool $enabled = true;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastSeenAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastSyncAt = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $firmwareVersion = null;

    #[ORM\Column(type: Types::INTEGER, options: ["default" => 1])]
    private int $configVersion = 1;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $lastIp = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $uptime = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $freeHeap = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastStatusAt = null;

    #[Assert\Valid]
    #[
        ORM\OneToOne(
            mappedBy: "device",
            targetEntity: Lock::class,
            cascade: ["persist", "remove"],
        ),
    ]
    private ?Lock $lock = null;

    #[ORM\ManyToOne(inversedBy: "devices", targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: "access_device")]
    private ?Building $building = null;

    #[ORM\Column(enumType: LockStates::class)]
    private ?LockStates $lockState = null;

    /**
     * @var Collection<int, Command>
     */
    #[
        ORM\OneToMany(
            targetEntity: Command::class,
            mappedBy: "accessDevice",
            orphanRemoval: true,
        ),
    ]
    private Collection $commands;

    public function __construct()
    {
        $this->commands = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return sprintf("%s", $this->name);
    }

    // ===== Security =====

    public function getUserIdentifier(): string
    {
        return $this->tokenPublicId;
    }

    public function getRoles(): array
    {
        return ["ROLE_DEVICE"];
    }

    public function eraseCredentials(): void {}

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getTokenPublicId(): ?string
    {
        return $this->tokenPublicId;
    }

    public function setTokenPublicId(?string $tokenPublicId): static
    {
        $this->tokenPublicId = $tokenPublicId;

        return $this;
    }

    public function getTokenHash(): ?string
    {
        return $this->tokenHash;
    }

    public function setTokenHash(?string $tokenHash): static
    {
        $this->tokenHash = $tokenHash;

        return $this;
    }

    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getLastSeenAt(): ?\DateTimeImmutable
    {
        return $this->lastSeenAt;
    }

    public function setLastSeenAt(?\DateTimeImmutable $lastSeenAt): static
    {
        $this->lastSeenAt = $lastSeenAt;

        return $this;
    }

    public function getLastSyncAt(): ?\DateTimeImmutable
    {
        return $this->lastSyncAt;
    }

    public function setLastSyncAt(?\DateTimeImmutable $lastSyncAt): static
    {
        $this->lastSyncAt = $lastSyncAt;

        return $this;
    }

    public function getFirmwareVersion(): ?string
    {
        return $this->firmwareVersion;
    }

    public function setFirmwareVersion(?string $firmwareVersion): static
    {
        $this->firmwareVersion = $firmwareVersion;

        return $this;
    }

    public function getConfigVersion(): ?int
    {
        return $this->configVersion;
    }

    public function setConfigVersion(int $configVersion): static
    {
        $this->configVersion = $configVersion;

        return $this;
    }

    public function bumpConfigVersion(): static
    {
        ++$this->configVersion;

        return $this;
    }

    public function getLastIp(): ?string
    {
        return $this->lastIp;
    }

    public function setLastIp(?string $lastIp): static
    {
        $this->lastIp = $lastIp;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getLock(): ?Lock
    {
        return $this->lock;
    }

    public function setLock(?Lock $lock): static
    {
        $this->lock = $lock;

        if ($lock && $lock->getDevice() !== $this) {
            $lock->setDevice($this);
        }

        return $this;
    }

    public function getBuilding(): ?Building
    {
        return $this->building;
    }

    public function setBuilding(?Building $building): static
    {
        $this->building = $building;

        return $this;
    }

    public function getUptime(): ?int
    {
        return $this->uptime;
    }

    public function setUptime(?int $uptime): static
    {
        $this->uptime = $uptime;

        return $this;
    }

    public function getFreeHeap(): ?int
    {
        return $this->freeHeap;
    }

    public function setFreeHeap(?int $freeHeap): static
    {
        $this->freeHeap = $freeHeap;

        return $this;
    }

    public function getLastStatusAt(): ?\DateTimeImmutable
    {
        return $this->lastStatusAt;
    }

    public function setLastStatusAt(?\DateTimeImmutable $lastStatusAt): static
    {
        $this->lastStatusAt = $lastStatusAt;

        return $this;
    }

    public function getLockState(): ?LockStates
    {
        return $this->lockState;
    }

    public function setLockState(LockStates $lockState): static
    {
        $this->lockState = $lockState;

        return $this;
    }

    /**
     * @return Collection<int, Command>
     */
    public function getCommands(): Collection
    {
        return $this->commands;
    }

    public function addCommand(Command $command): static
    {
        if (!$this->commands->contains($command)) {
            $this->commands->add($command);
            $command->setAccessDevice($this);
        }

        return $this;
    }

    public function removeCommand(Command $command): static
    {
        if ($this->commands->removeElement($command)) {
            // set the owning side to null (unless already changed)
            if ($command->getAccessDevice() === $this) {
                $command->setAccessDevice(null);
            }
        }

        return $this;
    }
}
