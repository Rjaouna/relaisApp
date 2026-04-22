<?php

namespace App\Controller;

use App\Service\DecisionSupportService;
use App\Service\DashboardService;
use App\Service\OfferService;
use App\Service\VisitService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard', name: 'app_dashboard')]
class DashboardController extends AbstractController
{
    public function __construct(
        private readonly DashboardService $dashboardService,
        private readonly VisitService $visitService,
        private readonly OfferService $offerService,
        private readonly DecisionSupportService $decisionSupportService,
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

    #[Route('/assign-clients', name: '_assign_clients', methods: ['POST'])]
    public function assignClients(): RedirectResponse
    {
        $count = $this->decisionSupportService->autoAssignClients();
        $this->addFlash('success', sprintf('%d client(s) affecte(s) automatiquement.', $count));

        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/rebuild-markets', name: '_rebuild_markets', methods: ['POST'])]
    public function rebuildMarkets(): RedirectResponse
    {
        $count = $this->decisionSupportService->rebuildMarketInsights();
        $this->addFlash('success', sprintf('%d zone(s) de marche recalculee(s).', $count));

        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/generate-tours', name: '_generate_tours', methods: ['POST'])]
    public function generateTours(): RedirectResponse
    {
        $count = $this->decisionSupportService->generateToursFromVisits();
        $this->addFlash('success', sprintf('%d tournee(s) generee(s) automatiquement.', $count));

        return $this->redirectToRoute('app_dashboard');
    }
}
