<?php

namespace App\Form;

use App\Entity\ReferenceOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReferenceOptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['locked_category']) {
            $builder->add('category', HiddenType::class);
        } else {
            $builder->add('category', ChoiceType::class, [
                'label' => 'Categorie',
                'choices' => ReferenceOption::categoryChoices(),
            ]);
        }

        $builder
            ->add('label', TextType::class, ['label' => 'Libelle'])
            ->add('value', TextType::class, [
                'label' => 'Valeur technique',
                'required' => false,
                'help' => 'Si vide, elle sera generee automatiquement.',
            ]);

        if (!$options['picker_mode']) {
            $builder
                ->add('sortOrder', IntegerType::class, ['label' => 'Ordre'])
                ->add('isActive', CheckboxType::class, [
                    'label' => 'Actif',
                    'required' => false,
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReferenceOption::class,
            'locked_category' => false,
            'picker_mode' => false,
        ]);
    }
}
