<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setDefaultSort(['nom' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('prenom', 'Prénom');
        yield TextField::new('nom', 'Nom');
        yield EmailField::new('email', 'Email');
        yield ArrayField::new('roles', 'Rôles')
            ->setHelp('ROLE_USER ou ROLE_ADMIN');
        yield TextField::new('password', 'Mot de passe')
            ->onlyOnForms()
            ->setFormTypeOption('required', false)
            ->setHelp('Laisser vide pour conserver le mot de passe actuel. Le mot de passe est haché automatiquement.');
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            $this->hashIfNeeded($entityInstance);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            $this->hashIfNeeded($entityInstance);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function hashIfNeeded(User $user): void
    {
        $password = (string) $user->getPassword();

        if ('' === $password) {
            return;
        }

        // Un hash existant (bcrypt/argon) renvoyé tel quel ne doit pas être re-haché.
        if ([] !== password_get_info($password)['algo'] && (string) password_get_info($password)['algo'] !== '0') {
            return;
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
    }
}
