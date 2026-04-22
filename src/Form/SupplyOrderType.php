<?php

namespace App\Form;

use App\Entity\ReferenceOption;
use App\Entity\Supplier;
use App\Entity\SupplyOrder;
use App\Service\ReferenceOptionCrudService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SupplyOrderType extends AbstractType
{
    public function __construct(
        private readonly ReferenceOptionCrudService $referenceOptionCrudService,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reference', TextType::class, ['label' => 'Reference'])
            ->add('supplier', EntityType::class, [
                'class' => Supplier::class,
                'choice_label' => 'name',
                'label' => 'Fournisseur',
            ])
            ->add('orderedAt', DateTimeType::class, [
                'label' => 'Date de commande',
                'widget' => 'single_text',
            ])
            ->add('leadTimeDays', IntegerType::class, ['label' => 'Delai'])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => $this->referenceOptionCrudService->getChoices(ReferenceOption::CATEGORY_SUPPLY_ORDER_STATUS, [
                    'En attente' => 'en_attente',
                    'En transit' => 'en_transit',
                    'Livree' => 'livree',
                    'Bloquee' => 'bloquee',
                ]),
            ])
            ->add('amount', MoneyType::class, [
                'label' => 'Montant',
                'currency' => 'MAD',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SupplyOrder::class,
        ]);
    }
}
