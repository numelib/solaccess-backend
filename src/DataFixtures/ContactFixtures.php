<?php

namespace App\DataFixtures;

use App\Config\LockStates;
use App\Entity\AccessDevice;
use App\Entity\Building;
use App\Entity\Contact;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ContactFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $contacts = [
            [
                "Captain",
                "Flam",
                $this->getReference(UserFixtures::USER_ONE, User::class),
            ],
            [
                "Patrick",
                "Attend",
                $this->getReference(UserFixtures::USER_ONE, User::class),
            ],
            [
                "Josiane",
                "Belaskip",
                $this->getReference(UserFixtures::USER_TWO, User::class),
            ],
        ];

        foreach ($contacts as $contact) {
            $contact = new Contact()
                ->setFirstname($contact[0])
                ->setLastname($contact[1])
                ->setManager($contact[2]);

            $manager->persist($contact);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
