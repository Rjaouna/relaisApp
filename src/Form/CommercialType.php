<?php

namespace App\Form;

use App\Entity\Commercial;
use App\Entity\User;
use App\Entity\Zone;
use App\Repository\CityRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommercialType extends AbstractType
{
    public function __construct(
        private readonly CityRepository $cityRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $cityChoices = [];
        foreach ($this->cityRepository->findBy(['isActive' => true], ['name' => 'ASC']) as $city) {
            $cityChoices[$city->getName()] = $city->getName();
        }

        $currentCommercial = $options['current_commercial'];
        $userChoices = [];
        foreach ($this->userRepository->findBy([], ['fullName' => 'ASC']) as $user) {
            if (!in_array('ROLE_COMMERCIAL', $user->getRoles(), true)) {
                continue;
            }

            $linkedCommercial = $user->getCommercial();
            if ($linkedCommercial !== null && $linkedCommercial !== $currentCommercial) {
                continue;
            }

            $userChoices[] = $user;
        }

        $builder
            ->add('fullName', TextType::class, ['label' => 'Nom complet'])
            ->add('city', ChoiceType::class, [
                'label' => 'Ville',
                'choices' => $cityChoices,
                'placeholder' => 'Selectionner une ville',
            ])
            ->add('zones', EntityType::class, [
                'label' => 'Zones',
                'class' => Zone::class,
                'choice_label' => static fn (Zone $zone): string => sprintf('%s - %s', $zone->getName(), $zone->getCity()?->getName() ?? 'Ville'),
                'required' => false,
                'multiple' => true,
                'help' => 'Tu peux rattacher plusieurs zones au meme commercial pour preparer des tournees partagees ou des couvertures transverses.',
            ])
            ->add('user', EntityType::class, [
                'label' => 'Compte utilisateur',
                'class' => User::class,
                'choices' => $userChoices,
                'choice_label' => static fn (User $user): string => sprintf('%s - %s', $user->getFullName(), $user->getEmail()),
                'required' => false,
                'placeholder' => 'Selectionner un compte commercial',
                'help' => 'Seuls les comptes utilisateur avec le role Commercial et non deja rattaches sont proposes.',
            ])
            ->add('salesTarget', IntegerType::class, ['label' => 'Objectif CA'])
            ->add('visitsTarget', IntegerType::class, ['label' => 'Objectif visites'])
            ->add('newClientsTarget', IntegerType::class, ['label' => 'Objectif nouveaux clients'])
            ->add('currentClientsLoad', IntegerType::class, ['label' => 'Clients affectes'])
            ->add('currentVisitsLoad', IntegerType::class, ['label' => 'Visites affectees'])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commercial::class,
            'current_commercial' => null,
        ]);

        $resolver->setAllowedTypes('current_commercial', ['null', Commercial::class]);
    }
}
