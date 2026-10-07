<?php

namespace App\DataFixtures;

use App\Entity\Building;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class BuildingFixtures extends Fixture
{
    const BUILDING_ONE = 'building_one';
    const BUILDING_TWO = 'building_two';

    public function load(ObjectManager $manager): void
    {
        $building = (new Building())
            ->setAddressCity('Paris')
            ->setAddressCountry('FR')
            ->setAddressLine('918 rue des chameaux')
            ->setAddressPostCode('75001')
            ->setLabel('Siège social EDF');

        $manager->persist($building);
        $this->addReference(self::BUILDING_ONE, $building);

        $building = (new Building())
            ->setAddressCity('Toulouse')
            ->setAddressCountry('FR')
            ->setAddressLine('36 rue des pigeons')
            ->setAddressPostCode('31100')
            ->setLabel('Boulangerie Feuillet');

        $manager->persist($building);
        $this->addReference(self::BUILDING_TWO, $building);

        $manager->flush();
    }
}
