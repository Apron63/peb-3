<?php

namespace App\Controller\Admin;

use App\Entity\ModuleSection;
use App\Entity\ModuleSectionPage;
use App\Decorator\MobileController;
use App\Service\InteractiveUploadService;
use App\Form\Admin\ModuleSectionPageEditType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\ModuleSectionPageRepository;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ModuleSectionPageController extends MobileController
{
    public function __construct(
        private readonly ModuleSectionPageRepository $moduleSectionPageRepository,
        private readonly InteractiveUploadService $interactiveUploadService,
    ) {}

    #[Route('/admin/module_section_page/add/{id<\d+>}/', name: 'admin_module_section_page_add')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function adminModuleSectionPageAdd(ModuleSection $moduleSection, Request $request): Response
    {
        $moduleSectionPage = new ModuleSectionPage();
        $moduleSectionPage->setSection($moduleSection);

        $form = $this->createForm(ModuleSectionPageEditType::class, $moduleSectionPage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->moduleSectionPageRepository->save($moduleSectionPage, true);

            if (null !== $form->get('filename')->getData()) {
                $this->interactiveUploadService->fileInteractiveUpload(
                    $form->get('filename')->getData(),
                    $moduleSectionPage
                );
            }

            $this->addFlash('success', 'Шаблон для курса успешно добавлен');

            return $this->redirectToRoute('admin_module_section_edit', ['id' => $moduleSection->getId()]);
        }

        return $this->mobileRender('admin/module-section/edit.html.twig', [
            'form' => $form->createView(),
            'module' => $moduleSection->getModule(),
        ]);
    }

    #[Route('/admin/module_section_page/edit/{id<\d+>}/', name: 'admin_module_section_page_edit')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function adminModuleSectionPageEdit(ModuleSectionPage $moduleSectionPage, Request $request): Response
    {
        $form = $this->createForm(ModuleSectionPageEditType::class, $moduleSectionPage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (null !== $form->get('filename')->getData()) {
                $this->interactiveUploadService->fileInteractiveUpload(
                    $form->get('filename')->getData(),
                    $moduleSectionPage
                );

                $moduleSectionPage->setUrl(
                    $form->get('filename')->getData()->getClientOriginalName()
                );
            }

            $this->moduleSectionPageRepository->save($moduleSectionPage, true);

            $this->addFlash('success', 'Шаблон для курса успешно изменен');

            return $this->redirectToRoute('admin_module_section_edit', ['id' => $moduleSectionPage->getSection()->getId()]);
        }

        return $this->mobileRender('admin/module-section/edit.html.twig', [
            'form' => $form->createView(),
            'module' => $moduleSectionPage->getSection()->getModule(),
        ]);
    }

    #[Route('/admin/module_section_page/delete/{id<\d+>}/', name: 'admin_module_section_page_delete')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function adminModuleSectionPageDelete(ModuleSectionPage $moduleSectionPage): Response
    {
        $moduleSectionId = $moduleSectionPage->getSection()->getId();
        $this->moduleSectionPageRepository->remove($moduleSectionPage, true);

        $this->addFlash('success', 'Шаблон для курса удален');
        
        return $this->redirectToRoute('admin_module_section_edit', ['id' => $moduleSectionId]);
    }
}
