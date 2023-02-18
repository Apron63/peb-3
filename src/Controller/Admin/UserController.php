<?php

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Entity\User;
use App\Form\Admin\ActionIntervalType;
use App\Form\Admin\UserEditType;
use App\Repository\PermissionRepository;
use App\Repository\UserRepository;
use App\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

class UserController extends MobileController
{
    public function __construct(
        readonly UserService $userService,
        readonly UserRepository $userRepository,
        readonly PermissionRepository $permissionRepository
    ) {
    }

    #[Route('/admin/user/', name: 'admin_user_list')]
    public function adminUserList(Request $request, PaginatorInterface $paginator): Response
    {
        $criteria = $request->get('user_search');

        $query = $this->userRepository->getUserSearchQuery($criteria);

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->mobileRender('admin/user/list.html.twig', [
            'pagination' => $pagination
        ]);
    }

    #[Route('/admin/user/create/', name: 'admin_user_create')]
    public function adminUserCreateAction(Request $request, PaginatorInterface $paginator): Response
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
        $pagination = $paginator->paginate(
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

    /**
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @param User $user
     * @return Response
     */
    #[Route('/admin/user/{id<\d+>}/', name: 'admin_user_edit')]
    public function adminUserEditAction(Request $request, PaginatorInterface $paginator, User $user): Response
    {
        // if (!$user) {
        //     throw new NotFoundHttpException();
        // }

        $form = $this->createForm(UserEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userRepository->save($user, true);

            return $this->redirectToRoute('admin_user_list');
        }

        $query = $this->permissionRepository->getPermissionQuery($user);
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        // $loggerQuery = $this->em->getRepository(Logger::class)
        //     ->getLoggerQuery($user);

        // $loggerPagination = $paginator->paginate(
        //     $loggerQuery,
        //     $request->query->getInt('logPage', 1),
        //     10
        // );

        // $actionPagination = $paginator->paginate(
        //     $this->actionRepository->getActionQuery($user),
        //     $request->query->getInt('actPage', 1),
        //     10,
        //     ['pageParameterName' => 'actPage'],
        // );

        return $this->mobileRender('admin/user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'pagination' => $pagination,
            // 'loggerPagination' => $loggerPagination,
            // 'actionPagination' => $actionPagination,
            'actionForm' => $this->createForm(ActionIntervalType::class)->createView(),
        ]);
    }

    #[Route('/admin/user/info/{id<\d+>}', name: 'admin_user_info')]
    public function adminUserInfo(Request $request, User $user): Response
    {
        // if (!$user) {
        //     throw new NotFoundHttpException();
        // }

        return $this->mobileRender('admin/user/info.html.twig', [
            'user' => $user,
        ]);
    }
}
