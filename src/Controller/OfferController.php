<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Form\OfferType;
use App\Service\OfferCrudService;
use App\Service\OfferService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/offres', name: 'app_offer_')]
class OfferController extends AbstractController
{
    public function __construct(
        private readonly OfferService $offerService,
        private readonly OfferCrudService $offerCrudService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('offer/index.html.twig', [
            'offers' => $this->offerCrudService->getListing(),
        ]);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('offer/_list.html.twig', [
            'offers' => $this->offerCrudService->getListing(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm($request, new Offer());
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Offer $offer): Response
    {
        return $this->handleForm($request, $offer);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Offer $offer): Response
    {
        return $this->render('offer/show.html.twig', [
            'offer' => $offer,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Offer $offer): JsonResponse
    {
        $this->offerCrudService->delete($offer);

        return $this->json(['success' => true]);
    }

    private function handleForm(Request $request, Offer $offer): Response
    {
        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->offerCrudService->save($offer);

            return $request->isXmlHttpRequest()
                ? $this->json(['success' => true])
                : $this->redirectToRoute('app_offer_show', ['id' => $offer->getId()]);
        }

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                return $this->json([
                    'success' => false,
                    'form' => $this->renderView('offer/_form.html.twig', [
                        'form' => $form,
                        'offer' => $offer,
                    ]),
                ]);
            }

            return new Response($this->renderView('offer/_form.html.twig', [
                'form' => $form,
                'offer' => $offer,
            ]));
        }

        return $this->render('offer/form_page.html.twig', [
            'form' => $form,
            'offer' => $offer,
        ]);
    }
}
