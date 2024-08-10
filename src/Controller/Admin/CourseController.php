<?php

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Entity\Course;
use App\Entity\User;
use App\Form\Admin\CourseEditType;
use App\Message\CourseCopyMessage;
use App\Repository\CourseInfoRepository;
use App\Repository\CourseRepository;
use App\Repository\CourseThemeRepository;
use App\Repository\ModuleInfoRepository;
use App\Repository\ModuleRepository;
use App\Repository\ProfileRepository;
use App\Repository\QuestionsRepository;
use App\Repository\TicketRepository;
use App\Service\CourseService;
use App\Service\FileUploadService;
use App\Service\ModuleSectionArrowsService;
use App\Service\ModuleTicketService;
use App\Service\TicketService;
use App\Trait\UrlSaveTrait;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CourseController extends MobileController
{
    use UrlSaveTrait;

    public function __construct(
        private readonly CourseRepository $courseRepository,
        private readonly ProfileRepository $profileRepository,
        private readonly CourseInfoRepository $courseInfoRepository,
        private readonly CourseThemeRepository $courseThemeRepository,
        private readonly ModuleRepository $moduleRepository,
        private readonly ModuleInfoRepository $moduleInfoRepository,
        private readonly QuestionsRepository $questionsRepository,
        private readonly TicketRepository $ticketRepository,
        private readonly TicketService $ticketService,
        private readonly PaginatorInterface $paginator,
        private readonly ModuleTicketService $moduleTicketService,
        private readonly CourseService $courseService,
        private readonly FileUploadService $fileUploadService,
        private readonly MessageBusInterface $messageBus,
        private readonly ModuleSectionArrowsService $moduleSectionArrowsService,
        private readonly string $courseUploadPath,
    ) {}

    #[Route('/admin/course/', name: 'admin_course_list')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $type = $request->get('type');
        $profile = $request->get('profile');
        $name = $request->get('name');

        $pagination = $paginator->paginate(
            $this->courseRepository->getAllCoursesQuery($type, $profile, $name),
            $request->query->getInt('page', 1),
            10
        );

        return $this->mobileRender('admin/course/index.html.twig', [
            'pagination' => $pagination,
            'profiles' => $this->profileRepository->getAllProfiles(),
            'name' => $name,
        ]);
    }

    #[Route('/admin/course/create/', name: 'admin_course_create')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function create(Request $request): Response
    {
        $this->saveUrl($request);

        $course = new Course();
        $form = $this->createForm(CourseEditType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->courseRepository->save($course, true);

            $this->addFlash('success', 'Курс добавлен');

            return $this->getRedirectUrl($request, 'admin_course_list');
        }

        return $this->render('admin/course/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/course/{id<\d+>}/', name: 'admin_course_edit')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function adminCourseEdit(Request $request, Course $course): Response
    {
        $this->saveUrl($request);

        $form = $this->createForm(CourseEditType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('image')->getData();

            if ($image instanceof UploadedFile) {
                $path = $this->courseUploadPath . DIRECTORY_SEPARATOR . $course->getId();
                $newFilename = $this->fileUploadService->uploadFile($image, $path, $course->getImage());

                $course->setImage($newFilename);
            }

            if (Course::INTERACTIVE === $course->getType()) {
                $sortOrder = $form->get('sortOrder')->getData();

                $this->courseService->saveModuleOrder($course, $sortOrder);
            }

            $this->courseRepository->save($course, true);

            $this->addFlash('success', 'Курс обновлен');

            return $this->getRedirectUrl($request, 'admin_course_list');
        }

        if (Course::INTERACTIVE === $course->getType()) {
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
        $this->saveUrl($request);

        $this->courseRepository->remove($course, true);

        $this->addFlash('success', 'Курс удален');

        return $this->getRedirectUrl($request, 'admin_course_list');
    }

    #[Route('/admin/course/copy/{id<\d+>}/', name: 'admin_course_copy')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function adminCourseCopy(Request $request, Course $course): Response
    {
        $this->saveUrl($request);

        /** @var User $user */
        $user = $this->getUser();

        $this->messageBus->dispatch(
            new CourseCopyMessage(
                $course->getShortName(),
                $user->getId(),
                $course->getId()
            )
        );

        $this->addFlash('success', 'Задача на копирование курса поставлена в очередь');

        return $this->getRedirectUrl($request, 'admin_course_list');
    }

    #[Route('admin/course/autonumeration/{id<\d+>}/', name: 'admin_course_autonumeration')]
    public function getInfoModule(Course $course): RedirectResponse
    {
        $user = $this->getUser();

        $this->moduleSectionArrowsService->autonumerationCourse($course, $user);

        $this->addFlash('success', 'Автонумерация выполнена');

        return $this->redirectToRoute('admin_course_edit', ['id' => $course->getId()]);
    }
}
