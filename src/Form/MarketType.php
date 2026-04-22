<?php

namespace App\Form;

use App\Entity\Market;
use App\Repository\CityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MarketType extends AbstractType
{
    public function __construct(
        private readonly CityRepository $cityRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $cityChoices = [];
        foreach ($this->cityRepository->findBy(['isActive' => true], ['name' => 'ASC']) as $city) {
            $cityChoices[$city->getName()] = $city->getName();
        }

        $builder
            ->add('city', ChoiceType::class, [
                'label' => 'Ville',
                'choices' => $cityChoices,
                'placeholder' => 'Selectionner une ville',
                'help' => 'Les indicateurs de marche sont calcules automatiquement a partir des clients et des offres.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Market::class,
        ]);
    }
}
