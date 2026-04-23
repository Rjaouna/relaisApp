<?php

namespace App\Controller;

use App\Entity\SupplierConsultation;
use App\Form\SupplierConsultationType;
use App\Service\SupplierConsultationCrudService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/consultations-fournisseurs', name: 'app_supplier_consultation_')]
class SupplierConsultationController extends AbstractController
{
    public function __construct(
        private readonly SupplierConsultationCrudService $crudService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('supplier_consultation/index.html.twig', [
            'consultations' => $this->crudService->getListing(),
        ]);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('supplier_consultation/_list.html.twig', [
            'consultations' => $this->crudService->getListing(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm($request, new SupplierConsultation());
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SupplierConsultation $consultation): Response
    {
        return $this->handleForm($request, $consultation);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(SupplierConsultation $consultation): Response
    {
        return $this->render('supplier_consultation/show.html.twig', [
            'consultation' => $consultation,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(SupplierConsultation $consultation): JsonResponse
    {
        $this->crudService->delete($consultation);

        return $this->json(['success' => true]);
    }

    private function handleForm(Request $request, SupplierConsultation $consultation): Response
    {
        $form = $this->createForm(SupplierConsultationType::class, $consultation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->crudService->save($consultation);

            return $request->isXmlHttpRequest()
                ? $this->json(['success' => true])
                : $this->redirectToRoute('app_supplier_consultation_show', ['id' => $consultation->getId()]);
        }

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                return $this->json([
                    'success' => false,
                    'form' => $this->renderView('supplier_consultation/_form.html.twig', [
                        'form' => $form,
                        'consultation' => $consultation,
                    ]),
                ]);
            }

            return new Response($this->renderView('supplier_consultation/_form.html.twig', [
                'form' => $form,
                'consultation' => $consultation,
            ]));
        }

        return $this->render('shared/form_page.html.twig', [
            'form_partial' => 'supplier_consultation/_form.html.twig',
            'form' => $form,
            'entity' => $consultation,
        ]);
    }
}
