<?php

namespace App\Controller\Frontend;

use App\Entity\CourseTheme;
use App\Entity\Permission;
use App\Repository\CourseThemeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PreparationController extends AbstractController
{
    public function __construct(
        readonly CourseThemeRepository $courseThemeRepository
    ) {}
    
    #[Route('/preparation-one/{id<\d+>}/', name: 'app_frontend_preparation_one')]
    public function preparationOne(CourseTheme $courseTheme): Response
    {
        return $this->render('frontend/preparation/index.html.twig', [
            'courseTheme' => $courseTheme,
        ]);
    }
    
    #[Route('/preparation-many/{id<\d+>}/', name: 'app_frontend_preparation_many')]
    public function preparationMany(Permission $permission): Response
    {
        return $this->render('frontend/course/_detail.html.twig', [
            'course' => $permission->getCourse(),
            'content' => $this->renderView('frontend/course/_theme-list.html.twig', [
                'themeInfo' => $this->courseThemeRepository->getCourseThemes($permission->getCourse()),
                'permission' => $permission,
            ]),
        ]);
    }
}
