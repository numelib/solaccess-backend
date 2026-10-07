<?php

namespace App\Controller\Admin;

use App\Entity\AccessCode;
use App\Field\ContactPickerField;
use App\Form\ContactPickerType;
use App\Validator\SecurePinCode;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Validator\Constraints\NotBlank;

class AccessCodeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AccessCode::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->hideNullValues();
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addColumn(12);
        yield ContactPickerField::new("contact", "owner");
        yield TextField::new("plainPin", "PIN")
            ->setHelp("access_code_pin_help")
            ->setFormTypeOption("constraints", new SecurePinCode())
            ->onlyOnForms();
        yield DateField::new("enablingAt", "enabling_at");
        yield DateField::new("disablingAt", "disabling_at")->setHelp(
            "Laisser vide pour garder actif",
        );
        yield TextField::new("pinSalt", "Salt")->hideOnForm();
        yield TextField::new("pinHash", "Hash PIN")->hideOnForm();
    }
}
