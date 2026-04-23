<?php

namespace App\Controller;

use App\Entity\Tour;
use App\Entity\Client;
use App\Entity\Visit;
use App\Form\TourAssignType;
use App\Form\TourType;
use App\Repository\CommercialRepository;
use App\Service\CommercialWorkflowService;
use App\Service\DecisionSupportService;
use App\Service\TourGenerationWorkflowService;
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
        private readonly TourGenerationWorkflowService $tourGenerationWorkflowService,
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

        $this->tourGenerationWorkflowService->start($selectedStatuses);

        return $this->redirectToRoute('app_tour_generation_prepare');
    }

    #[Route('/generation/preparation', name: 'generation_prepare', methods: ['GET', 'POST'])]
    public function generationPrepare(Request $request): Response
    {
        $this->denyBackOfficeToCommercial();

        if ($request->isMethod('POST')) {
            $zoneStates = [];
            foreach ((array) $request->request->all('zones') as $zoneId => $zonePayload) {
                $zoneStates[(int) $zoneId] = [
                    'included' => isset($zonePayload['included']),
                    'commercialId' => isset($zonePayload['commercialId']) && $zonePayload['commercialId'] !== ''
                        ? (int) $zonePayload['commercialId']
                        : null,
                ];
            }

            $statuses = array_values(array_filter(
                (array) $request->request->all('client_statuses'),
                static fn (mixed $status): bool => is_string($status) && $status !== ''
            ));

            $this->tourGenerationWorkflowService->updatePreparation($statuses, $zoneStates);

            if ($request->request->get('action') === 'conflicts') {
                return $this->redirectToRoute('app_tour_generation_conflicts');
            }

            if ($request->request->get('action') === 'finalize') {
                return $this->redirectToRoute('app_tour_generation_finalize');
            }
        }

        return $this->render('tour/generation_prepare.html.twig', [
            'generation' => $this->tourGenerationWorkflowService->getPreparationView(),
            'tourGenerationStatusChoices' => Client::statusChoices(),
        ]);
    }

    #[Route('/generation/repartition', name: 'generation_conflicts', methods: ['GET', 'POST'])]
    public function generationConflicts(Request $request): Response
    {
        $this->denyBackOfficeToCommercial();

        if ($request->isMethod('POST')) {
            $clientAssignments = [];
            foreach ((array) $request->request->all('client_assignments') as $clientId => $commercialId) {
                if ($commercialId !== '') {
                    $clientAssignments[(int) $clientId] = (int) $commercialId;
                }
            }

            $this->tourGenerationWorkflowService->updateClientAssignments($clientAssignments);

            if (!$this->tourGenerationWorkflowService->hasConflicts()) {
                return $this->redirectToRoute('app_tour_generation_finalize');
            }
        }

        return $this->render('tour/generation_conflicts.html.twig', [
            'conflicts' => $this->tourGenerationWorkflowService->getConflictItems(),
        ]);
    }

    #[Route('/generation/finaliser', name: 'generation_finalize', methods: ['GET'])]
    public function generationFinalize(): RedirectResponse
    {
        $this->denyBackOfficeToCommercial();

        try {
            $result = $this->tourGenerationWorkflowService->finalize();
            $this->addFlash('success', sprintf('%d visite(s) ont ete placee(s) dans %d tournee(s) preparee(s).', $result['visits'], $result['tours']));
        } catch (\LogicException $exception) {
            $this->addFlash('warning', $exception->getMessage());

            return $this->redirectToRoute('app_tour_generation_prepare');
        }

        return $this->redirectToRoute('app_tour_index');
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tour $tour): Response
    {
        $this->denyBackOfficeToCommercial();
        if (!$this->tourCrudService->canEdit($tour)) {
            $this->addFlash('warning', 'Impossible de modifier une tournee deja en cours.');

            return $this->redirectToRoute('app_tour_show', ['id' => $tour->getId()]);
        }

        return $this->handleForm($request, $tour);
    }

    #[Route('/{id}/assign', name: 'assign', methods: ['GET', 'POST'])]
    public function assign(Request $request, Tour $tour): Response
    {
        $this->denyBackOfficeToCommercial();

        if (!$this->tourCrudService->canReassign($tour)) {
            if ($request->isXmlHttpRequest()) {
                return new Response($this->renderView('tour/_assign_form.html.twig', [
                    'form' => null,
                    'tour' => $tour,
                    'assignmentLocked' => true,
                ]));
            }

            $this->addFlash('warning', 'Seules les tournees programmees peuvent etre reattribuees.');

            return $this->redirectToRoute('app_tour_show', ['id' => $tour->getId()]);
        }

        $form = $this->createForm(TourAssignType::class, null, [
            'commercial_choices' => array_values(array_filter(
                $this->commercialRepository->findActiveOrdered(),
                static fn ($commercial): bool => $commercial->getId() !== $tour->getCommercial()?->getId()
            )),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var array{commercial?: \App\Entity\Commercial} $data */
                $data = $form->getData();
                $movedClients = $this->tourCrudService->reassign($tour, $data['commercial']);

                if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'success' => true,
                        'message' => sprintf('%d client(s) de la tournee ont ete reattribue(s).', $movedClients),
                    ]);
                }

                $this->addFlash('success', sprintf('Tournee reattribuee. %d client(s) ont suivi le nouveau commercial.', $movedClients));

                return $this->redirectToRoute('app_tour_show', ['id' => $tour->getId()]);
            } catch (\LogicException $exception) {
                $form->get('commercial')->addError(new \Symfony\Component\Form\FormError($exception->getMessage()));
            }
        }

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                return $this->json([
                    'success' => false,
                    'form' => $this->renderView('tour/_assign_form.html.twig', [
                        'form' => $form,
                        'tour' => $tour,
                        'assignmentLocked' => false,
                    ]),
                ]);
            }

            return new Response($this->renderView('tour/_assign_form.html.twig', [
                'form' => $form,
                'tour' => $tour,
                'assignmentLocked' => false,
            ]));
        }

        return $this->render('tour/form_page.html.twig', [
            'form' => $form,
            'tour' => $tour,
        ]);
    }

    #[Route('/{id}/visites/{visit}/move', name: 'move_visit', methods: ['GET', 'POST'])]
    public function moveVisit(Request $request, Tour $tour, Visit $visit): Response
    {
        $this->denyBackOfficeToCommercial();

        if ($visit->getTour()?->getId() !== $tour->getId()) {
            throw $this->createNotFoundException();
        }

        $targets = $this->tourCrudService->getMovableTargets($tour);
        $commercials = $this->commercialRepository->findActiveOrdered();
        $moveError = null;

        if ($request->isMethod('POST')) {
            try {
                if ($request->request->get('mode') === 'new') {
                    $commercial = $this->commercialRepository->find((int) $request->request->get('commercial_id'));
                    if (!$commercial instanceof \App\Entity\Commercial) {
                        throw new \LogicException('Choisis un commercial pour creer la nouvelle tournee.');
                    }

                    $this->tourCrudService->createNewTourForVisit($visit, $commercial);
                } else {
                    $targetTour = $this->tourCrudService->getMovableTargets($tour);
                    $targetTour = array_values(array_filter(
                        $targetTour,
                        static fn (Tour $candidate): bool => $candidate->getId() === (int) $request->request->get('target_tour_id')
                    ));

                    if ($targetTour === []) {
                        throw new \LogicException('Choisis une tournee cible valide.');
                    }

                    $this->tourCrudService->moveVisitToTour($visit, $targetTour[0]);
                }

                if ($request->isXmlHttpRequest()) {
                    return $this->json(['success' => true]);
                }

                return $this->redirectToRoute('app_tour_show', ['id' => $tour->getId()]);
            } catch (\LogicException $exception) {
                if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'success' => false,
                        'form' => $this->renderView('tour/_move_visit_form.html.twig', [
                            'tour' => $tour,
                            'visit' => $visit,
                            'targets' => $targets,
                            'commercials' => $commercials,
                            'moveError' => $exception->getMessage(),
                        ]),
                    ]);
                }

                $moveError = $exception->getMessage();
            }
        }

        if ($request->isXmlHttpRequest()) {
            return new Response($this->renderView('tour/_move_visit_form.html.twig', [
                'tour' => $tour,
                'visit' => $visit,
                'targets' => $targets,
                'commercials' => $commercials,
                'moveError' => $moveError,
            ]));
        }

        return $this->render('tour/form_page.html.twig', [
            'customContent' => $this->renderView('tour/_move_visit_form.html.twig', [
                'tour' => $tour,
                'visit' => $visit,
                'targets' => $targets,
                'commercials' => $commercials,
                'moveError' => $moveError,
            ]),
        ]);
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
        $visits = $this->commercialWorkflowService->getVisitsForTour($tour);
        $reviewSummary = [
            'pending' => count(array_filter($visits, static fn ($visit): bool => $visit->getAdminReviewStatus() === \App\Entity\Visit::REVIEW_PENDING)),
            'validated' => count(array_filter($visits, static fn ($visit): bool => $visit->getAdminReviewStatus() === \App\Entity\Visit::REVIEW_VALIDATED)),
            'rejected' => count(array_filter($visits, static fn ($visit): bool => $visit->getAdminReviewStatus() === \App\Entity\Visit::REVIEW_REJECTED)),
        ];

        return $this->render('tour/show.html.twig', [
            'tour' => $tour,
            'visits' => $visits,
            'criticalClientsCount' => $this->tourCrudService->getCriticalClientCount($tour),
            'reviewSummary' => $reviewSummary,
        ]);
    }

    #[Route('/{id}/request-close', name: 'request_close', methods: ['POST'])]
    public function requestClose(Request $request, Tour $tour): Response
    {
        if (!$this->isGranted('ROLE_COMMERCIAL') || $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_DIRECTION')) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->commercialWorkflowService->canAccessTour($this->getUser(), $tour)) {
            throw $this->createAccessDeniedException();
        }

        $requested = $this->tourCrudService->requestClosure($tour);

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => $requested,
                'message' => $requested
                    ? 'La demande de fermeture a ete envoyee a l administration pour controle.'
                    : 'Impossible de demander la fermeture tant que toutes les visites ne sont pas renseignees.',
            ], $requested ? Response::HTTP_OK : Response::HTTP_FORBIDDEN);
        }

        $this->addFlash(
            $requested ? 'success' : 'warning',
            $requested
                ? 'La demande de fermeture a ete envoyee a l administration pour controle.'
                : 'Impossible de demander la fermeture tant que toutes les visites ne sont pas renseignees.'
        );

        return $this->redirectToRoute('app_tour_show', ['id' => $tour->getId()]);
    }

    #[Route('/{id}/close', name: 'close', methods: ['POST'])]
    public function close(Request $request, Tour $tour): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION')) {
            throw $this->createAccessDeniedException();
        }

        $closed = $this->tourCrudService->close($tour);

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => $closed,
                'message' => $closed
                    ? 'La tournee a ete cloturee apres controle administratif.'
                    : 'Impossible de cloturer cette tournee avant la fin du controle admin.',
            ], $closed ? Response::HTTP_OK : Response::HTTP_FORBIDDEN);
        }

        $this->addFlash(
            $closed ? 'success' : 'warning',
            $closed
                ? 'La tournee a ete cloturee apres controle administratif.'
                : 'Impossible de cloturer cette tournee avant la fin du controle admin.'
        );

        return $this->redirectToRoute('app_tour_show', ['id' => $tour->getId()]);
    }

    #[Route('/{id}/archive', name: 'archive', methods: ['POST'])]
    public function archive(Tour $tour): JsonResponse
    {
        $this->denyBackOfficeToCommercial();

        $this->tourCrudService->hydrateTour($tour);
        if ($tour->getStatus() !== Tour::STATUS_COMPLETED) {
            return $this->json([
                'success' => false,
                'message' => 'Impossible d archiver une tournee non cloturee.',
            ], Response::HTTP_FORBIDDEN);
        }

        if (!$this->tourCrudService->archive($tour)) {
            return $this->json([
                'success' => false,
                'message' => 'Cette tournee est deja archivee.',
            ], Response::HTTP_FORBIDDEN);
        }

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
