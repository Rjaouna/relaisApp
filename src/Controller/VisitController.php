<?php

namespace App\Controller;

use App\Entity\Visit;
use App\Service\VisitService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/visites', name: 'app_visit_')]
class VisitController extends AbstractController
{
    public function __construct(
        private readonly VisitService $visitService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('visit/index.html.twig', [
            'visits' => $this->visitService->getUpcomingVisits(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Visit $visit): Response
    {
        return $this->render('visit/show.html.twig', [
            'visit' => $visit,
        ]);
    }
}
