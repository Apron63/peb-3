<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Service\AdminReportService;
use App\Form\Admin\SendListToEmailType;
use App\Repository\PermissionRepository;
use App\Service\ConfigService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserReportAndSendController extends AbstractController
{
    public function __construct(
        private readonly AdminReportService $reportService,
        private readonly PermissionRepository $permissionRepository,
        private readonly ConfigService $configService,
    ) {}
    
    #[Route('/admin/user/report/send/get_content/', name: 'admin_user_report_send_get_content', condition: 'request.isXmlHttpRequest()')]
    public function adminGetModalContent():JsonResponse
    {
        $data = [
            'emails' => '',
            'subject' => '',
            'comment' => $this->replaceValue($this->configService->getConfigValue('emailAttachmentResultText')),
        ];

        $content = $this->renderView('admin/user/_send_email.html.twig', [
            'form' => $this->createForm(SendListToEmailType::class, $data)->createView(),
        ]);

        return new JsonResponse(['data' => $content]);
    }
    
    #[Route('/admin/user/report/send/get_content_statistic/', name: 'admin_user_report_send_get_content_statistic', condition: 'request.isXmlHttpRequest()')]
    public function adminGetModalContentStatictic():JsonResponse
    {
        $data = [
            'emails' => '',
            'subject' => '',
            'comment' => $this->replaceValue($this->configService->getConfigValue('emailAttachmentStatisticText')),
        ];

        $content = $this->renderView('admin/user/_send_email_statistic.html.twig', [
            'form' => $this->createForm(SendListToEmailType::class, $data)->createView(),
        ]);

        return new JsonResponse(['data' => $content]);
    }
    
    #[Route('/admin/user/report/send_letter_to_client/', name: 'admin_user_report_send_letter_to_client', condition: 'request.isXmlHttpRequest()')]
    public function adminSendLetterToClient(Request $request):JsonResponse
    {
        $recipient = $request->get('recipient');
        $subject = $request->get('subject');
        $comment = $request->get('comment');
        $type = $request->get('type');
        $criteria = $request->get('criteria');

        if (!empty($criteria)) {
            $data = $this->permissionRepository->getUserSearchQuery($criteria['user_search'], true)->getResult();
        }

        return new JsonResponse(
            $this->reportService->generateListAndSend($recipient, $subject, $comment, $type, $data)
        );
    }
    
    #[Route('/admin/user/report/send_statistic_to_client/', name: 'admin_user_report_send_statistic_to_client', condition: 'request.isXmlHttpRequest()')]
    public function adminSendStatisticToClient(Request $request):JsonResponse
    {
        $recipient = $request->get('recipient');
        $subject = $request->get('subject');
        $comment = $request->get('comment');
        $type = $request->get('type');
        $criteria = $request->get('criteria');

        if (!empty($criteria)) {
            $data = $this->permissionRepository->getUserSearchQuery($criteria['user_search'], true)->getResult();
        }

        return new JsonResponse(
            $this->reportService->generateListAndSendStatistic($recipient, $subject, $comment, $type, $data)
        );
    }

    public function replaceValue(string $source): string
    {
        $result = $source;

        $user = $this->getUser();

        if ($user instanceof User) {
            $result = str_replace(
                [
                    '{FIO}',
                    '{PHONE}',
                    '{EMAIL}',
                ],
                [
                    $user->getFullName(),
                    $user->getContact(),
                    $user->getEmail(),
                ], 
                $source
            );
        }

        return $result;
    }
}
