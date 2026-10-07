<?php

namespace App\Security;

final class PinHasher
{
    public function generateSalt(int $bytes = 16): string
    {
        return bin2hex(random_bytes($bytes));
    }

    public function hash(string $plainPin, string $salt): string
    {
        return hash('sha256', $plainPin . ':' . $salt);
    }

    public function verify(string $plainPin, string $salt, string $hash): bool
    {
        return hash_equals($hash, $this->hash($plainPin, $salt));
    }
}