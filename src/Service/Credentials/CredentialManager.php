<?php

declare(strict_types=1);

namespace App\Service\Credentials;

use App\Entity\AccessBadge;
use App\Entity\AccessCode;
use App\Entity\AccessDevice;
use App\Entity\Contact;
use App\Entity\User;
use App\Exception\CredentialOperationException;
use App\Repository\AccessBadgeRepository;
use App\Repository\AccessCodeRepository;
use App\Repository\AccessDeviceRepository;
use App\Repository\ContactRepository;
use App\Security\PinHasher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Opérations badges NFC / codes PIN indépendantes du transport HTTP.
 * L'acteur Symfony est explicite et chaque équipement et contact est filtré par propriétaire.
 */
final class CredentialManager
{
    public function __construct(
        private readonly AccessDeviceRepository $devices,
        private readonly ContactRepository $contacts,
        private readonly AccessCodeRepository $codes,
        private readonly AccessBadgeRepository $badges,
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
        private readonly CredentialViewBuilder $viewBuilder,
        private readonly PinHasher $pinHasher,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function listFor(User $actor, int $controllerId): array
    {
        $lock = $this->findOwnedDevice($actor, $controllerId)->getLock();
        if ($lock === null) {
            return [];
        }

        $items = [];
        foreach ($lock->getCodes() as $code) {
            $items[] = $this->viewBuilder->buildCode($code);
        }
        foreach ($lock->getBadges() as $badge) {
            $items[] = $this->viewBuilder->buildBadge($badge);
        }
        return $items;
    }

    /** @param array<string, mixed> $payload
     *  @return array<string, mixed>
     */
    public function createFor(User $actor, int $controllerId, array $payload): array
    {
        $device = $this->findOwnedDevice($actor, $controllerId);
        $lock = $device->getLock();
        if ($lock === null) {
            throw new CredentialOperationException('Controller has no lock', 409, 'controller_has_no_lock');
        }

        $type = is_string($payload['type'] ?? null)
            ? strtolower(trim($payload['type'])) : '';
        $contact = $this->findOwnedContact($actor, $payload['contactId'] ?? null);
        $enablingAt = $this->parseDate($payload['enablingAt'] ?? null, new \DateTimeImmutable());
        $disablingAt = $this->parseDate($payload['disablingAt'] ?? null);

        if ($type === 'pin') {
            $plainPin = $this->nonEmptyString($payload['pin'] ?? null);
            if ($plainPin === null) {
                throw new CredentialOperationException('pin is required', 422);
            }
            $code = (new AccessCode())
                ->setLock($lock)
                ->setUser($actor)
                ->setContact($contact)
                ->setEnablingAt($enablingAt)
                ->setDisablingAt($disablingAt)
                ->setPlainPin($plainPin);
            $this->assertValid($code);
            $this->em->persist($code);
            $device->bumpConfigVersion();
            try {
                $this->em->flush();
                return $this->viewBuilder->buildCode($code);
            } finally {
                $code->erasePlainPin();
            }
        }

        if ($type === 'nfc') {
            $uid = $this->nonEmptyString($payload['uid'] ?? null);
            if ($uid === null) {
                throw new CredentialOperationException('uid is required', 422);
            }
            $badge = (new AccessBadge())
                ->setLock($lock)
                ->setUser($actor)
                ->setContact($contact)
                ->setEnablingAt($enablingAt)
                ->setDisablingAt($disablingAt)
                ->setUid($uid);
            $this->assertValid($badge);
            $this->em->persist($badge);
            $device->bumpConfigVersion();
            $this->em->flush();
            return $this->viewBuilder->buildBadge($badge);
        }

        throw new CredentialOperationException('type must be pin or nfc', 422);
    }

    /** @param array<string, mixed> $payload
     *  @return array<string, mixed>
     */
    public function updateFor(User $actor, int $controllerId, string $credentialId, array $payload): array
    {
        $device = $this->findOwnedDevice($actor, $controllerId);
        $lock = $device->getLock();
        if ($lock === null) {
            throw new CredentialOperationException('Credential not found', 404);
        }
        $parsed = $this->viewBuilder->parsePublicId($credentialId);
        if ($parsed === null) {
            throw new CredentialOperationException('Credential not found', 404);
        }
        [$type, $entityId] = $parsed;

        $contact = array_key_exists('contactId', $payload)
            ? $this->findOwnedContact($actor, $payload['contactId']) : null;
        $enablingAt = array_key_exists('enablingAt', $payload)
            ? $this->parseDate($payload['enablingAt']) : null;
        $disablingAt = array_key_exists('disablingAt', $payload)
            ? $this->parseDate($payload['disablingAt']) : null;

        if ($type === 'pin') {
            $code = $this->codes->findOneBy(['id' => $entityId, 'lock' => $lock]);
            if (!$code instanceof AccessCode) {
                throw new CredentialOperationException('Credential not found', 404);
            }
            $plainPin = null;
            if (array_key_exists('pin', $payload)) {
                $plainPin = $this->nonEmptyString($payload['pin']);
                if ($plainPin === null) {
                    throw new CredentialOperationException('pin cannot be empty', 422);
                }
            }
            if ($contact !== null) $code->setContact($contact);
            if (array_key_exists('enablingAt', $payload)) $code->setEnablingAt($enablingAt);
            if (array_key_exists('disablingAt', $payload)) $code->setDisablingAt($disablingAt);
            if ($plainPin !== null) {
                // Le PIN est transient : une modification isolée ne déclenche pas
                // nécessairement preUpdate. Persister explicitement sel + hash.
                $salt = $this->pinHasher->generateSalt();
                $code->setPinSalt($salt);
                $code->setPinHash($this->pinHasher->hash($plainPin, $salt));
            }
            try {
                $this->assertValid($code);
                $device->bumpConfigVersion();
                $this->em->flush();
                return $this->viewBuilder->buildCode($code);
            } finally {
                $code->erasePlainPin();
            }
        }

        $badge = $this->badges->findOneBy(['id' => $entityId, 'lock' => $lock]);
        if (!$badge instanceof AccessBadge) {
            throw new CredentialOperationException('Credential not found', 404);
        }
        $uid = null;
        if (array_key_exists('uid', $payload)) {
            $uid = $this->nonEmptyString($payload['uid']);
            if ($uid === null) {
                throw new CredentialOperationException('uid cannot be empty', 422);
            }
        }
        if ($contact !== null) $badge->setContact($contact);
        if (array_key_exists('enablingAt', $payload)) $badge->setEnablingAt($enablingAt);
        if (array_key_exists('disablingAt', $payload)) $badge->setDisablingAt($disablingAt);
        if ($uid !== null) $badge->setUid($uid);
        $this->assertValid($badge);
        $device->bumpConfigVersion();
        $this->em->flush();
        return $this->viewBuilder->buildBadge($badge);
    }

    public function deleteFor(User $actor, int $controllerId, string $credentialId): void
    {
        $device = $this->findOwnedDevice($actor, $controllerId);
        $lock = $device->getLock();
        if ($lock === null) {
            throw new CredentialOperationException('Credential not found', 404);
        }
        $parsed = $this->viewBuilder->parsePublicId($credentialId);
        if ($parsed === null) {
            throw new CredentialOperationException('Credential not found', 404);
        }
        [$type, $entityId] = $parsed;
        $credential = $type === 'pin'
            ? $this->codes->findOneBy(['id' => $entityId, 'lock' => $lock])
            : $this->badges->findOneBy(['id' => $entityId, 'lock' => $lock]);
        if ($credential === null) {
            throw new CredentialOperationException('Credential not found', 404);
        }
        $this->em->remove($credential);
        $device->bumpConfigVersion();
        $this->em->flush();
    }

    private function findOwnedDevice(User $actor, int $controllerId): AccessDevice
    {
        $device = $this->devices->findOneBy(['id' => $controllerId, 'user' => $actor]);
        if (!$device instanceof AccessDevice) {
            throw new CredentialOperationException('Controller not found', 404);
        }
        return $device;
    }

    private function findOwnedContact(User $actor, mixed $id): Contact
    {
        $validId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($validId === false) {
            throw new CredentialOperationException('Contact not found', 404);
        }
        $contact = $this->contacts->findOneBy(['id' => $validId, 'manager' => $actor]);
        if (!$contact instanceof Contact) {
            throw new CredentialOperationException('Contact not found', 404);
        }
        return $contact;
    }

    private function nonEmptyString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);
        return $value !== '' ? $value : null;
    }

    private function parseDate(mixed $value, ?\DateTimeImmutable $default = null): ?\DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return $default;
        }
        if (!is_string($value)) {
            throw new CredentialOperationException('Invalid date value', 422);
        }
        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            // Ne pas répercuter une valeur éventuellement sensible dans l'erreur HTTP.
            throw new CredentialOperationException('Invalid date value', 422);
        }
    }

    private function assertValid(AccessCode|AccessBadge $credential): void
    {
        $errors = $this->validator->validate($credential);
        if (count($errors) === 0) {
            return;
        }
        $items = [];
        foreach ($errors as $error) {
            $items[] = [
                'property' => $error->getPropertyPath(),
                'message' => $error->getMessage(),
            ];
        }
        throw new CredentialOperationException('Validation failed', 422, errors: $items);
    }
}
