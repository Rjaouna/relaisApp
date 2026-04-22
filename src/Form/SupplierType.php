<?php

namespace App\Form;

use App\Entity\ReferenceOption;
use App\Entity\Supplier;
use App\Service\ReferenceOptionCrudService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SupplierType extends AbstractType
{
    public function __construct(
        private readonly ReferenceOptionCrudService $referenceOptionCrudService,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom'])
            ->add('country', TextType::class, ['label' => 'Pays'])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => $this->referenceOptionCrudService->getChoices(ReferenceOption::CATEGORY_SUPPLIER_STATUS, [
                    'Valide' => 'valide',
                    'Preselectionne' => 'preselectionne',
                    'A evaluer' => 'a_evaluer',
                ]),
            ])
            ->add('reactivityScore', IntegerType::class, ['label' => 'Score reactivite'])
            ->add('priceScore', IntegerType::class, ['label' => 'Score prix'])
            ->add('contactEmail', EmailType::class, [
                'label' => 'Email contact',
                'required' => false,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Supplier::class,
        ]);
    }
}
