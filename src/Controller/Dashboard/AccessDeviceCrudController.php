<?php

namespace App\Controller\Dashboard;

use App\Controller\Admin\LockCrudController;
use App\Entity\AccessDevice;
use App\Entity\Lock;
use App\Service\AccessDeviceTokenFactory;
use Container6uyOF8k\getLockCrudControllerdeleteEntityService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Override;
use Symfony\Component\Translation\TranslatableMessage;

class AccessDeviceCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AccessDeviceTokenFactory $tokenFactory
    ){}

    public static function getEntityFqcn(): string
    {
        return AccessDevice::class;
    }

    #[Override]
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->leftJoin('entity.user', 'entity_user')
            ->where('entity_user = (:user)')
            ->setParameter(':user', $this->getUser());
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('controller')
            ->setEntityLabelInPlural('controllers')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'name')->hideOnForm();
        yield BooleanField::new('enabled', 'enabled')->hideOnForm();
        yield AssociationField::new('user', 'owner')->hideOnForm();

        yield DateTimeField::new('lastSeenAt', 'last_contact')->hideOnForm();
        yield DateTimeField::new('lastSyncAt', 'last_sync_at')->hideOnForm();
        yield DateTimeField::new('createdAt', 'created_at')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'updated_at')->hideOnForm();

        yield AssociationField::new('building', 'building')
            ->setQueryBuilder(function(QueryBuilder $qb): QueryBuilder {
                return $qb
                    ->leftJoin("entity.accessDevice", "entity_device")
                    ->andWhere("entity_device.user = (:user)")
                    ->setParameter('user', $this->getUser());
            });
    }
}
