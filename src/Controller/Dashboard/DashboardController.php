<?php

namespace App\Controller\Dashboard;

use App\Controller\Dashboard\LockCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyAdminFriends\EasyAdminDashboardBundle\Service\EasyAdminDashboard;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/dashboard', routeName: 'dashboard', allowedControllers: [
    AccessBadgeCrudController::class,
    AccessCodeCrudController::class,
    AccessLogCrudController::class,
    BuildingCrudController::class,
    LockCrudController::class,
    AccessDeviceCrudController::class,
])]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly EasyAdminDashboard $easyAdminDashboard
    ){}

    public function index(): Response
    {
        return $this->render('@EasyAdminDashboard/Default/index.html.twig', [
            'dashboard' => $this->easyAdminDashboard->getDashboard()
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Solaccess');
    }

    public function configureCrud(): Crud
    {
        return Crud::new()
            ->showEntityActionsInlined()
        ;
    }

    public function configureActions(): Actions
    {
        $actions = parent::configureActions();

        return $actions
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->update(Crud::PAGE_EDIT, Action::INDEX, function (Action $action) {
                return $action->setIcon("fa fa-arrow-left");
            })

            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->update(Crud::PAGE_NEW, Action::INDEX, function (Action $action) {
                return $action->setIcon("fa fa-arrow-left");
            })
        ;
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::linkTo(BuildingCrudController::class, 'buildings', 'fa-solid fa-building');

        yield MenuItem::section('access_control');
        yield MenuItem::linkTo(AccessDeviceCrudController::class, 'controllers', 'fas fa-microchip');
        yield MenuItem::linkTo(LockCrudController::class, 'locks', 'fas fa-lock');
        yield MenuItem::linkTo(AccessLogCrudController::class, 'logs', 'fas fa-list');
    }
}
