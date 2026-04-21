<?php

namespace App\Service;

use App\Repository\VisitRepository;

class VisitService
{
    public function __construct(
        private readonly VisitRepository $visitRepository,
    ) {
    }

    public function getUpcomingVisits(): array
    {
        return $this->visitRepository->findUpcoming();
    }

    public function getDashboardStats(): array
    {
        $startOfMonth = new \DateTimeImmutable('first day of this month midnight');

        return [
            'completed_this_month' => $this->visitRepository->countCompletedThisMonth($startOfMonth),
            'planned' => $this->visitRepository->count([]),
        ];
    }
}
