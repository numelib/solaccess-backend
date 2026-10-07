<?php

namespace App\Controller\Dashboard;

use App\Entity\Building;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Locale;

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

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->leftJoin("entity.accessDevice", "entity_device");
        $qb->andWhere("entity_device.user = (:user)");
        $qb->setParameter('user', $this->getUser());

        return $qb;
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

        yield FormField::addColumn(6);
        yield FormField::addFieldset('address')->setIcon('fa-solid fa-address-book');
        yield CountryField::new('addressCountry', 'country')
            ->setFormTypeOption('data', $region);
        yield TextField::new('addressCity', 'city');
        yield TextField::new('addressPostCode', 'post_code');
        yield TextField::new('addressLine', 'address');
    }
}
