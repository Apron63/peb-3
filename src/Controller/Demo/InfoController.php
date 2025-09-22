<?php

declare (strict_types=1);

namespace App\Controller\Demo;

use App\Entity\Course;
use App\Repository\CourseInfoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class InfoController extends AbstractController
{
    public function __construct(
        private readonly CourseInfoRepository $courseInfoRepository,
    ) {}

    #[Route('/demo/info-list/{id<\d+>}/', name: 'app_demo_info_list')]
    public function getInfoList(Course $course): Response
    {
        if (!$course->isForDemo()) {
            throw new NotFoundHttpException();
        }

        $courseInfos = array_filter(
            $this->courseInfoRepository->getCourseInfos($course),
            static fn($info) => !empty($info->getName()),
        );

        return $this->render('frontend/demo/info/info-list.html.twig', [
            'course' => $course,
            'courseInfo' => $courseInfos,
        ]);
    }

    #[Route('/demo/info-view/{fileName}/{moduleTitle}/{id<\d+>}/', name: 'app_demo_info_view')]
    public function getInfoView(string $fileName, string $moduleTitle, Course $course): Response
    {
        if (! $course->isForDemo()) {
            throw new NotFoundHttpException();
        }

        $infoName = $this->getParameter('course_upload_directory') . '/' . $course->getId() . '/' . $fileName;

        if (! file_exists($infoName)) {
            throw new NotFoundHttpException();
        }

        return new BinaryFileResponse($infoName);
    }
}
