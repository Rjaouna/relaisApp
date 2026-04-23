<?php

namespace App\Controller;

use App\Entity\ProductLaunchProject;
use App\Form\ProductLaunchProjectType;
use App\Service\ProductLaunchProjectCrudService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/lancements-produits', name: 'app_product_launch_project_')]
class ProductLaunchProjectController extends AbstractController
{
    public function __construct(
        private readonly ProductLaunchProjectCrudService $crudService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('product_launch_project/index.html.twig', [
            'projects' => $this->crudService->getListing(),
        ]);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('product_launch_project/_list.html.twig', [
            'projects' => $this->crudService->getListing(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm($request, new ProductLaunchProject());
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ProductLaunchProject $project): Response
    {
        return $this->handleForm($request, $project);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(ProductLaunchProject $project): Response
    {
        return $this->render('product_launch_project/show.html.twig', [
            'project' => $project,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(ProductLaunchProject $project): JsonResponse
    {
        $this->crudService->delete($project);

        return $this->json(['success' => true]);
    }

    private function handleForm(Request $request, ProductLaunchProject $project): Response
    {
        $form = $this->createForm(ProductLaunchProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->crudService->save($project);

            return $request->isXmlHttpRequest()
                ? $this->json(['success' => true])
                : $this->redirectToRoute('app_product_launch_project_show', ['id' => $project->getId()]);
        }

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                return $this->json([
                    'success' => false,
                    'form' => $this->renderView('product_launch_project/_form.html.twig', [
                        'form' => $form,
                        'project' => $project,
                    ]),
                ]);
            }

            return new Response($this->renderView('product_launch_project/_form.html.twig', [
                'form' => $form,
                'project' => $project,
            ]));
        }

        return $this->render('shared/form_page.html.twig', [
            'form_partial' => 'product_launch_project/_form.html.twig',
            'form' => $form,
            'entity' => $project,
        ]);
    }
}
