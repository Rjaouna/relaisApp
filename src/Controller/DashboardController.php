<?php

namespace App\Controller;

use App\Service\DashboardService;
use App\Service\OfferService;
use App\Service\VisitService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard', name: 'app_dashboard')]
class DashboardController extends AbstractController
{
    public function __construct(
        private readonly DashboardService $dashboardService,
        private readonly VisitService $visitService,
        private readonly OfferService $offerService,
    ) {
    }

    #[Route('', name: '')]
    public function index(): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'overview' => $this->dashboardService->buildOverview(),
            'visits' => $this->visitService->getUpcomingVisits(),
            'offers' => $this->offerService->getLatestOffers(),
        ]);
    }
}
