<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\Commercial;
use App\Entity\Tour;
use App\Entity\Visit;
use App\Repository\ClientRepository;
use App\Repository\CommercialRepository;
use App\Repository\MarketRepository;
use App\Repository\OfferRepository;
use App\Repository\VisitRepository;

class StatsService
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly VisitRepository $visitRepository,
        private readonly OfferRepository $offerRepository,
        private readonly MarketRepository $marketRepository,
        private readonly CommercialRepository $commercialRepository,
        private readonly TourCrudService $tourCrudService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildOverview(): array
    {
        $startOfMonth = new \DateTimeImmutable('first day of this month midnight');
        $tourStatus = $this->tourCrudService->getStatusOverview();
        $clients = $this->clientRepository->findAll();
        $visits = $this->visitRepository->findAll();
        $commercials = $this->commercialRepository->findActiveOrdered();
        /** @var Tour[] $tours */
        $tours = $tourStatus['latest'] ?? [];

        // Use the hydrated listing to keep statuses coherent with the rest of the app.
        $tours = $this->tourCrudService->getListing();

        return [
            'kpis' => [
                'clients' => $this->clientRepository->count([]),
                'prospects' => $this->clientRepository->count(['status' => Client::STATUS_POTENTIAL]),
                'visits_completed' => $this->visitRepository->countCompletedThisMonth($startOfMonth),
                'offers_in_progress' => $this->offerRepository->count(['status' => 'en_cours']),
                'revenue' => $this->offerRepository->sumCurrentMonth($startOfMonth),
                'tours_completed' => $tourStatus['completed'],
            ],
            'charts' => [
                'clients_by_status' => $this->buildClientStatusChart(),
                'clients_by_type' => $this->buildClientTypeChart($clients),
                'visits_by_status' => $this->buildVisitStatusChart(),
                'visits_by_result' => $this->buildVisitResultChart($visits),
                'visits_by_type' => $this->buildVisitTypeChart($visits),
                'visits_by_priority' => $this->buildVisitPriorityChart($visits),
                'visits_by_commercial' => $this->buildVisitsByCommercialChart($commercials),
                'visits_by_city' => $this->buildVisitsByCityChart($visits),
                'tours_by_status' => $this->buildTourStatusChart($tourStatus),
                'tours_by_commercial' => $this->buildToursByCommercialChart($tours),
                'top_clients_by_visits' => $this->buildTopClientsByVisitsChart($visits),
                'monthly_revenue' => $this->buildMonthlyRevenueChart(),
                'monthly_activity' => $this->buildMonthlyActivityChart($visits, $tours),
                'markets_by_score' => $this->buildMarketScoreChart(),
            ],
            'top_commercials' => $this->buildTopCommercials(),
            'markets' => $this->marketRepository->findBy([], ['globalScore' => 'DESC'], 5),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildClientStatusChart(): array
    {
        $labels = [];
        $values = [];

        foreach (Client::statusChoices() as $label => $status) {
            $labels[] = $label;
            $values[] = $this->clientRepository->count(['status' => $status]);
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * @param Client[] $clients
     *
     * @return array<string, mixed>
     */
    private function buildClientTypeChart(array $clients): array
    {
        $counts = [];

        foreach (Client::typeChoices() as $label => $type) {
            $counts[$label] = 0;
            foreach ($clients as $client) {
                if ($client->getType() === $type) {
                    ++$counts[$label];
                }
            }
        }

        return [
            'labels' => array_keys($counts),
            'values' => array_values($counts),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildVisitStatusChart(): array
    {
        $labels = [];
        $values = [];

        foreach (Visit::statusChoices() as $label => $status) {
            $labels[] = $label;
            $values[] = $this->visitRepository->count(['status' => $status]);
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * @param Visit[] $visits
     *
     * @return array<string, mixed>
     */
    private function buildVisitResultChart(array $visits): array
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

    /**
     * @param Visit[] $visits
     *
     * @return array<string, mixed>
     */
    private function buildVisitTypeChart(array $visits): array
    {
        $counts = [];

        foreach (Visit::typeChoices() as $label => $type) {
            $counts[$label] = count(array_filter(
                $visits,
                static fn (Visit $visit): bool => $visit->getType() === $type
            ));
        }

        return [
            'labels' => array_keys($counts),
            'values' => array_values($counts),
        ];
    }

    /**
     * @param Visit[] $visits
     *
     * @return array<string, mixed>
     */
    private function buildVisitPriorityChart(array $visits): array
    {
        $counts = [];

        foreach (Visit::priorityChoices() as $label => $priority) {
            $counts[$label] = count(array_filter(
                $visits,
                static fn (Visit $visit): bool => $visit->getPriority() === $priority
            ));
        }

        return [
            'labels' => array_keys($counts),
            'values' => array_values($counts),
        ];
    }

    /**
     * @param array<string, mixed> $tourStatus
     *
     * @return array<string, mixed>
     */
    private function buildTourStatusChart(array $tourStatus): array
    {
        return [
            'labels' => ['Programmees', 'En cours', 'Terminees', 'Annulees'],
            'values' => [
                (int) ($tourStatus['programmed'] ?? 0),
                (int) ($tourStatus['in_progress'] ?? 0),
                (int) ($tourStatus['completed'] ?? 0),
                (int) ($tourStatus['cancelled'] ?? 0),
            ],
        ];
    }

    /**
     * @param Commercial[] $commercials
     *
     * @return array<string, mixed>
     */
    private function buildVisitsByCommercialChart(array $commercials): array
    {
        $rows = [];

        foreach ($commercials as $commercial) {
            $total = $this->visitRepository->countCompletedForCommercial($commercial) + $this->visitRepository->countPlannedForCommercial($commercial);
            $rows[] = [
                'label' => $commercial->getFullName() ?? 'Commercial',
                'value' => $total,
            ];
        }

        usort($rows, static fn (array $left, array $right): int => $right['value'] <=> $left['value']);
        $rows = array_slice($rows, 0, 8);

        return [
            'labels' => array_column($rows, 'label'),
            'values' => array_column($rows, 'value'),
        ];
    }

    /**
     * @param Visit[] $visits
     *
     * @return array<string, mixed>
     */
    private function buildVisitsByCityChart(array $visits): array
    {
        $counts = [];

        foreach ($visits as $visit) {
            $city = $visit->getClient()?->getCity() ?: 'Non definie';
            $counts[$city] = ($counts[$city] ?? 0) + 1;
        }

        arsort($counts);
        $counts = array_slice($counts, 0, 8, true);

        return [
            'labels' => array_keys($counts),
            'values' => array_values($counts),
        ];
    }

    /**
     * @param Tour[] $tours
     *
     * @return array<string, mixed>
     */
    private function buildToursByCommercialChart(array $tours): array
    {
        $counts = [];

        foreach ($tours as $tour) {
            $label = $tour->getCommercial()?->getFullName() ?: 'Non affecte';
            $counts[$label] = ($counts[$label] ?? 0) + 1;
        }

        arsort($counts);
        $counts = array_slice($counts, 0, 8, true);

        return [
            'labels' => array_keys($counts),
            'values' => array_values($counts),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMonthlyRevenueChart(): array
    {
        $months = [];

        foreach (range(5, 0) as $offset) {
            $date = (new \DateTimeImmutable("first day of -{$offset} month"))->setTime(0, 0);
            $key = $date->format('Y-m');
            $months[$key] = [
                'label' => $date->format('M Y'),
                'value' => 0.0,
            ];
        }

        $currentMonth = (new \DateTimeImmutable('first day of this month'))->setTime(0, 0);
        $months[$currentMonth->format('Y-m')] = [
            'label' => $currentMonth->format('M Y'),
            'value' => 0.0,
        ];

        foreach ($this->offerRepository->findAll() as $offer) {
            $issuedAt = $offer->getIssuedAt();
            if (!$issuedAt instanceof \DateTimeImmutable) {
                continue;
            }

            $key = $issuedAt->format('Y-m');
            if (!isset($months[$key])) {
                continue;
            }

            $months[$key]['value'] += (float) $offer->getAmount();
        }

        return [
            'labels' => array_column($months, 'label'),
            'values' => array_map(static fn (array $month): float => round($month['value'], 2), array_values($months)),
        ];
    }

    /**
     * @param Visit[] $visits
     * @param Tour[] $tours
     *
     * @return array<string, mixed>
     */
    private function buildMonthlyActivityChart(array $visits, array $tours): array
    {
        $months = [];

        foreach (range(5, 0) as $offset) {
            $date = (new \DateTimeImmutable("first day of -{$offset} month"))->setTime(0, 0);
            $key = $date->format('Y-m');
            $months[$key] = [
                'label' => $date->format('M Y'),
                'visits' => 0,
                'completed_visits' => 0,
                'tours' => 0,
            ];
        }

        $currentMonth = (new \DateTimeImmutable('first day of this month'))->setTime(0, 0);
        $months[$currentMonth->format('Y-m')] = [
            'label' => $currentMonth->format('M Y'),
            'visits' => 0,
            'completed_visits' => 0,
            'tours' => 0,
        ];

        foreach ($visits as $visit) {
            $scheduledAt = $visit->getScheduledAt();
            if (!$scheduledAt instanceof \DateTimeImmutable) {
                continue;
            }

            $key = $scheduledAt->format('Y-m');
            if (!isset($months[$key])) {
                continue;
            }

            ++$months[$key]['visits'];
            if ($visit->getStatus() === Visit::STATUS_COMPLETED) {
                ++$months[$key]['completed_visits'];
            }
        }

        foreach ($tours as $tour) {
            $scheduledFor = $tour->getScheduledFor();
            if (!$scheduledFor instanceof \DateTimeImmutable) {
                continue;
            }

            $key = $scheduledFor->format('Y-m');
            if (!isset($months[$key])) {
                continue;
            }

            ++$months[$key]['tours'];
        }

        return [
            'labels' => array_column($months, 'label'),
            'datasets' => [
                [
                    'label' => 'Visites prevues',
                    'values' => array_map(static fn (array $month): int => $month['visits'], array_values($months)),
                ],
                [
                    'label' => 'Visites realisees',
                    'values' => array_map(static fn (array $month): int => $month['completed_visits'], array_values($months)),
                ],
                [
                    'label' => 'Tournees',
                    'values' => array_map(static fn (array $month): int => $month['tours'], array_values($months)),
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMarketScoreChart(): array
    {
        $markets = $this->marketRepository->findBy([], ['globalScore' => 'DESC'], 6);

        return [
            'labels' => array_map(static fn ($market): string => $market->getCity(), $markets),
            'values' => array_map(static fn ($market): int => $market->getGlobalScore(), $markets),
        ];
    }

    /**
     * @param Visit[] $visits
     *
     * @return array<string, mixed>
     */
    private function buildTopClientsByVisitsChart(array $visits): array
    {
        $counts = [];

        foreach ($visits as $visit) {
            $label = $visit->getClient()?->getName() ?: 'Client inconnu';
            $counts[$label] = ($counts[$label] ?? 0) + 1;
        }

        arsort($counts);
        $counts = array_slice($counts, 0, 8, true);

        return [
            'labels' => array_keys($counts),
            'values' => array_values($counts),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildTopCommercials(): array
    {
        $commercials = [];

        foreach ($this->commercialRepository->findActiveOrdered() as $commercial) {
            $commercials[] = [
                'name' => $commercial->getFullName(),
                'city' => $commercial->getCity(),
                'clients' => $this->clientRepository->countForCommercial($commercial),
                'visits_completed' => $this->visitRepository->countCompletedForCommercial($commercial),
                'visits_planned' => $this->visitRepository->countPlannedForCommercial($commercial),
            ];
        }

        usort($commercials, static fn (array $left, array $right): int => $right['visits_completed'] <=> $left['visits_completed']);

        return array_slice($commercials, 0, 6);
    }
}
