<?php

namespace App\Controller\Dashboard;

use App\Entity\Lock;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class LockCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Lock::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::DELETE);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->leftJoin('entity.device', 'entity_device');
        $qb->andWhere("entity_device.user = (:user)");
        $qb->setParameter('user', $this->getUser());

        return $qb;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('lock')
            ->setEntityLabelInPlural('locks')
            ->hideNullValues();
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addColumn(6);
        yield FormField::addFieldset('lock')
            ->setIcon('fa-solid fa-lock');
        yield TextField::new('name', 'name');
        yield BooleanField::new('enabled', 'enabled');
        yield IntegerField::new('unlockMs', 'unlock_ms');
        yield IntegerField::new('alignGpio', 'align_gpio');
        yield BooleanField::new('alignActiveHigh', 'align_active_high');
        yield IntegerField::new('alignDelayMs', 'align_delay_ms');
        yield IntegerField::new('alignTimeoutMs', 'align_timeout_ms');
        yield BooleanField::new('closeOnAlignTimeout', 'close_on_align_timeout');

        yield FormField::addFieldset('access_methods')
            ->setIcon('fa-solid fa-key');
        yield BooleanField::new('buttonEnabled', 'button_enabled');
        yield BooleanField::new('keypadEnabled', 'keypad_enabled');
        yield BooleanField::new('nfcEnabled', 'nfc_enabled');

        yield FormField::addColumn(6);
        yield FormField::addFieldset('codes')
            ->setIcon('fa-solid fa-keyboard');
        yield CollectionField::new('codes', 'codes')
            ->useEntryCrudForm(AccessCodeCrudController::class)
            ->setColumns(12)
            ->allowAdd(false)
            ->allowDelete(false)
            ->setFormTypeOption('by_reference', false);

        yield FormField::addFieldset('badges')
            ->setIcon('fa-solid fa-id-badge');
        yield CollectionField::new('badges', 'badges')
            ->useEntryCrudForm(AccessBadgeCrudController::class)
            ->setColumns(12)
            ->allowAdd(false)
            ->allowDelete(false)
            ->setFormTypeOption('by_reference', false);
    }
}
