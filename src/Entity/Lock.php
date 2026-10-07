<?php

namespace App\Entity;

use App\Repository\LockRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LockRepository::class)]
#[ORM\Table(name: '`lock`')]
class Lock
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $name;

    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    #[ORM\Column(options: ['default' => 3000])]
    private int $unlockMs = 3000;

    #[ORM\Column(nullable: true)]
    private ?int $relayChannel = 1;

    #[ORM\Column(nullable: true)]
    private ?int $alignGpio = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $alignActiveHigh = true;

    #[ORM\Column(options: ['default' => 3000])]
    private int $alignDelayMs = 3000;

    #[ORM\Column(options: ['default' => 15000])]
    private int $alignTimeoutMs = 15000;

    #[ORM\Column(options: ['default' => true])]
    private bool $closeOnAlignTimeout = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $buttonEnabled = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $keypadEnabled = true;

    #[ORM\Column(options: ['default' => false])]
    private bool $nfcEnabled = false;

    #[ORM\OneToOne(inversedBy: 'lock', targetEntity: AccessDevice::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?AccessDevice $device = null;

    #[Assert\Valid]
    #[ORM\OneToMany(mappedBy: 'lock', targetEntity: AccessCode::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $codes;

    #[Assert\Valid]
    #[ORM\OneToMany(mappedBy: 'lock', targetEntity: AccessBadge::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $badges;

    #[ORM\OneToMany(mappedBy: 'lock', targetEntity: AccessLog::class)]
    private Collection $logs;

    public function __construct()
    {
        $this->codes = new ArrayCollection();
        $this->badges = new ArrayCollection();
        $this->logs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    public function getUnlockMs(): ?int
    {
        return $this->unlockMs;
    }

    public function setUnlockMs(int $unlockMs): static
    {
        $this->unlockMs = $unlockMs;

        return $this;
    }

    public function getRelayChannel(): ?int
    {
        return $this->relayChannel;
    }

    public function setRelayChannel(?int $relayChannel): static
    {
        $this->relayChannel = $relayChannel;

        return $this;
    }

    public function getAlignGpio(): ?int
    {
        return $this->alignGpio;
    }

    public function setAlignGpio(?int $alignGpio): static
    {
        $this->alignGpio = $alignGpio;

        return $this;
    }

    public function isAlignActiveHigh(): ?bool
    {
        return $this->alignActiveHigh;
    }

    public function setAlignActiveHigh(bool $alignActiveHigh): static
    {
        $this->alignActiveHigh = $alignActiveHigh;

        return $this;
    }

    public function getAlignDelayMs(): ?int
    {
        return $this->alignDelayMs;
    }

    public function setAlignDelayMs(int $alignDelayMs): static
    {
        $this->alignDelayMs = $alignDelayMs;

        return $this;
    }

    public function getAlignTimeoutMs(): ?int
    {
        return $this->alignTimeoutMs;
    }

    public function setAlignTimeoutMs(int $alignTimeoutMs): static
    {
        $this->alignTimeoutMs = $alignTimeoutMs;

        return $this;
    }

    public function isCloseOnAlignTimeout(): ?bool
    {
        return $this->closeOnAlignTimeout;
    }

    public function setCloseOnAlignTimeout(bool $closeOnAlignTimeout): static
    {
        $this->closeOnAlignTimeout = $closeOnAlignTimeout;

        return $this;
    }

    public function isButtonEnabled(): ?bool
    {
        return $this->buttonEnabled;
    }

    public function setButtonEnabled(bool $buttonEnabled): static
    {
        $this->buttonEnabled = $buttonEnabled;

        return $this;
    }

    public function isKeypadEnabled(): ?bool
    {
        return $this->keypadEnabled;
    }

    public function setKeypadEnabled(bool $keypadEnabled): static
    {
        $this->keypadEnabled = $keypadEnabled;

        return $this;
    }

    public function isNfcEnabled(): ?bool
    {
        return $this->nfcEnabled;
    }

    public function setNfcEnabled(bool $nfcEnabled): static
    {
        $this->nfcEnabled = $nfcEnabled;

        return $this;
    }

    public function getDevice(): ?AccessDevice
    {
        return $this->device;
    }

    public function setDevice(AccessDevice $device): static
    {
        $this->device = $device;

        return $this;
    }

    /**
     * @return Collection<int, AccessLog>
     */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function addLog(AccessLog $log): static
    {
        if (!$this->logs->contains($log)) {
            $this->logs->add($log);
            $log->setLock($this);
        }

        return $this;
    }

    public function removeLog(AccessLog $log): static
    {
        if ($this->logs->removeElement($log)) {
            // set the owning side to null (unless already changed)
            if ($log->getLock() === $this) {
                $log->setLock(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AccessCode>
     */
    public function getCodes(): Collection
    {
        return $this->codes;
    }

    public function addCode(AccessCode $code): static
    {
        if (!$this->codes->contains($code)) {
            $this->codes->add($code);
            $code->setLock($this);
        }

        return $this;
    }

    public function removeCode(AccessCode $code): static
    {
        if ($this->codes->removeElement($code)) {
            // set the owning side to null (unless already changed)
            if ($code->getLock() === $this) {
                $code->setLock(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AccessBadge>
     */
    public function getBadges(): Collection
    {
        return $this->badges;
    }

    public function addBadge(AccessBadge $badge): static
    {
        if (!$this->badges->contains($badge)) {
            $this->badges->add($badge);
            $badge->setLock($this);
        }

        return $this;
    }

    public function removeBadge(AccessBadge $badge): static
    {
        if ($this->badges->removeElement($badge)) {
            // set the owning side to null (unless already changed)
            if ($badge->getLock() === $this) {
                $badge->setLock(null);
            }
        }

        return $this;
    }

}
