<?php

namespace App\Service;

use App\Entity\AccessDevice;
use App\Security\ApiKeyManager;

class AccessDeviceTokenFactory
{
    public function __construct(
        private readonly ApiKeyManager $apiKeyManager,
    ) {
    }

    /**
     * Retourne le token clair à afficher une seule fois.
     */
    public function regenerateToken(AccessDevice $device): string
    {
        $publicId = $this->apiKeyManager->generateTokenPublicId();
        $secret = $this->apiKeyManager->generateSecret();
        $plainToken = $this->apiKeyManager->buildToken($publicId, $secret);

        $device
            ->setTokenPublicId($publicId)
            ->setTokenHash($this->apiKeyManager->hashSecret($secret));

        return $plainToken;
    }
}