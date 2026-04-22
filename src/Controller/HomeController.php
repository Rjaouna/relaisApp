<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): RedirectResponse
    {
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_DIRECTION')) {
            return $this->redirectToRoute('app_dashboard');
        }

        if ($this->isGranted('ROLE_COMMERCIAL')) {
            return $this->redirectToRoute('app_tour_index');
        }

        if ($this->isGranted('ROLE_ACHAT')) {
            return $this->redirectToRoute('app_supplier_index');
        }

        if ($this->isGranted('ROLE_LOGISTIQUE')) {
            return $this->redirectToRoute('app_delivery_index');
        }

        return $this->redirectToRoute('app_login');
    }
}
