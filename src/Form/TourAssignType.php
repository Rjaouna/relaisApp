<?php

namespace App\Form;

use App\Entity\Commercial;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TourAssignType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('commercial', EntityType::class, [
            'label' => 'Nouveau commercial',
            'class' => Commercial::class,
            'choice_label' => 'fullName',
            'choices' => $options['commercial_choices'],
            'placeholder' => 'Selectionner un commercial',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'commercial_choices' => [],
        ]);

        $resolver->setAllowedTypes('commercial_choices', 'array');
    }
}
