<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\ProductLaunchProject;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductLaunchProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'name',
                'label' => 'Produit',
                'required' => false,
            ])
            ->add('name', TextType::class, ['label' => 'Projet / gamme'])
            ->add('targetCity', TextType::class, [
                'label' => 'Ville cible',
                'required' => false,
            ])
            ->add('targetEntities', TextareaType::class, [
                'label' => 'Cible / entites',
                'required' => false,
            ])
            ->add('marketStudy', TextareaType::class, [
                'label' => 'Etude de marche',
                'required' => false,
            ])
            ->add('feasibilityNotes', TextareaType::class, [
                'label' => 'Faisabilite',
                'required' => false,
            ])
            ->add('importConditions', TextareaType::class, [
                'label' => 'Conditions d import',
                'required' => false,
            ])
            ->add('registrationRequired', CheckboxType::class, [
                'label' => 'Enregistrement necessaire',
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Phase',
                'choices' => ProductLaunchProject::statusChoices(),
            ])
            ->add('followUpNotes', TextareaType::class, [
                'label' => 'Suivi / recadrage',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductLaunchProject::class,
        ]);
    }
}
