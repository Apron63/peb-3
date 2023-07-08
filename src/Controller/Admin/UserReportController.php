<?php

namespace App\Controller\Admin;

use App\Repository\PermissionRepository;
use App\Service\AdminReportService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserReportController extends AbstractController
{
    public function __construct(
        private readonly AdminReportService $reportService,
        private readonly PermissionRepository $permissionRepository,
    ) {}

    #[Route('/admin/user/report/statistic/to_pdf/', name: 'admin_user_report_stattistic_to_pdf')]
    public function adminUserReportStatisticToPdf(Request $request): BinaryFileResponse
    {
        $criteria = $request->get('criteria');

        if (!empty($criteria)) {
            $data = $this->permissionRepository->getUserSearchQuery($criteria['user_search'], true)->getResult();
        }

        $fileName = $this->reportService->generateStatisticPdf($data);
        $response = new BinaryFileResponse($fileName);
        $response->headers->set('Content-Type', 'application/pdf');
        $response
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'statistic.pdf')
            ->deleteFileAfterSend(true);

        return $response;
    }
    
    #[Route('/admin/user/report/statistic/to_docx/', name: 'admin_user_report_stattistic_to_docx')]
    public function adminUserReportStatisticToDocx(Request $request): BinaryFileResponse
    {
        $criteria = $request->get('criteria');

        if (!empty($criteria)) {
            $data = $this->permissionRepository->getUserSearchQuery($criteria['user_search'], true)->getResult();
        }

        $fileName = $this->reportService->generateStatisticDocx($data);
        $response = new BinaryFileResponse($fileName);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $response
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'statistic.docx')
            ->deleteFileAfterSend(true);

        return $response;
    }

    #[Route('/admin/user/report/statistic/to_xlsx/', name: 'admin_user_report_stattistic_to_xlsx')]
    public function adminUserReportStatisticToXlsx(Request $request): BinaryFileResponse
    {
        $criteria = $request->get('criteria');

        if (!empty($criteria)) {
            $data = $this->permissionRepository->getUserSearchQuery($criteria['user_search'], true)->getResult();
        }

        $fileName = $this->reportService->generateStatisticXlsx($data);
        $response = new BinaryFileResponse($fileName);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'statistic.xlsx')
            ->deleteFileAfterSend(true);

        return $response;
    }

    #[Route('/admin/user/report/list/to_csv/', name: 'admin_user_report_list_to_csv')]
    public function adminUserReportListToCsv(Request $request): BinaryFileResponse
    {
        $criteria = $request->get('criteria');

        if (!empty($criteria)) {
            $data = $this->permissionRepository->getUserSearchQuery($criteria['user_search'])->getResult();
        }

        $fileName = $this->reportService->generateListCSV($data);
        $response = new BinaryFileResponse($fileName);
        $response->headers->set('Content-Type', 'text/csv');
        $response
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'report.csv')
            ->deleteFileAfterSend(true);

        return $response;
    }

    #[Route('/admin/user/report/list/to_xlsx/', name: 'admin_user_report_list_to_xlsx')]
    public function adminUserReportListToXlsx(Request $request): BinaryFileResponse
    {
        $criteria = $request->get('criteria');

        if (!empty($criteria)) {
            $data = $this->permissionRepository->getUserSearchQuery($criteria['user_search'])->getResult();
        }

        $fileName = $this->reportService->generateListXLSX($data);
        $response = new BinaryFileResponse($fileName);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'report.xlsx')
            ->deleteFileAfterSend(true);

        return $response;
    }

    #[Route('/admin/user/report/list/to_txt/', name: 'admin_user_report_list_to_txt')]
    public function adminUserReportListToTxt(Request $request): BinaryFileResponse
    {
        $criteria = $request->get('criteria');

        if (!empty($criteria)) {
            $data = $this->permissionRepository->getUserSearchQuery($criteria['user_search'])->getResult();
        }
        $fileName = $this->reportService->generateListTXT($data);
        $response = new BinaryFileResponse($fileName);
        $response->headers->set('Content-Type', 'text/plain');
        $response
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'report.txt')
            ->deleteFileAfterSend(true);

        return $response;
    }

    #[Route('/admin/user/report/list/to_docx/', name: 'admin_user_report_list_to_docx')]
    public function adminUserReportListToDocx(Request $request): BinaryFileResponse
    {
        $criteria = $request->get('criteria');

        if (!empty($criteria)) {
            $data = $this->permissionRepository->getUserSearchQuery($criteria['user_search'])->getResult();
        }

        $fileName = $this->reportService->generateListDocx($data);
        $response = new BinaryFileResponse($fileName);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $response
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'report.docx')
            ->deleteFileAfterSend(true);

        return $response;
    }
}
