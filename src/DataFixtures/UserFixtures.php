<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    const USER_ONE = 'user_one';
    const USER_TWO = 'user_two';

    public function __construct(
        private readonly UserPasswordHasherInterface $userPasswordHasher,
    ){}

    public function load(ObjectManager $manager): void
    {
        $user = (new User())
            ->setEmail('admin@test.fr')
            ->setFirstname('Admin')
            ->setLastname('Admin')
            ->setIsActive(true)
            ->setRoles(['ROLE_ADMIN']);

        $user->setPassword($this->userPasswordHasher->hashPassword($user, 'admin'));
        $manager->persist($user);

        $user = (new User())
            ->setEmail('user@test.fr')
            ->setFirstname('John')
            ->setLastname('Doe')
            ->setIsActive(true)
            ->setRoles(['ROLE_USER']);

        $user->setPassword($this->userPasswordHasher->hashPassword($user, 'user'));
        $this->addReference(self::USER_ONE, $user);
        $manager->persist($user);

        $user = (new User())
            ->setEmail('other@test.fr')
            ->setFirstname('Other')
            ->setLastname('Doe')
            ->setIsActive(true)
            ->setRoles(['ROLE_USER']);

        $user->setPassword($this->userPasswordHasher->hashPassword($user, 'other'));
        $this->addReference(self::USER_TWO, $user);
        $manager->persist($user);

        $user = (new User())
            ->setEmail('disabled@test.com')
            ->setFirstname('John')
            ->setLastname('Smith')
            ->setIsActive(false)
            ->setRoles(['ROLE_USER']);

        $user->setPassword($this->userPasswordHasher->hashPassword($user, 'disabled'));
        $manager->persist($user);

        $manager->flush();
    }
}
