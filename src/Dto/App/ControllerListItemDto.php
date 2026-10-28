<?php

namespace App\Dto\App;

final readonly class ControllerListItemDto
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $enabled,
        public bool $online,
        public ?string $lastSeenAt,
        public int $configVersion,
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
            'configVersion' => $this->configVersion,
            'lock' => $this->lock,
        ];
    }
}
