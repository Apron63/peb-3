<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use App\Service\AdminReportService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

class UserReportController extends AbstractController
{
    public function __construct(
        private readonly AdminReportService $reportService,
        private readonly UserRepository $userRepository,
    ) {}

    #[Route('/admin/user/report/statistic/to_pdf/', name: 'admin_user_report_statistic_to_pdf')]
    public function adminUserReportStatisticToPdf(Request $request): Response
    {
        $criteria = $request->get('criteria');

        if (! empty($criteria)) {
            $data = $this->userRepository->getUserSearchQuery($criteria['user_search'], true)->getResult();
        }

        try {
            $fileName = $this->reportService->generateStatisticPdf($data);

        } catch (Exception) {
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
        $criteria = $request->get('criteria');

        if (! empty($criteria)) {
            $data = $this->userRepository->getUserSearchQuery($criteria['user_search'], true)->getResult();
        }

        try {
            $fileName = $this->reportService->generateStatisticDocx($data);

        } catch (Exception) {
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
        $criteria = $request->get('criteria');

        if (! empty($criteria)) {
            $data = $this->userRepository->getUserSearchQuery($criteria['user_search'], true)->getResult();
        }

        try {
            $fileName = $this->reportService->generateStatisticXlsx($data);

        } catch (Exception) {
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
        $criteria = $request->get('criteria');

        if (! empty($criteria)) {
            $data = $this->userRepository->getUserSearchQuery($criteria['user_search'])->getResult();
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

        if (! empty($criteria)) {
            $data = $this->userRepository->getUserSearchQuery($criteria['user_search'], true)->getResult();
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

        if (! empty($criteria)) {
            $data = $this->userRepository->getUserSearchQuery($criteria['user_search'])->getResult();
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

        if (! empty($criteria)) {
            $data = $this->userRepository->getUserSearchQuery($criteria['user_search'], true)->getResult();
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
