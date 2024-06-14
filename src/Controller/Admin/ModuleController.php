<?php

namespace App\Controller\Admin;

use App\Entity\Course;
use App\Entity\Module;
use App\Service\TicketService;
use App\Form\Admin\ModuleEditType;
use App\Decorator\MobileController;
use App\Repository\ModuleRepository;
use App\Repository\ModuleSectionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ModuleController extends MobileController
{
    public function __construct(
        private readonly ModuleRepository $moduleRepository,
        private readonly ModuleSectionRepository $moduleSectionRepository,
        private readonly TicketService $ticketService,
    ) {}

    #[Route('/admin/module/add/{id<\d+>}/', name: 'admin_module_create')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function adminAddModule(Request $request, Course $course): Response
    {
        $module = new Module();
        $module->setCourse($course);
        $form = $this->createForm(ModuleEditType::class, $module);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->moduleRepository->save($module, true);

            $this->addFlash('success', 'Модуль добавлен');

            return $this->redirectToRoute('admin_course_edit', ['id' => $course->getId()]);
        }

        return $this->mobileRender('admin/course/interactive/edit.html.twig', [
            'form' => $form->createView(),
            'course' => $course,
            'modules' => $this->moduleRepository->getModules($course),
        ]);
    }

    #[Route('/admin/module/edit/{id<\d+>}/', name: 'admin_module_edit')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function adminEditModule(Module $module, Request $request): Response
    {
        $form = $this->createForm(ModuleEditType::class, $module);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->moduleRepository->save($module, true);

            $this->addFlash('success', 'Модуль обновлен');

            return $this->redirectToRoute('admin_course_edit', ['id' => $module->getCourse()->getId()]);
        }

        return $this->mobileRender('admin/course/interactive/edit.html.twig', [
            'form' => $form->createView(),
            'course' => $module->getCourse(),
            'moduleSection' => $this->moduleSectionRepository->findBy(['module' => $module]),
            'parentId' => $module->getId(),
        ]);
    }

    #[Route('/admin/module/delete/{id<\d+>}/', name: 'admin_module_delete')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function adminDeleteModule(Module $module): Response
    {
        $courseId = $module->getCourse()->getId();
        $this->moduleRepository->remove($module, true);
        $this->addFlash('success', 'Модуль удален');
        return $this->redirectToRoute('admin_course_edit', ['id' => $courseId]);
    }

    #[Route('/admin/module/create-tickets/{id<\d+>}/', name: 'admin_module_create_tickets', condition: 'request.isXmlHttpRequest()')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function adminCreateTickets(Request $request, Course $course): JsonResponse
    {
        $user = $this->getUser();

        $ticketCount = (int)$request->get('ticketCount');
        $questionCount = (int)$request->get('questionCount');
        $timeLeft = (int)$request->get('timeLeft');
        $errorsCount = (int)$request->get('errorsCount');

        $this->ticketService->createModuleTickets($course, $ticketCount, $questionCount, $timeLeft, $errorsCount, $user);

        return new JsonResponse([
            'result' => true,
        ]);
    }
}
