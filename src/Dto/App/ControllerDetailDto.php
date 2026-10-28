<?php

namespace App\Dto\App;

final readonly class ControllerDetailDto
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $enabled,
        public bool $online,
        public ?string $lastSeenAt,
        public ?string $lastSyncAt,
        public ?string $firmwareVersion,
        public int $configVersion,
        public ?string $lockState,
        public ?array $lock,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'enabled' => $this->enabled,
            'online' => $this->online,
            'lastSeenAt' => $this->lastSeenAt,
            'lastSyncAt' => $this->lastSyncAt,
            'firmwareVersion' => $this->firmwareVersion,
            'configVersion' => $this->configVersion,
            'lockState' => $this->lockState,
            'lock' => $this->lock,
        ];
    }
}
