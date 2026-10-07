<?php

namespace App\Entity;

use App\Repository\AccessLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: AccessLogRepository::class)]
#[ORM\Table(name: 'access_log')]
class AccessLog
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'logs', targetEntity: Lock::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Lock $lock = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $occurredAt;

    #[ORM\Column(length: 50)]
    private string $type;

    #[ORM\Column(length: 50)]
    private string $source;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $label;

    #[ORM\Column(nullable: true)]
    private ?bool $success = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $reason = null;

    #[ORM\ManyToOne(inversedBy: 'logs', targetEntity: Contact::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Contact $contactIdentifier = null;

    #[ORM\ManyToOne(inversedBy: 'logs', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $credentialIdentifier = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $rawPayload = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function isSuccess(): ?bool
    {
        return $this->success;
    }

    public function setSuccess(?bool $success): static
    {
        $this->success = $success;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function getRawPayload(): ?array
    {
        return $this->rawPayload;
    }

    public function setRawPayload(?array $rawPayload): static
    {
        $this->rawPayload = $rawPayload;

        return $this;
    }

    public function getDevice(): ?AccessDevice
    {
        return $this->getLock()?->getDevice();
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

    public function getOccurredAt(): ?\DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function setOccurredAt(\DateTimeImmutable $occurredAt): static
    {
        $this->occurredAt = $occurredAt;

        return $this;
    }

    public function getCredentialIdentifier(): ?User
    {
        return $this->credentialIdentifier;
    }

    public function setCredentialIdentifier(?User $credentialIdentifier): static
    {
        $this->credentialIdentifier = $credentialIdentifier;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getContactIdentifier(): ?Contact
    {
        return $this->contactIdentifier;
    }

    public function setContactIdentifier(?Contact $contactIdentifier): static
    {
        $this->contactIdentifier = $contactIdentifier;

        if ($contactIdentifier !== null) {
            $this->setLabel($contactIdentifier->__toString());
            if($contactIdentifier->getManager() !== null){
                $this->setCredentialIdentifier($contactIdentifier->getManager());
            }
        }

        return $this;
    }

}