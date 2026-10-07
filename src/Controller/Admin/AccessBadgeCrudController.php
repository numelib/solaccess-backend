<?php

namespace App\Controller\Admin;

use App\Entity\AccessBadge;
use App\Field\ContactPickerField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AccessBadgeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AccessBadge::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->hideNullValues();
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addColumn(12);
        yield ContactPickerField::new("contact", "owner");
        yield TextField::new("uid", "UID");
        yield DateField::new("enablingAt", "enabling_at");
        yield DateField::new("disablingAt", "disabling_at");
    }
}
