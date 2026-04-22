<?php

namespace App\Controller;

use App\Entity\Commercial;
use App\Form\CommercialType;
use App\Service\CommercialCrudService;
use App\Service\CommercialWorkflowService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/commerciaux', name: 'app_commercial_')]
class CommercialController extends AbstractController
{
    public function __construct(
        private readonly CommercialCrudService $commercialCrudService,
        private readonly CommercialWorkflowService $commercialWorkflowService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('commercial/index.html.twig', [
            'commercials' => $this->commercialCrudService->getListing(),
        ]);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('commercial/_list.html.twig', [
            'commercials' => $this->commercialCrudService->getListing(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm($request, new Commercial());
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Commercial $commercial): Response
    {
        return $this->handleForm($request, $commercial);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Commercial $commercial): Response
    {
        $isOwnCommercialView = $commercial->getUser()?->getId() === $this->getUser()?->getId();

        return $this->render('commercial/show.html.twig', [
            'commercial' => $commercial,
            'isOwnCommercialView' => $isOwnCommercialView,
            'showManagerMetrics' => !$isOwnCommercialView || $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_DIRECTION'),
            'operationalSummary' => $this->commercialWorkflowService->getOperationalSummary($commercial),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Commercial $commercial): JsonResponse
    {
        $this->commercialCrudService->delete($commercial);

        return $this->json(['success' => true]);
    }

    private function handleForm(Request $request, Commercial $commercial): Response
    {
        $form = $this->createForm(CommercialType::class, $commercial, [
            'current_commercial' => $commercial,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commercialCrudService->save($commercial);

            return $request->isXmlHttpRequest()
                ? $this->json(['success' => true])
                : $this->redirectToRoute('app_commercial_show', ['id' => $commercial->getId()]);
        }

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                return $this->json([
                    'success' => false,
                    'form' => $this->renderView('commercial/_form.html.twig', [
                        'form' => $form,
                        'commercial' => $commercial,
                    ]),
                ]);
            }

            return new Response($this->renderView('commercial/_form.html.twig', [
                'form' => $form,
                'commercial' => $commercial,
            ]));
        }

        return $this->render('commercial/form_page.html.twig', [
            'form' => $form,
            'commercial' => $commercial,
        ]);
    }
}
