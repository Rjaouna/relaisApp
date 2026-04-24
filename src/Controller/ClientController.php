<?php

namespace App\Controller;

use App\Entity\Client;
use App\Form\ClientType;
use App\Service\ClientGeocodingService;
use App\Service\ClientMapService;
use App\Service\ClientCrudService;
use App\Service\ClientImportService;
use App\Service\DecisionSupportService;
use App\Service\VisitCrudService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/clients', name: 'app_client_')]
class ClientController extends AbstractController
{
    public function __construct(
        private readonly ClientCrudService $clientCrudService,
        private readonly ClientImportService $clientImportService,
        private readonly DecisionSupportService $decisionSupportService,
        private readonly VisitCrudService $visitCrudService,
        private readonly ClientMapService $clientMapService,
        private readonly ClientGeocodingService $clientGeocodingService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $groupedClients = $this->clientCrudService->getGroupedListings();

        return $this->render('client/index.html.twig', [
            'groupedClients' => $groupedClients,
            'counters' => $this->clientCrudService->getCounters(),
        ]);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('client/_sections.html.twig', [
            'groupedClients' => $this->clientCrudService->getGroupedListings(),
            'counters' => $this->clientCrudService->getCounters(),
        ]);
    }

    #[Route('/carte', name: 'map', methods: ['GET'])]
    public function map(): Response
    {
        return $this->render('client/map.html.twig', [
            'mapConfig' => $this->clientMapService->getPageConfig(),
            'initialPayload' => $this->clientMapService->buildMapPayload(),
        ]);
    }

    #[Route('/carte/data', name: 'map_data', methods: ['GET'])]
    public function mapData(Request $request): JsonResponse
    {
        return $this->json($this->clientMapService->buildMapPayload($request->query->all()));
    }

    #[Route('/carte/geocode-missing', name: 'map_geocode_missing', methods: ['POST'])]
    public function mapGeocodeMissing(): JsonResponse
    {
        $missingClients = $this->clientMapService->getClientsWithoutCoordinates();
        if ($missingClients === []) {
            return $this->json([
                'success' => false,
                'message' => 'Tous les clients ont deja des coordonnees exploitables.',
            ], Response::HTTP_CONFLICT);
        }

        $result = $this->clientGeocodingService->geocodeMissingClients($missingClients);

        return $this->json([
            'success' => true,
            'message' => sprintf(
                '%d client(s) ont ete geolocalise(s). %d client(s) restent sans coordonnees exploitables.',
                $result['updated'],
                $result['skipped']
            ),
            'summary' => $result,
        ]);
    }

    #[Route('/{id}/carte/planifier-visite', name: 'map_plan_visit', methods: ['POST'])]
    public function mapPlanVisit(Request $request, Client $client): JsonResponse
    {
        $result = $this->visitCrudService->createBatch([$client->getId() ?? 0]);
        $created = (int) ($result['created'] ?? 0);

        if ($created < 1) {
            return $this->json([
                'success' => false,
                'message' => 'Impossible de creer une nouvelle visite pour ce client tant qu une visite ouverte existe deja.',
            ], Response::HTTP_CONFLICT);
        }

        return $this->json([
            'success' => true,
            'message' => 'La visite a ete preparee pour ce client.',
        ]);
    }

    #[Route('/refuses', name: 'refused_index', methods: ['GET'])]
    public function refusedIndex(): Response
    {
        return $this->render('client/refused_index.html.twig', [
            'clients' => $this->clientCrudService->getGroupedListings()['refused'],
            'counters' => $this->clientCrudService->getCounters(),
        ]);
    }

    #[Route('/refuses/{id}', name: 'refused_show', methods: ['GET'])]
    public function refusedShow(Client $client): Response
    {
        if ($client->getStatus() !== Client::STATUS_REFUSED) {
            return $this->redirectToRoute('app_client_show', [
                'id' => $client->getId(),
            ]);
        }

        return $this->render('client/show.html.twig', [
            'client' => $client,
            'read_only' => true,
            'back_route' => 'app_client_refused_index',
        ]);
    }

    #[Route('/import', name: 'import_form', methods: ['GET'])]
    public function importForm(): Response
    {
        return new Response($this->renderView('client/_import_form.html.twig'));
    }

    #[Route('/import/prepare', name: 'import_prepare', methods: ['POST'])]
    public function importPrepare(Request $request): JsonResponse
    {
        $uploadedFile = $request->files->get('import_file');

        if ($uploadedFile === null) {
            return $this->json([
                'success' => false,
                'form' => $this->renderView('client/_import_form.html.twig', [
                    'error' => 'Selectionne un fichier Excel avant de lancer l import.',
                ]),
            ]);
        }

        try {
            $payload = $this->clientImportService->prepareImport($uploadedFile);

            return $this->json([
                'success' => true,
                'token' => $payload['token'],
                'total' => $payload['total'],
                'batchSize' => $payload['batchSize'],
            ]);
        } catch (\Throwable $exception) {
            return $this->json([
                'success' => false,
                'form' => $this->renderView('client/_import_form.html.twig', [
                    'error' => $exception->getMessage(),
                ]),
            ]);
        }
    }

    #[Route('/import/{token}/process', name: 'import_process', methods: ['POST'])]
    public function importProcess(Request $request, string $token): JsonResponse
    {
        try {
            $payload = $request->getContent() !== '' ? $request->toArray() : [];
            $offset = max(0, (int) ($payload['offset'] ?? 0));
            $limit = max(1, min(25, (int) ($payload['limit'] ?? 10)));

            return $this->json($this->clientImportService->processBatch($token, $offset, $limit));
        } catch (\Throwable $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm($request, new Client());
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Client $client): Response
    {
        $blocked = $this->denyRefusedClientModification($request, $client);
        if ($blocked instanceof Response) {
            return $blocked;
        }

        return $this->handleForm($request, $client);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Client $client): Response
    {
        return $this->render('client/show.html.twig', [
            'client' => $client,
            'read_only' => false,
            'back_route' => 'app_client_index',
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Client $client): JsonResponse
    {
        if ($client->getStatus() === Client::STATUS_REFUSED) {
            return $this->json([
                'success' => false,
                'message' => 'Les clients refuses sont disponibles en lecture seule uniquement.',
            ], Response::HTTP_FORBIDDEN);
        }

        $this->clientCrudService->delete($client);

        return $this->json(['success' => true]);
    }

    #[Route('/auto-assign', name: 'auto_assign', methods: ['POST'])]
    public function autoAssign(): RedirectResponse
    {
        $count = $this->decisionSupportService->autoAssignClients();
        $this->addFlash('success', sprintf('%d client(s) affecte(s).', $count));

        return $this->redirectToRoute('app_client_index');
    }

    private function handleForm(Request $request, Client $client): Response
    {
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->clientCrudService->save($client);

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                ]);
            }

            return $this->redirectToRoute('app_client_show', [
                'id' => $client->getId(),
            ]);
        }

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                return $this->json([
                    'success' => false,
                    'form' => $this->renderView('client/_form.html.twig', [
                        'form' => $form,
                        'client' => $client,
                    ]),
                ]);
            }

            return new Response($this->renderView('client/_form.html.twig', [
                'form' => $form,
                'client' => $client,
            ]));
        }

        return $this->render('client/form_page.html.twig', [
            'form' => $form,
            'client' => $client,
        ]);
    }

    private function denyRefusedClientModification(Request $request, Client $client): ?Response
    {
        if ($client->getStatus() !== Client::STATUS_REFUSED) {
            return null;
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => false,
                'message' => 'Les clients refuses sont disponibles en lecture seule uniquement.',
            ], Response::HTTP_FORBIDDEN);
        }

        $this->addFlash('warning', 'Les clients refuses sont disponibles en lecture seule uniquement.');

        return $this->redirectToRoute('app_client_refused_show', [
            'id' => $client->getId(),
        ]);
    }
}
