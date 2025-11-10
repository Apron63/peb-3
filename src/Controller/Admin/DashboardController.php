<?php

declare (strict_types=1);

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Entity\MailingQueue;
use App\Entity\WhatsappQueue;
use App\Form\Admin\DashboardType;
use App\Form\Admin\MainlingQueueSearchType;
use App\Form\Admin\WhatsappQueueSearchType;
use App\Repository\MailingQueueRepository;
use App\Repository\WhatsappQueueRepository;
use App\Service\ConfigService;
use App\Service\DashboardService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends MobileController
{
    public function __construct(
        private readonly DashboardService $dashboardService,
        private readonly ConfigService $configService,
        private readonly PaginatorInterface $paginator,
        private readonly MailingQueueRepository $mailingQueueRepository,
        private readonly WhatsappQueueRepository $whatsappQueueRepository,
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
        $form
            ->get('userHasNewPermissionWhatsapp')
            ->setData($this->configService->getConfigValue('userHasNewPermissionWhatsapp'));
        $form
            ->get('userHasActivatedPermissionWhatsapp')
            ->setData($this->configService->getConfigValue('userHasActivatedPermissionWhatsapp'));
        $form
            ->get('permissionWillEndSoonWhatsapp')
            ->setData($this->configService->getConfigValue('permissionWillEndSoonWhatsapp'));
        $form
            ->get('userHasNewPermissionMax')
            ->setData($this->configService->getConfigValue('userHasNewPermissionMax'));
        $form
            ->get('userHasActivatedPermissionMax')
            ->setData($this->configService->getConfigValue('userHasActivatedPermissionMax'));
        $form
            ->get('permissionWillEndSoonMax')
            ->setData($this->configService->getConfigValue('permissionWillEndSoonMax'));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->configService->setConfigValue('emailAttachmentStatisticText', $form->get('emailAttachmentStatisticText')->getData());
            $this->configService->setConfigValue('emailAttachmentResultText', $form->get('emailAttachmentResultText')->getData());
            $this->configService->setConfigValue('userHasNewPermission', $form->get('userHasNewPermission')->getData());
            $this->configService->setConfigValue('userHasActivatedPermission', $form->get('userHasActivatedPermission')->getData());
            $this->configService->setConfigValue('permissionWillEndSoon', $form->get('permissionWillEndSoon')->getData());
            $this->configService->setConfigValue('userHasNewPermissionwhatsapp', $form->get('userHasNewPermissionWhatsapp')->getData());
            $this->configService->setConfigValue('userHasActivatedPermissionWhatsapp', $form->get('userHasActivatedPermissionWhatsapp')->getData());
            $this->configService->setConfigValue('permissionWillEndSoonWhatsapp', $form->get('permissionWillEndSoonWhatsapp')->getData());
            $this->configService->setConfigValue('userHasNewPermissionMax', $form->get('userHasNewPermissionMax')->getData());
            $this->configService->setConfigValue('userHasActivatedPermissionMax', $form->get('userHasActivatedPermissionMax')->getData());
            $this->configService->setConfigValue('permissionWillEndSoonMax', $form->get('permissionWillEndSoonMax')->getData());
        }

        return $this->mobileRender('admin/dashboard/index.html.twig', [
            'data' => $this->dashboardService->prepareData(),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/dashboard/maillist/', name: 'admin_dashboard_mail_list')]
    #[IsGranted('ROLE_ADMIN')]
    public function mailList(Request $request): Response
    {
        $form = $this->createForm(MainlingQueueSearchType::class);
        $form->handleRequest($request);

        $sender = $form->get('sender')->getData();
        $userName = $form->get('userName')->getData();
        $email = $form->get('email')->getData();

        $pagination = $this->paginator->paginate(
            $this->mailingQueueRepository->getMailQuery($sender, $userName, $email),
            $request->query->getInt('page', 1),
            10
        );

        return $this->mobileRender('admin/dashboard/mail-list.html.twig', [
            'form' => $form->createView(),
            'pagination' => $pagination,
        ]);
    }

    #[Route('/admin/dashboard/whatsapplist/', name: 'admin_dashboard_whatsapp_list')]
    #[IsGranted('ROLE_ADMIN')]
    public function whatsapplList(Request $request): Response
    {
        $form = $this->createForm(WhatsappQueueSearchType::class);
        $form->handleRequest($request);

        $sender = $form->get('sender')->getData();
        $userName = $form->get('userName')->getData();
        $phone = $form->get('phone')->getData();

        $pagination = $this->paginator->paginate(
            $this->whatsappQueueRepository->getWhatsappQuery($sender, $userName, $phone),
            $request->query->getInt('page', 1),
            10
        );

        return $this->mobileRender('admin/dashboard/whatsapp-list.html.twig', [
            'form' => $form->createView(),
            'pagination' => $pagination,
        ]);
    }

    #[Route('/admin/dashboard/maillist/detail/{id<\d+>}/', name: 'admin_dashboard_mail_list_detail')]
    #[IsGranted('ROLE_ADMIN')]
    public function mailListDetail(MailingQueue $mail): Response
    {
        return $this->mobileRender('admin/dashboard/mail-detail.html.twig', [
            'mail' => $mail,
        ]);
    }

    #[Route('/admin/dashboard/whatsapp/detail/{id<\d+>}/', name: 'admin_dashboard_whatsapp_list_detail')]
    #[IsGranted('ROLE_ADMIN')]
    public function whatsappListDetail(WhatsappQueue $message): Response
    {
        return $this->mobileRender('admin/dashboard/whatsapp-detail.html.twig', [
            'message' => $message,
        ]);
    }

    #[Route('/admin/dashboard/whatsapp/delete/{id<\d+>}/', name: 'admin_dashboard_whatsapp_list_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function whatsappListDelete(WhatsappQueue $message): Response
    {
        $this->whatsappQueueRepository->remove($message, true);

        $this->addFlash('success', 'Удален элемент рассылки');

        return $this->redirectToRoute('admin_dashboard_whatsapp_list');
    }

    #[Route('/admin/dashboard/query_user/clear/', name: 'admin_dashboard_query_user_clear')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function queryUserClear(): Response
    {
        $this->dashboardService->queryUserClear();

        $this->addFlash('success', 'Очередь создания слушателей успешно очищена');

        return $this->redirectToRoute('admin_dashboard');
    }
}
