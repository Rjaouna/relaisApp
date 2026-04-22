<?php

namespace App\Controller;

use App\Entity\Tour;
use App\Entity\Client;
use App\Form\TourType;
use App\Repository\CommercialRepository;
use App\Service\CommercialWorkflowService;
use App\Service\DecisionSupportService;
use App\Service\TourCrudService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tournees', name: 'app_tour_')]
class TourController extends AbstractController
{
    public function __construct(
        private readonly TourCrudService $tourCrudService,
        private readonly CommercialRepository $commercialRepository,
        private readonly CommercialWorkflowService $commercialWorkflowService,
        private readonly DecisionSupportService $decisionSupportService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        if ($this->isGranted('ROLE_COMMERCIAL') && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION')) {
            $tours = $this->commercialWorkflowService->getToursForUser($this->getUser());

            return $this->render('tour/index.html.twig', [
                'tours' => $tours,
                'tourCriticalCounts' => $this->tourCrudService->getCriticalClientCounts($tours),
            ]);
        }

        $tours = $this->tourCrudService->getListing();

        return $this->render('tour/index.html.twig', [
            'tours' => $tours,
            'tourCriticalCounts' => $this->tourCrudService->getCriticalClientCounts($tours),
            'tourGenerationStatusChoices' => Client::statusChoices(),
            'tourGenerationSelectedStatuses' => DecisionSupportService::DEFAULT_TOUR_CLIENT_STATUSES,
            'tourGenerationStatusHelp' => [
                Client::STATUS_POTENTIAL => 'Priorise la prospection pure.',
                Client::STATUS_IN_PROGRESS => 'Inclut les clients en maturation commerciale.',
                Client::STATUS_ACTIVE => 'Ajoute les clients deja confirmes.',
                Client::STATUS_REFUSED => 'Inclut les clients refuses si besoin de suivi.',
            ],
        ]);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        if ($this->isGranted('ROLE_COMMERCIAL') && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION')) {
            $tours = $this->commercialWorkflowService->getToursForUser($this->getUser());

            return $this->render('tour/_list.html.twig', [
                'tours' => $tours,
                'tourCriticalCounts' => $this->tourCrudService->getCriticalClientCounts($tours),
            ]);
        }

        $tours = $this->tourCrudService->getListing();

        return $this->render('tour/_list.html.twig', [
            'tours' => $tours,
            'tourCriticalCounts' => $this->tourCrudService->getCriticalClientCounts($tours),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyBackOfficeToCommercial();

        return $this->handleForm($request, new Tour());
    }

    #[Route('/generate', name: 'generate', methods: ['POST'])]
    public function generate(Request $request): RedirectResponse
    {
        $this->denyBackOfficeToCommercial();

        $selectedStatuses = array_values(array_filter(
            (array) $request->request->all('client_statuses'),
            static fn (mixed $status): bool => is_string($status) && $status !== ''
        ));

        $count = $this->decisionSupportService->generateToursFromVisits($selectedStatuses);
        $labels = array_flip(Client::statusChoices());
        $selectedLabels = array_map(
            static fn (string $status): string => $labels[$status] ?? $status,
            $selectedStatuses !== [] ? $selectedStatuses : DecisionSupportService::DEFAULT_TOUR_CLIENT_STATUSES
        );

        $this->addFlash(
            'success',
            $count > 0
                ? sprintf('%d tournee(s) programmee(s) automatiquement. Filtres : %s.', $count, implode(', ', $selectedLabels))
                : sprintf('Aucune nouvelle tournee a generer pour les statuts : %s.', implode(', ', $selectedLabels))
        );

        return $this->redirectToRoute('app_tour_index');
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tour $tour): Response
    {
        $this->denyBackOfficeToCommercial();

        return $this->handleForm($request, $tour);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Tour $tour): Response
    {
        if ($this->isGranted('ROLE_COMMERCIAL') && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION')) {
            if (!$this->commercialWorkflowService->canAccessTour($this->getUser(), $tour)) {
                throw $this->createAccessDeniedException();
            }
        }

        $this->tourCrudService->hydrateTour($tour);

        return $this->render('tour/show.html.twig', [
            'tour' => $tour,
            'visits' => $this->commercialWorkflowService->getVisitsForTour($tour),
            'criticalClientsCount' => $this->tourCrudService->getCriticalClientCount($tour),
        ]);
    }

    #[Route('/{id}/close', name: 'close', methods: ['POST'])]
    public function close(Tour $tour): RedirectResponse
    {
        if ($this->isGranted('ROLE_COMMERCIAL') && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION')) {
            if (!$this->commercialWorkflowService->canAccessTour($this->getUser(), $tour)) {
                throw $this->createAccessDeniedException();
            }
        }

        $closed = $this->tourCrudService->close($tour);

        $this->addFlash(
            $closed ? 'success' : 'warning',
            $closed
                ? 'La tournee a ete cloturee avec succes.'
                : 'Impossible de cloturer cette tournee tant que toutes les visites ne sont pas renseignees.'
        );

        return $this->redirectToRoute('app_tour_show', ['id' => $tour->getId()]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Tour $tour): JsonResponse
    {
        $this->denyBackOfficeToCommercial();

        $this->tourCrudService->hydrateTour($tour);
        if ($tour->getStatus() !== Tour::STATUS_COMPLETED) {
            return $this->json([
                'success' => false,
                'message' => 'Impossible de supprimer une tournee non cloturee.',
            ], Response::HTTP_FORBIDDEN);
        }

        $this->tourCrudService->delete($tour);

        return $this->json(['success' => true]);
    }

    private function handleForm(Request $request, Tour $tour): Response
    {
        $form = $this->createForm(TourType::class, $tour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->tourCrudService->save($tour);

            return $request->isXmlHttpRequest()
                ? $this->json(['success' => true])
                : $this->redirectToRoute('app_tour_show', ['id' => $tour->getId()]);
        }

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                return $this->json([
                    'success' => false,
                    'form' => $this->renderView('tour/_form.html.twig', [
                        'form' => $form,
                        'tour' => $tour,
                    ]),
                ]);
            }

            return new Response($this->renderView('tour/_form.html.twig', [
                'form' => $form,
                'tour' => $tour,
            ]));
        }

        return $this->render('tour/form_page.html.twig', [
            'form' => $form,
            'tour' => $tour,
        ]);
    }

    private function denyBackOfficeToCommercial(): void
    {
        if ($this->isGranted('ROLE_COMMERCIAL') && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION')) {
            throw $this->createAccessDeniedException();
        }
    }
}
