<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Morceaux;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class MorceauxCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Morceaux::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Morceau')
            ->setEntityLabelInPlural('Morceaux')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['nomMorceaux', 'artiste']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('nomMorceaux', 'Titre');
        yield TextField::new('artiste', 'Artiste');
        yield DateField::new('dateSortie', 'Date de sortie');
        yield AssociationField::new('genreid', 'Genre');
        yield AssociationField::new('createdBy', 'Ajouté par');
        yield ImageField::new('imageName', 'Pochette')
            ->setBasePath('/media/images')
            ->hideOnForm();
        yield TextField::new('musicName', 'Fichier audio')
            ->hideOnForm()
            ->hideOnIndex();
    }
}
