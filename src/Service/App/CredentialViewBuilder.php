<?php

namespace App\Service\App;

use App\Entity\AccessBadge;
use App\Entity\AccessCode;

final class CredentialViewBuilder
{
    public function buildCode(AccessCode $code): array
    {
        $contact = $code->getContact();
        $lock = $code->getLock();
        $device = $lock?->getDevice();

        return [
            'id' => $this->publicId('pin', (int) $code->getId()),
            'entityId' => $code->getId(),
            'type' => 'pin',
            'contact' => $contact === null ? null : [
                'id' => $contact->getId(),
                'firstname' => $contact->getFirstname(),
                'lastname' => $contact->getLastname(),
                'name' => (string) $contact,
            ],
            'controllerId' => $device?->getId(),
            'lockId' => $lock?->getId(),
            'enabled' => $code->isEnabled(),
            'enablingAt' => $code->getEnablingAt()?->format(\DateTimeInterface::ATOM),
            'disablingAt' => $code->getDisablingAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    public function buildBadge(AccessBadge $badge): array
    {
        $contact = $badge->getContact();
        $lock = $badge->getLock();
        $device = $lock?->getDevice();

        return [
            'id' => $this->publicId('nfc', (int) $badge->getId()),
            'entityId' => $badge->getId(),
            'type' => 'nfc',
            'contact' => $contact === null ? null : [
                'id' => $contact->getId(),
                'firstname' => $contact->getFirstname(),
                'lastname' => $contact->getLastname(),
                'name' => (string) $contact,
            ],
            'controllerId' => $device?->getId(),
            'lockId' => $lock?->getId(),
            'enabled' => $badge->isEnabled(),
            'enablingAt' => $badge->getEnablingAt()?->format(\DateTimeInterface::ATOM),
            'disablingAt' => $badge->getDisablingAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    public function publicId(string $type, int $id): string
    {
        return sprintf('%s-%d', $type, $id);
    }

    /** @return array{0: 'pin'|'nfc', 1: int}|null */
    public function parsePublicId(string $publicId): ?array
    {
        if (!preg_match('/^(pin|nfc)-([1-9]\d*)$/', $publicId, $matches)) {
            return null;
        }

        return [$matches[1], (int) $matches[2]];
    }
}
