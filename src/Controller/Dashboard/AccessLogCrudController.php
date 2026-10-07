<?php

namespace App\Controller\Dashboard;

use App\Entity\AccessLog;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AccessLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AccessLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('access_log')
            ->setEntityLabelInPlural('access_logs')
            ->setDefaultSort(['occurredAt' => 'DESC']);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->leftJoin("entity.lock", "entity_lock");
        $qb->leftJoin("entity_lock.device", "entity_lock_device");
        $qb->andWhere("entity_lock_device.user = (:user)");
        $qb->setParameter('user', $this->getUser());

        return $qb;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield DateTimeField::new('occurredAt', 'date');
        yield TextField::new('device', 'device');
        yield AssociationField::new('lock', 'lock');
        yield TextField::new('type', 'type');
        yield TextField::new('source', 'source');
        yield BooleanField::new('success', 'success');
        yield TextField::new('reason', 'reason');
        yield TextField::new('credentialIdentifier', 'credential_identifier');
        yield ArrayField::new('rawPayload', 'raw_payload')->hideOnIndex();
    }
}
