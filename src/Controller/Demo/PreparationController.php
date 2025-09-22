<?php

declare (strict_types=1);

namespace App\Controller\Demo;

use App\Entity\Course;
use App\Entity\CourseTheme;
use App\Repository\CourseThemeRepository;
use App\Service\DemoPreparationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class PreparationController extends AbstractController
{
    public function __construct(
        private readonly CourseThemeRepository $courseThemeRepository,
        private readonly DemoPreparationService $demoPreparationService,
    ) {}

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

        return $this->render('frontend/demo/preparation/info-theme.html.twig', [
            'course' => $course,
            'themeInfo' => $this->courseThemeRepository->getCourseThemes($course),
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

        return $this->render('frontend/demo/preparation/preparation.html.twig', [
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

    #[Route('/demo/preparation-one/next-page/', name: 'demo_frontend_preparation_one_next_page')]
    public function preparationOneNextPage(Request $request): JsonResponse
    {
        $requestContent = json_decode($request->getContent(), true);

        $page = $requestContent['page'];
        $perPage = $requestContent['per_page'];
        $themeId = $requestContent['theme_id'];
        $courseId = $requestContent['permission_id'];


        $data = $this->demoPreparationService->getQuestionData(
            $courseId,
            $themeId,
            $page,
            $perPage,
        );

        return new JsonResponse([
            'status' => 'success',
            'page' => $page + 1,
            'per_page' => $perPage,
            'total' => $data['total'],
            'content' => $this->renderView('frontend/demo/_partial.html.twig', [
                'data' => $data,
            ]),
        ]);
    }
}
