<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'solaccess_portal_session')]
#[ORM\Index(name: 'idx_solaccess_portal_numelib', columns: ['numelib_user_id'])]
class SolaccessPortalSession
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(length: 64)]
    private string $handleHash;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $solaccessUser;

    #[ORM\Column(length: 64)]
    private string $numelibUserId;

    #[ORM\Column(length: 64)]
    private string $browserBindingHash;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

    public function __construct(
        string $handleHash,
        User $solaccessUser,
        string $numelibUserId,
        string $browserBindingHash,
        \DateTimeImmutable $expiresAt
    ) {
        $this->handleHash = $handleHash;
        $this->solaccessUser = $solaccessUser;
        $this->numelibUserId = $numelibUserId;
        $this->browserBindingHash = $browserBindingHash;
        $this->expiresAt = $expiresAt;
    }

    public function getHandleHash(): string
    {
        return $this->handleHash;
    }

    public function getSolaccessUser(): User
    {
        return $this->solaccessUser;
    }

    public function getNumelibUserId(): string
    {
        return $this->numelibUserId;
    }

    public function getBrowserBindingHash(): string
    {
        return $this->browserBindingHash;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isValid(): bool
    {
        return $this->revokedAt === null
            && $this->expiresAt > new \DateTimeImmutable();
    }

    public function revoke(): void
    {
        $this->revokedAt = new \DateTimeImmutable();
    }
}
