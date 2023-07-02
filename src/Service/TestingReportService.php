<?php

namespace App\Service;

use DateTime;
use Twig\Environment;
use App\Entity\Logger;
use App\Service\TestingService;
use jonasarts\Bundle\TCPDFBundle\TCPDF\TCPDF;

class TestingReportService
{
    public function __construct(
        private readonly TestingService $testingService,
        private readonly Environment $twig,
        private readonly string $reportUploadPath,
    ) {}

    public function generateTestingPdf(Logger $logger): string
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->SetFont('dejavusans', '', 8, '', true);
        $pdf->setCreator(PDF_CREATOR);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->setAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        $pdf->AddPage();
        $pdf->writeHTML($this->generateDataHtml($logger), false);
        $pdf->lastPage();
        
        $fileName = $this->reportUploadPath . '/' . (new DateTime())->format('d-m-Y_H_i_s') . '_' . uniqid() . '.pdf';
        $pdf->Output($fileName, 'F');
        unset($pdf);

        return $fileName;
    } 

    private function generateDataHtml(Logger $logger): string
    {
        return $this->twig->render('frontend/testing/report.html.twig', [
            'logger' => $logger,
            'skipped' => $this->testingService->getSkippedQuestion($logger),
        ]);
    }
}
