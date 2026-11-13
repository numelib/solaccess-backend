<?php

declare(strict_types=1);

namespace App\Exception;

/** Erreur métier structurée, indépendante du contrôleur HTTP appelant. */
final class CredentialOperationException extends \RuntimeException
{
    /** @param list<array{property: string, message: string}> $errors */
    public function __construct(
        string $message,
        private readonly int $httpStatus,
        private readonly ?string $errorCode = null,
        private readonly array $errors = [],
    ) {
        parent::__construct($message);
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    /** @return array<string, mixed> */
    public function toPublicPayload(): array
    {
        $data = ['message' => $this->getMessage()];
        if ($this->errorCode !== null) {
            $data['code'] = $this->errorCode;
        }
        if ($this->errors !== []) {
            $data['errors'] = $this->errors;
        }
        return $data;
    }
}
