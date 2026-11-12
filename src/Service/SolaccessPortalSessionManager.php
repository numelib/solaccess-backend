<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\SolaccessPortalSession;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Stockage des sessions secondaires Solaccess du portail Numelib.
 *
 * ATTENTION : ce service ne vérifie pas l'authentification du serveur Teable,
 * ni les identifiants Symfony. Il ne doit être appelé que depuis des points
 * d'entrée qui effectuent préalablement ces deux vérifications.
 */
final class SolaccessPortalSessionManager
{
    private const LIFETIME_SECONDS = 1800; // 30 minutes, durée absolue

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * À appeler uniquement APRÈS authentification du compte Symfony et
     * vérification de l'identité Numelib par un canal serveur de confiance.
     *
     * $browserBinding doit être une valeur opaque, aléatoire (au moins 32 octets
     * d'entropie), liée à la session navigateur et fournie par le serveur Teable.
     *
     * Retourne le secret de session UNE SEULE FOIS. Ne jamais le journaliser.
     */
    public function create(
        User $solaccessUser,
        string $numelibUserId,
        string $browserBinding,
    ): string {
        if ($solaccessUser->getId() === null || $solaccessUser->isActive() !== true) {
            throw new \InvalidArgumentException('Compte Solaccess non autorisé.');
        }

        if (!$this->isValidNumelibUserId($numelibUserId)
            || !$this->isValidBrowserBinding($browserBinding)) {
            throw new \InvalidArgumentException('Liaison de session invalide.');
        }

        $handle = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $now = new \DateTimeImmutable();
        $expiresAt = $now->modify('+' . self::LIFETIME_SECONDS . ' seconds');

        $session = new SolaccessPortalSession(
            hash('sha256', $handle),
            $solaccessUser,
            $numelibUserId,
            hash('sha256', $browserBinding),
            $expiresAt,
        );

        $this->entityManager->persist($session);
        $this->entityManager->flush();

        return $handle;
    }

    /**
     * Retourne la session seulement si le handle, l'identité Numelib et
     * la liaison au navigateur correspondent et que le compte reste actif.
     */
    public function resolve(
        string $handle,
        string $numelibUserId,
        string $browserBinding,
    ): ?SolaccessPortalSession {
        $session = $this->findBoundSession($handle, $numelibUserId, $browserBinding);

        if ($session === null
            || !$session->isValid()
            || $session->getSolaccessUser()->isActive() !== true) {
            return null;
        }

        return $session;
    }

    /**
     * Révocation idempotente, y compris si la session a déjà expiré.
     * Une tentative avec une autre identité Numelib ou liaison navigateur
     * ne peut pas révoquer la session d'un tiers.
     */
    public function revoke(
        string $handle,
        string $numelibUserId,
        string $browserBinding,
    ): void {
        $session = $this->findBoundSession($handle, $numelibUserId, $browserBinding);

        if ($session === null) {
            return;
        }

        $session->revoke();
        $this->entityManager->flush();
    }

    private function findBoundSession(
        string $handle,
        string $numelibUserId,
        string $browserBinding,
    ): ?SolaccessPortalSession {
        if (!$this->isValidHandle($handle)
            || !$this->isValidNumelibUserId($numelibUserId)
            || !$this->isValidBrowserBinding($browserBinding)) {
            return null;
        }

        /** @var SolaccessPortalSession|null $session */
        $session = $this->entityManager
            ->getRepository(SolaccessPortalSession::class)
            ->find(hash('sha256', $handle));

        if ($session === null
            || $session->getNumelibUserId() !== $numelibUserId
            || !hash_equals(
                $session->getBrowserBindingHash(),
                hash('sha256', $browserBinding),
            )) {
            return null;
        }

        return $session;
    }

    private function isValidHandle(string $handle): bool
    {
        return preg_match('/\A[A-Za-z0-9_-]{43}\z/', $handle) === 1;
    }

    private function isValidNumelibUserId(string $numelibUserId): bool
    {
        return $numelibUserId !== ''
            && strlen($numelibUserId) <= 64
            && trim($numelibUserId) === $numelibUserId;
    }

    private function isValidBrowserBinding(string $browserBinding): bool
    {
        // La longueur seule ne prouve pas l'entropie : celle-ci doit être
        // garantie par le serveur Teable lors de la génération du secret.
        return strlen($browserBinding) >= 32 && strlen($browserBinding) <= 256;
    }
}
