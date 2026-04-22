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

        $tours = $this->tourRepository->findForCommercial($commercial);
        foreach ($tours as $tour) {
            $visits = $this->visitRepository->findForTour($tour);
            $tour->setPlannedVisits(count($visits));
            $tour->setCompletedVisits(count(array_filter($visits, static fn (Visit $visit): bool => $visit->getStatus() === Visit::STATUS_COMPLETED)));
        }

        return $tours;
    }

    public function getVisitsForTour(Tour $tour): array
    {
        return $this->visitRepository->findForTour($tour);
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
        $tours = $this->tourRepository->findForCommercial($commercial);
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
}
