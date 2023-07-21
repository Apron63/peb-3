<?php

namespace App\Controller\Admin;

use App\Service\AdminReportService;
use App\Repository\PermissionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserReportAndSendController extends AbstractController
{
    public function __construct(
        private readonly AdminReportService $reportService,
        private readonly PermissionRepository $permissionRepository,
    ) {}
    
    #[Route('/admin/user/report/send/get_content/', name: 'admin_user_report_send_get_content', condition: 'request.isXmlHttpRequest()')]
    public function adminGetModalContent():JsonResponse
    {
        $content = $this->renderView('admin/user/_send_email.html.twig');

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
            $data = $this->permissionRepository->getUserSearchQuery($criteria['user_search'])->getResult();
        }

        return new JsonResponse(
            $this->reportService->generateListAndSend($recipient, $subject, $comment, $type, $data)
        );
    }

    // #[Route('/admin/user/report/send/to_csv/', name: 'admin_user_report_send_to_csv')]
    // public function adminUserReportSendToCsv(Request $request): BinaryFileResponse
    // {
    //     $criteria = $request->get('criteria');

    //     if (!empty($criteria)) {
    //         $data = $this->permissionRepository->getUserSearchQuery($criteria['user_search'])->getResult();
    //     }

    //     $fileName = $this->reportService->generateListAndSendCSV($data);
    //     $response = new BinaryFileResponse($fileName);
    //     $response->headers->set('Content-Type', 'application/octet-stream');
    //     $response
    //         ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'mail.eml')
    //         ->deleteFileAfterSend(true);

    //     return $response;
    // }

    // #[Route('/admin/user/report/send/to_xlsx/', name: 'admin_user_report_send_to_xlsx')]
    // public function adminUserReportSendToXlsx(Request $request): BinaryFileResponse
    // {
    //     $criteria = $request->get('criteria');

    //     if (!empty($criteria)) {
    //         $data = $this->permissionRepository->getUserSearchQuery($criteria['user_search'])->getResult();
    //     }

    //     $fileName = $this->reportService->generateListXLSX($data);
    //     $response = new BinaryFileResponse($fileName);
    //     $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    //     $response
    //         ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'report.xlsx')
    //         ->deleteFileAfterSend(true);

    //     return $response;
    // }

    // #[Route('/admin/user/report/send/to_txt/', name: 'admin_user_report_send_to_txt')]
    // public function adminUserReportSendToTxt(Request $request): BinaryFileResponse
    // {
    //     $criteria = $request->get('criteria');

    //     if (!empty($criteria)) {
    //         $data = $this->permissionRepository->getUserSearchQuery($criteria['user_search'])->getResult();
    //     }
    //     $fileName = $this->reportService->generateListTXT($data);
    //     $response = new BinaryFileResponse($fileName);
    //     $response->headers->set('Content-Type', 'text/plain');
    //     $response
    //         ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'report.txt')
    //         ->deleteFileAfterSend(true);

    //     return $response;
    // }

    // #[Route('/admin/user/report/send/to_docx/', name: 'admin_user_report_send_to_docx')]
    // public function adminUserReportSendToDocx(Request $request): BinaryFileResponse
    // {
    //     $criteria = $request->get('criteria');

    //     if (!empty($criteria)) {
    //         $data = $this->permissionRepository->getUserSearchQuery($criteria['user_search'])->getResult();
    //     }

    //     $fileName = $this->reportService->generateListDocx($data);
    //     $response = new BinaryFileResponse($fileName);
    //     $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    //     $response
    //         ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'report.docx')
    //         ->deleteFileAfterSend(true);

    //     return $response;
    // }
}
