<?php

namespace App\Config;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum LockStates: string implements TranslatableInterface
{
    case LOCKED = 'LOCKED';
    case UNLOCKED = 'UNLOCKED';
    case WAIT_ALIGN_DELAY = 'WAIT_ALIGN_DELAY';
    case WAIT_ALIGN = 'WAIT_ALIGN';
    case LOCKING = 'LOCKING';
    case ERROR = 'ERROR';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::LOCKED  => $translator->trans('lock_states.locked', locale: $locale),
            self::UNLOCKED => $translator->trans('lock_states.unlocked', locale: $locale),
            self::WAIT_ALIGN_DELAY  => $translator->trans('lock_states.unlocked', locale: $locale),
            self::WAIT_ALIGN => $translator->trans('lock_states.unlocked', locale: $locale),
            self::LOCKING  => $translator->trans('lock_states.unlocked', locale: $locale),
            self::ERROR => $translator->trans('lock_states.locked', locale: $locale),
        };
    }
}
