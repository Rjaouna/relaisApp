<?php

namespace App\Service;

use App\Entity\Client;
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
                'visits_by_status' => $this->buildVisitStatusChart(),
                'tours_by_status' => $this->buildTourStatusChart($tourStatus),
                'monthly_revenue' => $this->buildMonthlyRevenueChart(),
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
