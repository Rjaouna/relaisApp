<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\Commercial;
use App\Entity\Market;
use App\Entity\Tour;
use App\Entity\Visit;
use App\Repository\ClientRepository;
use App\Repository\CommercialRepository;
use App\Repository\MarketRepository;
use App\Repository\OfferRepository;
use App\Repository\TourRepository;
use App\Repository\VisitRepository;
use Doctrine\ORM\EntityManagerInterface;

class DecisionSupportService
{
    public const DEFAULT_TOUR_CLIENT_STATUSES = [
        Client::STATUS_POTENTIAL,
        Client::STATUS_IN_PROGRESS,
        Client::STATUS_ACTIVE,
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ClientRepository $clientRepository,
        private readonly CommercialRepository $commercialRepository,
        private readonly VisitRepository $visitRepository,
        private readonly OfferRepository $offerRepository,
        private readonly MarketRepository $marketRepository,
        private readonly TourRepository $tourRepository,
    ) {
    }

    public function autoAssignClients(): int
    {
        $commercials = $this->commercialRepository->findActiveOrdered();
        if ($commercials === []) {
            return 0;
        }

        $assigned = 0;
        foreach ($this->clientRepository->findAssignable() as $client) {
            $commercial = $this->selectCommercialForClient($client, $commercials);
            if (!$commercial instanceof Commercial) {
                continue;
            }

            $client->setAssignedCommercial($commercial);
            $commercial->setCurrentClientsLoad($commercial->getCurrentClientsLoad() + 1);
            $commercial->touch();
            $client->touch();

            $this->entityManager->persist($client);
            $this->entityManager->persist($commercial);
            ++$assigned;
        }

        $this->entityManager->flush();

        return $assigned;
    }

    public function rebuildMarketInsights(): int
    {
        $clientsByCity = [];
        foreach ($this->clientRepository->findAll() as $client) {
            $city = $client->getCity() ?? 'Non defini';
            $clientsByCity[$city]['clients'][] = $client;
        }

        foreach ($this->offerRepository->findAll() as $offer) {
            $city = $offer->getClient()?->getCity() ?? 'Non defini';
            $clientsByCity[$city]['offers'][] = $offer;
        }

        $existingMarkets = [];
        foreach ($this->marketRepository->findAll() as $market) {
            $existingMarkets[$market->getCity()] = $market;
        }

        $count = 0;
        foreach ($clientsByCity as $city => $payload) {
            $clients = $payload['clients'] ?? [];
            $offers = $payload['offers'] ?? [];

            $market = $existingMarkets[$city] ?? new Market();
            $revenue = array_reduce($offers, static fn (float $carry, $offer): float => $carry + (float) $offer->getAmount(), 0.0);
            $activeClients = array_filter($clients, static fn (Client $client): bool => $client->getStatus() === Client::STATUS_ACTIVE);
            $coverageScore = min(100, (int) round((count($activeClients) / max(count($clients), 1)) * 100));
            $competitionScore = max(20, 100 - (count($clients) * 3));
            $globalScore = (int) round(($coverageScore + $competitionScore + min(100, $revenue / 1000)) / 3);

            $market
                ->setCity($city)
                ->setClientsCount(count($clients))
                ->setRevenue(number_format($revenue, 2, '.', ''))
                ->setCoverageScore($coverageScore)
                ->setCompetitionScore($competitionScore)
                ->setGlobalScore($globalScore)
                ->setZoneStatus($globalScore >= 75 ? 'saturee' : ($globalScore >= 45 ? 'a_developper' : 'opportunite'));

            $this->entityManager->persist($market);
            ++$count;
        }

        $this->entityManager->flush();

        return $count;
    }

    /**
     * @param string[] $clientStatuses
     */
    public function generateToursFromVisits(array $clientStatuses = []): int
    {
        $plannedVisits = $this->visitRepository->findPlannedForTourGeneration(
            $clientStatuses !== [] ? $this->sanitizeClientStatuses($clientStatuses) : self::DEFAULT_TOUR_CLIENT_STATUSES
        );
        $grouped = [];

        foreach ($plannedVisits as $visit) {
            $commercial = $visit->getClient()?->getAssignedCommercial();
            if (!$commercial instanceof Commercial) {
                continue;
            }

            $tourDate = $visit->getScheduledAt() instanceof \DateTimeImmutable
                ? new \DateTimeImmutable($visit->getScheduledAt()->format('Y-m-d 08:00:00'))
                : new \DateTimeImmutable('today 08:00');
            $dateKey = $tourDate->format('Y-m-d');
            $groupKey = $commercial->getId() . '_' . $visit->getClient()?->getCity() . '_' . $dateKey;
            $grouped[$groupKey]['commercial'] = $commercial;
            $grouped[$groupKey]['city'] = $visit->getClient()?->getCity() ?? $commercial->getCity();
            $grouped[$groupKey]['date'] = $tourDate;
            $grouped[$groupKey]['visits'][] = $visit;
        }

        $generated = 0;
        foreach ($grouped as $payload) {
            $refusedVisitsCount = count(array_filter(
                $payload['visits'],
                static fn (Visit $visit): bool => $visit->getClient()?->getStatus() === Client::STATUS_REFUSED
            ));

            $existingTour = $this->tourRepository->findOneBy([
                'commercial' => $payload['commercial'],
                'city' => $payload['city'],
                'scheduledFor' => $payload['date'],
            ]);

            if ($existingTour instanceof Tour) {
                $existingTour
                    ->setStatus(Tour::STATUS_PROGRAMMED)
                    ->setPlannedVisits(count($payload['visits']))
                    ->setRouteSummary($this->buildTourRouteSummary($payload['commercial']->getZoneLabel(), count($payload['visits']), $refusedVisitsCount))
                    ->touch();

                $this->entityManager->persist($existingTour);
                ++$generated;
                continue;
            }

            $tour = new Tour();
            $tour
                ->setCommercial($payload['commercial'])
                ->setCity($payload['city'])
                ->setScheduledFor($payload['date'])
                ->setStatus(Tour::STATUS_PROGRAMMED)
                ->setPlannedVisits(count($payload['visits']))
                ->setCompletedVisits(0)
                ->setName(sprintf('Tournee %s %s', $payload['city'], $payload['date']->format('d/m')))
                ->setRouteSummary($this->buildTourRouteSummary($payload['commercial']->getZoneLabel(), count($payload['visits']), $refusedVisitsCount));

            $this->entityManager->persist($tour);
            ++$generated;
        }

        $this->entityManager->flush();

        return $generated;
    }

    /**
     * @return string[]
     */
    public function getAvailableTourClientStatuses(): array
    {
        return array_values(Client::statusChoices());
    }

    public function getExecutiveMetrics(): array
    {
        $assignedClients = $this->clientRepository->countAssigned();
        $totalClients = $this->clientRepository->count([]);
        $markets = $this->marketRepository->findAll();
        $weakZones = array_filter($markets, static fn (Market $market): bool => $market->getGlobalScore() < 45);

        return [
            'assigned_clients' => $assignedClients,
            'assignment_rate' => $totalClients > 0 ? (int) round(($assignedClients / $totalClients) * 100) : 0,
            'weak_zones' => count($weakZones),
            'tours_generated' => $this->tourRepository->count([]),
        ];
    }

    /**
     * @param Commercial[] $commercials
     */
    private function selectCommercialForClient(Client $client, array $commercials): ?Commercial
    {
        usort($commercials, static fn (Commercial $left, Commercial $right): int => $left->getCommercialLoad() <=> $right->getCommercialLoad());

        foreach ($commercials as $commercial) {
            $zoneNames = array_map(
                static fn (\App\Entity\Zone $zone): string => $zone->getName() ?? '',
                $commercial->getZones()->toArray()
            );

            if ($zoneNames === [] && $commercial->getZone()?->getName()) {
                $zoneNames = [$commercial->getZone()?->getName() ?? ''];
            }

            if (
                $commercial->getCity() === $client->getCity()
                || in_array(($client->getZone()?->getName() ?: $client->getCity()), $zoneNames, true)
            ) {
                return $commercial;
            }
        }

        return $commercials[0] ?? null;
    }

    /**
     * @param string[] $clientStatuses
     *
     * @return string[]
     */
    private function sanitizeClientStatuses(array $clientStatuses): array
    {
        $allowed = $this->getAvailableTourClientStatuses();

        return array_values(array_filter(
            array_unique($clientStatuses),
            static fn (string $status): bool => in_array($status, $allowed, true)
        ));
    }

    private function buildTourRouteSummary(string $zoneLabel, int $plannedVisitsCount, int $refusedVisitsCount): string
    {
        $summary = sprintf(
            'Tournee programmee automatiquement pour %s (%d visite(s) prevue(s))',
            $zoneLabel,
            $plannedVisitsCount
        );

        if ($refusedVisitsCount > 0) {
            $summary .= sprintf(' - attention : %d client(s) refuse(s) a traiter en priorite.', $refusedVisitsCount);
        }

        return $summary;
    }
}
