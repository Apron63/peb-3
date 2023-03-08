<?php

namespace App\Controller\Frontend;

use App\Entity\Course;
use App\Repository\CourseInfoRepository;
use App\Service\UserPermissionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException as ExceptionAccessDeniedException;

class CourseController extends AbstractController
{
    public function __construct(
        readonly CourseInfoRepository $courseInfoRepository,
        readonly UserPermissionService $userPermissionService
    ) {}

    #[Route('/course/{id<\d+>}', name: 'app_frontend_course')]
    public function index(Course $course): Response
    {
        if (!$this->userPermissionService->checkPermissionForUser($course, $this->getUser())) {
            throw new ExceptionAccessDeniedException();
        }

        return $this->render('frontend/course/index.html.twig', [
            'course' => $course,
            'courseInfo' => $this->courseInfoRepository->findBy(['course' => $course]),
        ]);
    }
}
