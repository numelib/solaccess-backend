<?php

namespace App\Controller\Admin;

use App\Entity\Building;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Locale;
use Symfony\Component\Intl\Languages;

class BuildingCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Building::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('building')
            ->setEntityLabelInPlural('buildings');
    }

    public function configureFields(string $pageName): iterable
    {
        $request = $this->getContext()->getRequest();
        $locale = $request->getLocale();
        $cannonicalized = Locale::canonicalize($locale);
        $region = Locale::getRegion($cannonicalized);

        yield FormField::addColumn(6);
        yield FormField::addFieldset('general_informations')->setIcon('fa-solid fa-gear');
        yield TextField::new('label', 'label');
        yield AssociationField::new('accessDevice', 'controller')
            ->onlyOnForms();

        yield FormField::addColumn(6);
        yield FormField::addFieldset('address')->setIcon('fa-solid fa-address-book');
        yield CountryField::new('addressCountry', 'country')
            ->setFormTypeOption('data', $region);
        yield TextField::new('addressCity', 'city');
        yield TextField::new('addressPostCode', 'post_code');
        yield TextField::new('addressLine', 'address');
    }
}
