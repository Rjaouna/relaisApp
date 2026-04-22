<?php

namespace App\Controller;

use App\Service\StatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/stats', name: 'app_stats')]
class StatsController extends AbstractController
{
    public function __construct(
        private readonly StatsService $statsService,
    ) {
    }

    #[Route('', name: '_index', methods: ['GET'])]
    public function index(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION')) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('stats/index.html.twig', [
            'stats' => $this->statsService->buildOverview(),
        ]);
    }
}
