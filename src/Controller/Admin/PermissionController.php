<?php

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Entity\Permission;
use App\Entity\User;
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

    /**
     * @param Request $request
     * @param User $user
     * @return Response
     */
    #[Route('/admin/permission/create/{id<\d+>}', name: 'admin_permission_create')]
    public function adminPermissionCreate(Request $request, User $user): Response
    {
        // if (!$user) {
        //     throw new NotFoundHttpException('User Not Found');
        // }

        $permission = new Permission();
        $permission->setCreatedAt(new DateTime());
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
}
