<?php

namespace App\Controller\Dashboard;

use App\Entity\AccessBadge;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;

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
        yield AssociationField::new('user', 'user')
            ->setQueryBuilder(function(QueryBuilder $qb): QueryBuilder {
                return $qb
                    ->andwhere("entity.roles NOT LIKE '%ROLE_ADMIN%'")
                    ->andwhere("entity.roles NOT LIKE '%ROLE_SUPER_ADMIN%'");
            });
        yield DateField::new('enablingAt', 'enabling_at');
        yield DateField::new('disablingAt', 'disabling_at');
    }
}
