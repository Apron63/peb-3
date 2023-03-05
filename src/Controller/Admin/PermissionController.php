<?php

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Entity\Permission;
use App\Entity\User;
use App\Form\Admin\PermissionEditType;
use App\Repository\PermissionRepository;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PermissionController extends MobileController
{
    public function __construct(
        readonly PermissionRepository $permissionRepository
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
}
