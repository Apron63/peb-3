<?php

namespace App\Controller\Frontend;

use App\Entity\Course;
use App\Entity\CourseTheme;
use App\Entity\DemoLogger;
use App\Entity\ModuleSection;
use App\Repository\CourseInfoRepository;
use App\Repository\CourseRepository;
use App\Repository\CourseThemeRepository;
use App\Repository\DemoLoggerRepository;
use App\Repository\ModuleSectionPageRepository;
use App\Service\CourseService;
use App\Service\DemoPreparationService;
use App\Service\DemoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DemoController extends AbstractController
{
    public function __construct(
        private readonly CourseRepository $courseRepository,
        private readonly CourseInfoRepository $courseInfoRepository,
        private readonly CourseService $courseService,
        private readonly ModuleSectionPageRepository $moduleSectionPageRepository,
        private readonly CourseThemeRepository $courseThemeRepository,
        private readonly DemoPreparationService $demoPreparationService,
        private readonly DemoService $demoService,
        private readonly DemoLoggerRepository $demoLoggerRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    #[Route('/demo/', name: 'app_demo')]
    public function index(): Response
    {
        $courses = $this->courseRepository->findBy(['forDemo' => true]);

        return $this->render('frontend/demo/index.html.twig', [
            'courses' => $courses,
        ]);
    }

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
            return $this->render('frontend/demo/_classic.html.twig', [
                'course' => $course,
                'courseInfo' => $courseInfo,
                'hasMultipleThemes' => $hasMultipleThemes,
                'themeId' => $themeId,
            ]);
        } else {
            return $this->render('frontend/demo/_interactive.html.twig', [
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
    
    #[Route('/demo/final-testing/{id<\d+>}/', name: 'app_demo_final_testing')]
    public function finalTesting(Course $course, Request $request): Response
    {
        if (! $course->isForDemo()) {
            throw new NotFoundHttpException();
        }

        $loggerId = $request->cookies->get('loggerId');

        if (null === $loggerId) {
            $logger = $this->demoService->createNewLogger($course);
        } else {
            $logger = $this->demoLoggerRepository->findOneBy(['loggerId' => $loggerId]);

            if (
                ! $logger instanceof DemoLogger
                || null !== $logger->getEndAt()
            ) {
                $logger = $this->demoService->createNewLogger($course);
            }
        }

        if (0 === $logger->getTimeLeftInSeconds()) {
            return $this->redirect(
                $this->generateUrl('app_demo_final_testing_end', ['id' => $course->getId()])
            );
        }

        $response = new Response();

        $response->headers->setCookie(new Cookie('loggerId', $logger->getLoggerId(), time() + 3600));

        return $this->render('frontend/demo/_final-testing.html.twig', [
            'course' => $course,
            'data' => $this->demoService->getData($logger),
        ], $response);
    }

    #[Route('/demo/testing-next-step/{id<\d+>}/', name: 'app_demo_testing_next_step',  condition: 'request.isXmlHttpRequest()')]
    public function nextStep(Course $course, Request $request): JsonResponse
    {
        if (! $course->isForDemo()) {
            throw new NotFoundHttpException();
        }

        $loggerId = $request->cookies->get('loggerId');
        if (null === $loggerId) {
            throw new NotFoundHttpException('Logger not found');
        }

        $logger = $this->demoLoggerRepository->findOneBy(['loggerId' => $loggerId]);
        if (! $logger instanceof DemoLogger) {
            throw new NotFoundHttpException('Logger not found');
        }

        return new JsonResponse([
            'redirectUrl' => $this->demoService->ticketProcessing($logger, $request->request->all()),
        ]);
    }

    #[Route('/demo/testing/end/{id<\d+>}/', name: 'app_demo_final_testing_end')]
    public function endTesting(Course $course, Request $request): Response
    {
        $loggerId = $request->cookies->get('loggerId');
        if (null === $loggerId) {
            throw new NotFoundHttpException('Logger not found');
        }

        $logger = $this->demoLoggerRepository->findOneBy(['loggerId' => $loggerId]);
        if (! $logger instanceof DemoLogger) {
            throw new NotFoundHttpException('Logger not found');
        }

        return $this->render('frontend/demo/protocol.html.twig', [
            'logger' => $this->demoService->closeLogger($logger),
            'skipped' => $this->demoService->getSkippedQuestion($logger),
        ]);
    }
    
    #[Route('/demo/info-list/{id<\d+>}/', name: 'app_demo_info_list')]
    public function getInfoList(Course $course): Response
    {
        if (!$course->isForDemo()) {
            throw new NotFoundHttpException();
        }

        return $this->render('frontend/demo/_info-list.html.twig', [
            'course' => $course,
            'courseInfo' => $this->courseInfoRepository->getCourseInfos($course),
        ]);
    }
    
    #[Route('/demo/info-view/{fileName}/{moduleTitle}/{id<\d+>}/', name: 'app_demo_info_view')]
    public function getInfoView(string $fileName, string $moduleTitle, Course $course): Response
    {
        if (! $course->isForDemo()) {
            throw new NotFoundHttpException();
        }

        $infoName = $this->getParameter('course_upload_directory') . '/' . $course->getId() . '/' . $fileName;

        if(! file_exists($infoName)) {
            throw new NotFoundHttpException();
        }

        return new BinaryFileResponse($infoName);
    }

    #[Route('demo/preparation/{id<\d+>}/{themeId?}/', name: 'app_demo_preparation_course')]
    public function preparation(Course $course, Request $request, ?int $themeId = null): Response
    {
        if (! $course->isForDemo()) {
            throw new NotFoundHttpException();
        }

        if (null !== $themeId) {
            $courseTheme = $this->courseThemeRepository->find($themeId);

            if (! $courseTheme instanceof CourseTheme) {
                throw new NotFoundHttpException('Course theme not found');
            }
        }

        $data = $this->demoPreparationService->getQuestionDataForCourse(
            $course,
            $themeId,
            $request->get('page', 1),
            $request->get('perPage', 20),
        );

        return $this->render('frontend/demo/_preparation.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route('demo/preparation_many/{id<\d+>}/', name: 'app_demo_preparation_course_many')]
    public function preparationMany(Course $course): Response
    {
        if (! $course->isForDemo()) {
            throw new NotFoundHttpException();
        }

        return $this->render('frontend/demo/_info-theme.html.twig', [
            'course' => $course,
            'themeInfo' =>$this->courseThemeRepository->getCourseThemes($course),
        ]);
    }
    
    #[Route('demo/preparation_one/{id<\d+>}/{themeId}', name: 'app_demo_preparation_course_one')]
    public function preparationOne(Course $course, ?int $themeId, Request $request): Response
    {
        if (! $course->isForDemo()) {
            throw new NotFoundHttpException();
        }

        if (null !== $themeId) {
            $courseTheme = $this->courseThemeRepository->find($themeId);

            if (! $courseTheme instanceof CourseTheme) {
                throw new NotFoundHttpException('Course theme not found');
            }
        }

        $data = $this->demoPreparationService->getQuestionDataForCourse(
            $course,
            $themeId,
            $request->get('page', 1),
            $request->get('perPage', 20),
        );

        return $this->render('frontend/demo/_preparation.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route('demo/preparation_interactive/{id<\d+>}/', name: 'app_demo_preparation_interactive')]
    public function preparationInteractive(Course $course, Request $request): Response
    {
        if (! $course->isForDemo()) {
            throw new NotFoundHttpException();
        }

        $data = $this->demoPreparationService->getQuestionDataForCourse(
            $course,
            null,
            $request->get('page', 1),
            $request->get('perPage', 20),
        );

        return $this->render('frontend/demo/_preparation.html.twig', [
            'data' => $data,
        ]);
    }
}
