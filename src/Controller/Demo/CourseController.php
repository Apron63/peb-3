<?php

declare (strict_types=1);

namespace App\Controller\Demo;

use App\Entity\Course;
use App\Entity\ModuleSection;
use App\Repository\CourseInfoRepository;
use App\Repository\ModuleSectionPageRepository;
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

        return $this->render('frontend/demo/_info-file.html.twig', [
            'moduleSection' => $section,
            'moduleSectionPages' => $moduleSectionPages,
        ], $response);
    }
}
