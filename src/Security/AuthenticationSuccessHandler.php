<?php

namespace App\Security;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Security $security,
        private readonly TokenStorageInterface $tokenStorage,
    ){}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();
        if($this->security->isGrantedForUser($user, 'ROLE_ADMIN')) {
            return new RedirectResponse($this->urlGenerator->generate('admin'));
        }

        // Le formulaire Symfony ne donne plus accès à un espace utilisateur.
        // Ne pas conserver de session Symfony après une connexion non-admin.
        $this->tokenStorage->setToken(null);
        $request->getSession()->invalidate();

        return new Response('Le tableau de bord utilisateur Solaccess est désormais accessible via le Portail Numelib.', Response::HTTP_FORBIDDEN);
    }
}
