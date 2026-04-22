<?php

namespace App\Controller;

use App\Entity\Visit;
use App\Entity\Client;
use App\Form\VisitOutcomeType;
use App\Form\VisitType;
use App\Service\CommercialWorkflowService;
use App\Service\VisitCrudService;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/visites', name: 'app_visit_')]
class VisitController extends AbstractController
{
    public function __construct(
        private readonly VisitCrudService $visitCrudService,
        private readonly CommercialWorkflowService $commercialWorkflowService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyCommercialBackOffice();

        return $this->render('visit/index.html.twig', [
            'visits' => $this->visitCrudService->getListing(),
        ]);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        $this->denyCommercialBackOffice();

        return $this->render('visit/_list.html.twig', [
            'visits' => $this->visitCrudService->getListing(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyCommercialBackOffice();

        return $this->handleForm($request, new Visit());
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Visit $visit): Response
    {
        $this->denyCommercialBackOffice();

        return $this->handleForm($request, $visit);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Visit $visit): Response
    {
        $this->denyCommercialBackOffice();

        return $this->render('visit/show.html.twig', [
            'visit' => $visit,
        ]);
    }

    #[Route('/{id}/outcome', name: 'outcome', methods: ['GET', 'POST'])]
    public function outcome(Request $request, Visit $visit): Response
    {
        if ($this->isGranted('ROLE_COMMERCIAL') && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION')) {
            if (!$this->commercialWorkflowService->canAccessVisit($this->getUser(), $visit)) {
                throw $this->createAccessDeniedException();
            }
        }

        $form = $this->createForm(VisitOutcomeType::class, $visit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->visitCrudService->save($visit);
            $this->addFlash('success', 'Resultat de visite enregistre.');

            $tourId = $request->query->get('tour');
            if ($tourId !== null) {
                return $this->redirectToRoute('app_tour_show', ['id' => $tourId]);
            }

            return $this->redirectToRoute('app_home');
        }

        return $this->render('visit/outcome.html.twig', [
            'visit' => $visit,
            'form' => $form,
            'tourId' => $request->query->get('tour'),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Visit $visit): JsonResponse
    {
        $this->denyCommercialBackOffice();

        $this->visitCrudService->delete($visit);

        return $this->json(['success' => true]);
    }

    #[Route('/client/{id}/prefill', name: 'prefill', methods: ['GET'])]
    public function prefill(Client $client, Request $request): JsonResponse
    {
        $this->denyCommercialBackOffice();

        $currentVisitId = $request->query->getInt('current');
        $mode = $request->query->get('mode', 'new');

        if ($mode !== 'edit') {
            return $this->json([
                'found' => false,
                'fields' => $this->getDefaultPrefillFields(),
            ]);
        }

        $sourceVisit = $this->visitCrudService->getLatestForClient($client, $currentVisitId > 0 ? $currentVisitId : null);

        if (!$sourceVisit instanceof Visit) {
            return $this->json([
                'found' => false,
                'fields' => $this->getDefaultPrefillFields(),
            ]);
        }

        return $this->json([
            'found' => true,
            'fields' => [
                'type' => $sourceVisit->getType() ?? 'prospection',
                'priority' => $sourceVisit->getPriority() ?? 'moyenne',
                'status' => $sourceVisit->getStatus() ?? Visit::STATUS_PLANNED,
                'result' => $sourceVisit->getResult(),
                'objective' => $sourceVisit->getObjective(),
                'report' => $sourceVisit->getReport(),
                'nextAction' => $sourceVisit->getNextAction(),
                'interestLevel' => $sourceVisit->getInterestLevel(),
            ],
        ]);
    }

    private function handleForm(Request $request, Visit $visit): Response
    {
        $originalStatus = $visit->getStatus();
        $form = $this->createForm(VisitType::class, $visit, [
            'show_status' => $visit->getId() !== null,
            'current_visit' => $visit,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->validateStatusLockedWhenResultExists($form, $visit, $originalStatus);
            $this->validateSinglePlannedVisitPerClient($form, $visit);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->visitCrudService->save($visit);
            } catch (\LogicException $exception) {
                $form->get('client')->addError(new FormError($exception->getMessage()));
            }

            if ($form->isValid()) {
                return $request->isXmlHttpRequest()
                    ? $this->json(['success' => true])
                    : $this->redirectToRoute('app_visit_show', ['id' => $visit->getId()]);
            }
        }

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                return $this->json([
                    'success' => false,
                    'form' => $this->renderView('visit/_form.html.twig', [
                        'form' => $form,
                        'visit' => $visit,
                    ]),
                ]);
            }

            return new Response($this->renderView('visit/_form.html.twig', [
                'form' => $form,
                'visit' => $visit,
            ]));
        }

        return $this->render('visit/form_page.html.twig', [
            'form' => $form,
            'visit' => $visit,
        ]);
    }

    private function denyCommercialBackOffice(): void
    {
        if ($this->isGranted('ROLE_COMMERCIAL') && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION')) {
            throw $this->createAccessDeniedException();
        }
    }

    private function validateStatusLockedWhenResultExists(FormInterface $form, Visit $visit, ?string $originalStatus): void
    {
        if ($visit->getResult() === null || $visit->getStatus() === $originalStatus) {
            return;
        }

        $message = 'Le statut ne peut plus etre modifie une fois le resultat de visite renseigne.';
        $form->get('status')->addError(new FormError($message));
    }

    private function validateSinglePlannedVisitPerClient(FormInterface $form, Visit $visit): void
    {
        $client = $visit->getClient();

        if ($client === null || $visit->getStatus() !== Visit::STATUS_PLANNED) {
            return;
        }

        if (!$this->visitCrudService->hasAnotherPlannedVisitForClient($client, $visit->getId())) {
            return;
        }

        $message = 'Impossible de mettre cette visite en prevue : ce client a deja une autre visite prevue.';
        $form->get('client')->addError(new FormError($message));
        if ($form->has('status')) {
            $form->get('status')->addError(new FormError($message));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaultPrefillFields(): array
    {
        return [
            'type' => 'prospection',
            'priority' => 'moyenne',
            'status' => Visit::STATUS_PLANNED,
            'result' => null,
            'objective' => null,
            'report' => null,
            'nextAction' => null,
            'interestLevel' => null,
        ];
    }
}
