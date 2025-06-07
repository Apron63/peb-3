<?php

declare (strict_types=1);

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Entity\Module;
use App\Entity\ModuleSection;
use App\Event\AutonumerationCancelledEvent;
use App\Form\Admin\ModuleSectionEditType;
use App\Repository\ModuleSectionPageRepository;
use App\Repository\ModuleSectionRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ModuleSectionController extends MobileController
{
    public function __construct(
        private readonly ModuleSectionRepository $moduleSectionRepository,
        private readonly ModuleSectionPageRepository $moduleSectionPageRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    #[Route('/admin/module_section/add/{id<\d+>}/', name: 'admin_module_section_add')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function adminAddModuleSection(Module $module, Request $request): Response
    {
        $moduleSection = new ModuleSection();
        $moduleSection->setModule($module);

        $form = $this->createForm(ModuleSectionEditType::class, $moduleSection);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->moduleSectionRepository->save($moduleSection, true);
            $this->eventDispatcher->dispatch(new AutonumerationCancelledEvent($module->getCourse()->getId()));

            $this->addFlash('success', 'Добавлена новая страница');

            return $this->redirectToRoute('admin_module_edit', ['id' => $module->getId()]);
        }

        return $this->mobileRender('admin/module-section/index.html.twig', [
            'form' => $form->createView(),
            'moduleSection' => $moduleSection,
        ]);
    }

    #[Route('/admin/module_section/edit/{id<\d+>}/', name: 'admin_module_section_edit')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function adminEditModuleSection(ModuleSection $moduleSection, Request $request): Response
    {
        $form = $this->createForm(ModuleSectionEditType::class, $moduleSection);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->moduleSectionRepository->save($moduleSection, true);
            $this->eventDispatcher->dispatch(new AutonumerationCancelledEvent($moduleSection->getModule()->getCourse()->getId()));

            $this->addFlash('success', 'Страница обновлена');

            return $this->redirectToRoute('admin_module_edit', ['id' => $moduleSection->getModule()->getId()]);
        }

        return $this->mobileRender('admin/module-section/index.html.twig', [
            'form' => $form->createView(),
            'moduleSection' => $moduleSection,
            'moduleSectionPages' => $this->moduleSectionPageRepository->getModuleSectionPages($moduleSection),
        ]);
    }

    #[Route('/admin/module_section/delete/{id<\d+>}/', name: 'admin_module_section_delete')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function adminDeleteModuleSection(ModuleSection $moduleSection): Response
    {
        $moduleId = $moduleSection->getModule()->getId();
        $this->eventDispatcher->dispatch(new AutonumerationCancelledEvent($moduleSection->getModule()->getCourse()->getId()));
        $this->moduleSectionRepository->remove($moduleSection, true);

        $this->addFlash('success', 'Страница удалена');

        return $this->redirectToRoute('admin_module_edit', ['id' => $moduleId]);
    }
}
