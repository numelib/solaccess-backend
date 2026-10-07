<?php

namespace App\DataFixtures;

use App\Entity\AccessDevice;
use App\Entity\Lock;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LockFixtures extends Fixture implements DependentFixtureInterface
{
    const LOCK_ONE = 'lock_one';
    const LOCK_TWO = 'lock_two';

    public function load(ObjectManager $manager): void
    {
        $device = $this->getReference(AccessDeviceFixtures::DEVICE_ONE, AccessDevice::class);
        $lock = (new Lock())
            ->setName('Lock 1 / Device 1')
            ->setEnabled(true)
            ->setUnlockMs(3000)
            ->setRelayChannel(1)
            ->setAlignGpio(1)
            ->setAlignActiveHigh(true)
            ->setAlignDelayMs(3000)
            ->setAlignTimeoutMs(15000)
            ->setCloseOnAlignTimeout(true)
            ->setButtonEnabled(true)
            ->setKeypadEnabled(true)
            ->setNfcEnabled(true)
            ->setDevice($device);

        $this->addReference(self::LOCK_ONE, $lock);
        $manager->persist($lock);

        $device = $this->getReference(AccessDeviceFixtures::DEVICE_TWO, AccessDevice::class);
        $lock = (new Lock())
            ->setName('Lock 1 / Device 2')
            ->setEnabled(true)
            ->setUnlockMs(3000)
            ->setRelayChannel(1)
            ->setAlignGpio(1)
            ->setAlignActiveHigh(true)
            ->setAlignDelayMs(3000)
            ->setAlignTimeoutMs(15000)
            ->setCloseOnAlignTimeout(true)
            ->setButtonEnabled(true)
            ->setKeypadEnabled(true)
            ->setNfcEnabled(false)
            ->setDevice($device);

        $this->addReference(self::LOCK_TWO, $lock);
        $manager->persist($lock);

        $manager->flush();
    }

    public function getDependencies(): array
    {
    	return [
            AccessDeviceFixtures::class,
    	];
    }
}
