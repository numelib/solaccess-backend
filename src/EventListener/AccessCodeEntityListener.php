<?php

namespace App\EventListener;

use App\Entity\AccessCode;
use App\Security\PinHasher;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: AccessCode::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: AccessCode::class)]
class AccessCodeEntityListener
{
    private $pinHasher;

    public function __construct(PinHasher $pinHasher) 
    {
        $this->pinHasher = $pinHasher;
    }

    public function prePersist(AccessCode $code, PrePersistEventArgs $event): void
    {
        $this->hashPlainPin($code);
    }

    public function preUpdate(AccessCode $code, PreUpdateEventArgs $event): void
    {
        $this->hashPlainPin($code);
    }

    private function hashPlainPin(AccessCode $code): void
    {
        $plainPin = trim((string) $code->getPlainPin());

        if ('' === $plainPin) {
            return;
        }

        $salt = $this->pinHasher->generateSalt();
        $code->setPinSalt($salt);

        $code
            ->setPinHash($this->pinHasher->hash($plainPin, $salt))
            ->erasePlainPin();
    }
}