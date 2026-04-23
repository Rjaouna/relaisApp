<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Service\AppointmentCrudService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/rdv', name: 'app_appointment_')]
class AppointmentController extends AbstractController
{
    public function __construct(
        private readonly AppointmentCrudService $appointmentCrudService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        if ($this->isGranted('ROLE_COMMERCIAL') && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION')) {
            return $this->render('appointment/index.html.twig', [
                'appointments' => $this->appointmentCrudService->getListingForUser($this->getUser()),
            ]);
        }

        return $this->render('appointment/index.html.twig', [
            'appointments' => $this->appointmentCrudService->getListing(),
        ]);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        if ($this->isGranted('ROLE_COMMERCIAL') && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION')) {
            return $this->render('appointment/_list.html.twig', [
                'appointments' => $this->appointmentCrudService->getListingForUser($this->getUser()),
            ]);
        }

        return $this->render('appointment/_list.html.twig', [
            'appointments' => $this->appointmentCrudService->getListing(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Appointment $appointment): Response
    {
        if ($this->isGranted('ROLE_COMMERCIAL') && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION')) {
            if (!$this->appointmentCrudService->canAccessAppointment($this->getUser(), $appointment)) {
                throw $this->createAccessDeniedException();
            }
        }

        return $this->render('appointment/show.html.twig', [
            'appointment' => $appointment,
        ]);
    }

    #[Route('/{id}/cancel', name: 'cancel', methods: ['GET', 'POST'])]
    public function cancel(Request $request, Appointment $appointment): Response
    {
        $this->denyIfCommercialCannotAccess($appointment);

        if ($request->isMethod('POST')) {
            $this->appointmentCrudService->cancel($appointment, $request->request->get('note'));

            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => true]);
            }

            return $this->redirectToRoute('app_appointment_index');
        }

        if ($request->isXmlHttpRequest()) {
            return new Response($this->renderView('appointment/_cancel_form.html.twig', [
                'appointment' => $appointment,
                'error' => null,
            ]));
        }

        return $this->render('appointment/show.html.twig', ['appointment' => $appointment]);
    }

    #[Route('/{id}/reschedule', name: 'reschedule', methods: ['GET', 'POST'])]
    public function reschedule(Request $request, Appointment $appointment): Response
    {
        $this->denyIfCommercialCannotAccess($appointment);
        $error = null;

        if ($request->isMethod('POST')) {
            try {
                $scheduledAt = new \DateTimeImmutable((string) $request->request->get('scheduled_at'));
                $this->appointmentCrudService->reschedule($appointment, $scheduledAt, $request->request->get('note'));

                if ($request->isXmlHttpRequest()) {
                    return $this->json(['success' => true]);
                }

                return $this->redirectToRoute('app_appointment_index');
            } catch (\Throwable $exception) {
                $error = $exception->getMessage();

                if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'success' => false,
                        'form' => $this->renderView('appointment/_reschedule_form.html.twig', [
                            'appointment' => $appointment,
                            'error' => $error,
                        ]),
                    ]);
                }
            }
        }

        if ($request->isXmlHttpRequest()) {
            return new Response($this->renderView('appointment/_reschedule_form.html.twig', [
                'appointment' => $appointment,
                'error' => $error,
            ]));
        }

        return $this->render('appointment/show.html.twig', ['appointment' => $appointment]);
    }

    #[Route('/{id}/notify', name: 'notify', methods: ['POST'])]
    public function notify(Appointment $appointment): JsonResponse
    {
        $this->denyIfCommercialCannotAccess($appointment);

        try {
            $this->appointmentCrudService->notifyCommercial($appointment);
        } catch (\LogicException $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->json(['success' => true]);
    }

    private function denyIfCommercialCannotAccess(Appointment $appointment): void
    {
        if ($this->isGranted('ROLE_COMMERCIAL') && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION')) {
            if (!$this->appointmentCrudService->canAccessAppointment($this->getUser(), $appointment)) {
                throw $this->createAccessDeniedException();
            }
        }
    }
}
