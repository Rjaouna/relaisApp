<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\CustomerSatisfaction;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomerSatisfactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => 'name',
                'label' => 'Client',
            ])
            ->add('satisfactionLevel', ChoiceType::class, [
                'label' => 'Satisfaction',
                'choices' => CustomerSatisfaction::levelChoices(),
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => CustomerSatisfaction::statusChoices(),
            ])
            ->add('expectationSummary', TextareaType::class, [
                'label' => 'Attentes / ressenti',
                'required' => false,
            ])
            ->add('marketListening', TextareaType::class, [
                'label' => 'Ecoute marche',
                'required' => false,
            ])
            ->add('deliveryRequestedAt', DateType::class, [
                'label' => 'Date de livraison souhaitee',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('nextAction', TextareaType::class, [
                'label' => 'Prochaine action',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CustomerSatisfaction::class,
        ]);
    }
}
