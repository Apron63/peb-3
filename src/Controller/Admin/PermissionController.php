<?php

namespace App\Controller\Admin;

use DateTime;
use App\Entity\User;
use App\Entity\Permission;
use App\Service\TestingService;
use App\Repository\UserRepository;
use App\Decorator\MobileController;
use App\Entity\Logger;
use App\Form\Admin\PermissionEditType;
use App\Repository\PermissionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PermissionController extends MobileController
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
        private readonly TestingService $testingService,
        private readonly UserRepository $userRepository,
    ) {}

    #[Route('/admin/permission/create/{id<\d+>}', name: 'admin_permission_create')]
    public function adminPermissionCreate(Request $request, User $user): Response
    {
        $permission = new Permission();
        if (null === $permission->getCreatedAt()) {
            $permission->setCreatedAt(new DateTime());
        }
        $permission->setUser($user);
        $form = $this->createForm(PermissionEditType::class, $permission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->permissionRepository->save($permission, true);

            return $this->redirect(
                $this->generateUrl('admin_user_edit', ['id' => $user->getId()])
            );
        }

        return $this->mobileRender('admin/permission/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/permission/{id<\d+>}/', name: 'admin_permission_edit')]
    public function adminPermissionEdit(Request $request, Permission $permission): Response
    {
        // TODO 
        $course = $permission->getCourse()->getName();
        $user = $permission->getUser()->getFullName();

        $form = $this->createForm(PermissionEditType::class, $permission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->permissionRepository->save($permission, true);

            return $this->redirect(
                $this->generateUrl('admin_user_edit', ['id' => $permission->getUser()->getId()])
            );
        }

        return $this->mobileRender('admin/permission/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/permission/delete/{id<\d+>}', name: 'admin_permission_delete')]
    public function adminPermissionDelete(Permission $permission): Response
    {
        $userId = $permission->getUser()->getId();

        $this->permissionRepository->remove($permission, true);

        return $this->redirect(
            $this->generateUrl('admin_user_edit', ['id' => $userId])
        );
    }

    #[Route('/admin/permission/print/{id<\d+>}/{userId<\d+>}/', name: 'admin_print_testing')]
    public function printTesting(Permission $permission, int $userId): Response
    {
        $user = $this->userRepository->find($userId);
        if (! $user instanceof User) {
            throw new NotFoundHttpException('User Not Found');
        }

        $logger = $this->testingService->getFirstSuccesfullyLogger($permission, $user);

        if ($logger instanceof Logger) {
            return $this->render('admin/testing/protocol.html.twig', [
                'logger' => $logger,
                'skipped' => $this->testingService->getSkippedQuestion($logger),
            ]);
        } else {
            throw new NotFoundHttpException('Logger with success not found');
        }
    }
}
