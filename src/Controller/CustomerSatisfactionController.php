<?php

namespace App\Controller;

use App\Entity\CustomerSatisfaction;
use App\Form\CustomerSatisfactionType;
use App\Service\CustomerSatisfactionCrudService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/satisfaction-clients', name: 'app_customer_satisfaction_')]
class CustomerSatisfactionController extends AbstractController
{
    public function __construct(
        private readonly CustomerSatisfactionCrudService $crudService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('customer_satisfaction/index.html.twig', [
            'satisfactions' => $this->crudService->getListing(),
        ]);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('customer_satisfaction/_list.html.twig', [
            'satisfactions' => $this->crudService->getListing(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm($request, new CustomerSatisfaction());
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CustomerSatisfaction $satisfaction): Response
    {
        return $this->handleForm($request, $satisfaction);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(CustomerSatisfaction $satisfaction): Response
    {
        return $this->render('customer_satisfaction/show.html.twig', [
            'satisfaction' => $satisfaction,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(CustomerSatisfaction $satisfaction): JsonResponse
    {
        $this->crudService->delete($satisfaction);

        return $this->json(['success' => true]);
    }

    private function handleForm(Request $request, CustomerSatisfaction $satisfaction): Response
    {
        $form = $this->createForm(CustomerSatisfactionType::class, $satisfaction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->crudService->save($satisfaction);

            return $request->isXmlHttpRequest()
                ? $this->json(['success' => true])
                : $this->redirectToRoute('app_customer_satisfaction_show', ['id' => $satisfaction->getId()]);
        }

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                return $this->json([
                    'success' => false,
                    'form' => $this->renderView('customer_satisfaction/_form.html.twig', [
                        'form' => $form,
                        'satisfaction' => $satisfaction,
                    ]),
                ]);
            }

            return new Response($this->renderView('customer_satisfaction/_form.html.twig', [
                'form' => $form,
                'satisfaction' => $satisfaction,
            ]));
        }

        return $this->render('shared/form_page.html.twig', [
            'form_partial' => 'customer_satisfaction/_form.html.twig',
            'form' => $form,
            'entity' => $satisfaction,
        ]);
    }
}
