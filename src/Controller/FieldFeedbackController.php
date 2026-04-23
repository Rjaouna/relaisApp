<?php

namespace App\Controller;

use App\Entity\FieldFeedback;
use App\Form\FieldFeedbackType;
use App\Service\FieldFeedbackCrudService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/retours-terrain', name: 'app_field_feedback_')]
class FieldFeedbackController extends AbstractController
{
    public function __construct(
        private readonly FieldFeedbackCrudService $crudService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('field_feedback/index.html.twig', [
            'feedbacks' => $this->crudService->getListing(),
        ]);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('field_feedback/_list.html.twig', [
            'feedbacks' => $this->crudService->getListing(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm($request, new FieldFeedback());
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, FieldFeedback $feedback): Response
    {
        return $this->handleForm($request, $feedback);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(FieldFeedback $feedback): Response
    {
        return $this->render('field_feedback/show.html.twig', [
            'feedback' => $feedback,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(FieldFeedback $feedback): JsonResponse
    {
        $this->crudService->delete($feedback);

        return $this->json(['success' => true]);
    }

    private function handleForm(Request $request, FieldFeedback $feedback): Response
    {
        $form = $this->createForm(FieldFeedbackType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->crudService->save($feedback);

            return $request->isXmlHttpRequest()
                ? $this->json(['success' => true])
                : $this->redirectToRoute('app_field_feedback_show', ['id' => $feedback->getId()]);
        }

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                return $this->json([
                    'success' => false,
                    'form' => $this->renderView('field_feedback/_form.html.twig', [
                        'form' => $form,
                        'feedback' => $feedback,
                    ]),
                ]);
            }

            return new Response($this->renderView('field_feedback/_form.html.twig', [
                'form' => $form,
                'feedback' => $feedback,
            ]));
        }

        return $this->render('shared/form_page.html.twig', [
            'form_partial' => 'field_feedback/_form.html.twig',
            'form' => $form,
            'entity' => $feedback,
        ]);
    }
}
