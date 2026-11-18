<?php

declare(strict_types=1);

namespace App\Integration\Numelib\Service;

use App\Entity\AccessDevice;
use App\Entity\Contact;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Vue de lecture limitée aux ressources rattachées au compte Symfony authentifié.
 * Aucun PIN, UID, token ESP32 ou secret n'est sérialisé.
 *
 * Les associations des credentials à des contrôleurs varient selon le modèle
 * métier ; on ne fabrique pas de controllerId lorsque le modèle ne le fournit pas.
 */
final class PortalBridgeSnapshot
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /** @return array<string, mixed> */
    public function forUser(User $user): array
    {
        /** @var list<AccessDevice> $devices */
        $devices = $this->entityManager->getRepository(AccessDevice::class)
            ->findBy(['user' => $user], ['id' => 'ASC']);

        /** @var list<Contact> $contacts */
        $contacts = $this->entityManager->getRepository(Contact::class)
            ->findBy(['manager' => $user], ['id' => 'ASC']);

        $controllers = [];
        foreach ($devices as $device) {
            $lock = $device->getLock();
            $lockView = null;
            if ($lock !== null) {
                $lockView = [
                    'id' => $lock->getId(),
                    'name' => method_exists($lock, 'getName') ? $lock->getName() : null,
                    'enabled' => method_exists($lock, 'isEnabled') ? $lock->isEnabled() : null,
                ];
            }
            $controllers[] = [
                'id' => $device->getId(),
                'name' => $device->getName(),
                'enabled' => $device->isEnabled(),
                'lastSeenAt' => $device->getLastSeenAt()?->format(\DateTimeInterface::ATOM),
                'configVersion' => $device->getConfigVersion(),
                'lock' => $lockView,
                // "online" est volontairement absent : seuil métier non unifié.
            ];
        }

        $publicContacts = [];
        foreach ($contacts as $contact) {
            $publicContacts[] = [
                'id' => $contact->getId(),
                'firstname' => $contact->getFirstname(),
                'lastname' => $contact->getLastname(),
                'name' => trim(($contact->getFirstname() ?? '') . ' ' . ($contact->getLastname() ?? '')),
                'credentialCount' => $contact->getAccessBadges()->count() + $contact->getAccessCodes()->count(),
            ];
        }

        $credentials = [];
        foreach ($user->getAccessBadges() as $badge) {
            $credentials[] = $this->credentialView($badge, 'nfc');
        }
        foreach ($user->getAccessCodes() as $code) {
            $credentials[] = $this->credentialView($code, 'pin');
        }

        return [
            'controllers' => ['items' => $controllers],
            'contacts' => ['items' => $publicContacts],
            'credentials' => ['items' => $credentials],
        ];
    }

    /** @return array<string, int|string|bool|null> */
    private function credentialView(object $credential, string $type): array
    {
        $entityId = $credential->getId();
        $contact = $credential->getContact();

        $controllerId = null;
        // Ce lien ne doit être retourné que s'il existe dans le modèle réellement chargé.
        if (method_exists($credential, 'getLock')) {
            $lock = $credential->getLock();
            if ($lock !== null && method_exists($lock, 'getDevice')) {
                $controllerId = $lock->getDevice()?->getId();
            }
        }

        $enabled = null;
        if (method_exists($credential, 'isEnabled')) {
            $enabled = $credential->isEnabled();
        }

        return [
            'id' => sprintf('%s-%s', $type, $entityId),
            'type' => $type,
            'contactId' => $contact?->getId(),
            'controllerId' => $controllerId,
            'enabled' => $enabled,
        ];
    }
}
