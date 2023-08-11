<?php

namespace App\Controller\Admin;

use App\Service\DashboardService;
use App\Decorator\MobileController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends MobileController
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    #[Route('/admin/dashboard/', name: 'admin_dashboard')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function index(): Response
    {
        return $this->mobileRender('admin/dashboard/index.html.twig', [
            'data' => $this->dashboardService->prepareData(),
        ]);
    }
}
