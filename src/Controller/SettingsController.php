<?php

namespace App\Controller;

use App\Form\MenuVisibilityType;
use App\Service\MenuConfigurationService;
use App\Repository\CityRepository;
use App\Repository\ReferenceOptionRepository;
use App\Repository\UserRepository;
use App\Repository\ZoneRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/parametrage', name: 'app_settings_')]
class SettingsController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        CityRepository $cityRepository,
        ZoneRepository $zoneRepository,
        ReferenceOptionRepository $referenceOptionRepository,
        UserRepository $userRepository,
        MenuConfigurationService $menuConfigurationService,
    ): Response {
        return $this->render('settings/index.html.twig', [
            'citiesCount' => $cityRepository->count([]),
            'zonesCount' => $zoneRepository->count([]),
            'choicesCount' => $referenceOptionRepository->count([]),
            'usersCount' => $userRepository->count([]),
            'menuCount' => $menuConfigurationService->countEnabled(),
        ]);
    }

    #[Route('/menu', name: 'menu', methods: ['GET', 'POST'])]
    public function menu(Request $request, MenuConfigurationService $menuConfigurationService): Response
    {
        $data = $menuConfigurationService->getFormData();
        $form = $this->createForm(MenuVisibilityType::class, $data, [
            'menu_choices' => $menuConfigurationService->getMenuChoices(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $menuConfigurationService->save($data);
            $this->addFlash('success', 'La configuration du menu a ete mise a jour.');

            return $this->redirectToRoute('app_settings_menu');
        }

        return $this->render('settings/menu.html.twig', [
            'form' => $form,
            'menuItems' => $menuConfigurationService->getOrderedMenuGroups(),
        ]);
    }
}
