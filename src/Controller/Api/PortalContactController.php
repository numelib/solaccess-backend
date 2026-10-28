<?php

namespace App\Controller\Api;

use App\Entity\Contact;
use App\Entity\User;
use App\Repository\ContactRepository;
use App\Service\App\CredentialViewBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/app/contacts', name: 'api_app_contacts_')]
final class PortalContactController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(ContactRepository $contactRepository): JsonResponse
    {
        $user = $this->requireUser();
        $contacts = $contactRepository->findBy(
            ['manager' => $user],
            ['lastname' => 'ASC', 'firstname' => 'ASC'],
        );

        return $this->json([
            'items' => array_map(
                fn (Contact $contact) => $this->buildContact($contact, false),
                $contacts,
            ),
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->requireUser();
        $payload = $this->decodeJson($request);
        $firstname = trim((string) ($payload['firstname'] ?? ''));
        $lastname = trim((string) ($payload['lastname'] ?? ''));

        if ($firstname === '' || $lastname === '') {
            return $this->json(['message' => 'firstname and lastname are required'], 422);
        }

        $contact = (new Contact())
            ->setFirstname($firstname)
            ->setLastname($lastname)
            ->setManager($user);

        $em->persist($contact);
        $em->flush();

        return $this->json($this->buildContact($contact, true), 201);
    }

    #[Route('/{id<\d+>}', name: 'detail', methods: ['GET'])]
    public function detail(
        int $id,
        ContactRepository $contactRepository,
        CredentialViewBuilder $credentialViewBuilder,
    ): JsonResponse {
        $contact = $this->findOwnedContact($id, $contactRepository);
        return $this->json($this->buildContact($contact, true, $credentialViewBuilder));
    }

    #[Route('/{id<\d+>}', name: 'update', methods: ['PATCH'])]
    public function update(
        int $id,
        Request $request,
        ContactRepository $contactRepository,
        EntityManagerInterface $em,
        CredentialViewBuilder $credentialViewBuilder,
    ): JsonResponse {
        $contact = $this->findOwnedContact($id, $contactRepository);
        $payload = $this->decodeJson($request);

        if (array_key_exists('firstname', $payload)) {
            $firstname = trim((string) $payload['firstname']);
            if ($firstname === '') {
                return $this->json(['message' => 'firstname cannot be empty'], 422);
            }
            $contact->setFirstname($firstname);
        }

        if (array_key_exists('lastname', $payload)) {
            $lastname = trim((string) $payload['lastname']);
            if ($lastname === '') {
                return $this->json(['message' => 'lastname cannot be empty'], 422);
            }
            $contact->setLastname($lastname);
        }

        $em->flush();
        return $this->json($this->buildContact($contact, true, $credentialViewBuilder));
    }

    #[Route('/{id<\d+>}', name: 'delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        ContactRepository $contactRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        $contact = $this->findOwnedContact($id, $contactRepository);

        if (!$contact->getAccessCodes()->isEmpty() || !$contact->getAccessBadges()->isEmpty()) {
            return $this->json([
                'message' => 'Contact still has credentials',
                'code' => 'contact_has_credentials',
            ], 409);
        }

        $em->remove($contact);
        $em->flush();
        return new JsonResponse(null, 204);
    }

    private function buildContact(
        Contact $contact,
        bool $includeCredentials,
        ?CredentialViewBuilder $credentialViewBuilder = null,
    ): array {
        $data = [
            'id' => $contact->getId(),
            'firstname' => $contact->getFirstname(),
            'lastname' => $contact->getLastname(),
            'name' => (string) $contact,
        ];

        if (!$includeCredentials) {
            $data['credentialCount'] = $contact->getAccessCodes()->count() + $contact->getAccessBadges()->count();
            return $data;
        }

        $credentials = [];
        if ($credentialViewBuilder !== null) {
            foreach ($contact->getAccessCodes() as $code) {
                $credentials[] = $credentialViewBuilder->buildCode($code);
            }
            foreach ($contact->getAccessBadges() as $badge) {
                $credentials[] = $credentialViewBuilder->buildBadge($badge);
            }
        }
        $data['credentials'] = $credentials;
        return $data;
    }

    private function findOwnedContact(int $id, ContactRepository $contactRepository): Contact
    {
        $user = $this->requireUser();
        $contact = $contactRepository->findOneBy(['id' => $id, 'manager' => $user]);

        if (!$contact instanceof Contact) {
            throw $this->createNotFoundException('Contact not found');
        }
        return $contact;
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        return $user;
    }

    private function decodeJson(Request $request): array
    {
        $payload = json_decode($request->getContent(), true);
        return is_array($payload) ? $payload : [];
    }
}
