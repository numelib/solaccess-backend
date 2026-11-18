<?php

declare(strict_types=1);

namespace App\Integration\Numelib\Entity;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Association administrative entre un compte externe et un compte Solaccess.
 * Une liaison NE représente PAS une session d'authentification.
 */
#[ORM\Entity]
#[ORM\Table(name: 'solaccess_portal_account_link')]
#[ORM\UniqueConstraint(name: 'uniq_spal_client_external', columns: ['client_id', 'external_user_id'])]
#[ORM\UniqueConstraint(name: 'uniq_spal_client_solaccess', columns: ['client_id', 'solaccess_user_id'])]
class SolaccessPortalAccountLink
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private string $clientId;

    #[ORM\Column(length: 64)]
    private string $externalUserId;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $solaccessUser;

    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    // Les moyens d'accès peuvent ouvrir des serrures : droit séparé, désactivé par défaut.
    #[ORM\Column(options: ['default' => false])]
    private bool $canManageCredentials = false;

    public function __construct(string $clientId, string $externalUserId, User $solaccessUser)
    {
        $this->clientId = $clientId;
        $this->externalUserId = $externalUserId;
        $this->solaccessUser = $solaccessUser;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getExternalUserId(): string
    {
        return $this->externalUserId;
    }

    public function getSolaccessUser(): User
    {
        return $this->solaccessUser;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function canManageCredentials(): bool
    {
        return $this->canManageCredentials;
    }

    public function setCanManageCredentials(bool $allowed): static
    {
        $this->canManageCredentials = $allowed;
        return $this;
    }
}
