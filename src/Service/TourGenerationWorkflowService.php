<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\Commercial;
use App\Entity\Tour;
use App\Entity\Visit;
use App\Repository\CommercialRepository;
use App\Repository\TourRepository;
use App\Repository\VisitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class TourGenerationWorkflowService
{
    private const SESSION_KEY = 'tour_generation_workflow';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly CommercialRepository $commercialRepository,
        private readonly VisitRepository $visitRepository,
        private readonly TourRepository $tourRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param string[] $statuses
     */
    public function start(array $statuses = []): void
    {
        $draft = $this->buildDraft($statuses);
        $this->storeDraft($draft);
    }

    /**
     * @param string[] $statuses
     * @param array<int, array{included:bool, commercialId:?int}> $zoneStates
     */
    public function updatePreparation(array $statuses, array $zoneStates): void
    {
        $draft = $this->buildDraft($statuses, $zoneStates, $this->getDraft()['clientAssignments'] ?? []);
        $this->storeDraft($draft);
    }

    /**
     * @param array<int, int> $clientAssignments
     */
    public function updateClientAssignments(array $clientAssignments): void
    {
        $draft = $this->getDraft();
        $draft['clientAssignments'] = [];

        foreach ($clientAssignments as $clientId => $commercialId) {
            $draft['clientAssignments'][(int) $clientId] = (int) $commercialId;
        }

        $this->storeDraft($draft);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPreparationView(): array
    {
        $draft = $this->getOrInitializeDraft();
        $commercials = $this->commercialRepository->findActiveOrdered();
        $columns = [];

        foreach ($commercials as $commercial) {
            $columns[] = [
                'commercial' => $commercial,
                'zones' => array_values(array_filter(
                    $draft['zones'],
                    static fn (array $zone): bool => $zone['included'] && $zone['commercialId'] === $commercial->getId()
                )),
            ];
        }

        return [
            'statuses' => $draft['statuses'],
            'columns' => $columns,
            'unassignedZones' => array_values(array_filter(
                $draft['zones'],
                static fn (array $zone): bool => $zone['included'] && $zone['commercialId'] === null
            )),
            'excludedZones' => array_values(array_filter(
                $draft['zones'],
                static fn (array $zone): bool => !$zone['included']
            )),
            'hasConflicts' => $this->hasConflicts(),
            'draft' => $draft,
            'commercialChoices' => $commercials,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getConflictItems(): array
    {
        $draft = $this->getOrInitializeDraft();
        $conflicts = [];

        foreach ($draft['zones'] as $zoneId => $zoneData) {
            if (!$zoneData['included'] || $zoneData['commercialId'] !== null || count($zoneData['availableCommercialIds']) < 2) {
                continue;
            }

            if ($zoneData['clientIds'] === []) {
                continue;
            }

            $commercials = array_values(array_filter(
                $this->commercialRepository->findActiveOrdered(),
                static fn (Commercial $commercial): bool => in_array($commercial->getId(), $zoneData['availableCommercialIds'], true)
            ));

            $clients = [];
            foreach ($zoneData['clients'] as $clientData) {
                $clients[] = [
                    ...$clientData,
                    'selectedCommercialId' => $draft['clientAssignments'][$clientData['id']] ?? null,
                ];
            }

            $conflicts[] = [
                'zoneId' => $zoneId,
                'zoneName' => $zoneData['zoneName'],
                'cityName' => $zoneData['cityName'],
                'commercials' => $commercials,
                'clients' => $clients,
            ];
        }

        return $conflicts;
    }

    public function hasConflicts(): bool
    {
        foreach ($this->getConflictItems() as $conflict) {
            foreach ($conflict['clients'] as $client) {
                if (!isset($client['selectedCommercialId']) || $client['selectedCommercialId'] === null) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array{tours:int, visits:int}
     */
    public function finalize(): array
    {
        $draft = $this->getOrInitializeDraft();

        if ($this->hasConflicts()) {
            throw new \LogicException('La repartition des clients sur les zones partagees doit etre finalisee avant la generation.');
        }

        $groups = [];
        $visitsCount = 0;

        foreach ($this->visitRepository->findPlannedForTourGeneration($draft['statuses']) as $visit) {
            $client = $visit->getClient();
            $zone = $client?->getZone();
            $zoneId = $zone?->getId();

            if ($client === null || $zoneId === null || !isset($draft['zones'][$zoneId])) {
                continue;
            }

            $zoneData = $draft['zones'][$zoneId];
            if (!$zoneData['included']) {
                continue;
            }

            $commercialId = $zoneData['commercialId'] ?? ($draft['clientAssignments'][$client->getId() ?? 0] ?? null);
            if ($commercialId === null) {
                throw new \LogicException('Une zone partagee n a pas ete correctement arbitree.');
            }

            $commercial = $this->commercialRepository->find($commercialId);
            if (!$commercial instanceof Commercial) {
                continue;
            }

            $groupKey = $commercialId . '_' . $zoneId;
            $groups[$groupKey]['commercial'] = $commercial;
            $groups[$groupKey]['zone'] = $zone;
            $groups[$groupKey]['visits'][] = $visit;
        }

        if ($groups === []) {
            throw new \LogicException('Aucun client concerne par cette generation. Aucune tournee n a ete creee.');
        }

        $generatedTours = 0;
        foreach ($groups as $payload) {
            $commercial = $payload['commercial'];
            $zone = $payload['zone'];
            $tour = $this->tourRepository->findReusableForGeneration($commercial, $zone);

            if (!$tour instanceof Tour) {
                $tour = new Tour();
                $tour
                    ->setCommercial($commercial)
                    ->setZone($zone)
                    ->setCity($zone->getCity()?->getName() ?? $commercial->getCity() ?? 'Non definie')
                    ->setScheduledFor(new \DateTimeImmutable('today 08:00'))
                    ->setStatus(Tour::STATUS_PROGRAMMED)
                    ->setCompletedVisits(0)
                    ->setClosureRequestedAt(null)
                    ->setArchivedAt(null)
                    ->setName(sprintf('Tournee %s - %s', $commercial->getFullName(), $zone->getName()))
                    ->setRouteSummary(sprintf('Preparation manuelle sur la zone %s', $zone->getName()));
                ++$generatedTours;
            }

            foreach ($payload['visits'] as $visit) {
                $visit->setTour($tour);
                $visit->touch();
                $client = $visit->getClient();
                if ($client instanceof Client) {
                    $client->setAssignedCommercial($commercial);
                    $client->touch();
                    $this->entityManager->persist($client);
                }
                $this->entityManager->persist($visit);
                ++$visitsCount;
            }

            $tour->setPlannedVisits(count($payload['visits']));
            $this->entityManager->persist($tour);
        }

        $this->entityManager->flush();
        $this->clear();

        return [
            'tours' => $generatedTours,
            'visits' => $visitsCount,
        ];
    }

    public function clear(): void
    {
        $this->requestStack->getSession()->remove(self::SESSION_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    private function getOrInitializeDraft(): array
    {
        $draft = $this->getDraft();
        if ($draft !== []) {
            return $draft;
        }

        $this->start();

        return $this->getDraft();
    }

    /**
     * @return array<string, mixed>
     */
    private function getDraft(): array
    {
        return (array) $this->requestStack->getSession()->get(self::SESSION_KEY, []);
    }

    /**
     * @param array<string, mixed> $draft
     */
    private function storeDraft(array $draft): void
    {
        $this->requestStack->getSession()->set(self::SESSION_KEY, $draft);
    }

    /**
     * @param string[] $statuses
     * @param array<int, array{included:bool, commercialId:?int}> $zoneStates
     * @param array<int, int> $clientAssignments
     *
     * @return array<string, mixed>
     */
    private function buildDraft(array $statuses = [], array $zoneStates = [], array $clientAssignments = []): array
    {
        $statuses = $statuses !== [] ? array_values(array_unique($statuses)) : DecisionSupportService::DEFAULT_TOUR_CLIENT_STATUSES;
        $commercials = $this->commercialRepository->findActiveOrdered();
        $commercialIndex = [];
        foreach ($commercials as $commercial) {
            if ($commercial->getId() !== null) {
                $commercialIndex[$commercial->getId()] = $commercial;
            }
        }

        $zones = [];
        foreach ($commercials as $commercial) {
            $assignedZones = $commercial->getZones()->toArray();
            if ($assignedZones === [] && $commercial->getZone() !== null) {
                $assignedZones = [$commercial->getZone()];
            }

            foreach ($assignedZones as $zone) {
                $zoneId = $zone->getId();
                if ($zoneId === null) {
                    continue;
                }

                $zones[$zoneId] ??= [
                    'id' => $zoneId,
                    'zoneName' => $zone->getName(),
                    'cityName' => $zone->getCity()?->getName() ?? 'Ville',
                    'availableCommercialIds' => [],
                    'commercialId' => null,
                    'included' => true,
                    'clientIds' => [],
                    'clients' => [],
                ];

                if (!in_array($commercial->getId(), $zones[$zoneId]['availableCommercialIds'], true)) {
                    $zones[$zoneId]['availableCommercialIds'][] = $commercial->getId();
                }
            }
        }

        foreach ($this->visitRepository->findPlannedForTourGeneration($statuses) as $visit) {
            $client = $visit->getClient();
            $zoneId = $client?->getZone()?->getId();
            if ($client === null || $zoneId === null || !isset($zones[$zoneId])) {
                continue;
            }

            $clientId = $client->getId();
            if ($clientId === null || in_array($clientId, $zones[$zoneId]['clientIds'], true)) {
                continue;
            }

            $zones[$zoneId]['clientIds'][] = $clientId;
            $zones[$zoneId]['clients'][] = [
                'id' => $clientId,
                'name' => $client->getName(),
                'city' => $client->getCity(),
                'status' => $client->getStatus(),
            ];
        }

        foreach ($zones as $zoneId => &$zoneData) {
            $state = $zoneStates[$zoneId] ?? null;
            $zoneData['included'] = $state['included'] ?? true;

            if (isset($state['commercialId']) && $state['commercialId'] !== null && isset($commercialIndex[$state['commercialId']])) {
                $zoneData['commercialId'] = (int) $state['commercialId'];
            } elseif (count($zoneData['availableCommercialIds']) === 1) {
                $zoneData['commercialId'] = $zoneData['availableCommercialIds'][0];
            }

            $zoneData['clientCount'] = count($zoneData['clientIds']);
        }
        unset($zoneData);

        $zones = array_filter(
            $zones,
            static fn (array $zoneData): bool => ($zoneData['clientCount'] ?? 0) > 0
        );

        return [
            'statuses' => $statuses,
            'zones' => $zones,
            'clientAssignments' => $clientAssignments,
        ];
    }
}
