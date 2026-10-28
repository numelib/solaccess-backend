<?php

namespace App\Service\App;

use App\Dto\App\ControllerDetailDto;
use App\Dto\App\ControllerListItemDto;
use App\Entity\AccessDevice;

final class AccessDeviceViewBuilder
{
    /**
     * À ajuster selon la fréquence réelle de heartbeat/polling de l'ESP32.
     * Exemple : pour un heartbeat toutes les 30s, 120s laisse une marge confortable.
     */
    private const ONLINE_THRESHOLD_SECONDS = 120;

    public function buildListItem(AccessDevice $device): ControllerListItemDto
    {
        $lock = $device->getLock();

        return new ControllerListItemDto(
            id: (int) $device->getId(),
            name: (string) $device->getName(),
            enabled: (bool) $device->isEnabled(),
            online: $this->isOnline($device),
            lastSeenAt: $device->getLastSeenAt()?->format(\DateTimeInterface::ATOM),
            configVersion: (int) $device->getConfigVersion(),
            lock: $lock === null ? null : [
                'id' => $lock->getId(),
                'name' => $lock->getName(),
                'enabled' => $lock->isEnabled(),
                'access' => [
                    'button' => $lock->isButtonEnabled(),
                    'keypad' => $lock->isKeypadEnabled(),
                    'nfc' => $lock->isNfcEnabled(),
                ],
            ],
        );
    }

    public function buildDetail(AccessDevice $device): ControllerDetailDto
    {
        $lock = $device->getLock();

        return new ControllerDetailDto(
            id: (int) $device->getId(),
            name: (string) $device->getName(),
            enabled: (bool) $device->isEnabled(),
            online: $this->isOnline($device),
            lastSeenAt: $device->getLastSeenAt()?->format(\DateTimeInterface::ATOM),
            lastSyncAt: $device->getLastSyncAt()?->format(\DateTimeInterface::ATOM),
            firmwareVersion: $device->getFirmwareVersion(),
            configVersion: (int) $device->getConfigVersion(),
            lockState: $device->getLockState()?->value,
            lock: $lock === null ? null : [
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
            ],
        );
    }

    private function isOnline(AccessDevice $device): bool
    {
        $lastSeenAt = $device->getLastSeenAt();

        if ($lastSeenAt === null) {
            return false;
        }

        return $lastSeenAt >= new \DateTimeImmutable(
            sprintf('-%d seconds', self::ONLINE_THRESHOLD_SECONDS),
        );
    }
}
