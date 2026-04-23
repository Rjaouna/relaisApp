<?php

namespace App\Controller;

use App\Repository\CityRepository;
use App\Repository\ReferenceOptionRepository;
use App\Repository\UserRepository;
use App\Repository\ZoneRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    ): Response {
        return $this->render('settings/index.html.twig', [
            'citiesCount' => $cityRepository->count([]),
            'zonesCount' => $zoneRepository->count([]),
            'choicesCount' => $referenceOptionRepository->count([]),
            'usersCount' => $userRepository->count([]),
        ]);
    }
}
