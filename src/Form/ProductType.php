<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\ReferenceOption;
use App\Entity\Supplier;
use App\Service\ReferenceOptionCrudService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function __construct(
        private readonly ReferenceOptionCrudService $referenceOptionCrudService,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom'])
            ->add('category', ChoiceType::class, [
                'label' => 'Categorie',
                'choices' => $this->referenceOptionCrudService->getChoices(ReferenceOption::CATEGORY_PRODUCT_CATEGORY, [
                    'Imagerie' => 'Imagerie',
                    'Monitoring' => 'Monitoring',
                    'Consommable' => 'Consommable',
                ]),
            ])
            ->add('purchasePrice', MoneyType::class, [
                'label' => 'Prix achat',
                'currency' => 'MAD',
            ])
            ->add('salePrice', MoneyType::class, [
                'label' => 'Prix vente',
                'currency' => 'MAD',
            ])
            ->add('stockQuantity', IntegerType::class, ['label' => 'Stock'])
            ->add('marketStatus', ChoiceType::class, [
                'label' => 'Statut marche',
                'choices' => $this->referenceOptionCrudService->getChoices(ReferenceOption::CATEGORY_PRODUCT_MARKET_STATUS, [
                    'Standard' => 'standard',
                    'Innovation' => 'innovation',
                    'En lancement' => 'en_lancement',
                ]),
            ])
            ->add('supplier', EntityType::class, [
                'label' => 'Fournisseur',
                'class' => Supplier::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Selectionner un fournisseur',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
