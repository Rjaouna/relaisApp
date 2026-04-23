<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\ReferenceOption;
use App\Entity\Visit;
use App\Repository\ClientRepository;
use App\Repository\VisitRepository;
use App\Service\ReferenceOptionCrudService;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VisitType extends AbstractType
{
    public function __construct(
        private readonly ReferenceOptionCrudService $referenceOptionCrudService,
        private readonly VisitRepository $visitRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $showStatus = (bool) $options['show_status'];
        $currentVisit = $options['current_visit'];
        $blockedClientIds = $this->visitRepository->findClientIdsWithPlannedVisits($currentVisit?->getId());
        $currentClientId = $currentVisit?->getClient()?->getId();

        if ($currentClientId !== null) {
            $blockedClientIds = array_values(array_filter(
                $blockedClientIds,
                static fn (int $clientId): bool => $clientId !== $currentClientId
            ));
        }

        $builder
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => 'name',
                'label' => 'Client',
                'query_builder' => static function (ClientRepository $clientRepository) use ($blockedClientIds) {
                    $queryBuilder = $clientRepository->createQueryBuilder('client')
                        ->orderBy('client.name', 'ASC');

                    if ($blockedClientIds !== []) {
                        $queryBuilder
                            ->andWhere('client.id NOT IN (:blockedClientIds)')
                            ->setParameter('blockedClientIds', $blockedClientIds);
                    }

                    return $queryBuilder;
                },
                'help' => $blockedClientIds !== []
                    ? 'Les clients qui ont deja une visite prevue sont retires de la liste.'
                    : null,
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => $this->referenceOptionCrudService->getChoices(ReferenceOption::CATEGORY_VISIT_TYPE, Visit::typeChoices()),
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'Priorite',
                'choices' => $this->referenceOptionCrudService->getChoices(ReferenceOption::CATEGORY_VISIT_PRIORITY, Visit::priorityChoices()),
            ])
            ->add('result', ChoiceType::class, [
                'label' => 'Resultat de visite',
                'choices' => $this->referenceOptionCrudService->getChoices(ReferenceOption::CATEGORY_VISIT_RESULT, Visit::resultChoices()),
                'required' => false,
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
            ->add('objective', TextareaType::class, [
                'label' => 'Objectif',
                'required' => false,
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
                'label' => 'Interet client',
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

        if ((bool) $options['show_scheduled_at']) {
            $builder->add('scheduledAt', DateTimeType::class, [
                'label' => 'Date de la visite',
                'widget' => 'single_text',
            ]);
        }

        if ($showStatus) {
            $builder->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => $this->referenceOptionCrudService->getChoices(ReferenceOption::CATEGORY_VISIT_STATUS, Visit::statusChoices()),
                'help' => 'Une fois le resultat renseigne, le statut ne peut plus etre modifie.',
                'attr' => [
                    'data-visit-status' => true,
                    'data-status-locked-message' => 'Le statut est bloque une fois le resultat de visite renseigne.',
                ],
            ]);
        } else {
            $builder->add('status', HiddenType::class, [
                'data' => Visit::STATUS_PLANNED,
            ]);
        }

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            $visit = $event->getForm()->getData();

            if (!is_array($data) || !$visit instanceof Visit) {
                return;
            }

            if (($data['status'] ?? null) === null || $data['status'] === '') {
                $data['status'] = $visit->getStatus() ?? Visit::STATUS_PLANNED;
            }

            if (($data['type'] ?? null) === null || $data['type'] === '') {
                $data['type'] = $visit->getType() ?? 'prospection';
            }

            if (($data['priority'] ?? null) === null || $data['priority'] === '') {
                $data['priority'] = $visit->getPriority() ?? 'moyenne';
            }

            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Visit::class,
            'show_status' => true,
            'show_scheduled_at' => true,
            'current_visit' => null,
        ]);
    }
}
