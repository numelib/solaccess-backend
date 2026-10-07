<?php

namespace App\Security;

use App\Entity\AccessDevice;
use App\Repository\AccessDeviceRepository;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class AccessDeviceUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly AccessDeviceRepository $repository,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $device = $this->repository->findOneBy([
            'publicId' => $identifier,
            'enabled' => true,
        ]);

        if (!$device instanceof AccessDevice) {
            throw new UserNotFoundException(sprintf('Device "%s" not found.', $identifier));
        }

        return $device;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return AccessDevice::class === $class || is_subclass_of($class, AccessDevice::class);
    }
}