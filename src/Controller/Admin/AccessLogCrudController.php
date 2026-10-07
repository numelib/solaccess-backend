<?php

namespace App\Controller\Admin;

use App\Entity\AccessLog;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
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

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT);
    }

    public function configureFields(string $pageName): iterable
    {
        yield DateTimeField::new('occurredAt', 'date')->setTimezone('Europe/Paris');
        yield TextField::new('device', 'device');
        yield AssociationField::new('lock', 'lock');
        yield TextField::new('type', 'type');
        yield TextField::new('source', 'source');
        yield BooleanField::new('success', 'success')->renderAsSwitch(false);
        yield TextField::new('reason', 'reason');
        yield TextField::new('label', 'owner');
        yield TextField::new('credentialIdentifier', 'user');
        yield ArrayField::new('rawPayload', 'raw_payload')->hideOnIndex();
    }
}
