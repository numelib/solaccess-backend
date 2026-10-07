<?php

namespace App\Controller\Admin;

use App\Config\LockStates;
use App\Controller\Admin\LockCrudController;
use App\Entity\AccessDevice;
use App\Entity\AccessLog;
use App\Entity\Command;
use App\Entity\Lock;
use App\Entity\User;
use App\Field\RawHTMLField;
use App\Repository\AccessDeviceRepository;
use App\Repository\AccessLogRepository;
use App\Service\AccessDeviceTokenFactory;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Override;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Translation\TranslatableMessage;

class AccessDeviceCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AccessDeviceTokenFactory $tokenFactory,
        private readonly AdminUrlGeneratorInterface $adminUrlGenerator,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public static function getEntityFqcn(): string
    {
        return AccessDevice::class;
    }

    #[Override]
    public function configureActions(Actions $actions): Actions
    {
        $regenerateToken = Action::new("regenerateToken", "regenerate_token")
            ->askConfirmation(
                new TranslatableMessage("confirm_token_regeneration"),
                new TranslatableMessage("confirm_token_regeneration_label"),
            )
            ->asDangerAction()
            ->setIcon("fa-solid fa-repeat")
            ->linkToCrudAction("regenerateToken");

        $unlockDevice = Action::new("unlockDevice", "unlock_device")
            ->asSuccessAction()
            ->setIcon("fa-solid fa-unlock-keyhole")
            ->linkToCrudAction("unlockDevice")
            ->renderAsButton()
            ->displayIf(
                static fn(
                    AccessDevice $device,
                ): bool => $device->getLockState() === LockStates::LOCKED,
            );

        $actions
            ->add(Crud::PAGE_DETAIL, $regenerateToken)
            ->add(Crud::PAGE_DETAIL, $unlockDevice)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->remove(Crud::PAGE_INDEX, Action::DELETE);

        return $actions;
    }

    #[Override]
    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addJsFile(
                Asset::new("js/access_device.js")->htmlAttr("type", "module"),
            )
            ->addCssFile("styles/app.css");
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular("controller")
            ->setEntityLabelInPlural("controllers")
            ->setDefaultSort(["id" => "DESC"])
            ->setDefaultRowAction(Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab("controller");
        yield FormField::addFieldset("identification")
            ->addCssClass("mb-0")
            ->setIcon("fa fa fa-id-card");

        if ($pageName !== Crud::PAGE_DETAIL) {
            yield FormField::addColumn(6)->hideOnDetail();
        }

        yield TextField::new("name", "name");
        yield TextField::new("tokenPublicId", "token_public_id")->hideOnForm();
        yield BooleanField::new("enabled", "enabled");
        yield AssociationField::new("user", "owner");

        if ($pageName === Crud::PAGE_DETAIL) {
            yield FormField::addFieldset("command_history")->setIcon(
                "fa fa-solid fa-list",
            );

            yield RawHTMLField::new("")
                ->onlyOnDetail()
                ->setLabel(false)
                ->setInnerHTML(
                    $this->renderView(
                        "admin/includes/command_history.html.twig",
                    ),
                );
        }

        // Champ factice pour forcer le chargement du script easyadmin collection-field.js
        // sinon l'ajout via prototype de fonctionne sur le embedform
        // hack temporaire le temps de trouver mieux
        yield FormField::addColumn(12);
        yield FormField::addFieldset("Dummy")->setCssClass("d-none");
        yield CollectionField::new("dummyField", "Dummy")
            ->onlyOnForms()
            ->setFormTypeOptions([
                "mapped" => false,
                "attr" => ["class" => "d-done"],
            ])
            ->setCssClass("d-done");

        yield IntegerField::new(
            "configVersion",
            "config_version",
        )->hideOnForm();

        yield TextField::new("firmwareVersion", "firmware")->hideOnForm();
        yield TextField::new("lastIp", "last_ip")->hideOnForm();
        yield DateTimeField::new("lastSeenAt", "last_contact")->hideOnForm();
        yield DateTimeField::new("lastSyncAt", "last_sync_at")->hideOnForm();

        yield ChoiceField::new("lockState", "lock_state")
            ->setTemplatePath("admin/field/access_device/lock_state.html.twig")
            ->setChoices(LockStates::cases())
            ->hideOnForm();

        if ($pageName === Crud::PAGE_DETAIL) {
            yield FormField::addTab("access_tab");
            yield FormField::addFieldset("access_logs", "fa fa-solid fa-list");
            yield Field::new("")->setFormType(
                "EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormColumnCloseType",
            );
            $accessDevice = $this->getContext()->getEntity()->getInstance();
            $accessLogs = $this->entityManager
                ->getRepository(AccessLog::class)
                ->findLatestByDevice($accessDevice);
            yield FormField::addRow();
            yield RawHTMLField::new("")
                ->onlyOnDetail()
                ->setLabel(false)
                ->setInnerHTML(
                    $this->renderView(
                        "admin/includes/access_log_history.html.twig",
                        ["accessLogs" => $accessLogs],
                    ),
                );
        }
        yield FormField::addTab("access_tab");
        yield AssociationField::new("lock", "access_device_lock")
            ->renderAsEmbeddedForm(LockCrudController::class)
            ->setColumns(12)
            ->setFormTypeOption("label", "")
            ->onlyOnForms();
    }

    public function createEntity(string $entityFqcn): AccessDevice
    {
        $device = new AccessDevice();
        $lock = new Lock()->setName("Nouvelle serrure");

        $device->setLock($lock);

        return $device;
    }

    /**
     * @param AccessDevice $entityInstance
     */
    public function persistEntity(
        EntityManagerInterface $entityManager,
        $entityInstance,
    ): void {
        $this->syncContactsManager($entityInstance);

        if (!$entityInstance instanceof AccessDevice) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }

        $plainToken = null;

        if (
            !$entityInstance->getTokenPublicId() ||
            !$entityInstance->getTokenHash()
        ) {
            $plainToken = $this->tokenFactory->regenerateToken($entityInstance);
        }

        if (null === $entityInstance->getLock()) {
            $entityInstance->setLock(
                new Lock()->setName($entityInstance->getName() ?: "Serrure"),
            );
        }

        $entityInstance->bumpConfigVersion();

        //force l'écoute du AccessCodeEntityListener
        $accessCodes = $entityInstance->getLock()?->getCodes();
        if ($accessCodes) {
            foreach ($accessCodes as $accessCode) {
                $accessCode->setUpdatedAt(new \DateTime());
            }
        }

        $entityInstance->setLockState(LockStates::LOCKED);

        parent::persistEntity($entityManager, $entityInstance);

        if ($plainToken) {
            $params = [
                "%device_name%" => $entityInstance->getName(),
                "%token%" => $plainToken,
            ];
            $this->addFlash(
                "warning",
                new TranslatableMessage(
                    "generated_token",
                    $params,
                    domain: "flashes",
                ),
            );
        }
    }

    /**
     * @param AccessDevice $entityInstance
     */
    public function updateEntity(
        EntityManagerInterface $entityManager,
        $entityInstance,
    ): void {
        $this->syncContactsManager($entityInstance);

        if (!$entityInstance instanceof AccessDevice) {
            parent::updateEntity($entityManager, $entityInstance);
            return;
        }

        if (null === $entityInstance->getLock()) {
            $entityInstance->setLock(
                new Lock()->setName($entityInstance->getName() ?: "Serrure"),
            );
        }

        $entityInstance->bumpConfigVersion();

        //force l'écoute du AccessCodeEntityListener
        $accessCodes = $entityInstance->getLock()?->getCodes();
        if ($accessCodes) {
            foreach ($accessCodes as $accessCode) {
                $accessCode->setUpdatedAt(new \DateTime());
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function syncContactsManager(AccessDevice $device)
    {
        $manager = $device->getUser();
        foreach ($device->getLock()->getCodes() as $code) {
            $contact = $code->getContact();
            // If contact has been created on the fly and not selected in an existing list
            // Set his manager to the user owning the device
            if ($contact->getId() === null) {
                $contact->setManager($manager);
            }
        }

        foreach ($device->getLock()->getBadges() as $badge) {
            $contact = $badge->getContact();
            // If contact has been created on the fly and not selected in an existing list
            // Set his manager to the user owning the device
            if ($contact->getId() === null) {
                $contact->setManager($manager);
            }
        }
    }

    #[AdminRoute("/{entityId:accessDevice.id}/regenerate-token")]
    public function regenerateToken(
        AccessDevice $accessDevice,
        EntityManagerInterface $entityManager,
    ): Response {
        $plainToken = $this->tokenFactory->regenerateToken($accessDevice);
        $entityManager->flush();
        $params = [
            "%device_name%" => $accessDevice->getName(),
            "%token%" => $plainToken,
        ];
        $this->addFlash(
            "warning",
            new TranslatableMessage(
                "generated_token",
                $params,
                domain: "flashes",
            ),
        );

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Crud::PAGE_INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    #[AdminRoute("/{entityId:accessDevice.id}/unlock")]
    public function unlockDevice(
        AccessDevice $accessDevice,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var Command */
        $command =
            $entityManager
                ->getRepository(Command::class)
                ->findLastInProgressByDevice($accessDevice) ??
            new Command()->setAccessDevice($accessDevice);

        $command->setIssuedAt(new \DateTimeImmutable());

        $entityManager->persist($command);
        $entityManager->flush();

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction("commandStatus")
            ->setEntityId($accessDevice->getId())
            ->generateUrl();

        return $this->json(["statusUrl" => $url]);
    }

    // Endpoint de polling JS
    #[AdminRoute("/lock-states")]
    public function lockState(
        AccessDeviceRepository $repository,
        #[MapQueryParameter(filter: \FILTER_VALIDATE_INT)] array $ids,
    ): JsonResponse {
        $devices = $repository->findLockStates($ids);
        $data = [];
        foreach ($devices as $device) {
            $data[$device->getId()] = $device->getLockState()->value;
        }

        return new JsonResponse($data);
    }

    /**
     * @param int $since - Show command status issued since last x days
     */
    #[AdminRoute("/{entityId:accessDevice.id}/status")]
    public function commandStatus(
        AccessDevice $accessDevice,
        EntityManagerInterface $entityManager,
        #[MapQueryParameter] int $page,
        #[MapQueryParameter] ?int $since = null,
    ): JsonResponse {
        $query = $entityManager
            ->getRepository(Command::class)
            ->createQueryBuilder("command")
            ->where("command.accessDevice = :device");

        if ($since !== null) {
            $limitDate = \DateTimeImmutable::createFromTimestamp(
                time() - $since * 24 * 60 * 60,
            );
            $query
                ->andWhere("command.issuedAt >= :limitDate")
                ->setParameter("limitDate", $limitDate);
            $count = $entityManager
                ->getRepository(Command::class)
                ->countIssuedAfter($limitDate);
        } else {
            $count = $entityManager->getRepository(Command::class)->count();
        }

        $limit = 20;
        $offset = ($page - 1) * $limit;
        $pageCount = ceil($count / $limit);

        $commands = $query
            ->orderBy("command.issuedAt", "DESC")
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->setParameter("device", $accessDevice)
            ->getQuery()
            ->getResult();

        return new JsonResponse([
            "page" => $page,
            "pageCount" => $pageCount,
            "commands" => array_map(static function (Command $command) {
                return [
                    "id" => $command->getId(),
                    "type" => "unlock_device",
                    "status" => $command->getStatus()->value,
                    "issuedAt" => $command->getIssuedAt()?->getTimestamp(),
                    "processedAt" => $command
                        ->getProcessedAt()
                        ?->getTimestamp(),
                    "timeoutAt" => $command->getTimeoutAt()?->getTimestamp(),
                ];
            }, $commands),
        ]);
    }
}
