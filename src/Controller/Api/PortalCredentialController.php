<?php

namespace App\Controller\Api;

use App\Entity\AccessBadge;
use App\Entity\AccessCode;
use App\Entity\AccessDevice;
use App\Entity\Contact;
use App\Entity\User;
use App\Repository\AccessBadgeRepository;
use App\Repository\AccessCodeRepository;
use App\Repository\AccessDeviceRepository;
use App\Repository\ContactRepository;
use App\Service\App\CredentialViewBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/app/controllers/{controllerId<\d+>}/credentials', name: 'api_app_credentials_')]
final class PortalCredentialController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        int $controllerId,
        AccessDeviceRepository $accessDeviceRepository,
        CredentialViewBuilder $viewBuilder,
    ): JsonResponse {
        $device = $this->findOwnedDevice($controllerId, $accessDeviceRepository);
        $lock = $device->getLock();

        if ($lock === null) {
            return $this->json(['items' => []]);
        }

        $items = [];
        foreach ($lock->getCodes() as $code) {
            $items[] = $viewBuilder->buildCode($code);
        }
        foreach ($lock->getBadges() as $badge) {
            $items[] = $viewBuilder->buildBadge($badge);
        }

        return $this->json(['items' => $items]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        int $controllerId,
        Request $request,
        AccessDeviceRepository $accessDeviceRepository,
        ContactRepository $contactRepository,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        CredentialViewBuilder $viewBuilder,
    ): JsonResponse {
        $user = $this->requireUser();
        $device = $this->findOwnedDevice($controllerId, $accessDeviceRepository);
        $lock = $device->getLock();

        if ($lock === null) {
            return $this->json(['message' => 'Controller has no lock', 'code' => 'controller_has_no_lock'], 409);
        }

        $payload = $this->decodeJson($request);
        $type = strtolower(trim((string) ($payload['type'] ?? '')));
        $contact = $contactRepository->findOneBy([
            'id' => (int) ($payload['contactId'] ?? 0),
            'manager' => $user,
        ]);

        if (!$contact instanceof Contact) {
            throw $this->createNotFoundException('Contact not found');
        }

        try {
            $enablingAt = $this->parseDate($payload['enablingAt'] ?? null, new \DateTimeImmutable());
            $disablingAt = $this->parseDate($payload['disablingAt'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        if ($type === 'pin') {
            $plainPin = trim((string) ($payload['pin'] ?? ''));
            if ($plainPin === '') {
                return $this->json(['message' => 'pin is required'], 422);
            }

            $credential = (new AccessCode())
                ->setLock($lock)
                ->setUser($user)
                ->setContact($contact)
                ->setEnablingAt($enablingAt)
                ->setDisablingAt($disablingAt)
                ->setPlainPin($plainPin);

            $errors = $validator->validate($credential);
            if (count($errors) > 0) {
                return $this->validationError($errors);
            }

            $em->persist($credential);
            $device->bumpConfigVersion();
            $em->flush();
            $credential->erasePlainPin();

            return $this->json($viewBuilder->buildCode($credential), 201);
        }

        if ($type === 'nfc') {
            $uid = trim((string) ($payload['uid'] ?? ''));
            if ($uid === '') {
                return $this->json(['message' => 'uid is required'], 422);
            }

            $credential = (new AccessBadge())
                ->setLock($lock)
                ->setUser($user)
                ->setContact($contact)
                ->setEnablingAt($enablingAt)
                ->setDisablingAt($disablingAt)
                ->setUid($uid);

            $errors = $validator->validate($credential);
            if (count($errors) > 0) {
                return $this->validationError($errors);
            }

            $em->persist($credential);
            $device->bumpConfigVersion();
            $em->flush();

            return $this->json($viewBuilder->buildBadge($credential), 201);
        }

        return $this->json(['message' => 'type must be pin or nfc'], 422);
    }

    #[Route('/{credentialId}', name: 'update', methods: ['PATCH'])]
    public function update(
        int $controllerId,
        string $credentialId,
        Request $request,
        AccessDeviceRepository $accessDeviceRepository,
        ContactRepository $contactRepository,
        AccessCodeRepository $accessCodeRepository,
        AccessBadgeRepository $accessBadgeRepository,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        CredentialViewBuilder $viewBuilder,
    ): JsonResponse {
        $user = $this->requireUser();
        $device = $this->findOwnedDevice($controllerId, $accessDeviceRepository);
        $lock = $device->getLock();
        if ($lock === null) {
            throw $this->createNotFoundException('Credential not found');
        }

        $parsed = $viewBuilder->parsePublicId($credentialId);
        if ($parsed === null) {
            throw $this->createNotFoundException('Credential not found');
        }
        [$type, $entityId] = $parsed;
        $payload = $this->decodeJson($request);

        $contact = null;
        if (array_key_exists('contactId', $payload)) {
            $contact = $contactRepository->findOneBy([
                'id' => (int) $payload['contactId'],
                'manager' => $user,
            ]);
            if (!$contact instanceof Contact) {
                throw $this->createNotFoundException('Contact not found');
            }
        }

        try {
            $enablingAt = array_key_exists('enablingAt', $payload) ? $this->parseDate($payload['enablingAt']) : null;
            $disablingAt = array_key_exists('disablingAt', $payload) ? $this->parseDate($payload['disablingAt']) : null;
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        if ($type === 'pin') {
            $credential = $accessCodeRepository->findOneBy(['id' => $entityId, 'lock' => $lock]);
            if (!$credential instanceof AccessCode) {
                throw $this->createNotFoundException('Credential not found');
            }

            if ($contact !== null) $credential->setContact($contact);
            if (array_key_exists('enablingAt', $payload)) $credential->setEnablingAt($enablingAt);
            if (array_key_exists('disablingAt', $payload)) $credential->setDisablingAt($disablingAt);
            if (array_key_exists('pin', $payload)) {
                $plainPin = trim((string) $payload['pin']);
                if ($plainPin === '') return $this->json(['message' => 'pin cannot be empty'], 422);
                $credential->setPlainPin($plainPin);
            }

            $errors = $validator->validate($credential);
            if (count($errors) > 0) return $this->validationError($errors);

            $device->bumpConfigVersion();
            $em->flush();
            $credential->erasePlainPin();
            return $this->json($viewBuilder->buildCode($credential));
        }

        $credential = $accessBadgeRepository->findOneBy(['id' => $entityId, 'lock' => $lock]);
        if (!$credential instanceof AccessBadge) {
            throw $this->createNotFoundException('Credential not found');
        }

        if ($contact !== null) $credential->setContact($contact);
        if (array_key_exists('enablingAt', $payload)) $credential->setEnablingAt($enablingAt);
        if (array_key_exists('disablingAt', $payload)) $credential->setDisablingAt($disablingAt);
        if (array_key_exists('uid', $payload)) {
            $uid = trim((string) $payload['uid']);
            if ($uid === '') return $this->json(['message' => 'uid cannot be empty'], 422);
            $credential->setUid($uid);
        }

        $errors = $validator->validate($credential);
        if (count($errors) > 0) return $this->validationError($errors);

        $device->bumpConfigVersion();
        $em->flush();
        return $this->json($viewBuilder->buildBadge($credential));
    }

    #[Route('/{credentialId}', name: 'delete', methods: ['DELETE'])]
    public function delete(
        int $controllerId,
        string $credentialId,
        AccessDeviceRepository $accessDeviceRepository,
        AccessCodeRepository $accessCodeRepository,
        AccessBadgeRepository $accessBadgeRepository,
        EntityManagerInterface $em,
        CredentialViewBuilder $viewBuilder,
    ): JsonResponse {
        $device = $this->findOwnedDevice($controllerId, $accessDeviceRepository);
        $lock = $device->getLock();
        if ($lock === null) throw $this->createNotFoundException('Credential not found');

        $parsed = $viewBuilder->parsePublicId($credentialId);
        if ($parsed === null) throw $this->createNotFoundException('Credential not found');
        [$type, $entityId] = $parsed;

        $credential = $type === 'pin'
            ? $accessCodeRepository->findOneBy(['id' => $entityId, 'lock' => $lock])
            : $accessBadgeRepository->findOneBy(['id' => $entityId, 'lock' => $lock]);

        if ($credential === null) throw $this->createNotFoundException('Credential not found');

        $em->remove($credential);
        $device->bumpConfigVersion();
        $em->flush();
        return new JsonResponse(null, 204);
    }

    private function findOwnedDevice(int $id, AccessDeviceRepository $repository): AccessDevice
    {
        $device = $repository->findOneBy(['id' => $id, 'user' => $this->requireUser()]);
        if (!$device instanceof AccessDevice) throw $this->createNotFoundException('Controller not found');
        return $device;
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) throw $this->createAccessDeniedException();
        return $user;
    }

    private function decodeJson(Request $request): array
    {
        $payload = json_decode($request->getContent(), true);
        return is_array($payload) ? $payload : [];
    }

    private function parseDate(mixed $value, ?\DateTimeImmutable $default = null): ?\DateTimeImmutable
    {
        if ($value === null || $value === '') return $default;
        if (!is_string($value)) throw new \InvalidArgumentException('Invalid date value');

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            throw new \InvalidArgumentException(sprintf('Invalid date: %s', $value));
        }
    }

    private function validationError(iterable $errors): JsonResponse
    {
        $items = [];
        foreach ($errors as $error) {
            $items[] = ['property' => $error->getPropertyPath(), 'message' => $error->getMessage()];
        }
        return $this->json(['message' => 'Validation failed', 'errors' => $items], 422);
    }
}
