<?php

namespace App\Controller\Admin;

use App\Service\ReportGenerator\ReportGeneratorService;
use App\Service\ReportGenerator\StatisticGeneratorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

class UserReportController extends AbstractController
{
    public function __construct(
        private readonly StatisticGeneratorService $statisticGeneratorService,
        private readonly ReportGeneratorService $reportGeneratorService,
    ) {}

    #[Route('/admin/user/report/statistic/to_pdf/', name: 'admin_user_report_statistic_to_pdf')]
    public function adminUserReportStatisticToPdf(Request $request): Response
    {
        $user = $this->getUser();
        $criteria = $request->get('criteria');

        try {
            $fileName = $this->statisticGeneratorService->generateDocument($user, 'PDF', $criteria);
        } catch (Throwable) {
            $this->addFlash('error', 'Файл не был сформирован, т.к. в выгрузке присутствуют слушатели, у которых не назначены доступы');

            return $this->redirectToRoute('admin_user_list', $criteria);
        }

        $response = new BinaryFileResponse($fileName);
        $response->headers->set('Content-Type', 'application/pdf');
        $response
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'statistic.pdf')
            ->deleteFileAfterSend(true);

        return $response;
    }

    #[Route('/admin/user/report/statistic/to_docx/', name: 'admin_user_report_statistic_to_docx')]
    public function adminUserReportStatisticToDocx(Request $request): Response
    {
        $user = $this->getUser();
        $criteria = $request->get('criteria');

        try {
            $fileName = $this->statisticGeneratorService->generateDocument($user, 'DOCX', $criteria);
        } catch (Throwable) {
            $this->addFlash('error', 'Файл не был сформирован, т.к. в выгрузке присутствуют слушатели, у которых не назначены доступы');

            return $this->redirectToRoute('admin_user_list', $criteria);
        }

        $response = new BinaryFileResponse($fileName);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $response
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'statistic.docx')
            ->deleteFileAfterSend(true);

        return $response;
    }

    #[Route('/admin/user/report/statistic/to_xlsx/', name: 'admin_user_report_statistic_to_xlsx')]
    public function adminUserReportStatisticToXlsx(Request $request): Response
    {
        $user = $this->getUser();
        $criteria = $request->get('criteria');

        try {
            $fileName = $this->statisticGeneratorService->generateDocument($user, 'XLSX', $criteria);
        } catch (Throwable) {
            $this->addFlash('error', 'Файл не был сформирован, т.к. в выгрузке присутствуют слушатели, у которых не назначены доступы');

            return $this->redirectToRoute('admin_user_list', $criteria);
        }

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
        $user = $this->getUser();
        $criteria = $request->get('criteria');

        $fileName = $this->reportGeneratorService->generateDocument($user, 'CSV', $criteria);
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
        $user = $this->getUser();
        $criteria = $request->get('criteria');

        $fileName = $this->reportGeneratorService->generateDocument($user, 'XLSX', $criteria);
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
        $user = $this->getUser();
        $criteria = $request->get('criteria');

        $fileName = $this->reportGeneratorService->generateDocument($user, 'TXT', $criteria);
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
        $user = $this->getUser();
        $criteria = $request->get('criteria');

        $fileName = $this->reportGeneratorService->generateDocument($user, 'DOCX', $criteria);
        $response = new BinaryFileResponse($fileName);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $response
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'report.docx')
            ->deleteFileAfterSend(true);

        return $response;
    }
}
