<?php

namespace App\Controller;

use App\Entity\Objective;
use App\Form\ObjectiveType;
use App\Service\ObjectiveCrudService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/objectifs', name: 'app_objective_')]
class ObjectiveController extends AbstractController
{
    public function __construct(
        private readonly ObjectiveCrudService $objectiveCrudService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('objective/index.html.twig', [
            'objectives' => $this->objectiveCrudService->getListing(),
        ]);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('objective/_list.html.twig', [
            'objectives' => $this->objectiveCrudService->getListing(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm($request, new Objective());
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Objective $objective): Response
    {
        return $this->handleForm($request, $objective);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Objective $objective): Response
    {
        return $this->render('objective/show.html.twig', [
            'objective' => $this->objectiveCrudService->hydrate($objective),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Objective $objective): JsonResponse
    {
        $this->objectiveCrudService->delete($objective);

        return $this->json(['success' => true]);
    }

    private function handleForm(Request $request, Objective $objective): Response
    {
        $form = $this->createForm(ObjectiveType::class, $objective);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->objectiveCrudService->save($objective);

            return $request->isXmlHttpRequest()
                ? $this->json(['success' => true])
                : $this->redirectToRoute('app_objective_show', ['id' => $objective->getId()]);
        }

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                return $this->json([
                    'success' => false,
                    'form' => $this->renderView('objective/_form.html.twig', [
                        'form' => $form,
                        'objective' => $objective,
                    ]),
                ]);
            }

            return new Response($this->renderView('objective/_form.html.twig', [
                'form' => $form,
                'objective' => $objective,
            ]));
        }

        return $this->render('objective/form_page.html.twig', [
            'form' => $form,
            'objective' => $objective,
        ]);
    }
}
