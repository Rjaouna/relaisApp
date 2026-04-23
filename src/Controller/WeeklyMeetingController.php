<?php

namespace App\Controller;

use App\Entity\WeeklyMeeting;
use App\Entity\User;
use App\Form\WeeklyMeetingType;
use App\Repository\UserRepository;
use App\Service\WeeklyMeetingCrudService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reunions-commerciales', name: 'app_weekly_meeting_')]
class WeeklyMeetingController extends AbstractController
{
    public function __construct(
        private readonly WeeklyMeetingCrudService $crudService,
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('weekly_meeting/index.html.twig', [
            'meetings' => $this->crudService->getListing(),
        ]);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('weekly_meeting/_list.html.twig', [
            'meetings' => $this->crudService->getListing(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm($request, new WeeklyMeeting());
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, WeeklyMeeting $meeting): Response
    {
        return $this->handleForm($request, $meeting);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(WeeklyMeeting $meeting): Response
    {
        return $this->render('weekly_meeting/show.html.twig', [
            'meeting' => $meeting,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(WeeklyMeeting $meeting): JsonResponse
    {
        $this->crudService->delete($meeting);

        return $this->json(['success' => true]);
    }

    private function handleForm(Request $request, WeeklyMeeting $meeting): Response
    {
        $form = $this->createForm(WeeklyMeetingType::class, $meeting, [
            'meeting_attendees' => $this->userRepository->findActiveForMeetingSelection(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sentCount = $this->crudService->save($meeting, $this->getUser() instanceof User ? $this->getUser() : null);
            $this->addFlash('success', sprintf('Reunion enregistree. %d notification(s) email preparee(s).', $sentCount));

            return $request->isXmlHttpRequest()
                ? $this->json(['success' => true])
                : $this->redirectToRoute('app_weekly_meeting_show', ['id' => $meeting->getId()]);
        }

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                return $this->json([
                    'success' => false,
                    'form' => $this->renderView('weekly_meeting/_form.html.twig', [
                        'form' => $form,
                        'meeting' => $meeting,
                    ]),
                ]);
            }

            return new Response($this->renderView('weekly_meeting/_form.html.twig', [
                'form' => $form,
                'meeting' => $meeting,
            ]));
        }

        return $this->render('shared/form_page.html.twig', [
            'form_partial' => 'weekly_meeting/_form.html.twig',
            'form' => $form,
            'entity' => $meeting,
        ]);
    }
}
