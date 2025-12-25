<?php

declare(strict_types=1);

namespace App\Controller\Frontend;

use App\Entity\Permission;
use App\Entity\User;
use App\Service\FavoritesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class FavoritesController extends AbstractController
{
    public function __construct(
        private readonly FavoritesService $favoritesService,
    ) {}

    #[Route('/frontend/favorites/set/{id<\d+>}/{questionId<\d+>}/', name: 'app_frontend_favorites_set', condition: 'request.isXmlHttpRequest()', methods: 'POST')]
    public function setFavorites(Permission $permission, int $questionId): JsonResponse
    {
        $user = $this->getUser();

        if (! $user instanceof User) {
            throw new NotFoundHttpException('User Not Found');
        }

        if ($user->getId() !== $permission->getUser()->getId()) {
            throw new AccessDeniedException('Permission : ' . $permission->getId() . ' not available for user : ' . $user->getId());
        }

        if (! $permission->isSurveyEnabled()) {
            throw new AccessDeniedException('Survey not enebled for permission: ' . $permission->getId());
        }

        $html = $this->favoritesService->setFavorites($permission, $questionId);

        return new JsonResponse([
            'html' => $html,
        ]);
    }

    #[Route('/frontend/favorites/list/{id<\d+>}/', name: 'app_frontend_favorites_list')]
    public function listFavorites(Permission $permission, Request $request): Response
    {
        $user = $this->getUser();

        if (! $user instanceof User) {
            throw new NotFoundHttpException('User Not Found');
        }

        if ($user->getId() !== $permission->getUser()->getId()) {
            throw new AccessDeniedException('Permission : ' . $permission->getId() . ' not available for user : ' . $user->getId());
        }

        if (! $permission->isSurveyEnabled()) {
            throw new AccessDeniedException('Survey not enebled for permission: ' . $permission->getId());
        }

        $data = $this->favoritesService->getFavoritesQuestionData(
            $permission,
            (int) $request->get('page', 1),
            (int) $request->get('perPage', 20),
        );

        if (empty($data)) {
            throw new NotFoundHttpException('Favorites list empty for permission: ' . $permission->getId());
        }

        return $this->render('frontend/preparation/index.html.twig', [
            'data' => $data,
        ]);
    }
}
