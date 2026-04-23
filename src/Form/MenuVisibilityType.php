<?php

namespace App\Form;

use App\Model\MenuConfigurationData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MenuVisibilityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('visibleMenus', ChoiceType::class, [
            'label' => false,
            'choices' => $options['menu_choices'],
            'expanded' => true,
            'multiple' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MenuConfigurationData::class,
            'menu_choices' => [],
        ]);

        $resolver->setAllowedTypes('menu_choices', 'array');
    }
}
