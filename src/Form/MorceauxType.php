<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Genre;
use App\Entity\Morceaux;
use App\Repository\GenreRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Vich\UploaderBundle\Form\Type\VichImageType;

class MorceauxType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomMorceaux', TextType::class, [
                'label' => 'Titre du morceau',
                'attr' => [
                    'placeholder' => 'Ex. Bohemian Rhapsody',
                    'minlength' => 2,
                    'maxlength' => 50,
                ],
            ])
            ->add('artiste', TextType::class, [
                'label' => 'Artiste / groupe',
                'attr' => [
                    'placeholder' => 'Ex. Queen',
                    'minlength' => 2,
                    'maxlength' => 255,
                ],
            ])
            ->add('dateSortie', DateType::class, [
                'label' => 'Date de sortie',
                'widget' => 'single_text',
                'required' => false,
                'help' => 'Laissez vide si vous ne connaissez pas la date exacte.',
            ])
            ->add('genreid', EntityType::class, [
                'class' => Genre::class,
                'label' => 'Genre musical',
                'choice_label' => 'nomGenre',
                'placeholder' => 'Choisir un genre…',
                'query_builder' => static fn (GenreRepository $repository) => $repository
                    ->createQueryBuilder('g')
                    ->orderBy('g.nomGenre', 'ASC'),
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Pochette de l\'album',
                'required' => false,
                'allow_delete' => false,
                'download_uri' => false,
                'image_uri' => static fn (Morceaux $morceaux) => null !== $morceaux->getImageName()
                    ? '/media/images/'.$morceaux->getImageName()
                    : null,
                'help' => 'JPG, PNG ou WebP — 2 Mo maximum.',
            ])
            ->add('musicFile', VichFileType::class, [
                'label' => 'Fichier audio',
                'required' => false,
                'allow_delete' => false,
                'download_uri' => static fn (Morceaux $morceaux) => null !== $morceaux->getMusicName()
                    ? '/media/morceaux/'.$morceaux->getMusicName()
                    : null,
                'help' => 'MP3, OGG, WAV ou FLAC — 20 Mo maximum.',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Morceaux::class,
        ]);
    }
}
