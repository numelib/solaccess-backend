<?php

namespace App\Service;

use App\Entity\AccessDevice;

final class AppControllerViewBuilder
{
    public function buildListItem(AccessDevice $device): array
    {
        $lock = $device->getLock();

        return [
            'id' => $device->getId(),
            'name' => $device->getName(),
            'online' => $this->isOnline($device),
            'lastSeenAt' => $device->getLastSeenAt()?->format(\DateTimeInterface::ATOM),
            'configVersion' => $device->getConfigVersion(),
            'lock' => $lock ? [
                'id' => $lock->getId(),
                'name' => $lock->getName(),
                'enabled' => $lock->isEnabled(),
                'access' => [
                    'button' => $lock->isButtonEnabled(),
                    'keypad' => $lock->isKeypadEnabled(),
                    'nfc' => $lock->isNfcEnabled(),
                ],
            ] : null,
        ];
    }

    public function buildDetail(AccessDevice $device): array
    {
        $lock = $device->getLock();

        return [
            'id' => $device->getId(),
            'name' => $device->getName(),
            'online' => $this->isOnline($device),
            'lastSeenAt' => $device->getLastSeenAt()?->format(\DateTimeInterface::ATOM),
            'configVersion' => $device->getConfigVersion(),
            'lockState' => $device->getLockState()?->value,
            'firmwareVersion' => $device->getFirmwareVersion(),
            'lock' => $lock ? [
                'id' => $lock->getId(),
                'name' => $lock->getName(),
                'enabled' => $lock->isEnabled(),
                'unlockMs' => $lock->getUnlockMs(),
                'relayChannel' => $lock->getRelayChannel(),
                'alignment' => [
                    'gpio' => $lock->getAlignGpio(),
                    'activeHigh' => $lock->isAlignActiveHigh(),
                    'delayMs' => $lock->getAlignDelayMs(),
                    'timeoutMs' => $lock->getAlignTimeoutMs(),
                    'closeOnTimeout' => $lock->isCloseOnAlignTimeout(),
                ],
                'access' => [
                    'button' => $lock->isButtonEnabled(),
                    'keypad' => $lock->isKeypadEnabled(),
                    'nfc' => $lock->isNfcEnabled(),
                ],
            ] : null,
        ];
    }

    private function isOnline(AccessDevice $device): bool
    {
        $lastSeenAt = $device->getLastSeenAt();
        if ($lastSeenAt === null) {
            return false;
        }

        // Polling + heartbeat : 90 s laisse une marge raisonnable.
        return $lastSeenAt >= new \DateTimeImmutable('-90 seconds');
    }
}
