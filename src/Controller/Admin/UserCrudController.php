<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('user')
            ->setEntityLabelInPlural('users')
            ->setEntityPermission('ROLE_ADMIN');
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $queryBuilder->where("entity.roles LIKE '%ROLE_USER%'");

        return $queryBuilder;
    }

    public function configureFields(string $pageName): iterable
    {

        if ($pageName === Crud::PAGE_INDEX || $pageName === Crud::PAGE_DETAIL) {
            yield Field::new('firstname', 'firstname');
            yield Field::new('lastname', 'lastname');
            yield EmailField::new('email', 'email');
            yield DateTimeField::new('last_login', 'last_login');
            yield BooleanField::new('is_active', 'profile_activated')->setHelp("help.profile.isactive");
            return;
        }

        if ($pageName === Crud::PAGE_NEW || $pageName === Crud::PAGE_EDIT) {
            yield FormField::addColumn(6);
            yield FormField::addFieldset('personnal_informations')->setIcon('fa fa fa-id-card');
            yield Field::new('firstname', 'firstname')->setFormTypeOptions(['required' => true]);
            yield Field::new('lastname', 'lastname')->setFormTypeOptions(['required' => true]);

            yield FormField::addColumn(6);
            yield FormField::addFieldset('profile')->setIcon('fa fa fa-user');
            yield Field::new('email', 'email')->setHelp('help.profile.email');
            // Les utilisateurs du Portail Numelib n'ont pas de mot de passe Symfony.
            yield BooleanField::new('is_active', 'is_profile_active')->setHelp("help.profile.isactive");

            return;
        }
    }

    public function persistEntity(
        EntityManagerInterface $entityManager,
        $entityInstance,
    ): void {
        if (!$entityInstance instanceof User) {
            throw new \LogicException('Entite utilisateur attendue.');
        }

        $entityInstance->setRoles(['ROLE_USER']);
        $entityInstance->setPassword(null);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(
        EntityManagerInterface $entityManager,
        $entityInstance,
    ): void {
        $this->assertBusinessUser($entityInstance);
        // Un ancien compte utilisateur peut encore porter un hash historique.
        $entityInstance->setPassword(null);

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function deleteEntity(
        EntityManagerInterface $entityManager,
        $entityInstance,
    ): void {
        $this->assertBusinessUser($entityInstance);
        parent::deleteEntity($entityManager, $entityInstance);
    }

    private function assertBusinessUser(mixed $entity): void
    {
        // Une URL EasyAdmin directe ne doit pas permettre de toucher a un admin
        // via ce CRUD qui reinitialise le mot de passe.
        if (!$entity instanceof User || in_array('ROLE_ADMIN', $entity->getRoles(), true)) {
            throw new AccessDeniedHttpException('Ce compte ne peut pas etre gere ici.');
        }
    }
}
