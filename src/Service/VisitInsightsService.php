<?php

namespace App\Service;

use App\Entity\Visit;
use App\Repository\VisitRepository;

class VisitInsightsService
{
    public function __construct(
        private readonly VisitRepository $visitRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildOverview(): array
    {
        $activeVisits = $this->visitRepository->findForListing();
        $archivedVisits = $this->visitRepository->findForListing(true);
        $allVisits = [...$activeVisits, ...$archivedVisits];

        return [
            'kpis' => [
                'active' => count($activeVisits),
                'planned' => count(array_filter($activeVisits, static fn (Visit $visit): bool => $visit->getStatus() === Visit::STATUS_PLANNED)),
                'completed' => count(array_filter($activeVisits, static fn (Visit $visit): bool => $visit->getStatus() === Visit::STATUS_COMPLETED)),
                'archived' => count($archivedVisits),
            ],
            'charts' => [
                'clients' => $this->buildTopClientsChart($allVisits),
                'cities' => $this->buildCityChart($allVisits),
                'results' => $this->buildResultChart($allVisits),
            ],
        ];
    }

    /**
     * @param Visit[] $visits
     * @return array<string, mixed>
     */
    private function buildTopClientsChart(array $visits): array
    {
        $counts = [];

        foreach ($visits as $visit) {
            $label = $visit->getClient()?->getName() ?: 'Client inconnu';
            $counts[$label] = ($counts[$label] ?? 0) + 1;
        }

        arsort($counts);
        $counts = array_slice($counts, 0, 6, true);

        return [
            'labels' => array_keys($counts),
            'values' => array_values($counts),
        ];
    }

    /**
     * @param Visit[] $visits
     * @return array<string, mixed>
     */
    private function buildCityChart(array $visits): array
    {
        $counts = [];

        foreach ($visits as $visit) {
            $label = $visit->getClient()?->getCity() ?: 'Ville non definie';
            $counts[$label] = ($counts[$label] ?? 0) + 1;
        }

        arsort($counts);
        $counts = array_slice($counts, 0, 6, true);

        return [
            'labels' => array_keys($counts),
            'values' => array_values($counts),
        ];
    }

    /**
     * @param Visit[] $visits
     * @return array<string, mixed>
     */
    private function buildResultChart(array $visits): array
    {
        $counts = [];

        foreach (Visit::resultChoices() as $label => $result) {
            $counts[$label] = count(array_filter(
                $visits,
                static fn (Visit $visit): bool => $visit->getResult() === $result
            ));
        }

        return [
            'labels' => array_keys($counts),
            'values' => array_values($counts),
        ];
    }
}
