<?php

namespace App\Entity;

use App\Repository\AccessBadgeRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: AccessBadgeRepository::class)]
class AccessBadge
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: "badges", targetEntity: Lock::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Lock $lock = null;

    #[ORM\Column(length: 255)]
    private string $uid;

    #[ORM\ManyToOne(inversedBy: "accessBadges")]
    private ?User $user = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $enablingAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $disablingAt = null;

    #[ORM\ManyToOne(inversedBy: "accessBadges", cascade: ["persist"])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Contact $contact = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return $this->contact?->__toString() ?? "";
    }

    #[Assert\Callback]
    public function validate(
        ExecutionContextInterface $context,
        mixed $payload,
    ): void {
        if (
            $this->enablingAt !== null &&
            $this->disablingAt !== null &&
            $this->enablingAt > $this->disablingAt
        ) {
            $context
                ->buildViolation("access_badge.enabled_after_disabled")
                ->atPath("enablingAt")
                ->addViolation();
        }
    }

    public function isEnabled(): bool
    {
        $now = new \DateTimeImmutable();

        if (
            $this->enablingAt !== null &&
            $this->disablingAt === null &&
            $now > $this->enablingAt
        ) {
            return true;
        }

        // Technically not necessary but good for clarity
        if (
            $this->enablingAt === null &&
            $this->disablingAt !== null &&
            $now > $this->disablingAt
        ) {
            return false;
        }

        if ($this->enablingAt !== null && $this->disablingAt !== null) {
            return $now > $this->enablingAt && $now < $this->disablingAt;
        }

        return false;
    }

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function setUid(string $uid): static
    {
        $this->uid = $uid;

        return $this;
    }

    public function getLock(): ?Lock
    {
        return $this->lock;
    }

    public function setLock(?Lock $lock): static
    {
        $this->lock = $lock;

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

    public function getEnablingAt(): ?\DateTimeImmutable
    {
        return $this->enablingAt;
    }

    public function setEnablingAt(?\DateTimeImmutable $enablingAt): static
    {
        $this->enablingAt = $enablingAt;

        return $this;
    }

    public function getDisablingAt(): ?\DateTimeImmutable
    {
        return $this->disablingAt;
    }

    public function setDisablingAt(?\DateTimeImmutable $disablingAt): static
    {
        $this->disablingAt = $disablingAt;

        return $this;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(?Contact $contact): static
    {
        $this->contact = $contact;

        return $this;
    }
}
