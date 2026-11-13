<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Anomalie métier concernant un contact. Les transporteurs HTTP traduisent
 * ce code en statut/réponse, sans que le service connaisse le protocole.
 */
final class ContactOperationException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $httpStatus,
        private readonly ?string $errorCode = null,
    ) {
        parent::__construct($message);
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    /** @return array{message: string, code?: string} */
    public function toPublicPayload(): array
    {
        $payload = ['message' => $this->getMessage()];
        if ($this->errorCode !== null) {
            $payload['code'] = $this->errorCode;
        }

        return $payload;
    }
}
