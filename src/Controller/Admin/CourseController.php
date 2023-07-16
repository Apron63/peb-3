<?php

namespace App\Controller\Admin;

use App\Entity\Course;
use App\Service\TicketService;
use App\Form\Admin\CourseEditType;
use App\Decorator\MobileController;
use App\Repository\CourseRepository;
use App\Repository\ModuleRepository;
use App\Repository\TicketRepository;
use App\Service\ModuleTicketService;
use App\Repository\ProfileRepository;
use App\Repository\QuestionsRepository;
use App\Repository\CourseInfoRepository;
use App\Repository\ModuleInfoRepository;
use App\Repository\CourseThemeRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class CourseController extends MobileController
{
    public function __construct(
        private readonly CourseRepository $couseRepository,
        private readonly ProfileRepository $profileRepository,
        private readonly CourseInfoRepository $courseInfoRepository,
        private readonly CourseThemeRepository $courseThemeRepository,
        private readonly ModuleRepository $moduleRepository,
        private readonly ModuleInfoRepository $moduleInfoRepository,
        private readonly QuestionsRepository $questionsRepository,
        private readonly TicketRepository $ticketRepository,
        private readonly TicketService $ticketService,
        private readonly SluggerInterface $slugger,
        private readonly PaginatorInterface $paginator,
        private readonly ModuleTicketService $moduleTicketService,
    ) {}

    #[Route('/admin/course/', name: 'admin_course_list')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $type = $request->get('type');
        $profile = $request->get('profile');

        $pagination = $paginator->paginate(
            $this->couseRepository->getAllCoursesQuery($type, $profile),
            $request->query->getInt('page', 1),
            10
        );

        return $this->mobileRender('admin/course/index.html.twig', [
            'pagination' => $pagination,
            'profiles' => $this->profileRepository->getAllProfiles(),
        ]);
    }
    
    #[Route('/admin/course/create/', name: 'admin_course_create')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function create(Request $request, PaginatorInterface $paginator): Response
    {
        $course = new Course();
        $form = $this->createForm(CourseEditType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->isGranted('ROLE_SUPER_ADMIN')) {
                $this->couseRepository->save($course, true);
            }

            return $this->redirectToRoute('admin_course_list');
        }

        return $this->render('admin/course/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/course/{id<\d+>}/', name: 'admin_course_edit')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function adminCourseEdit(Request $request, Course $course): Response
    {
        $form = $this->createForm(CourseEditType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->isGranted('ROLE_SUPER_ADMIN')) {
                // @TODO вынести в сервис
                $image = $form->get('image')->getData();

                if ($image) {
                    $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                    
                    $imgPath = $this->getParameter('course_upload_directory') . '/' . $course->getId();

                    if (!file_exists($imgPath)) { 
                        mkdir($imgPath, 0777, true);
                    }

                    $newFilename = 
                        $this->slugger->slug($originalFilename)
                        . '-'
                        . uniqid()
                        . '.'
                        . $image->guessExtension();

                    try {
                        $image->move($imgPath, $newFilename);
                    } catch (FileException $e) {
                    }

                    $course->setImage($newFilename);
                }
                $this->couseRepository->save($course, true);
            }

            return $this->redirectToRoute('admin_course_list');
        }

        if ($course->getType() === Course::INTERACTIVE) {
            $pagination = $this->paginator->paginate(
                $this->questionsRepository->getQuestionQuery($course),
                $request->query->getInt('page', 1),
                10
            );

            return $this->mobileRender('admin/course/interactive/list.html.twig', [
                'form' => $form->createView(),
                'course' => $course,
                'modules' => $this->moduleRepository->getModules($course),
                'courseInfos' => $this->courseInfoRepository->getCourseInfos($course),
                'moduleInfos' => $this->moduleInfoRepository->getModuleInfos($course),
                'questions' => $pagination,
                'tickets' => $this->moduleTicketService->renderTickets($course),
            ]);
        } else {
            $courseThemes = $this->courseThemeRepository->getCourseThemes($course);
            $ticketCount = $this->ticketRepository->getTicketCount($course, $courseThemes);

            return $this->mobileRender('admin/course/edit.html.twig', [
                'form' => $form->createView(),
                'course' => $course,
                'courseInfos' => $this->courseInfoRepository->getCourseInfos($course),
                'courseThemes' => $courseThemes,
                'ticketCount' => $ticketCount,
                'tickets' => $this->ticketService->renderTickets($course),
            ]);
        }
    }

    #[Route('/admin/course/delete/{id<\d+>}/', name: 'admin_course_delete')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function adminCourseDelete(Request $request, Course $course): Response
    {
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->couseRepository->remove($course, true);
        }

        return $this->redirectToRoute('admin_course_list');
    }
}
