<?php

declare (strict_types=1);

namespace App\Controller\Demo;

use App\Entity\Course;
use App\Entity\ModuleSection;
use App\Entity\User;
use App\Repository\CourseInfoRepository;
use App\Repository\ModuleSectionPageRepository;
use App\Repository\ModuleSectionRepository;
use App\Service\CourseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CourseController extends AbstractController
{
    public function __construct(
        private readonly CourseInfoRepository $courseInfoRepository,
        private readonly CourseService $courseService,
        private readonly ModuleSectionPageRepository $moduleSectionPageRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ModuleSectionRepository $moduleSectionRepository,
    ) {}

    #[Route('/demo/{id<\d+>}/', name: 'app_demo_course')]
    public function getCourse(Course $course): Response
    {
        if (! $course->isForDemo()) {
            throw new NotFoundHttpException();
        }

        $courseInfo = $this->courseInfoRepository->getCourseInfos($course);

        if (Course::CLASSIC === $course->getType()) {
            $themeId = $this->courseService->getClassicCourseTheme($course);

            $hasMultipleThemes = false;
            if (null === $themeId) {
                $hasMultipleThemes = true;
            }
            return $this->render('frontend/demo/course/classic.html.twig', [
                'course' => $course,
                'courseInfo' => $courseInfo,
                'hasMultipleThemes' => $hasMultipleThemes,
                'themeId' => $themeId,
            ]);
        } else {
            return $this->render('frontend/demo/course/interactive.html.twig', [
                'course' => $course,
                'courseInfo' => $courseInfo,
                'courseProgress' => $this->courseService->getCourseProgressForDemo($course),
            ]);
        }
    }

    #[Route('/demo/section/{id<\d+>}/', name: 'app_demo_module_section')]
    public function getSection(ModuleSection $section, Request $request): Response
    {
        if (! $section->getModule()->getCourse()->isForDemo()) {
            throw new NotFoundHttpException();
        }

        if (ModuleSection::TYPE_TESTING === $section->getType()) {
            $course = $section->getModule()->getCourse();

            if (Course::INTERACTIVE === $course->getType()) {
                $url = $this->urlGenerator->generate('app_demo_preparation_interactive', ['id' => $course->getId()]);
            } else {
                $url = $this->urlGenerator->generate('app_demo_preparation_course_many', ['id' => $course->getId()]);
            }

            return $this->redirect($url);
        }

        $sessionId = $request->cookies->get('PHPSESSID');
        $response = new Response();
        $response->headers->setCookie(new Cookie('init', md5($sessionId), time() + 3600));

        $moduleSectionPages = $this->moduleSectionPageRepository->getModuleSectionPages($section);

        return $this->render('frontend/demo/info/info-file.html.twig', [
            'course' => $section->getModule()->getCourse(),
            'moduleSection' => $section,
            'moduleSectionPages' => $moduleSectionPages,
        ], $response);
    }

    #[Route('demo/course/interactive/{id<\d+>}/{moduleId<\d+>}/', name: 'demo_get_info_module')]
    public function getInfoModule(Course $course, int $moduleId, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user instanceof User) {
            throw new NotFoundHttpException('User Not Allowed');
        }

        $sessionId = $request->cookies->get('PHPSESSID');

        $moduleSection = $this->moduleSectionRepository->find($moduleId);
        if (! $moduleSection instanceof ModuleSection) {
            throw new NotFoundHttpException('Section not found');
        }

        $response = new Response();
        $response->headers->setCookie(new Cookie('init', md5($sessionId), time() + 3600));

        if (ModuleSection::TYPE_TESTING === $moduleSection->getType()) {
            return $this->redirectToRoute('app_demo_preparation_interactive', ['id' => $course->getId()]);
        } else {
            $moduleSectionPages = $this->moduleSectionPageRepository->getModuleSectionPages($moduleSection);
        }

        return $this->render('frontend/course/_page.html.twig', [
            'permission' => $course,
            'moduleSection' => $moduleSection,
            'moduleSectionPages' => $moduleSectionPages,
        ], $response);
    }
}
