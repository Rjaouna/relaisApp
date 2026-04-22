<?php

namespace App\Controller;

use App\Entity\ReferenceOption;
use App\Form\ReferenceOptionType;
use App\Service\ReferenceOptionCrudService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/parametres/choix', name: 'app_reference_option_')]
class ReferenceOptionController extends AbstractController
{
    public function __construct(
        private readonly ReferenceOptionCrudService $referenceOptionCrudService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('reference_option/index.html.twig', [
            'options' => $this->referenceOptionCrudService->getListing(),
        ]);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('reference_option/_list.html.twig', [
            'options' => $this->referenceOptionCrudService->getListing(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $referenceOption = new ReferenceOption();
        $category = $request->query->get('category');
        if (is_string($category) && $category !== '') {
            $referenceOption->setCategory($category);
        }

        return $this->handleForm($request, $referenceOption);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ReferenceOption $referenceOption): Response
    {
        return $this->handleForm($request, $referenceOption);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(ReferenceOption $referenceOption): Response
    {
        return $this->render('reference_option/show.html.twig', [
            'option' => $referenceOption,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(ReferenceOption $referenceOption): JsonResponse
    {
        $this->referenceOptionCrudService->delete($referenceOption);

        return $this->json(['success' => true]);
    }

    private function handleForm(Request $request, ReferenceOption $referenceOption): Response
    {
        $picker = $request->query->getBoolean('picker');
        $lockedCategory = $picker && $request->query->has('category');
        $form = $this->createForm(ReferenceOptionType::class, $referenceOption, [
            'picker_mode' => $picker,
            'locked_category' => $lockedCategory,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->referenceOptionCrudService->save($referenceOption);

            if ($request->isXmlHttpRequest()) {
                if ($picker) {
                    return $this->json([
                        'success' => true,
                        'option' => [
                            'category' => $referenceOption->getCategory(),
                            'label' => $referenceOption->getLabel(),
                            'value' => $referenceOption->getValue(),
                        ],
                    ]);
                }

                return $this->json(['success' => true]);
            }

            return $this->redirectToRoute('app_reference_option_show', ['id' => $referenceOption->getId()]);
        }

        $template = $picker ? 'reference_option/_picker_form.html.twig' : 'reference_option/_form.html.twig';

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                return $this->json([
                    'success' => false,
                    'form' => $this->renderView($template, [
                        'form' => $form,
                        'option' => $referenceOption,
                    ]),
                ]);
            }

            return new Response($this->renderView($template, [
                'form' => $form,
                'option' => $referenceOption,
            ]));
        }

        return $this->render('reference_option/form_page.html.twig', [
            'form' => $form,
            'option' => $referenceOption,
        ]);
    }
}
