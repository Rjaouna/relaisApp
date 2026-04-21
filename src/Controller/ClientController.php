<?php

namespace App\Controller;

use App\Entity\Client;
use App\Form\ClientType;
use App\Service\ClientCrudService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/clients', name: 'app_client_')]
class ClientController extends AbstractController
{
    public function __construct(
        private readonly ClientCrudService $clientCrudService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('client/index.html.twig', [
            'clients' => $this->clientCrudService->getListing(),
            'counters' => $this->clientCrudService->getCounters(),
        ]);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('client/_list.html.twig', [
            'clients' => $this->clientCrudService->getListing(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm($request, new Client());
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Client $client): Response
    {
        return $this->handleForm($request, $client);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Client $client): Response
    {
        return $this->render('client/show.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Client $client): JsonResponse
    {
        $this->clientCrudService->delete($client);

        return $this->json(['success' => true]);
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
}
