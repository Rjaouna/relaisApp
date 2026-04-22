<?php

namespace App\Controller;

use App\Entity\City;
use App\Form\CityType;
use App\Service\CityCrudService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/villes', name: 'app_city_')]
class CityController extends AbstractController
{
    public function __construct(
        private readonly CityCrudService $cityCrudService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('city/index.html.twig', [
            'cities' => $this->cityCrudService->getListing(),
        ]);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('city/_list.html.twig', [
            'cities' => $this->cityCrudService->getListing(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm($request, new City());
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, City $city): Response
    {
        return $this->handleForm($request, $city);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(City $city): Response
    {
        return $this->render('city/show.html.twig', [
            'city' => $city,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(City $city): JsonResponse
    {
        $this->cityCrudService->delete($city);

        return $this->json(['success' => true]);
    }

    private function handleForm(Request $request, City $city): Response
    {
        $form = $this->createForm(CityType::class, $city);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->cityCrudService->save($city);

            if ($request->isXmlHttpRequest()) {
                if ($request->query->getBoolean('picker')) {
                    return $this->json([
                        'success' => true,
                        'city' => [
                            'id' => $city->getId(),
                            'name' => $city->getName(),
                        ],
                    ]);
                }

                return $this->json(['success' => true]);
            }

            return $this->redirectToRoute('app_city_show', ['id' => $city->getId()]);
        }

        $template = $request->query->getBoolean('picker') ? 'city/_picker_form.html.twig' : 'city/_form.html.twig';

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                return $this->json([
                    'success' => false,
                    'form' => $this->renderView($template, [
                        'form' => $form,
                        'city' => $city,
                    ]),
                ]);
            }

            return new Response($this->renderView($template, [
                'form' => $form,
                'city' => $city,
            ]));
        }

        return $this->render('city/form_page.html.twig', [
            'form' => $form,
            'city' => $city,
        ]);
    }
}
