<?php

declare (strict_types=1);

namespace App\Controller\Demo;

use App\Entity\User;
use App\Repository\CourseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends AbstractController
{
    public function __construct(
        private readonly CourseRepository $courseRepository,
    ) {}

    #[Route('/demo/', name: 'app_demo')]
    public function index(): Response
    {
        $user = $this->getUser();

        if ($user instanceof User) {
            throw new NotFoundHttpException('User Not Allowed');
        }

        $courses = $this->courseRepository->findBy(['forDemo' => true]);

        return $this->render('frontend/demo/index.html.twig', [
            'courses' => $courses,
        ]);
    }
}
