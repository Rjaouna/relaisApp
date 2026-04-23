<?php

namespace App\Form;

use App\Entity\ReferenceOption;
use App\Entity\Visit;
use App\Service\ReferenceOptionCrudService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VisitOutcomeType extends AbstractType
{
    public function __construct(
        private readonly ReferenceOptionCrudService $referenceOptionCrudService,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('result', ChoiceType::class, [
                'label' => 'Resultat de la visite',
                'choices' => $this->referenceOptionCrudService->getChoices(ReferenceOption::CATEGORY_VISIT_RESULT, Visit::resultChoices()),
                'placeholder' => 'Selectionner un resultat',
                'attr' => [
                    'data-visit-result' => true,
                ],
            ])
            ->add('appointmentScheduledAt', DateTimeType::class, [
                'label' => 'Date du rendez-vous',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'data-appointment-field' => true,
                ],
            ])
            ->add('report', TextareaType::class, [
                'label' => 'Compte rendu',
                'required' => false,
            ])
            ->add('nextAction', TextareaType::class, [
                'label' => 'Prochaine action',
                'required' => false,
            ])
            ->add('interestLevel', ChoiceType::class, [
                'label' => 'Niveau d interet',
                'required' => false,
                'placeholder' => false,
                'choices' => [
                    'Tres faible' => 1,
                    'Faible' => 2,
                    'Moyen' => 3,
                    'Fort' => 4,
                    'Tres fort' => 5,
                ],
                'expanded' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Visit::class,
        ]);
    }
}
