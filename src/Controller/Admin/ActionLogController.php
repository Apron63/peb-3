<?php

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Repository\ActionLogRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ActionLogController extends MobileController
{
    public function __construct(
        private readonly ActionLogRepository $actionLogRepository,
        private readonly PaginatorInterface $paginator,
    ) {}

    #[Route('/admin/action-log/', name: 'admin_action_log')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $pagination = $paginator->paginate(
            $this->actionLogRepository->getActionLogQuery(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->mobileRender('admin/action-log/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }
}
