<?php

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Entity\Course;
use App\Form\Admin\CourseEditType;
use App\Repository\CourseInfoRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\CourseRepository;
use App\Repository\CourseThemeRepository;
use App\Repository\ModuleInfoRepository;
use App\Repository\ModuleRepository;
use App\Repository\ProfileRepository;
use App\Repository\TicketRepository;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

class CourseController extends MobileController
{
    public function __construct(
        readonly CourseRepository $couseRepository,
        readonly ProfileRepository $profileRepository,
        readonly CourseInfoRepository $courseInfoRepository,
        readonly CourseThemeRepository $courseThemeRepository,
        readonly ModuleRepository $moduleRepository,
        readonly ModuleInfoRepository $moduleInfoRepository,
        readonly TicketRepository $ticketRepository,
        readonly SluggerInterface $slugger,
    ) {}

    #[Route('/admin/course/', name: 'admin_course_list')]
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
    
    #[Route('/admin/course/create', name: 'admin_course_create')]
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
                        //$imgPath
                        //. '/'
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
            return $this->mobileRender('admin/course/interactive/list.html.twig', [
                'form' => $form->createView(),
                'course' => $course,
                'modules' => $this->moduleRepository->getModules($course),
                'courseInfos' => $this->courseInfoRepository->getCourseInfos($course),
                'moduleInfos' => $this->moduleInfoRepository->getModuleInfos($course),
            ]);
        } else {
            $courseThemes = $this->courseThemeRepository->getCourseThemes($course);
            $ticketCount = $this->ticketRepository->getTicketCount($course, $courseThemes);

            // $totalTicketCount = $this->em->getRepository(Ticket::class)
            //     ->getTotalTicketCount($course);

            /** @var Ticket $ticket */
            //$ticket = $course->getTickets()->first();
            return $this->mobileRender('admin/course/edit.html.twig', [
                'form' => $form->createView(),
                'course' => $course,
                'courseInfos' => $this->courseInfoRepository->getCourseInfos($course),
                'courseThemes' => $courseThemes,
                'ticketCount' => $ticketCount,
                // 'totalTicketCount' => $totalTicketCount,
                // 'ticket' => $ticket,
            ]);
        }
    }

    #[Route('/admin/course/delete/{id<\d+>}/', name: 'admin_course_delete')]
    public function adminCourseDelete(Request $request, Course $course): Response
    {
        // if (!$course) {
        //     throw new NotFoundHttpException();
        // }

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->couseRepository->remove($course, true);
        }

        return $this->redirectToRoute('admin_course_list');
    }
}
