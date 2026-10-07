<?php

namespace App\Controller\Admin;

use App\Controller\Admin\UserAdminCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyAdminFriends\EasyAdminDashboardBundle\Service\EasyAdminDashboard;
use Symfony\Component\HttpFoundation\Response;

#[
    AdminDashboard(
        routePath: "/admin",
        routeName: "admin",
        allowedControllers: [
            AccessBadgeCrudController::class,
            AccessCodeCrudController::class,
            AccessDeviceCrudController::class,
            AccessLogCrudController::class,
            BuildingCrudController::class,
            LockCrudController::class,
            UserAdminCrudController::class,
            UserCrudController::class,
        ],
    ),
]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly EasyAdminDashboard $easyAdminDashboard,
    ) {}

    public function index(): Response
    {
        return $this->render("@EasyAdminDashboard/Default/index.html.twig", [
            "dashboard" => $this->easyAdminDashboard->getDashboard(),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle("Solaccess");
    }

    public function configureCrud(): Crud
    {
        return Crud::new()
            ->overrideTemplate("layout", "admin/layout.html.twig")
            ->showEntityActionsInlined();
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
            });
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard("Dashboard", "fa fa-home");

        yield MenuItem::linkTo(
            BuildingCrudController::class,
            "buildings",
            "fa-solid fa-building",
        );

        yield MenuItem::section("access_control");
        yield MenuItem::linkTo(
            AccessDeviceCrudController::class,
            "controllers",
            "fas fa-microchip",
        );
        yield MenuItem::linkTo(
            AccessLogCrudController::class,
            "logs",
            "fas fa-list",
        );

        yield MenuItem::section("users");
        yield MenuItem::linkTo(
            UserCrudController::class,
            "users",
            "fa-solid fa-user",
        )->setPermission("ROLE_ADMIN");
        yield MenuItem::linkTo(
            UserAdminCrudController::class,
            "administrators",
            "fas fa-user-shield",
        )->setPermission("ROLE_SUPER_ADMIN");
    }
}
