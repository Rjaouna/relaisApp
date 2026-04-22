<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Offer;
use App\Entity\ReferenceOption;
use App\Service\ReferenceOptionCrudService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OfferType extends AbstractType
{
    public function __construct(
        private readonly ReferenceOptionCrudService $referenceOptionCrudService,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reference', TextType::class, [
                'label' => 'Reference',
            ])
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => 'name',
                'label' => 'Client',
            ])
            ->add('issuedAt', DateTimeType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
            ])
            ->add('amount', MoneyType::class, [
                'label' => 'Montant',
                'currency' => 'MAD',
                'divisor' => 1,
                'disabled' => true,
                'help' => 'Le total est calcule automatiquement a partir des articles.',
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => $this->referenceOptionCrudService->getChoices(ReferenceOption::CATEGORY_OFFER_STATUS, [
                    'En cours' => 'en_cours',
                    'Acceptee' => 'acceptee',
                    'Refusee' => 'refusee',
                    'Brouillon' => 'brouillon',
                ]),
            ])
            ->add('conditionsSummary', TextareaType::class, [
                'label' => 'Conditions',
                'required' => false,
            ])
            ->add('historyNotes', TextareaType::class, [
                'label' => 'Historique',
                'required' => false,
            ])
            ->add('items', CollectionType::class, [
                'label' => false,
                'entry_type' => OfferItemType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Offer::class,
        ]);
    }
}
