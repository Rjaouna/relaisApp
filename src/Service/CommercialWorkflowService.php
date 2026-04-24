<?php

namespace App\Service;

use App\Entity\Commercial;
use App\Entity\Client;
use App\Entity\Tour;
use App\Entity\User;
use App\Entity\Visit;
use App\Repository\ClientRepository;
use App\Repository\CommercialRepository;
use App\Repository\TourRepository;
use App\Repository\VisitRepository;

class CommercialWorkflowService
{
    public function __construct(
        private readonly CommercialRepository $commercialRepository,
        private readonly TourRepository $tourRepository,
        private readonly VisitRepository $visitRepository,
        private readonly ClientRepository $clientRepository,
    ) {
    }

    public function getCommercialForUser(?User $user): ?Commercial
    {
        if (!$user instanceof User) {
            return null;
        }

        return $this->commercialRepository->findOneBy(['user' => $user]);
    }

    public function getToursForUser(?User $user): array
    {
        $commercial = $this->getCommercialForUser($user);
        if (!$commercial instanceof Commercial) {
            return [];
        }

        $tours = $this->hydrateToursForCommercial($commercial);

        return array_values(array_filter(
            $tours,
            fn (Tour $tour): bool => !$this->isArchivedForCommercial($tour)
        ));
    }

    public function getVisitsForTour(Tour $tour): array
    {
        return $this->visitRepository->findForTour($tour);
    }

    /**
     * @return Tour[]
     */
    public function getReadyToCloseToursForUser(?User $user): array
    {
        $commercial = $this->getCommercialForUser($user);
        if (!$commercial instanceof Commercial) {
            return [];
        }

        return array_values(array_filter(
            $this->hydrateToursForCommercial($commercial),
            static fn (Tour $tour): bool => $tour->getArchivedAt() === null
                && $tour->getClosureRequestedAt() === null
                && $tour->getPlannedVisits() > 0
                && $tour->getCompletedVisits() >= $tour->getPlannedVisits()
        ));
    }

    public function canAccessTour(?User $user, Tour $tour): bool
    {
        $commercial = $this->getCommercialForUser($user);

        return $commercial instanceof Commercial && $tour->getCommercial()?->getId() === $commercial->getId();
    }

    public function canAccessVisit(?User $user, Visit $visit): bool
    {
        $commercial = $this->getCommercialForUser($user);

        return $commercial instanceof Commercial && $visit->getClient()?->getAssignedCommercial()?->getId() === $commercial->getId();
    }

    /**
     * @return array<string, mixed>
     */
    public function getOperationalSummary(Commercial $commercial): array
    {
        $tours = array_values(array_filter(
            $this->hydrateToursForCommercial($commercial),
            fn (Tour $tour): bool => !$this->isArchivedForCommercial($tour)
        ));
        $today = new \DateTimeImmutable('today');
        $tomorrow = $today->modify('+1 day');
        $nextVisit = $this->visitRepository->findNextPlannedForCommercial($commercial);

        $todayTours = array_filter($tours, static fn (Tour $tour): bool => $tour->getScheduledFor() >= $today && $tour->getScheduledFor() < $tomorrow);
        $criticalClients = array_filter(
            $this->clientRepository->findBy(['assignedCommercial' => $commercial]),
            static fn (Client $client): bool => $client->getStatus() === Client::STATUS_REFUSED
        );

        return [
            'today_tours' => count($todayTours),
            'assigned_clients' => $this->clientRepository->countForCommercial($commercial),
            'planned_visits' => $this->visitRepository->countPlannedForCommercial($commercial),
            'completed_visits' => $this->visitRepository->countCompletedForCommercial($commercial),
            'critical_clients' => count($criticalClients),
            'next_visit' => $nextVisit,
        ];
    }

    /**
     * @return Tour[]
     */
    private function hydrateToursForCommercial(Commercial $commercial): array
    {
        $tours = $this->tourRepository->findForCommercial($commercial);

        foreach ($tours as $tour) {
            $visits = $this->visitRepository->findForTour($tour);
            $tour->setPlannedVisits(count($visits));
            $tour->setCompletedVisits(count(array_filter($visits, static fn (Visit $visit): bool => $visit->getStatus() === Visit::STATUS_COMPLETED)));
        }

        return $tours;
    }

    private function isArchivedForCommercial(Tour $tour): bool
    {
        if ($tour->getArchivedAt() instanceof \DateTimeImmutable) {
            return true;
        }

        if ($tour->getStatus() !== Tour::STATUS_COMPLETED) {
            return false;
        }

        $reviewableVisits = array_values(array_filter(
            $this->visitRepository->findForTour($tour),
            static fn (Visit $visit): bool => $visit->getStatus() === Visit::STATUS_COMPLETED && $visit->getResult() !== null
        ));

        if ($reviewableVisits === []) {
            return false;
        }

        foreach ($reviewableVisits as $visit) {
            if (!in_array($visit->getAdminReviewStatus(), [Visit::REVIEW_VALIDATED, Visit::REVIEW_REJECTED], true)) {
                return false;
            }
        }

        return true;
    }
}
