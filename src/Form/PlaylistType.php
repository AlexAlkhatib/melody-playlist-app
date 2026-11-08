<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Morceaux;
use App\Entity\Playlist;
use App\Repository\MorceauxRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlaylistType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomPlaylist', TextType::class, [
                'label' => 'Nom de la playlist',
                'attr' => [
                    'placeholder' => 'Ex. Mes tubes du dimanche',
                    'minlength' => 2,
                    'maxlength' => 255,
                ],
            ])
            ->add('morceauxid', EntityType::class, [
                'class' => Morceaux::class,
                'label' => 'Morceaux',
                'choice_label' => static fn (Morceaux $morceaux): string => (string) $morceaux,
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'by_reference' => false,
                'attr' => ['size' => 10],
                'help' => 'Ctrl / Cmd + clic pour sélectionner plusieurs morceaux.',
                'query_builder' => static fn (MorceauxRepository $repository) => $repository
                    ->createQueryBuilder('m')
                    ->addOrderBy('m.artiste', 'ASC')
                    ->addOrderBy('m.nomMorceaux', 'ASC'),
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer la playlist',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Playlist::class,
        ]);
    }
}
