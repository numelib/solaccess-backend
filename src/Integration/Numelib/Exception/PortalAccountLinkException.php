<?php

declare(strict_types=1);

namespace App\Integration\Numelib\Exception;

/** Erreur métier publique, sans détails personnels ni secrets. */
final class PortalAccountLinkException extends \RuntimeException
{
    public function __construct(
        private readonly string $errorCode,
        private readonly int $httpStatus,
    ) {
        parent::__construct($errorCode);
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    /** @return array{error: string} */
    public function toPublicPayload(): array
    {
        return ['error' => $this->errorCode];
    }
}
