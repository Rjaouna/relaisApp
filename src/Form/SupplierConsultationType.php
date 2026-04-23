<?php

namespace App\Form;

use App\Entity\Supplier;
use App\Entity\SupplierConsultation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SupplierConsultationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('supplier', EntityType::class, [
                'class' => Supplier::class,
                'choice_label' => 'name',
                'label' => 'Fournisseur',
            ])
            ->add('needTitle', TextType::class, ['label' => 'Besoin'])
            ->add('needDetails', TextareaType::class, ['label' => 'Specification'])
            ->add('expectedDelay', TextType::class, [
                'label' => 'Delai attendu',
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut consultation',
                'choices' => SupplierConsultation::statusChoices(),
            ])
            ->add('sampleStatus', ChoiceType::class, [
                'label' => 'Echantillon / conformite',
                'choices' => SupplierConsultation::sampleStatusChoices(),
            ])
            ->add('quotedAmount', MoneyType::class, [
                'label' => 'Montant devis',
                'required' => false,
                'currency' => 'MAD',
            ])
            ->add('negotiatedAmount', MoneyType::class, [
                'label' => 'Montant negocie',
                'required' => false,
                'currency' => 'MAD',
            ])
            ->add('complianceNotes', TextareaType::class, [
                'label' => 'Conformite / justificatifs',
                'required' => false,
            ])
            ->add('negotiationNotes', TextareaType::class, [
                'label' => 'Notes de negociation',
                'required' => false,
            ])
            ->add('selectedSupplier', CheckboxType::class, [
                'label' => 'Fournisseur retenu',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SupplierConsultation::class,
        ]);
    }
}
