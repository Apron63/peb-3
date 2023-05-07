<?php

namespace App\Controller\Frontend;

use App\Entity\Permission;
use App\Service\PermissionService;
use App\Service\UserPermissionService;
use App\Repository\PermissionRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException as ExceptionAccessDeniedException;

class TimeControlController extends AbstractController
{
    public function __construct(
        private readonly UserPermissionService $userPermissionService,
        private readonly PermissionRepository $permissionRepository,
        private readonly PermissionService $permissionService,
        private readonly Security $security,
    ) {}

    #[Route('/frontend/timing/', name: 'app_frontend_timing', condition: 'request.isXmlHttpRequest()', methods: 'POST')]
    public function index(Request $request): Response
    {
        $permissionId = $request->get('permissionId');

        $permission = $this->permissionRepository->find($permissionId);

        if (!$permission instanceof Permission) {
            throw new NotFoundHttpException();
        }

        $user = $this->getUser();

        if (!$this->userPermissionService->checkPermissionForUser($permission, $user, false)) {
            throw new ExceptionAccessDeniedException();
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
