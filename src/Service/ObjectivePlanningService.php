<?php

namespace App\Service;

use App\Entity\Commercial;
use App\Entity\Objective;
use App\Repository\ClientRepository;
use App\Repository\ObjectiveRepository;
use App\Repository\VisitRepository;

class ObjectivePlanningService
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly VisitRepository $visitRepository,
        private readonly ObjectiveRepository $objectiveRepository,
    ) {
    }

    public function getSuggestedPeriodLabel(): string
    {
        $date = new \DateTimeImmutable();
        $formatter = new \IntlDateFormatter(
            'fr_FR',
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            $date->getTimezone()->getName(),
            null,
            'MMMM yyyy'
        );

        $formatted = $formatter->format($date) ?: $date->format('m/Y');

        return mb_convert_case((string) $formatted, MB_CASE_TITLE, 'UTF-8');
    }

    public function buildContext(?Commercial $commercial, ?string $periodLabel, ?Objective $currentObjective = null): array
    {
        if (!$commercial instanceof Commercial) {
            return [
                'ready' => false,
                'message' => 'Selectionne un commercial pour afficher sa charge actuelle et ses reperes.',
            ];
        }

        $normalizedPeriod = trim((string) ($periodLabel ?: $this->getSuggestedPeriodLabel()));
        $clientsAssigned = $this->clientRepository->countForCommercial($commercial);
        $plannedVisits = $this->visitRepository->countPlannedForCommercial($commercial);
        $load = $clientsAssigned + (2 * $plannedVisits);
        $duplicate = $this->objectiveRepository->findOneForCommercialAndPeriod($commercial, $normalizedPeriod, $currentObjective?->getId());
        $lastObjective = $this->objectiveRepository->findLatestForCommercial($commercial, $currentObjective?->getId());

        return [
            'ready' => true,
            'commercial' => [
                'id' => $commercial->getId(),
                'name' => $commercial->getFullName(),
                'zone' => $commercial->getZoneLabel(),
                'clientsAssigned' => $clientsAssigned,
                'plannedVisits' => $plannedVisits,
                'load' => $load,
            ],
            'periodLabel' => $normalizedPeriod,
            'duplicate' => $duplicate instanceof Objective,
            'duplicateMessage' => $duplicate instanceof Objective
                ? sprintf('Un objectif existe deja pour %s sur la periode %s.', $commercial->getFullName(), $normalizedPeriod)
                : null,
            'lastObjective' => $lastObjective instanceof Objective ? [
                'periodLabel' => $lastObjective->getPeriodLabel(),
                'salesTarget' => $lastObjective->getSalesTarget(),
                'visitsTarget' => $lastObjective->getVisitsTarget(),
                'newClientsTarget' => $lastObjective->getNewClientsTarget(),
            ] : null,
            'recommendedTargets' => [
                'salesTarget' => max($commercial->getSalesTarget(), $clientsAssigned * 1500),
                'visitsTarget' => max($commercial->getVisitsTarget(), max($plannedVisits, (int) ceil($clientsAssigned * 1.5))),
                'newClientsTarget' => max($commercial->getNewClientsTarget(), max(1, (int) ceil($clientsAssigned * 0.12))),
            ],
        ];
    }
}
