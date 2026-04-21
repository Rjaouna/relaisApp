<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Service\OfferService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/offres', name: 'app_offer_')]
class OfferController extends AbstractController
{
    public function __construct(
        private readonly OfferService $offerService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('offer/index.html.twig', [
            'offers' => $this->offerService->getLatestOffers(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Offer $offer): Response
    {
        return $this->render('offer/show.html.twig', [
            'offer' => $offer,
        ]);
    }
}
