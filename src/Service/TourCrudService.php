<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\Commercial;
use App\Entity\Tour;
use App\Repository\ClientRepository;
use App\Repository\TourRepository;
use App\Repository\VisitRepository;
use Doctrine\ORM\EntityManagerInterface;

class TourCrudService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TourRepository $tourRepository,
        private readonly VisitRepository $visitRepository,
        private readonly ClientRepository $clientRepository,
        private readonly ObjectivePerformanceService $objectivePerformanceService,
        private readonly VisitCrudService $visitCrudService,
    ) {
    }

    public function getListing(): array
    {
        $tours = $this->hydrateProgress($this->tourRepository->findBy([], ['scheduledFor' => 'DESC']));

        usort($tours, static function (Tour $left, Tour $right): int {
            $leftPriority = [
                $left->getArchivedAt() instanceof \DateTimeImmutable ? 3 : ($left->getStatus() === Tour::STATUS_COMPLETED ? 2 : 1),
                $left->getClosureRequestedAt() instanceof \DateTimeImmutable ? 0 : 1,
                $left->getStatus() === Tour::STATUS_PROGRAMMED && $left->getCompletedVisits() === 0 ? 0 : 1,
            ];
            $rightPriority = [
                $right->getArchivedAt() instanceof \DateTimeImmutable ? 3 : ($right->getStatus() === Tour::STATUS_COMPLETED ? 2 : 1),
                $right->getClosureRequestedAt() instanceof \DateTimeImmutable ? 0 : 1,
                $right->getStatus() === Tour::STATUS_PROGRAMMED && $right->getCompletedVisits() === 0 ? 0 : 1,
            ];

            return $leftPriority <=> $rightPriority
                ?: (($right->getScheduledFor()?->getTimestamp() ?? 0) <=> ($left->getScheduledFor()?->getTimestamp() ?? 0));
        });

        return $tours;
    }

    public function save(Tour $tour): void
    {
        $isNew = $tour->getId() === null;

        if (!$tour->getZone()) {
            throw new \LogicException('Choisis une zone avant de creer la tournee.');
        }

        if ($tour->getZone()?->getCity()?->getName()) {
            $tour->setCity($tour->getZone()?->getCity()?->getName() ?? $tour->getCity() ?? 'Non definie');
        }

        if (!$tour->getScheduledFor() instanceof \DateTimeImmutable) {
            $tour->setScheduledFor(new \DateTimeImmutable('today 08:00'));
        }

        if ($isNew) {
            $clients = $this->clientRepository->findForZone($tour->getZone());
            if ($clients === []) {
                throw new \LogicException('Aucun client n est rattache a cette zone. Impossible de creer la tournee.');
            }

            $visits = [];
            $skippedClients = 0;

            foreach ($clients as $client) {
                $existingVisits = $this->visitRepository->findUnarchivedForClientIds([$client->getId() ?? 0]);
                $selectedVisit = null;

                foreach ($existingVisits as $existingVisit) {
                    if ($existingVisit->getStatus() === \App\Entity\Visit::STATUS_COMPLETED) {
                        continue;
                    }

                    if ($existingVisit->getTour() instanceof Tour) {
                        $selectedVisit = null;
                        ++$skippedClients;
                        continue 2;
                    }

                    $selectedVisit = $existingVisit;
                    break;
                }

                if (!$selectedVisit instanceof \App\Entity\Visit) {
                    $selectedVisit = new \App\Entity\Visit();
                    $selectedVisit->setClient($client);
                    $selectedVisit->setScheduledAt($selectedVisit->getCreatedAt());
                }

                $selectedVisit->setTour($tour);
                $selectedVisit->touch();
                $this->entityManager->persist($selectedVisit);
                $visits[] = $selectedVisit;

                if ($tour->getCommercial() instanceof Commercial) {
                    $client->setAssignedCommercial($tour->getCommercial());
                    $client->touch();
                    $this->entityManager->persist($client);
                }
            }

            if ($visits === []) {
                throw new \LogicException('Tous les clients de cette zone sont deja engages dans d autres tournees. Aucune tournee manuelle n a ete creee.');
            }

            $summary = sprintf('Tournee manuelle preparee pour la zone %s.', $tour->getZone()->getName());
            if ($skippedClients > 0) {
                $summary .= sprintf(' %d client(s) deja engages ailleurs ont ete ignores.', $skippedClients);
            }

            $tour
                ->setStatus(Tour::STATUS_PROGRAMMED)
                ->setPlannedVisits(count($visits))
                ->setCompletedVisits(0)
                ->setRouteSummary($summary);

            $this->entityManager->persist($tour);
        }

        $this->entityManager->persist($tour);
        $this->entityManager->flush();
    }

    public function canEdit(Tour $tour): bool
    {
        $this->hydrateTour($tour);

        return $tour->getStatus() !== Tour::STATUS_IN_PROGRESS
            && $tour->getStatus() !== Tour::STATUS_COMPLETED
            && !$this->hasClosureRequest($tour);
    }

    public function canReassign(Tour $tour): bool
    {
        $this->hydrateTour($tour);

        return $tour->getStatus() === Tour::STATUS_PROGRAMMED && !$this->hasClosureRequest($tour);
    }

    public function requestClosure(Tour $tour): bool
    {
        $this->hydrateTour($tour);

        if ($tour->getPlannedVisits() === 0 || $tour->getCompletedVisits() < $tour->getPlannedVisits()) {
            return false;
        }

        $visits = $this->visitRepository->findForTour($tour);

        foreach ($visits as $visit) {
            if ($visit->getStatus() === \App\Entity\Visit::STATUS_COMPLETED && $visit->getResult() !== null && $visit->getAdminReviewStatus() === null) {
                $visit->setAdminReviewStatus(\App\Entity\Visit::REVIEW_PENDING);
                $this->entityManager->persist($visit);
            }
        }

        $tour
            ->setStatus(Tour::STATUS_IN_PROGRESS)
            ->setClosureRequestedAt(new \DateTimeImmutable());

        $this->entityManager->persist($tour);
        $this->entityManager->flush();

        return true;
    }

    public function close(Tour $tour): bool
    {
        $this->hydrateTour($tour);

        if (!$this->canBeClosedByAdmin($tour)) {
            return false;
        }

        $visits = $this->visitRepository->findForTour($tour);
        $this->visitCrudService->archiveVisits($visits);

        $tour->setStatus(Tour::STATUS_COMPLETED);
        $this->entityManager->persist($tour);
        $this->entityManager->flush();

        return true;
    }

    public function archive(Tour $tour): bool
    {
        $this->hydrateTour($tour);

        if ($tour->getStatus() !== Tour::STATUS_COMPLETED || $tour->getArchivedAt() instanceof \DateTimeImmutable) {
            return false;
        }

        $tour->setArchivedAt(new \DateTimeImmutable());
        $this->entityManager->persist($tour);
        $this->entityManager->flush();

        return true;
    }

    public function delete(Tour $tour): void
    {
        $this->entityManager->remove($tour);
        $this->entityManager->flush();
    }

    public function reassign(Tour $tour, Commercial $newCommercial): int
    {
        if (!$this->canReassign($tour)) {
            throw new \LogicException('Seules les tournees programmees peuvent etre reattribuees.');
        }

        $currentCommercial = $tour->getCommercial();
        if (!$currentCommercial instanceof Commercial) {
            throw new \LogicException('Cette tournee n a pas de commercial attribue.');
        }

        if ($currentCommercial->getId() === $newCommercial->getId()) {
            throw new \LogicException('Cette tournee est deja attribuee a ce commercial.');
        }

        $visits = $this->visitRepository->findForTour($tour);
        $movedClientIds = [];

        foreach ($visits as $visit) {
            $client = $visit->getClient();
            if ($client === null || $client->getId() === null) {
                continue;
            }

            if (isset($movedClientIds[$client->getId()])) {
                continue;
            }

            $client->setAssignedCommercial($newCommercial);
            $client->touch();
            $this->entityManager->persist($client);
            $visit->touch();
            $this->entityManager->persist($visit);
            $movedClientIds[$client->getId()] = true;
        }

        $tour->setCommercial($newCommercial);
        $this->entityManager->persist($tour);

        $this->refreshCommercialLoad($currentCommercial);
        $this->refreshCommercialLoad($newCommercial);

        $this->entityManager->flush();

        $this->objectivePerformanceService->syncAllObjectivesForCommercial($currentCommercial);
        $this->objectivePerformanceService->syncAllObjectivesForCommercial($newCommercial);

        return count($movedClientIds);
    }

    public function createGenerated(?Commercial $commercial = null): Tour
    {
        $tour = new Tour();
        $tour
            ->setName('Tournee optimisee')
            ->setCity($commercial?->getCity() ?? 'Casablanca')
            ->setScheduledFor(new \DateTimeImmutable('tomorrow 09:00'))
            ->setStatus(Tour::STATUS_PROGRAMMED)
            ->setPlannedVisits(6)
            ->setCompletedVisits(0)
            ->setRouteSummary('Itineraire optimise par zone et priorite');

        if ($commercial instanceof Commercial) {
            $tour->setCommercial($commercial);
        }

        return $tour;
    }

    public function hydrateTour(Tour $tour): Tour
    {
        $visits = $this->visitRepository->findForTour($tour);
        $tour->setPlannedVisits(count($visits));
        $tour->setCompletedVisits(count(array_filter($visits, static fn ($visit): bool => $visit->getStatus() === \App\Entity\Visit::STATUS_COMPLETED)));

        if ($tour->getStatus() !== Tour::STATUS_CANCELLED && $tour->getStatus() !== Tour::STATUS_COMPLETED) {
            if ($tour->getCompletedVisits() > 0) {
                $tour->setStatus(Tour::STATUS_IN_PROGRESS);
            } else {
                $tour->setStatus(Tour::STATUS_PROGRAMMED);
            }
        }

        return $tour;
    }

    public function canMoveVisit(Tour $tour): bool
    {
        $this->hydrateTour($tour);

        return !$this->hasClosureRequest($tour) && !in_array($tour->getStatus(), [Tour::STATUS_COMPLETED, Tour::STATUS_CANCELLED], true);
    }

    public function canReceiveVisit(Tour $tour): bool
    {
        return $this->canMoveVisit($tour);
    }

    /**
     * @return Tour[]
     */
    public function getMovableTargets(Tour $sourceTour): array
    {
        return array_values(array_filter(
            $this->getListing(),
            fn (Tour $tour): bool => $tour->getId() !== $sourceTour->getId() && $this->canReceiveVisit($tour)
        ));
    }

    public function moveVisitToTour(\App\Entity\Visit $visit, Tour $targetTour): void
    {
        $sourceTour = $visit->getTour();
        if (!$sourceTour instanceof Tour || !$this->canMoveVisit($sourceTour) || !$this->canReceiveVisit($targetTour)) {
            throw new \LogicException('Le deplacement est interdit sur une tournee verrouillee.');
        }

        $visit->setTour($targetTour);
        $visit->touch();
        $this->entityManager->persist($visit);

        $client = $visit->getClient();
        if ($client instanceof Client) {
            $client->setAssignedCommercial($targetTour->getCommercial());
            $client->touch();
            $this->entityManager->persist($client);
        }

        $this->finalizeEmptySourceTourIfNeeded($sourceTour);
        $this->entityManager->flush();
    }

    public function createNewTourForVisit(\App\Entity\Visit $visit, Commercial $commercial): Tour
    {
        $sourceTour = $visit->getTour();
        if (!$sourceTour instanceof Tour || !$this->canMoveVisit($sourceTour)) {
            throw new \LogicException('Impossible de creer une nouvelle tournee depuis une tournee verrouillee.');
        }

        $zone = $visit->getClient()?->getZone();
        $tour = new Tour();
        $tour
            ->setCommercial($commercial)
            ->setZone($zone)
            ->setCity($zone?->getCity()?->getName() ?? $commercial->getCity() ?? 'Non definie')
            ->setScheduledFor(new \DateTimeImmutable('today 08:00'))
            ->setStatus(Tour::STATUS_PROGRAMMED)
            ->setPlannedVisits(0)
            ->setCompletedVisits(0)
            ->setName(sprintf('Tournee %s - %s', $commercial->getFullName(), $zone?->getName() ?? 'Zone libre'))
            ->setRouteSummary('Tournee creee suite a une reaffectation de client');

        $this->entityManager->persist($tour);
        $this->entityManager->flush();

        $this->moveVisitToTour($visit, $tour);

        return $tour;
    }

    public function hasClosureRequest(Tour $tour): bool
    {
        return $tour->getClosureRequestedAt() instanceof \DateTimeImmutable;
    }

    public function hasPendingReview(Tour $tour): bool
    {
        foreach ($this->visitRepository->findForTour($tour) as $visit) {
            if ($visit->getStatus() !== \App\Entity\Visit::STATUS_COMPLETED || $visit->getResult() === null) {
                continue;
            }

            if (!in_array($visit->getAdminReviewStatus(), [\App\Entity\Visit::REVIEW_VALIDATED, \App\Entity\Visit::REVIEW_REJECTED], true)) {
                return true;
            }
        }

        return false;
    }

    public function canBeClosedByAdmin(Tour $tour): bool
    {
        if (!$this->hasClosureRequest($tour)) {
            return false;
        }

        if ($tour->getPlannedVisits() === 0 || $tour->getCompletedVisits() < $tour->getPlannedVisits()) {
            return false;
        }

        return !$this->hasPendingReview($tour);
    }

    /**
     * @param Tour[] $tours
     *
     * @return array<int, int>
     */
    public function getCriticalClientCounts(array $tours): array
    {
        $counts = [];

        foreach ($tours as $tour) {
            $counts[$tour->getId() ?? 0] = $this->getCriticalClientCount($tour);
        }

        return $counts;
    }

    public function getCriticalClientCount(Tour $tour): int
    {
        return count(array_filter(
            $this->visitRepository->findForTour($tour),
            static fn ($visit): bool => $visit->getClient()?->getStatus() === Client::STATUS_REFUSED
        ));
    }

    /**
     * @param Tour[] $tours
     * @return Tour[]
     */
    private function hydrateProgress(array $tours): array
    {
        foreach ($tours as $tour) {
            $this->hydrateTour($tour);
        }

        return $tours;
    }

    public function getStatusOverview(): array
    {
        $tours = $this->getListing();
        $total = count($tours);

        return [
            'total' => $total,
            'programmed' => count(array_filter($tours, static fn (Tour $tour): bool => $tour->getStatus() === Tour::STATUS_PROGRAMMED)),
            'in_progress' => count(array_filter($tours, static fn (Tour $tour): bool => $tour->getStatus() === Tour::STATUS_IN_PROGRESS)),
            'completed' => count(array_filter($tours, static fn (Tour $tour): bool => $tour->getStatus() === Tour::STATUS_COMPLETED)),
            'cancelled' => count(array_filter($tours, static fn (Tour $tour): bool => $tour->getStatus() === Tour::STATUS_CANCELLED)),
            'latest' => array_slice($tours, 0, 10),
        ];
    }

    /**
     * @return Tour[]
     */
    public function getPendingClosureTours(): array
    {
        return $this->hydrateProgress($this->tourRepository->findPendingClosureRequests());
    }

    private function refreshCommercialLoad(Commercial $commercial): void
    {
        $commercial
            ->setCurrentClientsLoad($this->clientRepository->countForCommercial($commercial))
            ->setCurrentVisitsLoad($this->visitRepository->countPlannedForCommercial($commercial));

        $commercial->touch();
        $this->entityManager->persist($commercial);
    }

    private function finalizeEmptySourceTourIfNeeded(Tour $sourceTour): void
    {
        $remainingVisits = $this->visitRepository->findForTour($sourceTour);
        if ($remainingVisits !== []) {
            return;
        }

        $sourceTour
            ->setPlannedVisits(0)
            ->setCompletedVisits(0)
            ->setStatus(Tour::STATUS_COMPLETED)
            ->setClosureRequestedAt(null)
            ->setArchivedAt(new \DateTimeImmutable())
            ->setRouteSummary('Tournee cloturee automatiquement apres reaffectation du dernier client.')
            ->setNotes($sourceTour->getNotes() ?: 'Cloture automatique : aucun client restant dans la tournee source.');

        $this->entityManager->persist($sourceTour);
    }
}
