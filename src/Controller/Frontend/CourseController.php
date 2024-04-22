<?php

namespace App\Controller\Frontend;

use App\Entity\Course;
use App\Entity\ModuleSection;
use App\Entity\Permission;
use App\Repository\CourseInfoRepository;
use App\Repository\ModuleSectionPageRepository;
use App\Repository\ModuleSectionRepository;
use App\Service\CourseService;
use App\Service\ModuleSectionArrowsService;
use App\Service\UserPermissionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException as ExceptionAccessDeniedException;

class CourseController extends AbstractController
{
    public function __construct(
        private readonly CourseInfoRepository $courseInfoRepository,
        private readonly ModuleSectionRepository $moduleSectionRepository,
        private readonly ModuleSectionPageRepository $moduleSectionPageRepository,
        private readonly UserPermissionService $userPermissionService,
        private readonly CourseService $courseService,
        private readonly ModuleSectionArrowsService $moduleSectionArrowsService,
    ) {}

    #[Route('/course/{id<\d+>}/', name: 'app_frontend_course')]
    public function index(Permission $permission): Response
    {
        if (! $this->userPermissionService->checkPermissionForUser($permission, $this->getUser(), true)) {
            throw new ExceptionAccessDeniedException();
        }

        $options = [
            'permission' => $permission,
            'courseInfo' => $this->courseInfoRepository->getCourseInfoWhereNotEmpty($permission->getCourse()),
            'courseProgress' => $this->courseService->checkForCourseStage($permission, true),
        ];

        if (Course::CLASSIC === $permission->getCourse()->getType()) {
            $themeId = $this->courseService->getClassicCourseTheme($permission->getCourse());

            if (null !== $themeId) {
                $options['hasMultipleThemes'] = false;
                $options['themeId'] = $themeId;
            } else {
                $options['hasMultipleThemes'] = true;
            }
        }

        return $this->render('frontend/course/index.html.twig', $options);
    }

    #[Route('/course/view-list/{id<\d+>}/', name: 'app_frontend_course_view_list')]
    public function viewList(Permission $permission): Response
    {
        if (! $this->userPermissionService->checkPermissionForUser($permission, $this->getUser(), false)) {
            throw new ExceptionAccessDeniedException();
        }

        return $this->render('frontend/course/_detail.html.twig', [
            'permission' => $permission,
            'content' => $this->renderView('frontend/course/_info-list.html.twig', [
                'courseInfo' => $this->courseInfoRepository->findBy(['course' => $permission->getCourse()]),
                'permission' => $permission,
            ]),
        ]);
    }

    #[Route('/course/view-file/{id<\d+>}/{fileName}/', name: 'app_frontend_course_view_file')]
    public function viewFile(Permission $permission, Request $request, string $fileName): Response
    {
        if (! $this->userPermissionService->checkPermissionForUser($permission, $this->getUser(), true)) {
            throw new ExceptionAccessDeniedException();
        }

        $url = '/view/' . $fileName . '/?courseId=' . $permission->getCourse()->getId();

        $infoName = $this->getParameter('course_upload_directory') . '/' . $permission->getCourse()->getId() . '/' . $fileName;

        if (! file_exists($infoName)) {
            throw new NotFoundHttpException();
        }

        return $this->render('frontend/course/_storage.html.twig', [
            'permission' => $permission,
            'fileName' => $url,
            'moduleTitle' => $request->get('moduleTitle'),
            'lastAccess' => $permission->getLastAccess()->getTimestamp(),
        ]);
    }

    #[Route('/course/interactive/{id<\d+>}/{moduleId<\d+>}/', name: 'user_get_info_module')]
    public function getInfoModule(Permission $permission, int $moduleId, Request $request): Response
    {
        if (! $this->userPermissionService->checkPermissionForUser($permission, $this->getUser(), true)) {
            throw new ExceptionAccessDeniedException();
        }

        $sessionId = $request->cookies->get('PHPSESSID');

        $moduleSection = $this->moduleSectionRepository->find($moduleId);
        if (! $moduleSection instanceof ModuleSection) {
            throw new NotFoundHttpException('Section not found');
        }

        $finalTestingEnabled = false;
        if ($moduleSection->isFinalTestingIsNext()) {
            $finalTestingEnabled = $this->moduleSectionArrowsService->isFinalTestingEnabled($permission);
        }

        $this->userPermissionService->checkPermissionHistory($permission, $moduleSection);

        $response = new Response();
        $response->headers->setCookie(new Cookie('init', md5($sessionId), time() + 3600));

        if (ModuleSection::TYPE_TESTING === $moduleSection->getType()) {
            return $this->redirectToRoute('app_frontend_preparation_interactive', ['id' => $permission->getId()]);
        } else {
            $moduleSectionPages = $this->moduleSectionPageRepository->getModuleSectionPages($moduleSection);
        }

        return $this->render('frontend/course/_page.html.twig', [
            'permission' => $permission,
            'moduleSection' => $moduleSection,
            'moduleSectionPages' => $moduleSectionPages,
            'finalTestingEnabled' => $finalTestingEnabled,
        ], $response);
    }
}
