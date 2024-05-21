<?php

namespace App\Controller\Frontend;

use App\Entity\Permission;
use App\Entity\User;
use App\Service\PermissionService;
use App\Service\UserPermissionService;
use App\Repository\PermissionRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class TimeControlController extends AbstractController
{
    public function __construct(
        private readonly UserPermissionService $userPermissionService,
        private readonly PermissionRepository $permissionRepository,
        private readonly PermissionService $permissionService,
        private readonly Security $security,
    ) {}

    #[Route('/frontend/timing/', name: 'app_frontend_timing', methods: 'POST', condition: 'request.isXmlHttpRequest()')]
    public function index(Request $request): Response
    {
        $permissionId = $request->get('permissionId');

        $permission = $this->permissionRepository->find($permissionId);

        if (! $permission instanceof Permission) {
            throw new NotFoundHttpException('Permission Not Found');
        }

        $user = $this->getUser();

        if (! $user instanceof User) {
            throw new AccessDeniedException('No Auth User');
        }

        if (! $this->userPermissionService->checkPermissionForUser($permission, $user, false)) {
            throw new AccessDeniedException('Permission: ' . $permission->getId() . ' not available for user: ' . $user->getId());
        }

        $startTime = (int) $request->get('startTime');
        $this->permissionService->setTimeSpent($permission, $startTime);

        $needRestart = $request->get('logout', 'false');

        if ($needRestart === strtolower('true')) {
            $this->security->logout(false);
        }

        return new Response();
    }
}
