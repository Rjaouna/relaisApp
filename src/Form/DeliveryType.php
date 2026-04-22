<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Delivery;
use App\Entity\ReferenceOption;
use App\Repository\CityRepository;
use App\Service\ReferenceOptionCrudService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DeliveryType extends AbstractType
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
            ->add('reference', TextType::class, ['label' => 'Reference'])
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => 'name',
                'label' => 'Client',
            ])
            ->add('city', ChoiceType::class, [
                'label' => 'Ville',
                'choices' => $cityChoices,
                'placeholder' => 'Selectionner une ville',
            ])
            ->add('scheduledAt', DateTimeType::class, [
                'label' => 'Date de livraison',
                'widget' => 'single_text',
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => $this->referenceOptionCrudService->getChoices(ReferenceOption::CATEGORY_DELIVERY_STATUS, [
                    'Planifiee' => 'planifiee',
                    'En cours' => 'en_cours',
                    'Livree' => 'livree',
                    'En retard' => 'en_retard',
                ]),
            ])
            ->add('delayDays', IntegerType::class, ['label' => 'Retard (jours)']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Delivery::class,
        ]);
    }
}
