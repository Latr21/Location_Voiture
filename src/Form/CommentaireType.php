<?php

namespace App\Form;

use App\Entity\Commentaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class CommentaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contenu', TextareaType::class, [
                'label' => 'Votre avis',
                'attr' => ['placeholder' => 'Écrivez votre avis ici...', 'rows' => 5],
            ])
            ->add('note', ChoiceType::class, [
                'label' => 'Note',
                'choices' => [
                    '⭐' => '1',
                    '⭐⭐' => '2',
                    '⭐⭐⭐' => '3',
                    '⭐⭐⭐⭐' => '4',
                    '⭐⭐⭐⭐⭐' => '5',
                ],
                'expanded' => true, // Afficher sous forme de boutons radio
                'multiple' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commentaire::class,
        ]);
    }
}