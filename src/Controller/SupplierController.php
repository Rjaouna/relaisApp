<?php

namespace App\Controller;

use App\Entity\Supplier;
use App\Form\SupplierType;
use App\Service\SupplierCrudService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/fournisseurs', name: 'app_supplier_')]
class SupplierController extends AbstractController
{
    public function __construct(
        private readonly SupplierCrudService $supplierCrudService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('supplier/index.html.twig', [
            'suppliers' => $this->supplierCrudService->getListing(),
        ]);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('supplier/_list.html.twig', [
            'suppliers' => $this->supplierCrudService->getListing(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm($request, new Supplier());
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Supplier $supplier): Response
    {
        return $this->handleForm($request, $supplier);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Supplier $supplier): Response
    {
        return $this->render('supplier/show.html.twig', [
            'supplier' => $supplier,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Supplier $supplier): JsonResponse
    {
        $this->supplierCrudService->delete($supplier);

        return $this->json(['success' => true]);
    }

    private function handleForm(Request $request, Supplier $supplier): Response
    {
        $form = $this->createForm(SupplierType::class, $supplier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->supplierCrudService->save($supplier);

            return $request->isXmlHttpRequest()
                ? $this->json(['success' => true])
                : $this->redirectToRoute('app_supplier_show', ['id' => $supplier->getId()]);
        }

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                return $this->json([
                    'success' => false,
                    'form' => $this->renderView('supplier/_form.html.twig', [
                        'form' => $form,
                        'supplier' => $supplier,
                    ]),
                ]);
            }

            return new Response($this->renderView('supplier/_form.html.twig', [
                'form' => $form,
                'supplier' => $supplier,
            ]));
        }

        return $this->render('supplier/form_page.html.twig', [
            'form' => $form,
            'supplier' => $supplier,
        ]);
    }
}
