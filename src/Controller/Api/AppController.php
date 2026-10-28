<?php

namespace App\Controller\Api;

use App\Config\LockStates;
use App\Entity\AccessDevice;
use App\Entity\AccessLog;
use App\Entity\Contact;
use App\Entity\User;
use App\Repository\CommandRepository;
use App\Service\DeviceConfigBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route("/api/app", name: "api_app_")]
final class AppController extends AbstractController
{

}

?>