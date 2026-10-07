<?php

namespace App\Controller\Admin;

use App\Entity\Lock;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
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

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->hideNullValues();
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addColumn(6);
        yield FormField::addFieldset("lock")->setIcon("fa-solid fa-lock");
        yield TextField::new("name", "name");
        yield BooleanField::new("enabled", "enabled");
        yield IntegerField::new("unlockMs", "unlock_ms");
        yield IntegerField::new("relayChannel", "relay_channel");
        yield IntegerField::new("alignGpio", "align_gpio")->setHelp(
            "align_gpio_help",
        );
        yield BooleanField::new(
            "alignActiveHigh",
            "align_active_high",
        )->setHelp("align_gpio_high_help");
        yield IntegerField::new("alignDelayMs", "align_delay_ms");
        yield IntegerField::new("alignTimeoutMs", "align_timeout_ms");
        yield BooleanField::new(
            "closeOnAlignTimeout",
            "close_on_align_timeout",
        );

        yield FormField::addFieldset("access_methods")->setIcon(
            "fa-solid fa-key",
        );
        yield BooleanField::new("buttonEnabled", "button_enabled");
        yield BooleanField::new("keypadEnabled", "keypad_enabled");
        yield BooleanField::new("nfcEnabled", "nfc_enabled");

        yield FormField::addColumn(6);
        yield FormField::addFieldset("codes")
            ->setIcon("fa-solid fa-keyboard")
            ->setHtmlAttribute("data-field-collection", "codes");
        yield CollectionField::new("codes", false)
            ->useEntryCrudForm(AccessCodeCrudController::class)
            ->setColumns(12)
            ->allowAdd()
            ->allowDelete()
            ->setFormTypeOption("by_reference", false);

        yield FormField::addFieldset("badges")
            ->setIcon("fa-solid fa-id-badge")
            ->setHtmlAttribute("data-field-collection", "badges");
        yield CollectionField::new("badges", false)
            ->useEntryCrudForm(AccessBadgeCrudController::class)
            ->setColumns(12)
            ->allowAdd()
            ->allowDelete()
            ->setFormTypeOption("by_reference", false);
    }
}
