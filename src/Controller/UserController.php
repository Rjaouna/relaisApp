<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Service\UserCrudService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/utilisateurs', name: 'app_user_')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly UserCrudService $userCrudService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $this->userCrudService->getListing(),
        ]);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('user/_list.html.twig', [
            'users' => $this->userCrudService->getListing(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm($request, new User(), false);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user): Response
    {
        return $this->handleForm($request, $user, true);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(User $user): JsonResponse
    {
        if ($this->getUser() instanceof User && $this->getUser()->getId() === $user->getId()) {
            return $this->json([
                'success' => false,
                'message' => 'Tu ne peux pas supprimer ton propre compte.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->userCrudService->delete($user);

        return $this->json(['success' => true]);
    }

    private function handleForm(Request $request, User $user, bool $isEdit): Response
    {
        $form = $this->createForm(UserType::class, $user, ['is_edit' => $isEdit]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $roles = $user->getRoles();
            if ($roles === [] || $roles === ['ROLE_USER']) {
                $form->get('roles')->addError(new FormError('Selectionne au moins un role metier.'));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = (string) $form->get('plainPassword')->getData();
            $this->userCrudService->save($user, $plainPassword);

            return $request->isXmlHttpRequest()
                ? $this->json(['success' => true])
                : $this->redirectToRoute('app_user_show', ['id' => $user->getId()]);
        }

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                return $this->json([
                    'success' => false,
                    'form' => $this->renderView('user/_form.html.twig', [
                        'form' => $form,
                        'userEntity' => $user,
                    ]),
                ]);
            }

            return new Response($this->renderView('user/_form.html.twig', [
                'form' => $form,
                'userEntity' => $user,
            ]));
        }

        return $this->render('user/form_page.html.twig', [
            'form' => $form,
            'userEntity' => $user,
        ]);
    }
}
