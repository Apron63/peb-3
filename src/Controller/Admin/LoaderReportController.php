<?php

namespace App\Controller\Admin;

use App\Service\LoaderReportService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LoaderReportController extends AbstractController
{
    public function __construct(
        private readonly LoaderReportService $reportService,

    ) {}

    #[Route('/admin/loader/report/to_csv/', name: 'admin_loader_report_to_csv')]
    public function adminLoaderReportToCsv(): BinaryFileResponse
    {
        $user = $this->getUser();
        $fileName = $this->reportService->generateCSV($user);
        $response = new BinaryFileResponse($fileName);
        $response->headers->set('Content-Type', 'text/csv');
        $response
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'report.csv')
            ->deleteFileAfterSend(true);

        return $response;
    }
    
    #[Route('/admin/loader/report/to_xlsx/', name: 'admin_loader_report_to_xlsx')]
    public function adminLoaderReportToXlsx(): BinaryFileResponse
    {
        $user = $this->getUser();
        $fileName = $this->reportService->generateXLSX($user);
        $response = new BinaryFileResponse($fileName);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'report.xlsx')
            ->deleteFileAfterSend(true);

        return $response;
    }

    #[Route('/admin/loader/report/to_txt/', name: 'admin_loader_report_to_txt')]
    public function adminLoaderReportToTxt(): BinaryFileResponse
    {
        $user = $this->getUser();
        $fileName = $this->reportService->generateTXT($user);
        $response = new BinaryFileResponse($fileName);
        $response->headers->set('Content-Type', 'text/plain');
        $response
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'report.txt')
            ->deleteFileAfterSend(true);

        return $response;
    }

    #[Route('/admin/loader/report/to_docx/', name: 'admin_loader_report_to_docx')]
    public function adminLoaderReportToDocx(): BinaryFileResponse
    {
        $user = $this->getUser();
        $fileName = $this->reportService->generateDOCX($user);
        $response = new BinaryFileResponse($fileName);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $response
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'report.docx')
            ->deleteFileAfterSend(true);

        return $response;
    }
}
