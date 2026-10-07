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
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $userPasswordHasher,
    ){}

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
            yield Field::new('plainPassword', 'password')
                ->setFormType(RepeatedType::class)
                ->setFormTypeOptions([
                    'required' => $pageName === Crud::PAGE_NEW,
                    'type' => PasswordType::class,
                    'first_options' => ['label' => 'password'],
                    'second_options' => ['label' => 'password_confirmation'],
                    'invalid_message' => 'mismatched_passwords',
            ]);
            yield BooleanField::new('is_active', 'is_profile_active')->setHelp("help.profile.isactive");

            return;
        }
    }

    /**
     * @param User $user
     */
    public function persistEntity(EntityManagerInterface $entityManager, $user): void
    {
        $password = $this->userPasswordHasher->hashPassword($user, $user->getPlainPassword());
        $user
            ->setPassword($password)
            ->setRoles(['ROLE_USER']);

        parent::persistEntity($entityManager, $user);
    }
}
