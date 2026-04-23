<?php

namespace App\Controller;

use App\Entity\Commercial;
use App\Entity\Objective;
use App\Entity\User;
use App\Service\ObjectiveCrudService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
        if ($this->isGranted('ROLE_COMMERCIAL') && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION')) {
            $commercial = $this->getCurrentCommercial();

            if (!$commercial instanceof Commercial) {
                throw $this->createAccessDeniedException('Aucun profil commercial n est rattache a ce compte.');
            }

            $objectives = $this->objectiveCrudService->getListingForCommercial($commercial);

            if ($objectives !== []) {
                return $this->redirectToRoute('app_objective_show', [
                    'id' => $objectives[0]->getId(),
                ]);
            }

            return $this->render('objective/index.html.twig', [
                'objectives' => [],
            ]);
        }

        return $this->render('objective/index.html.twig', [
            'objectives' => $this->objectiveCrudService->getListing(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Objective $objective): Response
    {
        if ($this->isGranted('ROLE_COMMERCIAL') && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION')) {
            $commercial = $this->getCurrentCommercial();

            if (
                !$commercial instanceof Commercial
                || !$this->objectiveCrudService->belongsToCommercial($objective, $commercial)
            ) {
                throw $this->createAccessDeniedException('Cet objectif ne t est pas accessible.');
            }
        }

        return $this->render('objective/show.html.twig', [
            'objective' => $this->objectiveCrudService->hydrate($objective),
            'insights' => $this->objectiveCrudService->getInsights($objective),
        ]);
    }

    private function getCurrentCommercial(): ?Commercial
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return null;
        }

        return $user->getCommercial();
    }
}
