<?php

namespace App\Controller\Admin;

use App\Service\ConfigService;
use App\Form\Admin\DashboardType;
use App\Service\DashboardService;
use App\Decorator\MobileController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends MobileController
{
    public function __construct(
        private readonly DashboardService $dashboardService,
        private readonly ConfigService $configService,
    ) {}

    #[Route('/admin/dashboard/', name: 'admin_dashboard')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function index(Request $request): Response
    {
        $form = $this->createForm(DashboardType::class);
        $form
            ->get('emailAttachmentStatisticText')
            ->setData($this->configService->getConfigValue('emailAttachmentStatisticText'));
        $form
            ->get('emailAttachmentResultText')
            ->setData($this->configService->getConfigValue('emailAttachmentResultText'));
        $form
            ->get('userHasNewPermission')
            ->setData($this->configService->getConfigValue('userHasNewPermission'));
        $form
            ->get('userHasActivatedPermission')
            ->setData($this->configService->getConfigValue('userHasActivatedPermission'));
        $form
            ->get('permissionWillEndSoon')
            ->setData($this->configService->getConfigValue('permissionWillEndSoon'));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->configService->setConfigValue('emailAttachmentStatisticText', $form->get('emailAttachmentStatisticText')->getData());
            $this->configService->setConfigValue('emailAttachmentResultText', $form->get('emailAttachmentResultText')->getData());
            $this->configService->setConfigValue('userHasNewPermission', $form->get('userHasNewPermission')->getData());
            $this->configService->setConfigValue('userHasActivatedPermission', $form->get('userHasActivatedPermission')->getData());
            $this->configService->setConfigValue('permissionWillEndSoon', $form->get('permissionWillEndSoon')->getData());
        }

        return $this->mobileRender('admin/dashboard/index.html.twig', [
            'data' => $this->dashboardService->prepareData(),
            'form' => $form->createView(),
        ]);
    }
}
