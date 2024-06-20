<?php

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Repository\ActionLogRepository;
use App\Service\TestingHistoryService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ActionLogController extends MobileController
{
    public function __construct(
        private readonly ActionLogRepository $actionLogRepository,
        private readonly TestingHistoryService $testingHistoryService,
        private readonly PaginatorInterface $paginator,
    ) {}

    #[Route('/admin/action-log/', name: 'admin_action_log')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function getActionLog(Request $request, PaginatorInterface $paginator): Response
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

    #[Route('/admin/testing-history/', name: 'admin_testing_history')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function getTestingHistory(): Response
    {
        $results = $this->testingHistoryService->testingHistoryService();

        return $this->mobileRender('admin/testing-history/index.html.twig', [
            'results' => $results,
        ]);
    }
}
