<?php

namespace App\Security;

final class ApiKeyManager
{
    public function generateTokenPublicId(): string
    {
        return 'dev_' . bin2hex(random_bytes(4));
    }

    public function generateSecret(int $bytes = 24): string
    {
        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }

    public function buildToken(string $publicId, string $secret): string
    {
        return sprintf('%s.%s', $publicId, $secret);
    }

    public function parseToken(string $token): ?array
    {
        $parts = explode('.', $token, 2);

        if (2 !== count($parts) || '' === $parts[0] || '' === $parts[1]) {
            return null;
        }

        return [
            'publicId' => $parts[0],
            'secret' => $parts[1],
        ];
    }

    public function hashSecret(string $secret): string
    {
        return password_hash($secret, PASSWORD_ARGON2ID);
    }

    public function verifySecret(string $secret, string $hash): bool
    {
        return password_verify($secret, $hash);
    }
}