<?php

namespace App\Service;

class DashboardService
{
    public function __construct(
        private readonly ClientCrudService $clientCrudService,
        private readonly VisitService $visitService,
        private readonly OfferService $offerService,
        private readonly DecisionSupportService $decisionSupportService,
        private readonly TourCrudService $tourCrudService,
    ) {
    }

    public function buildOverview(): array
    {
        $clientCounters = $this->clientCrudService->getCounters();
        $visitStats = $this->visitService->getDashboardStats();
        $offerStats = $this->offerService->getDashboardStats();
        $tourOverview = $this->tourCrudService->getStatusOverview();

        return [
            'clients' => $clientCounters['total'],
            'clients_active' => $clientCounters['active'],
            'prospects' => $clientCounters['potential'],
            'visits_planned' => $visitStats['planned'],
            'visits_completed' => $visitStats['completed_this_month'],
            'offers_in_progress' => $offerStats['in_progress'],
            'revenue' => $offerStats['revenue'],
            'tour_status' => $tourOverview,
            ...$this->decisionSupportService->getExecutiveMetrics(),
        ];
    }
}
