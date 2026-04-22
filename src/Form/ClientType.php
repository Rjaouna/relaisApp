<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Commercial;
use App\Entity\ReferenceOption;
use App\Entity\Zone;
use App\Repository\CityRepository;
use App\Service\ReferenceOptionCrudService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientType extends AbstractType
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
            ->add('name', TextType::class, ['label' => 'Nom'])
            ->add('city', ChoiceType::class, [
                'label' => 'Ville',
                'choices' => $cityChoices,
                'placeholder' => 'Selectionner une ville',
            ])
            ->add('zone', EntityType::class, [
                'label' => 'Zone',
                'class' => Zone::class,
                'choice_label' => static fn (Zone $zone): string => sprintf('%s - %s', $zone->getCity()?->getName() ?? 'Ville', $zone->getName() ?? 'Zone'),
                'placeholder' => 'Selectionner une zone',
                'required' => false,
                'query_builder' => static fn (\App\Repository\ZoneRepository $repository) => $repository->createQueryBuilder('zone')
                    ->leftJoin('zone.city', 'city')
                    ->addSelect('city')
                    ->orderBy('city.name', 'ASC')
                    ->addOrderBy('zone.name', 'ASC'),
                'attr' => [
                    'data-zone-select' => true,
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => $this->referenceOptionCrudService->getChoices(ReferenceOption::CATEGORY_CLIENT_TYPE, Client::typeChoices()),
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => $this->referenceOptionCrudService->getChoices(ReferenceOption::CATEGORY_CLIENT_STATUS, Client::statusChoices()),
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
            ])
            ->add('phone', TextType::class, [
                'label' => 'Telephone',
                'required' => false,
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'required' => true,
            ])
            ->add('potentialScore', ChoiceType::class, [
                'label' => 'Potentiel commercial',
                'required' => false,
                'choices' => $this->referenceOptionCrudService->getChoices(ReferenceOption::CATEGORY_CLIENT_POTENTIAL_LEVEL, Client::scoreLevelChoices()),
                'placeholder' => 'Selectionner un niveau',
                'help' => 'Niveau de potentiel estime du client.',
            ])
            ->add('segment', ChoiceType::class, [
                'label' => 'Segment',
                'required' => false,
                'choices' => $this->referenceOptionCrudService->getChoices(ReferenceOption::CATEGORY_CLIENT_SEGMENT, Client::segmentChoices()),
                'placeholder' => 'Selectionner un segment',
            ])
            ->add('solvencyScore', ChoiceType::class, [
                'label' => 'Solvabilite',
                'required' => false,
                'choices' => $this->referenceOptionCrudService->getChoices(ReferenceOption::CATEGORY_CLIENT_SOLVENCY_LEVEL, Client::scoreLevelChoices()),
                'placeholder' => 'Selectionner un niveau',
                'help' => 'Capacite estimee du client a honorer ses engagements.',
            ])
            ->add('annualRevenue', MoneyType::class, [
                'label' => 'CA annuel',
                'currency' => 'MAD',
                'help' => 'Chiffre d affaires annuel estime du client.',
            ])
            ->add('assignedCommercial', EntityType::class, [
                'label' => 'Commercial affecte',
                'class' => Commercial::class,
                'choice_label' => 'fullName',
                'required' => false,
                'placeholder' => 'Affectation automatique ou manuelle',
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Client::class,
        ]);
    }
}
