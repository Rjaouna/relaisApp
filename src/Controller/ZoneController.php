<?php

namespace App\Controller;

use App\Entity\Zone;
use App\Form\ZoneType;
use App\Service\ZoneCrudService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/zones', name: 'app_zone_')]
class ZoneController extends AbstractController
{
    public function __construct(
        private readonly ZoneCrudService $zoneCrudService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('zone/index.html.twig', [
            'zones' => $this->zoneCrudService->getListing(),
        ]);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('zone/_list.html.twig', [
            'zones' => $this->zoneCrudService->getListing(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm($request, new Zone());
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Zone $zone): Response
    {
        return $this->handleForm($request, $zone);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Zone $zone): Response
    {
        return $this->render('zone/show.html.twig', [
            'zone' => $zone,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Zone $zone): JsonResponse
    {
        $this->zoneCrudService->delete($zone);

        return $this->json(['success' => true]);
    }

    private function handleForm(Request $request, Zone $zone): Response
    {
        $form = $this->createForm(ZoneType::class, $zone);
        $form->handleRequest($request);
        $picker = $request->query->getBoolean('picker');

        if ($form->isSubmitted() && $form->isValid()) {
            $this->zoneCrudService->save($zone);

            if ($request->isXmlHttpRequest()) {
                if ($picker) {
                    return $this->json([
                        'success' => true,
                        'zone' => [
                            'id' => $zone->getId(),
                            'name' => $zone->getName(),
                            'label' => sprintf('%s - %s', $zone->getCity()?->getName() ?? 'Ville', $zone->getName() ?? 'Zone'),
                        ],
                    ]);
                }

                return $this->json(['success' => true]);
            }

            return $this->redirectToRoute('app_zone_show', ['id' => $zone->getId()]);
        }

        $template = $picker ? 'zone/_picker_form.html.twig' : 'zone/_form.html.twig';

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                return $this->json([
                    'success' => false,
                    'form' => $this->renderView($template, [
                        'form' => $form,
                        'zone' => $zone,
                    ]),
                ]);
            }

            return new Response($this->renderView($template, [
                'form' => $form,
                'zone' => $zone,
            ]));
        }

        return $this->render('zone/form_page.html.twig', [
            'form' => $form,
            'zone' => $zone,
        ]);
    }
}
