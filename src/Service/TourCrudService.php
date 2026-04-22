<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\Commercial;
use App\Entity\Tour;
use App\Repository\TourRepository;
use App\Repository\VisitRepository;
use Doctrine\ORM\EntityManagerInterface;

class TourCrudService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TourRepository $tourRepository,
        private readonly VisitRepository $visitRepository,
    ) {
    }

    public function getListing(): array
    {
        return $this->hydrateProgress($this->tourRepository->findBy([], ['scheduledFor' => 'DESC']));
    }

    public function save(Tour $tour): void
    {
        $this->entityManager->persist($tour);
        $this->entityManager->flush();
    }

    public function close(Tour $tour): bool
    {
        $this->hydrateTour($tour);

        if ($tour->getPlannedVisits() === 0 || $tour->getCompletedVisits() < $tour->getPlannedVisits()) {
            return false;
        }

        $tour->setStatus(Tour::STATUS_COMPLETED);
        $this->entityManager->persist($tour);
        $this->entityManager->flush();

        return true;
    }

    public function delete(Tour $tour): void
    {
        $this->entityManager->remove($tour);
        $this->entityManager->flush();
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
}
