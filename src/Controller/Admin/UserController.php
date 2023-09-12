<?php

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Entity\User;
use App\Form\Admin\ActionIntervalType;
use App\Form\Admin\UserEditType;
use App\Repository\PermissionRepository;
use App\Repository\UserRepository;
use App\Service\UserService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends MobileController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly UserRepository $userRepository,
        private readonly PermissionRepository $permissionRepository,
        private readonly PaginatorInterface $paginator,
    ) {}

    #[Route('/admin/user/', name: 'admin_user_list')]
    public function adminUserList(Request $request): Response
    {
        $criteria = $request->get('user_search');

        $query = $this->userRepository->getUserSearchQuery($criteria);

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10,
            ['distinct' => false],
        );

        return $this->mobileRender('admin/user/list.html.twig', [
            'pagination' => $pagination
        ]);
    }

    #[Route('/admin/user/create/', name: 'admin_user_create')]
    public function adminUserCreateAction(Request $request): Response
    {
        $user = new User();

        $form = $this->createForm(UserEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->userService->setNewUser($user);

            $this->userRepository->save($user, true);

            return $this->redirect(
                $this->generateUrl('admin_user_edit', ['id' => $user->getId()])
            );
        }

        $query = $this->permissionRepository->getPermissionQuery($user);
        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->mobileRender('admin/user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'pagination' => $pagination
        ]);
    }

    #[Route('/admin/user/{id<\d+>}/', name: 'admin_user_edit')]
    public function adminUserEditAction(Request $request, User $user): Response
    {
        $form = $this->createForm(UserEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userRepository->save($user, true);

            return $this->redirectToRoute('admin_user_list');
        }

        $query = $this->permissionRepository->getPermissionQuery($user);
        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->mobileRender('admin/user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'pagination' => $pagination,
            'actionForm' => $this->createForm(ActionIntervalType::class)->createView(),
        ]);
    }

    #[Route('/admin/user/info/{id<\d+>}/', name: 'admin_user_info')]
    public function adminUserInfo(User $user): Response
    {
        return $this->mobileRender('admin/user/info.html.twig', [
            'user' => $user,
        ]);
    }
}
