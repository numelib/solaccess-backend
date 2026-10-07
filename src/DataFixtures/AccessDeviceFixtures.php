<?php

namespace App\DataFixtures;

use App\Config\LockStates;
use App\Entity\AccessDevice;
use App\Entity\Building;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AccessDeviceFixtures extends Fixture implements DependentFixtureInterface
{
    const DEVICE_ONE = 'access_device_one';
    const DEVICE_TWO = 'access_device_two';

    public function load(ObjectManager $manager): void
    {
        $randomStr = fn() => bin2hex(random_bytes(64));
        $user = $this->getReference(UserFixtures::USER_ONE, User::class);
        $building = $this->getReference(BuildingFixtures::BUILDING_ONE, Building::class);

        $device = (new AccessDevice())
            ->setName('First device')
            ->setEnabled(true)
            ->setBuilding($building)
            ->setUser($user)
            ->setLockState(LockStates::UNLOCKED);

        $this->addReference(self::DEVICE_ONE, $device);
        $manager->persist($device);

        $user = $this->getReference(UserFixtures::USER_TWO, User::class);
        $building = $this->getReference(BuildingFixtures::BUILDING_TWO, Building::class);
        $device = (new AccessDevice())
            ->setName('Second device')
            ->setEnabled(false)
            ->setBuilding($building)
            ->setUser($user)
            ->setLockState(LockStates::LOCKED);

        $this->addReference(self::DEVICE_TWO, $device);
        $manager->persist($device);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            BuildingFixtures::class,
            UserFixtures::class,
        ];
    }
}
