<?php

declare (strict_types=1);

namespace App\Controller\Frontend;

use App\Entity\Course;
use App\Entity\CourseTheme;
use App\Entity\Permission;
use App\Entity\User;
use App\Repository\CourseThemeRepository;
use App\Repository\PermissionRepository;
use App\Repository\PreparationHistoryRepository;
use App\Service\PreparationService;
use App\Service\UserPermissionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException as ExceptionAccessDeniedException;

class PreparationController extends AbstractController
{
    public function __construct(
        private readonly CourseThemeRepository $courseThemeRepository,
        private readonly UserPermissionService $userPermissionService,
        private readonly PreparationService $preparationService,
        private readonly PermissionRepository $permissionRepository,
        private readonly PreparationHistoryRepository $preparationHistoryRepository,
    ) {}

    #[Route('/preparation-one/{id<\d+>}/{themeId<\d+>}/', name: 'app_frontend_preparation_one')]
    public function preparationOne(Permission $permission, int $themeId = null, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (! $this->userPermissionService->checkPermissionForUser($permission, $user, true)) {
            throw new ExceptionAccessDeniedException('Permission: ' . $permission->getId() . ' not available for user: ' . $user->getId());
        }

        if (Course::CLASSIC !== $permission->getCourse()->getType()) {
            throw new ExceptionAccessDeniedException('This url enabled only for classic course');
        }

        $courseTheme = $this->courseThemeRepository->find($themeId);
        if (! $courseTheme instanceof CourseTheme) {
            throw new NotFoundHttpException('Course theme not found');
        }

        $data = $this->preparationService->getQuestionData(
            $permission,
            $themeId,
            $request->get('page', 1),
            $request->get('perPage', 20),
        );

        if (empty($permission->getHistory())) {
            $permission->setHistory(['active']);

            $this->permissionRepository->save($permission, true);
        }

        return $this->render('frontend/preparation/index.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route('/preparation-many/{id<\d+>}/', name: 'app_frontend_preparation_many')]
    public function preparationMany(Permission $permission): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (! $this->userPermissionService->checkPermissionForUser($permission, $user, false)) {
            throw new ExceptionAccessDeniedException('Permission: ' . $permission->getId() . ' not available for user: ' . $user->getId());
        }

        if (Course::CLASSIC !== $permission->getCourse()->getType()) {
            throw new ExceptionAccessDeniedException('This url enabled only for classic course');
        }

        return $this->render('frontend/course/_detail.html.twig', [
            'permission' => $permission,
            'content' => $this->renderView('frontend/course/_theme-list.html.twig', [
                'themeInfo' => $this->courseThemeRepository->getCourseThemes($permission->getCourse()),
                'permission' => $permission,
            ]),
        ]);
    }

    #[Route('/preparation-interactive/{id<\d+>}/', name: 'app_frontend_preparation_interactive')]
    public function preparationInteractive(Permission $permission, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (! $this->userPermissionService->checkPermissionForUser($permission, $user, true)) {
            throw new ExceptionAccessDeniedException('Permission: ' . $permission->getId() . ' not available for user: ' . $user->getId());
        }

        if (Course::INTERACTIVE !== $permission->getCourse()->getType()) {
            throw new ExceptionAccessDeniedException('This url enabled only for interactive course');
        }

        $data = $this->preparationService->getQuestionData(
            $permission,
            null,
            $request->get('page', 1),
            $request->get('perPage', 20),
        );

        return $this->render('frontend/preparation/index.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route('/preparation-one/next-page/', name: 'app_frontend_preparation_one_next_page')]
    public function preparationOneNextPage(Request $request): JsonResponse
    {
        $requestContent = json_decode($request->getContent(), true);

        /** @var User $user */
        $user = $this->getUser();

        $page = $requestContent['page'];
        $perPage = $requestContent['per_page'];
        $themeId = $requestContent['theme_id'];
        $permissionId = $requestContent['permission_id'];

        $permission = $this->permissionRepository->find($permissionId);

        if (! $permission instanceof Permission) {
            throw new NotFoundHttpException('Permission: ' . $permissionId . ' not found');
        }

        if (! $this->userPermissionService->checkPermissionForUser($permission, $user, false)) {
            throw new ExceptionAccessDeniedException('Permission: ' . $permission->getId() . ' not available for user: ' . $user->getId());
        }

        $data = $this->preparationService->getQuestionData(
            $permission,
            $themeId,
            $page,
            $perPage,
        );

        return new JsonResponse([
            'status' => 'success',
            'page' => $page + 1,
            'per_page' => $perPage,
            'total' => $data['total'],
            'content' => $this->renderView('frontend/preparation/_partial.html.twig', [
                'data' => $data,
            ]),
        ]);
    }

    #[Route('/preparation-save-history/', name: 'app_frontend_preparation_save_history', condition: 'request.isXmlHttpRequest()', methods: 'POST')]
    public function preparationSaveHistory(Request $request): JsonResponse
    {
        $response = new JsonResponse();

        $requestContent = json_decode($request->getContent(), true);

        /** @var User $user */
        $user = $this->getUser();

        $permission = $this->permissionRepository->find($requestContent['permissionId']);
        if ($permission->getUser()->getId() !== $user->getId()) {
            return $response;
        }

        $preparationHistory = $this->preparationHistoryRepository->getPreparationHistory($permission);
        if (null === $preparationHistory) {
            return $response;
        }

        $content = $preparationHistory->getContent();
        $content[$requestContent['questionId']] = $requestContent['answers'];
        $preparationHistory->setContent($content);

        $this->preparationHistoryRepository->save($preparationHistory, true);

        return $response;
    }
}
