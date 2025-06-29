<?php

declare (strict_types=1);

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Entity\ModuleSection;
use App\Entity\ModuleSectionPage;
use App\Event\AutonumerationCancelledEvent;
use App\Form\Admin\ModuleSectionPageEditType;
use App\Repository\ModuleSectionPageRepository;
use App\Service\InteractiveUploadService;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ModuleSectionPageController extends MobileController
{
    public function __construct(
        private readonly ModuleSectionPageRepository $moduleSectionPageRepository,
        private readonly InteractiveUploadService $interactiveUploadService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly string $videoUploadPath,
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
            if (null !== $form->get('filename')->getData()) {
                $this->interactiveUploadService->fileInteractiveUpload(
                    $form->get('filename')->getData(),
                    $moduleSectionPage
                );
            }

            if (null !== $form->get('videoFilename')->getData()) {
                $this->interactiveUploadService->videoFileInteractiveUpload(
                    $form->get('videoFilename')->getData(),
                    $moduleSectionPage,
                    $request->getSchemeAndHttpHost(),
                );
            }

            $this->moduleSectionPageRepository->save($moduleSectionPage, true);
            $this->eventDispatcher->dispatch(new AutonumerationCancelledEvent($moduleSection->getModule()->getCourse()->getId()));
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

            if (null !== $form->get('videoFilename')->getData()) {
                $this->interactiveUploadService->videoFileInteractiveUpload(
                    $form->get('videoFilename')->getData(),
                    $moduleSectionPage,
                    $request->getSchemeAndHttpHost(),
                );
            }

            $this->moduleSectionPageRepository->save($moduleSectionPage, true);
            $this->eventDispatcher->dispatch(new AutonumerationCancelledEvent($moduleSectionPage->getSection()->getModule()->getCourse()->getId()));
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

        if (null !== $moduleSectionPage->getvideoUrl()) {
            $videoFileUrl = explode(DIRECTORY_SEPARATOR, $moduleSectionPage->getvideoUrl());

            $videoFilename = $this->videoUploadPath
                . DIRECTORY_SEPARATOR
                . $moduleSectionPage->getSection()->getModule()->getCourse()->getId()
                . DIRECTORY_SEPARATOR
                . $videoFileUrl[array_key_last($videoFileUrl)];

            try {
                unlink($videoFilename);
            }
            catch (Exception $e) {

            }
        }

        $this->eventDispatcher->dispatch(new AutonumerationCancelledEvent($moduleSectionPage->getSection()->getModule()->getCourse()->getId()));
        $this->moduleSectionPageRepository->remove($moduleSectionPage, true);
        $this->addFlash('success', 'Шаблон для курса удален');
        return $this->redirectToRoute('admin_module_section_edit', ['id' => $moduleSectionId]);
    }
}
