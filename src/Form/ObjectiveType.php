<?php

namespace App\Form;

use App\Entity\Commercial;
use App\Entity\Objective;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ObjectiveType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('periodLabel', TextType::class, [
                'label' => 'Periode',
                'attr' => [
                    'placeholder' => 'Ex. Avril 2026',
                    'data-objective-period' => 'true',
                ],
                'help' => 'Utilise une periode lisible comme Avril 2026.',
            ])
            ->add('commercial', EntityType::class, [
                'class' => Commercial::class,
                'choice_label' => 'fullName',
                'label' => 'Commercial',
                'attr' => [
                    'data-objective-commercial' => 'true',
                ],
            ])
            ->add('salesTarget', IntegerType::class, ['label' => 'Objectif CA'])
            ->add('visitsTarget', IntegerType::class, ['label' => 'Objectif visites'])
            ->add('newClientsTarget', IntegerType::class, ['label' => 'Objectif nouveaux clients'])
            ->add('salesActual', HiddenType::class)
            ->add('visitsActual', HiddenType::class)
            ->add('newClientsActual', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Objective::class,
        ]);
    }
}
