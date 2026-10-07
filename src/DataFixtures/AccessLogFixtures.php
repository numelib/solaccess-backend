<?php

namespace App\DataFixtures;

use App\Entity\AccessLog;
use App\Entity\Lock;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AccessLogFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $lock = $this->getReference(LockFixtures::LOCK_ONE, Lock::class);
        $occuredAt = time() - 24 * 60 * 60;
        $log = (new AccessLog())
            ->setOccurredAt(\DateTimeImmutable::createFromTimestamp($occuredAt))
            ->setSource('keypad')
            ->setReason('PIN_INVALID')
            ->setType('unlock')
            ->setLock($lock);

        $manager->persist($log);

        $occuredAt -= 24 * 60 * 60;
        $log = (new AccessLog())
            ->setOccurredAt(\DateTimeImmutable::createFromTimestamp($occuredAt))
            ->setSource('keypad')
            ->setReason('PIN_OK')
            ->setType('unlock')
            ->setLock($lock);

        $manager->persist($log);

        $occuredAt -= 24 * 60 * 60;
        $log = (new AccessLog())
            ->setOccurredAt(\DateTimeImmutable::createFromTimestamp($occuredAt))
            ->setSource('keypad')
            ->setReason('PIN_OK')
            ->setType('unlock')
            ->setLock($lock);

        $manager->persist($log);

        $occuredAt -= 24 * 60 * 60;
        $log = (new AccessLog())
            ->setOccurredAt(\DateTimeImmutable::createFromTimestamp($occuredAt))
            ->setSource('keypad')
            ->setReason('PIN_OK')
            ->setType('unlock')
            ->setLock($lock);

        $manager->persist($log);

        $lock = $this->getReference(LockFixtures::LOCK_TWO, Lock::class);
        $occuredAt = time() - 72 * 60 * 60;
        $log = (new AccessLog())
            ->setOccurredAt(\DateTimeImmutable::createFromTimestamp($occuredAt))
            ->setSource('keypad')
            ->setReason('PIN_INVALID')
            ->setType('unlock')
            ->setLock($lock);

        $manager->persist($log);

        $occuredAt -= 48 * 60 * 60;
        $log = (new AccessLog())
            ->setOccurredAt(\DateTimeImmutable::createFromTimestamp($occuredAt))
            ->setSource('keypad')
            ->setReason('PIN_INVALID')
            ->setType('unlock')
            ->setLock($lock);

        $manager->persist($log);

        $occuredAt -= 48 * 60 * 60;
        $log = (new AccessLog())
            ->setOccurredAt(\DateTimeImmutable::createFromTimestamp($occuredAt))
            ->setSource('keypad')
            ->setReason('PIN_INVALID')
            ->setType('unlock')
            ->setLock($lock);

        $manager->persist($log);

        $occuredAt -= 48 * 60 * 60;
        $log = (new AccessLog())
            ->setOccurredAt(\DateTimeImmutable::createFromTimestamp($occuredAt))
            ->setSource('keypad')
            ->setReason('PIN_OK')
            ->setType('unlock')
            ->setLock($lock);

        $manager->persist($log);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            LockFixtures::class,
        ];
    }
}
