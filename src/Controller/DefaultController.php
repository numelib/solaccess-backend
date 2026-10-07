<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;


final class DefaultController extends AbstractController
{
    #[Route(name: 'index', path: '/')]
    public function index(): Response
    {
        return $this->redirectToRoute('admin');
    }

}
