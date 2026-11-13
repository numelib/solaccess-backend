<?php

declare(strict_types=1);

namespace App\Service\Contacts;

use App\Entity\Contact;
use App\Service\Credentials\CredentialViewBuilder;

/**
 * Projection publique Contacts ; les valeurs PIN/UID ne sont pas exposées.
 */
final class ContactViewBuilder
{
    public function __construct(
        private readonly CredentialViewBuilder $credentials,
    ) {
    }

    /** @return array<string, mixed> */
    public function build(Contact $contact, bool $includeCredentials): array
    {
        $data = [
            'id' => $contact->getId(),
            'firstname' => $contact->getFirstname(),
            'lastname' => $contact->getLastname(),
            'name' => (string) $contact,
        ];

        if (!$includeCredentials) {
            $data['credentialCount'] = $contact->getAccessCodes()->count()
                + $contact->getAccessBadges()->count();
            return $data;
        }

        $credentials = [];
        foreach ($contact->getAccessCodes() as $code) {
            $credentials[] = $this->credentials->buildCode($code);
        }
        foreach ($contact->getAccessBadges() as $badge) {
            $credentials[] = $this->credentials->buildBadge($badge);
        }
        $data['credentials'] = $credentials;

        return $data;
    }
}
