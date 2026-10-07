<?php

namespace App\Service;

use App\Entity\AccessBadge;
use App\Entity\AccessCode;
use App\Entity\AccessDevice;
use App\Repository\CommandRepository;

class DeviceConfigBuilder
{
    public function __construct(
        private readonly CommandRepository $commandRepository,
    ) {}

    public function build(AccessDevice $device): array
    {
        $lock = $device->getLock();

        $command = $this->commandRepository->findLastInProgressByDevice(
            $device,
        );

        $pendingCommand = $command === null ? null : "unlock";

        return [
            "ok" => true,
            "configVersion" => $device->getConfigVersion(),
            "pendingCommand" => $pendingCommand,
            "device" => [
                "name" => $device->getName(),
            ],
            "lock" => $lock
                ? [
                    "id" => $lock->getId(),
                    "name" => $lock->getName(),
                    "enabled" => $lock->isEnabled(),
                    "unlockMs" => $lock->getUnlockMs(),
                    "relayChannel" => $lock->getRelayChannel(),
                    "alignGpio" => $lock->getAlignGpio(),
                    "alignActiveHigh" => $lock->isAlignActiveHigh(),
                    "alignDelayMs" => $lock->getAlignDelayMs(),
                    "alignTimeoutMs" => $lock->getAlignTimeoutMs(),
                    "closeOnAlignTimeout" => $lock->isCloseOnAlignTimeout(),
                ]
                : null,
            "access" => $lock
                ? [
                    "buttonEnabled" => $lock->isButtonEnabled(),
                    "keypadEnabled" => $lock->isKeypadEnabled(),
                    "nfcEnabled" => $lock->isNfcEnabled(),
                ]
                : null,
            "codes" => $lock
                ? $this->buildCodes($lock->getCodes()->toArray())
                : [],
            "badges" => $lock
                ? $this->buildBadges($lock->getBadges()->toArray())
                : [],
        ];
    }

    /**
     * @param AccessCode[] $codes
     */
    private function buildCodes(array $codes): array
    {
        return array_map(
            static fn(AccessCode $code) => [
                "id" => $code->getId(),
                "label" => $code->__toString(),
                "userId" => $code->getContact()?->getId(),
                "devicePinHash" => $code->getPinHash(),
                "pinSalt" => $code->getPinSalt(),
                "enabled" => $code->isEnabled(),
                "generic" => false,
            ],
            $codes,
        );
    }

    /**
     * @param AccessBadge[] $badges
     */
    private function buildBadges(array $badges): array
    {
        return array_map(
            static fn(AccessBadge $badge) => [
                "id" => $badge->getId(),
                "label" => $badge->__toString(),
                "userId" => $badge->getContact()?->getId(),
                "uid" => $badge->getUid(),
                "enabled" => $badge->isEnabled(),
            ],
            $badges,
        );
    }
}
