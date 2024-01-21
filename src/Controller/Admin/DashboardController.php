<?php

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Entity\MailingQueue;
use App\Entity\User;
use App\Form\Admin\DashboardType;
use App\Repository\MailingQueueRepository;
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

    #[Route('/admin/dashboard/maillist/', name: 'admin_dashboard_mail_list')]
    #[IsGranted('ROLE_ADMIN')]
    public function mailList(Request $request): Response
    {
        $mailUser = null;
        $currentUser = $this->getUser();

        $userRoles = $currentUser->getRoles();

        if (! in_array(User::ROLE_SUPER_ADMIN, $userRoles)) {
            $mailUser = $currentUser;
        }

        $pagination = $this->paginator->paginate(
            $this->mailingQueueRepository->getMailQuery($mailUser),
            $request->query->getInt('page', 1),
            10
        );

        return $this->mobileRender('admin/dashboard/mail-list.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/admin/dashboard/maillist/detail/{id<\d+>}/', name: 'admin_dashboard_mail_list_detail')]
    #[IsGranted('ROLE_ADMIN')]
    public function mailListDetail(MailingQueue $mail): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $userRoles = $currentUser->getRoles();

        if (
            ! in_array(User::ROLE_SUPER_ADMIN, $userRoles)
            && (null === $mail->getCreatedBy() || $mail->getCreatedBy()->getId() !== $currentUser->getId())
        ) {
            return $this->redirectToRoute('admin_dashboard_mail_list');
        }

        return $this->mobileRender('admin/dashboard/mail-detail.html.twig', [
            'mail' => $mail,
        ]);
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
