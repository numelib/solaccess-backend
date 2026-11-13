<?php

declare(strict_types=1);

namespace App\Service\Contacts;

use App\Entity\Contact;
use App\Entity\User;
use App\Exception\ContactOperationException;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Règles métier Contacts indépendantes du transport HTTP.
 * Chaque opération reçoit explicitement le compte Solaccess concerné.
 */
final class ContactManager
{
    public function __construct(
        private readonly ContactRepository $contacts,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /** @return list<Contact> */
    public function listFor(User $actor): array
    {
        return $this->contacts->findBy(
            ['manager' => $actor],
            ['lastname' => 'ASC', 'firstname' => 'ASC'],
        );
    }

    public function findFor(User $actor, int $id): Contact
    {
        $contact = $this->contacts->findOneBy(['id' => $id, 'manager' => $actor]);

        if (!$contact instanceof Contact) {
            throw new ContactOperationException('Contact not found', 404);
        }

        return $contact;
    }

    /** @param array<string, mixed> $payload */
    public function createFor(User $actor, array $payload): Contact
    {
        $firstname = $this->requiredName($payload['firstname'] ?? null);
        $lastname = $this->requiredName($payload['lastname'] ?? null);
        if ($firstname === null || $lastname === null) {
            throw new ContactOperationException('firstname and lastname are required', 422);
        }

        $contact = (new Contact())
            ->setFirstname($firstname)
            ->setLastname($lastname)
            ->setManager($actor);

        $this->em->persist($contact);
        $this->em->flush();

        return $contact;
    }

    /** @param array<string, mixed> $payload */
    public function updateFor(User $actor, int $id, array $payload): Contact
    {
        $contact = $this->findFor($actor, $id);

        // Validation complète avant toute mutation (pas de changement partiel).
        $firstname = array_key_exists('firstname', $payload)
            ? $this->requiredName($payload['firstname'])
            : null;
        $lastname = array_key_exists('lastname', $payload)
            ? $this->requiredName($payload['lastname'])
            : null;

        if (array_key_exists('firstname', $payload) && $firstname === null) {
            throw new ContactOperationException('firstname cannot be empty', 422);
        }
        if (array_key_exists('lastname', $payload) && $lastname === null) {
            throw new ContactOperationException('lastname cannot be empty', 422);
        }

        if ($firstname !== null) {
            $contact->setFirstname($firstname);
        }
        if ($lastname !== null) {
            $contact->setLastname($lastname);
        }

        $this->em->flush();

        return $contact;
    }

    public function deleteFor(User $actor, int $id): void
    {
        $contact = $this->findFor($actor, $id);
        if (!$contact->getAccessCodes()->isEmpty() || !$contact->getAccessBadges()->isEmpty()) {
            throw new ContactOperationException(
                'Contact still has credentials',
                409,
                'contact_has_credentials',
            );
        }

        $this->em->remove($contact);
        $this->em->flush();
    }

    private function requiredName(mixed $value): ?string
    {
        // Les tableaux/objets/null sont rejetés plutôt que convertis en "Array".
        if (!is_string($value)) {
            return null;
        }

        $name = trim($value);
        return $name === '' ? null : $name;
    }
}
