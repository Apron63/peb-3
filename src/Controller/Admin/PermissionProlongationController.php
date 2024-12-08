<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Service\PermissionProlongationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class PermissionProlongationController extends MobileController
{
    public function __construct(
        private readonly PermissionProlongationService $permissionProlongationService,
    ) {}

    #[Route('/admin/permission/check-premission/', name: 'admin_permission_check_permission', condition: 'request.isXmlHttpRequest()')]
    public function checkPermission(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $permissionId = (int) $request->get('permissionId');

        return new JsonResponse(
            $this->permissionProlongationService->checkPermission($permissionId, $user)
        );
    }

    #[Route('/admin/permission/check/select-all/', name: 'admin_permission_check_select_all')]
    public function selectPermission(Request $request): RedirectResponse
    {
        $user = $this->getUser();
        $criteria = $request->get('criteria');

        $result = $this->permissionProlongationService->selectAll($criteria, $user);

        if (null === $result) {
            $this->addFlash('success', 'Проставлена отметка для всех выбранных пользователей');
        } else {
            $this->addFlash('error', $result);
        }

        return $this->redirectToRoute('admin_user_list', $criteria);
    }

    #[Route('/admin/permission/check/cancel-select/', name: 'admin_permission_check_cancel_select')]
    public function cancelSelectPermission(Request $request): RedirectResponse
    {
        $user = $this->getUser();
        $criteria = $request->get('criteria');

        $result = $this->permissionProlongationService->cancelSelectAll($criteria, $user);

        if (null === $result) {
            $this->addFlash('success', 'Снята отметка для всех выбранных пользователей');
        } else {
            $this->addFlash('error', $result);
        }

        return $this->redirectToRoute('admin_user_list', $criteria);
    }

    #[Route('/admin/permission/prolongate/load-form/', name: 'admin_permission_prolongate_load_form', condition: 'request.isXmlHttpRequest()')]
    public function permissionProlongateLoadForm(): JsonResponse
    {
        return new JsonResponse(
            $this->renderView('admin/permission/mass-prolongate.html.twig')
        );
    }

    #[Route('/admin/permission/prolongate/action/', name: 'admin_permission_prolongate_action', condition: 'request.isXmlHttpRequest()')]
    public function permissionProlongateAction(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $duration = (int) $request->get('duration');

        $this->permissionProlongationService->permissionProlongate($duration, $user);

        return new JsonResponse('Продление достуров успешно выполнено');
    }
}
