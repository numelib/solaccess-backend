<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Controle uniquement la connexion Symfony /admin.
 * UserChecker reste independant du role, car Bridge le reutilise.
 */
final class BackofficeUserChecker implements UserCheckerInterface
{
    public function __construct(private readonly UserChecker $userChecker)
    {
    }

    public function checkPreAuth(UserInterface $user): void
    {
        $this->userChecker->checkPreAuth($user);
        $this->assertAdministrator($user);
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        $this->userChecker->checkPostAuth($user);
        $this->assertAdministrator($user);
    }

    private function assertAdministrator(UserInterface $user): void
    {
        if (!$user instanceof User
            || !in_array('ROLE_ADMIN', $user->getRoles(), true)
            || $user->isActive() !== true
            || $user->getPassword() === null
            || $user->getPassword() === ''
        ) {
            throw new CustomUserMessageAccountStatusException('Identifiants invalides.');
        }
    }
}
