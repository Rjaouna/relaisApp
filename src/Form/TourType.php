<?php

namespace App\Form;

use App\Entity\Commercial;
use App\Entity\ReferenceOption;
use App\Entity\Tour;
use App\Repository\CityRepository;
use App\Service\ReferenceOptionCrudService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TourType extends AbstractType
{
    public function __construct(
        private readonly CityRepository $cityRepository,
        private readonly ReferenceOptionCrudService $referenceOptionCrudService,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $cityChoices = [];
        foreach ($this->cityRepository->findBy(['isActive' => true], ['name' => 'ASC']) as $city) {
            $cityChoices[$city->getName()] = $city->getName();
        }

        $builder
            ->add('name', TextType::class, ['label' => 'Nom de tournee'])
            ->add('commercial', EntityType::class, [
                'label' => 'Commercial',
                'class' => Commercial::class,
                'choice_label' => 'fullName',
            ])
            ->add('city', ChoiceType::class, [
                'label' => 'Ville',
                'choices' => $cityChoices,
                'placeholder' => 'Selectionner une ville',
            ])
            ->add('scheduledFor', DateTimeType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => $this->referenceOptionCrudService->getChoices(ReferenceOption::CATEGORY_TOUR_STATUS, Tour::statusChoices()),
            ])
            ->add('plannedVisits', IntegerType::class, ['label' => 'Visites prevues'])
            ->add('completedVisits', IntegerType::class, ['label' => 'Visites realisees'])
            ->add('routeSummary', TextType::class, [
                'label' => 'Resume du trajet',
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
            'data_class' => Tour::class,
        ]);
    }
}
