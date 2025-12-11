<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Entity\Logger;
use App\Entity\Permission;
use App\Entity\User;
use App\Form\Admin\PermissionBatchCreateType;
use App\Form\Admin\PermissionEditType;
use App\Repository\PermissionHistoryRepository;
use App\Repository\PermissionRepository;
use App\Repository\UserRepository;
use App\Service\BatchCreatePermissionService;
use App\Service\TestingReportService;
use App\Service\TestingService;
use App\Service\UserPermissionService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PermissionController extends MobileController
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
        private readonly TestingService $testingService,
        private readonly UserRepository $userRepository,
        private readonly BatchCreatePermissionService $batchCreatePermissionService,
        private readonly UserPermissionService $userPermissionService,
        private readonly TestingReportService $reportService,
        private readonly PermissionHistoryRepository $permissionHistoryRepository,
    ) {}

    #[Route('/admin/permission/create/{id<\d+>}/', name: 'admin_permission_create')]
    public function adminPermissionCreate(Request $request, User $user): Response
    {
        $permission = new Permission();

        $permission
            ->setUser($user)
            ->setCreatedBy($this->getUser());

        $form = $this->createForm(PermissionEditType::class, $permission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->permissionRepository->save($permission, true);

            $this->addFlash('success', 'Доступ добавлен');

            return $this->redirect(
                $this->generateUrl('admin_user_edit', ['id' => $user->getId()])
            );
        }

        return $this->mobileRender('admin/permission/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/permission/batch_create/{id<\d+>}/', name: 'admin_permission_batch_create')]
    public function adminPermissionBatchCreate(Request $request, User $user): Response
    {
        $form = $this->createForm(PermissionBatchCreateType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = [
                'duration' => $form->get('duration')->getData(),
                'orderNom' => $form->get('orderNom')->getData(),
                'course' => $form->get('course')->getData(),
                'user' => $user,
                'creator' => $this->getUser(),
            ];

            if (count($data['course']) >= 50) {
                $this->addFlash('error', 'Вы выбрали более 50 курсов одновременно. Максимальное количество - 50 курсов');
            }
            else {
                $this->batchCreatePermissionService->batchCreatePermission($data);

                $this->addFlash('success', 'Доступы успешно созданы');

                return $this->redirect(
                    $this->generateUrl('admin_user_edit', ['id' => $user->getId()])
                );
            }
        }

        return $this->mobileRender('admin/permission/create-batch.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/admin/permission/{id<\d+>}/', name: 'admin_permission_edit')]
    public function adminPermissionEdit(Request $request, Permission $permission): Response
    {
        $form = $this->createForm(PermissionEditType::class, $permission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->permissionRepository->save($permission, true);

            $this->addFlash('success', 'Доступ обновлен');

            return $this->redirect(
                $this->generateUrl('admin_user_edit', ['id' => $permission->getUser()->getId()])
            );
        }

        return $this->mobileRender('admin/permission/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/permission/delete/{id<\d+>}/', name: 'admin_permission_delete')]
    public function adminPermissionDelete(Permission $permission): Response
    {
        $userId = $permission->getUser()->getId();

        $this->permissionRepository->remove($permission, true);

        $this->addFlash('success', 'Доступ удален');

        return $this->redirect(
            $this->generateUrl('admin_user_edit', ['id' => $userId])
        );
    }

    #[Route('/admin/permission/history/{id<\d+>}/', name: 'admin_permission_clear_history')]
    public function adminPermissionClearHistory(Permission $permission): Response
    {
        $userId = $permission->getUser()->getId();

        $permission->setHistory($this->userPermissionService->createHistory($permission));

        $this->permissionRepository->save($permission, true);

        $this->addFlash('success', 'История обучения сброшена');

        return $this->redirect(
            $this->generateUrl('admin_user_edit', ['id' => $userId])
        );
    }

    #[Route('/admin/permission/print/{id<\d+>}/{userId<\d+>}/', name: 'admin_print_testing')]
    public function printTesting(Permission $permission, int $userId): BinaryFileResponse
    {
        $user = $this->userRepository->find($userId);
        if (! $user instanceof User) {
            throw new NotFoundHttpException('User Not Found');
        }

        $logger = $this->testingService->getFirstSuccessfullyLogger($permission, $user);

        if ($logger instanceof Logger) {
            $fileName = $this->reportService->generateTestingPdf($logger);

            $response = new BinaryFileResponse($fileName);
            $response->headers->set('Content-Type', 'application/pdf');
            $response
                ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'protocol.pdf')
                ->deleteFileAfterSend(true);

            return $response;

        } else {
            throw new NotFoundHttpException('Logger with success not found');
        }
    }

    #[Route('/admin/permission/add-duration/', name: 'admin_permission_add_duration', condition: 'request.isXmlHttpRequest()'), IsGranted('ROLE_ADMIN')]
    public function addDuration(Request $request): JsonResponse
    {
        $permissionId = (int) $request->query->get('permissionId');
        $duration = $request->query->get('duration');

        $permission = $this->permissionRepository->find($permissionId);
        if (! $permission instanceof Permission) {
            throw new NotFoundHttpException('Permission Not Found');
        }

        if ($permission->getDuration() + $duration <= Permission::MAX_DURATION) {
            $permission->setDuration($permission->getDuration() + $duration);

            $this->permissionRepository->save($permission, true);
        }

        $content = $this->renderView('/admin/user/_permission_history.html.twig', [
            'permissionId' => $permissionId,
            'permissionsHistories' => $this->permissionHistoryRepository->getPermissionsHistories($permissionId),
        ]);

        return new JsonResponse([
            'duration' => $permission->getDuration(),
            'content' => $content,
        ]);
    }
}
