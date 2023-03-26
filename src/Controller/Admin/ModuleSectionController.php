<?php

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Entity\Module;
use App\Entity\ModuleSection;
use App\Form\Admin\ModuleSectionEditType;
use App\Repository\ModuleSectionRepository;
use App\Service\InteractiveUploadService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ModuleSectionController extends MobileController
{
    public function __construct(
        readonly ModuleSectionRepository $moduleSectionRepository,
        readonly InteractiveUploadService $interactiveUploadService
    ) {}

    #[Route('/admin/module_section/add/{id<\d+>}/', name: 'admin_module_section_add')]
    public function adminAddModuleSection(Module $module, Request $request): Response
    {
        $moduleSection = new ModuleSection();
        $moduleSection->setModule($module);
        $moduleSection->setPart((int)$request->get('part', 1));

        $form = $this->createForm(ModuleSectionEditType::class, $moduleSection);
        $form->handleRequest($request);

        if (empty($moduleSection->getUrl())) {
            $moduleSection->setUrl('.');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->moduleSectionRepository->save($moduleSection, true);

            // if (null !== $form->get('filename')->getData()) {
            //     $this->interactiveUploadService->fileInteractiveUpload(
            //         $form->get('filename')->getData(),
            //         $moduleInfo
            //     );

            //     $moduleInfo->setUrl(
            //         $form->get('filename')->getData()->getClientOriginalName()
            //     );

            //     $this->moduleInfoRepository->save($moduleInfo, true);
            // }

            return $this->redirectToRoute('admin_module_edit', ['id' => $module->getId()]);
        }

        return $this->mobileRender('admin/module-section/edit.html.twig', [
            'form' => $form->createView(),
            'module' => $module,
        ]);
    }

    #[Route('/admin/module_section/edit/{id<\d+>}/', name: 'admin_module_section_edit')]
    public function adminEditModuleSection(ModuleSection $moduleSection, Request $request): Response
    {
        $form = $this->createForm(ModuleSectionEditType::class, $moduleSection);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (null !== $form->get('filename')->getData()) {
                $this->interactiveUploadService->fileInteractiveUpload(
                    $form->get('filename')->getData(),
                    $moduleSection
                );

                $moduleSection->setUrl(
                    $form->get('filename')->getData()->getClientOriginalName()
                );
            }

            $this->moduleSectionRepository->save($moduleSection, true);

            return $this->redirectToRoute('admin_module_edit', ['id' => $moduleSection->getModule()->getId()]);
        }

        return $this->mobileRender('admin/module-section/edit.html.twig', [
            'form' => $form->createView(),
            'module' => $moduleSection->getModule(),
        ]);
    }

    #[Route('/admin/module_section/delete/{id<\d+>}/', name: 'admin_module_section_delete')]
    public function adminDeleteModuleSection(ModuleSection $moduleSection): Response
    {
        $moduleId = $moduleSection->getModule()->getId();
        $this->moduleSectionRepository->remove($moduleSection, true);
        return $this->redirectToRoute('admin_module_edit', ['id' => $moduleId]);
    }
}
