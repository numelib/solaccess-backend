<?php

namespace App\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;

class UserAdminCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters,
    ): QueryBuilder {
        return parent::createIndexQueryBuilder(
            $searchDto,
            $entityDto,
            $fields,
            $filters,
        )->andwhere("entity.roles like '%ROLE_ADMIN%'");
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural("administrators")
            ->setEntityLabelInSingular("administrator")
            ->setPageTitle("index", "administrators")
            ->setEntityPermission("ROLE_SUPER_ADMIN");
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab("administrator");
        yield FormField::addColumn(6);
        yield FormField::addFieldset("personnal_informations")->setIcon(
            "fa fa fa-id-card",
        );
        yield Field::new("firstname", "firstname");
        yield Field::new("lastname", "lastname");

        yield FormField::addColumn(6);
        yield FormField::addFieldset("profile")->setIcon("fa fa fa-user");
        yield EmailField::new("email", "email");
        yield Field::new("plainPassword", "password")
            ->onlyOnForms()
            ->setFormType(RepeatedType::class)
            ->setFormTypeOptions([
                "required" => $pageName === Crud::PAGE_NEW,
                "options" => [
                    "attr" => ["autocomplete" => "off"],
                ],
                "type" => PasswordType::class,
                "first_options" => ["label" => "password"],
                "second_options" => ["label" => "password_confirmation"],
                "invalid_message" => "mismatched_passwords",
            ])
            ->setColumns("col-12");
        yield BooleanField::new("is_active", "is_profile_active")->setHelp(
            "help.profile.isactive",
        );
        yield DateTimeField::new("last_login", "last_login")->hideOnForm();
    }

    public function persistEntity(
        EntityManagerInterface $entityManager,
        $entityInstance,
    ): void {
        $this->encodePassword($entityInstance);
        $entityInstance->setRoles(["ROLE_ADMIN"]);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(
        EntityManagerInterface $entityManager,
        $entityInstance,
    ): void {
        $this->encodePassword($entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    #[AdminRoute("/{entityId:user.id}/contacts")]
    public function ownerContacts(User $user)
    {
        $data = ["contacts" => []];
        foreach ($user->getContacts() as $contact) {
            $data["contacts"][] = [
                "id" => $contact->getId(),
                "firstname" => $contact->getFirstname(),
                "lastname" => $contact->getlastname(),
            ];
        }

        return $this->json($data);
    }

    private function encodePassword(User $user): void
    {
        if ($user->getPlainPassword() != null) {
            $hash = $this->passwordHasher->hashPassword(
                $user,
                $user->getPlainPassword(),
            );
            $user->setPassword($hash);
        }
    }
}
