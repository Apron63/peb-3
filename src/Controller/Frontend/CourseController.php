<?php

namespace App\Controller\Frontend;

use App\Entity\Course;
use App\Entity\ModuleSection;
use App\Entity\Permission;
use App\Repository\CourseInfoRepository;
use App\Repository\ModuleInfoRepository;
use App\Repository\ModuleSectionRepository;
use App\Service\CourseService;
use App\Service\UserPermissionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException as ExceptionAccessDeniedException;

class CourseController extends AbstractController
{
    public function __construct(
        readonly CourseInfoRepository $courseInfoRepository,
        readonly ModuleInfoRepository $moduleInfoRepository,
        readonly ModuleSectionRepository $moduleSectionRepository,
        readonly UserPermissionService $userPermissionService,
        readonly CourseService $courseService
    ) {}

    #[Route('/course/{id<\d+>}/', name: 'app_frontend_course')]
    public function index(Permission $permission): Response
    {
        if (!$this->userPermissionService->checkPermissionForUser($permission->getCourse(), $this->getUser())) {
            throw new ExceptionAccessDeniedException();
        }

        return $this->render('frontend/course/index.html.twig', [
            'permission' => $permission,
            'courseInfo' => $this->courseInfoRepository->findBy(['course' => $permission->getCourse()]),
            'courseProgress' => $this->courseService->checkForCourseStage($permission),
        ]);
    }
    
    #[Route('/course/view-list/{id<\d+>}/', name: 'app_frontend_course_view_list')]
    public function viewList(Course $course): Response
    {
        if (!$this->userPermissionService->checkPermissionForUser($course, $this->getUser())) {
            throw new ExceptionAccessDeniedException();
        }

        return $this->render('frontend/course/_detail.html.twig', [
            'course' => $course,
            'content' => $this->renderView('frontend/course/_info-list.html.twig', [
                'courseInfo' => $this->courseInfoRepository->findBy(['course' => $course]),
            ]),
        ]);
    }
    
    #[Route('/course/view-file/{id<\d+>}/{fileName}/', name: 'app_frontend_course_view_file')]
    public function viewFile(Course $course, string $fileName): Response
    {
        if (!$this->userPermissionService->checkPermissionForUser($course, $this->getUser())) {
            throw new ExceptionAccessDeniedException();
        }

        $url = '/view/' . $fileName . '/?courseId=' . $course->getId();

        $infoName = $this->getParameter('course_upload_directory') . '/' . $course->getShortNameCleared() . '/' . $fileName;

        if(!file_exists($infoName)) {
            throw new NotFoundHttpException();
        }

        return $this->render('frontend/course/_file.html.twig', [
            'course' => $course,
            'fileName' => $url,
        ]);
    }

    #[Route('/course/interactive/{id<\d+>}/{moduleId<\d+>}/', name: 'user_get_info_module')]
    public function getInfoModule(Course $course, int $moduleId, Request $request): Response
    {
        if (!$this->userPermissionService->checkPermissionForUser($course, $this->getUser())) {
            throw new ExceptionAccessDeniedException();
        }

        $sessionId = $request->cookies->get('PHPSESSID');

        $response = new Response();
        $response->headers->setCookie(new Cookie('init', md5($sessionId), time() + 3600));

        return $this->render(
            'frontend/course/_file.html.twig', 
            [
                'course' => $course,
                'fileName' => "/storage/interactive/{$course->getId()}/{$moduleId}/res/index.php",
            ],
            $response
        );
    }

    #[Route('/course/external/{id<\d+>}/{moduleId<\d+>}/', name: 'user_get_info_module_external')]
    public function getInfoModuleExternal(Course $course, int $moduleId): Response
    {
        $moduleSection = $this->moduleSectionRepository->find($moduleId);

        if (!$moduleSection instanceof ModuleSection) {
            throw new NotFoundHttpException();
        }

        return $this->render('frontend/course/_file.html.twig', [
            'course' => $course,
            'fileName' => $moduleSection->getUrl(),
            // 'module' => $moduleInfo->getModule(),
        ]);
    }
}
