<?php

namespace App\Controller;

use App\Entity\Market;
use App\Form\MarketType;
use App\Service\DecisionSupportService;
use App\Service\MarketCrudService;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/marches', name: 'app_market_')]
class MarketController extends AbstractController
{
    public function __construct(
        private readonly MarketCrudService $marketCrudService,
        private readonly DecisionSupportService $decisionSupportService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('market/index.html.twig', [
            'markets' => $this->marketCrudService->getListing(),
        ]);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('market/_list.html.twig', [
            'markets' => $this->marketCrudService->getListing(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm($request, new Market());
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Market $market): Response
    {
        return $this->handleForm($request, $market);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Market $market): Response
    {
        return $this->render('market/show.html.twig', [
            'market' => $market,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Market $market): JsonResponse
    {
        $this->marketCrudService->delete($market);

        return $this->json(['success' => true]);
    }

    #[Route('/recalculate', name: 'recalculate', methods: ['POST'])]
    public function recalculate(): RedirectResponse
    {
        $count = $this->decisionSupportService->rebuildMarketInsights();
        $this->addFlash('success', sprintf('%d marche(s) recalcule(s).', $count));

        return $this->redirectToRoute('app_market_index');
    }

    private function handleForm(Request $request, Market $market): Response
    {
        $form = $this->createForm(MarketType::class, $market);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->validateUniqueCity($form, $market);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->marketCrudService->save($market);
            $this->decisionSupportService->rebuildMarketInsights();

            return $request->isXmlHttpRequest()
                ? $this->json(['success' => true])
                : $this->redirectToRoute('app_market_show', ['id' => $market->getId()]);
        }

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                return $this->json([
                    'success' => false,
                    'form' => $this->renderView('market/_form.html.twig', [
                        'form' => $form,
                        'market' => $market,
                    ]),
                ]);
            }

            return new Response($this->renderView('market/_form.html.twig', [
                'form' => $form,
                'market' => $market,
            ]));
        }

        return $this->render('market/form_page.html.twig', [
            'form' => $form,
            'market' => $market,
        ]);
    }

    private function validateUniqueCity(FormInterface $form, Market $market): void
    {
        $city = trim((string) $market->getCity());
        if ($city === '') {
            return;
        }

        $existing = $this->marketCrudService->findOneByCity($city);
        if ($existing instanceof Market && $existing->getId() !== $market->getId()) {
            $form->get('city')->addError(new FormError('Une analyse existe deja pour cette ville. Utilise plutot le bouton Recalculer.'));
        }
    }
}
