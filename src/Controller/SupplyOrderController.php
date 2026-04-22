<?php

namespace App\Controller;

use App\Entity\SupplyOrder;
use App\Form\SupplyOrderType;
use App\Service\SupplyOrderCrudService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/imports', name: 'app_supply_order_')]
class SupplyOrderController extends AbstractController
{
    public function __construct(
        private readonly SupplyOrderCrudService $supplyOrderCrudService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('supply_order/index.html.twig', [
            'orders' => $this->supplyOrderCrudService->getListing(),
        ]);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('supply_order/_list.html.twig', [
            'orders' => $this->supplyOrderCrudService->getListing(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm($request, new SupplyOrder());
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SupplyOrder $supplyOrder): Response
    {
        return $this->handleForm($request, $supplyOrder);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(SupplyOrder $supplyOrder): Response
    {
        return $this->render('supply_order/show.html.twig', [
            'order' => $supplyOrder,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(SupplyOrder $supplyOrder): JsonResponse
    {
        $this->supplyOrderCrudService->delete($supplyOrder);

        return $this->json(['success' => true]);
    }

    private function handleForm(Request $request, SupplyOrder $supplyOrder): Response
    {
        $form = $this->createForm(SupplyOrderType::class, $supplyOrder);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->supplyOrderCrudService->save($supplyOrder);

            return $request->isXmlHttpRequest()
                ? $this->json(['success' => true])
                : $this->redirectToRoute('app_supply_order_show', ['id' => $supplyOrder->getId()]);
        }

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                return $this->json([
                    'success' => false,
                    'form' => $this->renderView('supply_order/_form.html.twig', [
                        'form' => $form,
                        'order' => $supplyOrder,
                    ]),
                ]);
            }

            return new Response($this->renderView('supply_order/_form.html.twig', [
                'form' => $form,
                'order' => $supplyOrder,
            ]));
        }

        return $this->render('supply_order/form_page.html.twig', [
            'form' => $form,
            'order' => $supplyOrder,
        ]);
    }
}
