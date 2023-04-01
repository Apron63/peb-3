<?php

namespace App\Controller\Frontend;

use App\Entity\CourseTheme;
use App\Entity\Permission;
use App\Repository\CourseThemeRepository;
use App\Service\UserPermissionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException as ExceptionAccessDeniedException;

class PreparationController extends AbstractController
{
    public function __construct(
        readonly CourseThemeRepository $courseThemeRepository,
        readonly UserPermissionService $userPermissionService,
    ) {}
    
    #[Route('/preparation-one/{id<\d+>}/{themeId<\d+>}/', name: 'app_frontend_preparation_one')]
    public function preparationOne(Permission $permission, int $themeId): Response
    {
        if (!$this->userPermissionService->checkPermissionForUser($permission, $this->getUser(), true)) {
            throw new ExceptionAccessDeniedException();
        }

        $courseTheme = $this->courseThemeRepository->find($themeId);
        if (!$courseTheme instanceof CourseTheme) {
            throw new NotFoundHttpException('Course theme not found');
        }

        return $this->render('frontend/preparation/index.html.twig', [
            'courseTheme' => $courseTheme,
        ]);
    }
    
    #[Route('/preparation-many/{id<\d+>}/', name: 'app_frontend_preparation_many')]
    public function preparationMany(Permission $permission): Response
    {
        if (!$this->userPermissionService->checkPermissionForUser($permission, $this->getUser(), false)) {
            throw new ExceptionAccessDeniedException();
        }

        return $this->render('frontend/course/_detail.html.twig', [
            'permission' => $permission,
            'content' => $this->renderView('frontend/course/_theme-list.html.twig', [
                'themeInfo' => $this->courseThemeRepository->getCourseThemes($permission->getCourse()),
                'permission' => $permission,
            ]),
        ]);
    }
}
