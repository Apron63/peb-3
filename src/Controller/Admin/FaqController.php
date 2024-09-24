<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Entity\Faq;
use App\Form\Admin\FaqType;
use App\Repository\FaqRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class FaqController extends MobileController
{
    public function __construct(
        private readonly FaqRepository $faqRepository,
    ) {}

    #[Route('/admin/faq/', name: 'admin_faq_list')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function index(): Response
    {
        return $this->mobileRender('admin/faq/index.html.twig', [
            'faqList' => $this->faqRepository->findAll(),
        ]);
    }

    #[Route('/admin/faq/create/', name: 'admin_faq_create')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function create(Request $request): Response
    {
        $faq = new Faq();
        $form = $this->createForm(FaqType::class, $faq);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->faqRepository->save($faq, true);

            $this->addFlash('success', 'Вопрос добавлен');

            return $this->redirectToRoute('admin_faq_list');
        }

        return $this->render('admin/faq/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/faq/edit/{id<\d+>}/', name: 'admin_faq_edit')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function edit(Faq $faq, Request $request): Response
    {
        $form = $this->createForm(FaqType::class, $faq);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->faqRepository->save($faq, true);

            $this->addFlash('success', 'Вопрос добавлен');

            return $this->redirectToRoute('admin_faq_list');
        }

        return $this->render('admin/faq/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/faq/delete/{id<\d+>}/', name: 'admin_faq_delete')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function delete(Faq $faq): Response
    {
        $this->faqRepository->remove($faq, true);

        $this->addFlash('success', 'Вопрос удален');

        return $this->redirectToRoute('admin_faq_list');
    }
}
