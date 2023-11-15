<?php

namespace App\Controller;

use App\Entity\Course;
use App\Repository\CourseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class StorageController extends AbstractController
{
    public function __construct(
        private readonly CourseRepository $courseRepository
    ) {}

    #[Route('/view/{filename}/', name: 'view')]
    public function fileViewAction(Request $request, string $filename): Response
    {
        $courseId = $request->get('courseId');

        $course = $this->courseRepository->findOneBy(['id' => $courseId]);

        return new BinaryFileResponse($this->getViewedFileName($course, $filename));
    }

    #[Route('/view/report/{filename}/', name: 'view_report')]
    public function reportViewAction(string $filename): Response
    {
        $viewedFileName = $this->getParameter('report_upload_directory') . '/' . $filename;

        if (! file_exists($viewedFileName)) {
            throw new NotFoundHttpException('File not found');
        }

        return new BinaryFileResponse($viewedFileName);
    }

    private function getViewedFileName(Course $course, string $filename): string
    {
        if (empty($filename)) {
            throw new NotFoundHttpException('Filename cannot be blank');
        }

        if (!$course) {
            throw new NotFoundHttpException('Course not found');
        }

        $viewedFileName = $this->getParameter('course_upload_directory')
            . '/'
            . $course->getId()
            . '/'
            . $filename;

        if (! file_exists($viewedFileName)) {
            throw new NotFoundHttpException('File not found');
        }

        return $viewedFileName;
    }
}
