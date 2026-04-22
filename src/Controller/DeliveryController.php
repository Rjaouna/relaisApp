<?php

namespace App\Controller;

use App\Entity\Delivery;
use App\Form\DeliveryType;
use App\Service\DeliveryCrudService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/livraisons', name: 'app_delivery_')]
class DeliveryController extends AbstractController
{
    public function __construct(
        private readonly DeliveryCrudService $deliveryCrudService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('delivery/index.html.twig', [
            'deliveries' => $this->deliveryCrudService->getListing(),
        ]);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('delivery/_list.html.twig', [
            'deliveries' => $this->deliveryCrudService->getListing(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm($request, new Delivery());
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Delivery $delivery): Response
    {
        return $this->handleForm($request, $delivery);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Delivery $delivery): Response
    {
        return $this->render('delivery/show.html.twig', [
            'delivery' => $delivery,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Delivery $delivery): JsonResponse
    {
        $this->deliveryCrudService->delete($delivery);

        return $this->json(['success' => true]);
    }

    private function handleForm(Request $request, Delivery $delivery): Response
    {
        $form = $this->createForm(DeliveryType::class, $delivery);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->deliveryCrudService->save($delivery);

            return $request->isXmlHttpRequest()
                ? $this->json(['success' => true])
                : $this->redirectToRoute('app_delivery_show', ['id' => $delivery->getId()]);
        }

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                return $this->json([
                    'success' => false,
                    'form' => $this->renderView('delivery/_form.html.twig', [
                        'form' => $form,
                        'delivery' => $delivery,
                    ]),
                ]);
            }

            return new Response($this->renderView('delivery/_form.html.twig', [
                'form' => $form,
                'delivery' => $delivery,
            ]));
        }

        return $this->render('delivery/form_page.html.twig', [
            'form' => $form,
            'delivery' => $delivery,
        ]);
    }
}
