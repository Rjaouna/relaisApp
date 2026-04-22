<?php

namespace App\Form;

use App\Entity\OfferItem;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OfferItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => fn (Product $product) => sprintf('%s (%s)', $product->getName(), $product->getCategory()),
                'choice_attr' => fn (Product $product) => [
                    'data-sale-price' => $product->getSalePrice(),
                ],
                'label' => 'Article',
                'placeholder' => 'Choisir un article',
                'attr' => [
                    'data-offer-item-product' => true,
                ],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantite',
                'attr' => [
                    'min' => 1,
                    'data-offer-item-quantity' => true,
                ],
            ])
            ->add('unitPrice', MoneyType::class, [
                'label' => 'Prix unitaire',
                'currency' => 'MAD',
                'divisor' => 1,
                'attr' => [
                    'data-offer-item-unit-price' => true,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OfferItem::class,
        ]);
    }
}
