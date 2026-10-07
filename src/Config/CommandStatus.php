<?php

namespace App\Config;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum CommandStatus: string implements TranslatableInterface
{
    case TIMEOUT = "timeout";
    case PROCESSED = "processed";
    case IN_PROGRESS = "in_progress";

    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return match ($this) {
            self::TIMEOUT => $translator->trans(
                "command_status.timeout",
                locale: $locale,
            ),
            self::PROCESSED => $translator->trans(
                "command_status.processed",
                locale: $locale,
            ),
            self::IN_PROGRESS => $translator->trans(
                "command_status.in_progress",
                locale: $locale,
            ),
        };
    }
}
