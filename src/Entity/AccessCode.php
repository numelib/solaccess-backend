<?php

namespace App\Entity;

use App\Repository\AccessCodeRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: AccessCodeRepository::class)]
class AccessCode
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: "codes", targetEntity: Lock::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Lock $lock = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $pinSalt = null;

    #[ORM\Column(length: 64)]
    private string $pinHash = "";

    private ?string $plainPin = null;

    #[ORM\ManyToOne(inversedBy: "accessCodes")]
    private ?User $user = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $enablingAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $disablingAt = null;

    #[ORM\ManyToOne(inversedBy: "accessCodes", cascade: ["persist"])]
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
                ->buildViolation("access_code.enabled_after_disabled")
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

    public function getLock(): ?Lock
    {
        return $this->lock;
    }

    public function setLock(?Lock $lock): static
    {
        $this->lock = $lock;

        return $this;
    }

    public function getPlainPin(): ?string
    {
        return $this->plainPin;
    }

    public function setPlainPin(?string $plainPin): static
    {
        $this->plainPin = $plainPin;

        return $this;
    }

    public function erasePlainPin(): static
    {
        $this->plainPin = null;

        return $this;
    }

    public function getPinSalt(): ?string
    {
        return $this->pinSalt;
    }

    public function setPinSalt(?string $pinSalt): static
    {
        $this->pinSalt = $pinSalt;

        return $this;
    }

    public function getPinHash(): ?string
    {
        return $this->pinHash;
    }

    public function setPinHash(string $pinHash): static
    {
        $this->pinHash = $pinHash;

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
