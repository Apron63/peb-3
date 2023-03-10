<?php

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Entity\Course;
use App\Entity\Module;
use App\Form\Admin\ModuleEditType;
use App\Repository\ModuleRepository;
use App\Repository\ModuleSectionRepository;
use App\Repository\ModuleTicketRepository;
use App\Repository\QuestionsRepository;
use App\Service\TicketService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ModuleController extends MobileController
{
    public function __construct(
        readonly ModuleRepository $moduleRepository,
        readonly ModuleSectionRepository $moduleSectionRepository,
        readonly QuestionsRepository $questionsRepository,
        readonly ModuleTicketRepository $moduleTicketRepository,
        readonly TicketService $ticketService
    ) {}

    #[Route('/admin/module/add/{id<\d+>}/', name: 'admin_module_create')]
    public function adminAddModule(Request $request, Course $course): Response
    {
        $module = new Module();
        $module->setCourse($course);
        $form = $this->createForm(ModuleEditType::class, $module);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->moduleRepository->save($module, true);

            return $this->redirectToRoute('admin_course_edit', ['id' => $course->getId()]);
        }

        return $this->mobileRender('admin/course/interactive/edit.html.twig', [
            'form' => $form->createView(),
            'course' => $course,
            'modules' => $this->moduleRepository->getModules($course),
        ]);
    }

    #[Route('/admin/module/edit/{id<\d+>}/', name: 'admin_module_edit')]
    public function adminEditModule(
        Module $module, 
        Request $request, 
        PaginatorInterface $paginator 
    ): Response {
        $pagination = $paginator->paginate(
            $this->questionsRepository->getQuestionQuery($module->getCourse(), $module->getId()),
            $request->query->getInt('page', 1),
            10
        );

        $form = $this->createForm(ModuleEditType::class, $module);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->moduleRepository->save($module, true);

            return $this->redirectToRoute('admin_course_edit', ['id' => $module->getCourse()->getId()]);
        }

        return $this->mobileRender('admin/course/interactive/edit.html.twig', [
            'form' => $form->createView(),
            'course' => $module->getCourse(),
            'moduleSection' => $this->moduleSectionRepository->findBy(['module' => $module]),
            'pagination' => $pagination,
            'parentId' => $module->getId(),
            'tickets' => $this->moduleTicketRepository->getTickets($module),
        ]);
    }

    #[Route('/admin/module/delete/{id<\d+>}/', name: 'admin_module_delete')]
    public function adminDeleteModule(Module $module): Response
    {
        $courseId = $module->getCourse()->getId();
        $this->moduleRepository->remove($module, true);
        return $this->redirectToRoute('admin_course_edit', ['id' => $courseId]);
    }
    
    #[Route('/admin/module/create-tickets/{id<\d+>}/', name: 'admin_module_create_tickets', condition: 'request.isXmlHttpRequest()')]
    public function adminCreateTickets(Request $request, Module $module): JsonResponse
    {
        $ticketCount = $request->get('ticketCount');
        $questionCount = $request->get('questionCount');
        $errorsCount = $request->get('errorsCount');

        $this->ticketService->createModuleTickets($module, $ticketCount, $questionCount, $errorsCount);
        
        return new JsonResponse([
            'result' => true,
        ]);
    }
}
