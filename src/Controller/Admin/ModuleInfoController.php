<?php

namespace App\Controller\Admin;

use App\Entity\Course;
use App\Entity\ModuleInfo;
use App\Decorator\MobileController;
use App\Form\Admin\ModuleInfoEditType;
use App\Repository\ModuleInfoRepository;
use App\Service\InteractiveUploadService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ModuleInfoController extends MobileController
{
    public function __construct(
        private readonly ModuleInfoRepository $moduleInfoRepository,
        private readonly InteractiveUploadService $interactiveUploadService
    ) {}

    #[Route('/admin/module_info/add/{id<\d+>}/', name: 'admin_module_info_add')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function adminAddModuleInfo(Course $course, Request $request): Response
    {
        $moduleInfo = new ModuleInfo();
        $moduleInfo->setCourse($course);

        $form = $this->createForm(ModuleInfoEditType::class, $moduleInfo);
        $form->handleRequest($request);

        if (empty($moduleInfo->getUrl())) {
            $moduleInfo->setUrl('.');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->moduleInfoRepository->save($moduleInfo, true);

            if (null !== $form->get('filename')->getData()) {
                $this->interactiveUploadService->fileInteractiveUpload(
                    $form->get('filename')->getData(),
                    $moduleInfo
                );

                $moduleInfo->setUrl(
                    $form->get('filename')->getData()->getClientOriginalName()
                );

                $this->moduleInfoRepository->save($moduleInfo, true);
            }

            return $this->redirectToRoute('admin_module_edit', ['id' => $course->getId()]);
        }

        return $this->mobileRender('admin/module-info/edit.html.twig', [
            'form' => $form->createView(),
            'course' => $course,
        ]);
    }

    #[Route('/admin/module_info/edit/{id<\d+>}/', name: 'admin_module_info_edit')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function adminEditModuleInfo(ModuleInfo $moduleInfo, Request $request): Response
    {
        $form = $this->createForm(ModuleInfoEditType::class, $moduleInfo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (null !== $form->get('filename')->getData()) {
                $this->interactiveUploadService->fileInteractiveUpload(
                    $form->get('filename')->getData(),
                    $moduleInfo
                );

                $moduleInfo->setUrl(
                    $form->get('filename')->getData()->getClientOriginalName()
                );
            }

            $this->moduleInfoRepository->save($moduleInfo, true);

            return $this->redirectToRoute('admin_module_edit', ['id' => $moduleInfo->getModule()->getId()]);
        }

        return $this->mobileRender('admin/module-info/edit.html.twig', [
            'form' => $form->createView(),
            'module' => $moduleInfo->getModule(),
        ]);
    }

    #[Route('/admin/module_info/delete/{id<\d+>}/', name: 'admin_module_info_delete')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function adminDeleteModuleInfo(ModuleInfo $moduleInfo): Response
    {
        $moduleId = $moduleInfo->getModule()->getId();
        $this->moduleInfoRepository->remove($moduleInfo, true);
        return $this->redirectToRoute('admin_module_edit', ['id' => $moduleId]);
    }
}
