<?php

declare(strict_types=1);

namespace App\Service\ReportGenerator;

use App\Entity\Survey;
use App\Entity\User;
use App\Repository\SurveyRepository;
use DateTime;
use jonasarts\Bundle\TCPDFBundle\TCPDF\TCPDF;
use PhpOffice\PhpSpreadsheet\IOFactory as XlsxFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpWord\IOFactory as WordFactory;
use PhpOffice\PhpWord\PhpWord;
use Twig\Environment;

class SurveyGeneratorService
{
    private ?string $personalPath = null;

    public function __construct(
        private readonly string $reportUploadPath,
        private readonly SurveyRepository $surveyRepository,
        private readonly StatisticGeneratorService $statisticGeneratorService,
        private readonly Environment $twig,
    ) {}

    public function generateSurveyReport(User $user, array $data): array
    {
        $reportData = $this->surveyRepository->getDataForReport($data);
        $this->personalPath = $this->statisticGeneratorService->getUserUploadDir($user);

        switch ($data['reportType']) {
            case 'pdf':
                $reportDto = [
                    'filename' => $this->generatePdfReport($reportData),
                    'contentType' => 'application/pdf',
                    'attachment' => 'Отзывы.pdf',
                ];

                break;

            case 'xlsx':
                $reportDto = [
                    'filename' => $this->generateXlsxReport($reportData),
                    'contentType' => 'application/vnd.ms-excel',
                    'attachment' => 'Отзывы.xlsx',
                ];

                break;
            case 'docx':
                $reportDto = [
                    'filename' => $this->generateDocxReport($reportData),
                    'contentType' => 'application/msword',
                    'attachment' => 'Отзывы.docx',
                ];
        }

        return $reportDto;
    }

    /**
     * @param Survey[] $reportData
     */
    private function generatePdfReport(array $reportData): string
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->SetFont('dejavusans', '', 8, '', true);
        $pdf->setCreator(PDF_CREATOR);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->setAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        $pdf->AddPage();
        $pdf->writeHTML($this->generateDataHtml($reportData), false);
        $pdf->lastPage();

        $fileName = $this->personalPath . DIRECTORY_SEPARATOR . (new DateTime())->format('d-m-Y_H_i_s') . '.pdf';
        $pdf->Output($fileName, 'F');
        unset($pdf);

        return $fileName;
    }

    /**
     * @param Survey[] $reportData
     */
    private function generateXlsxReport(array $reportData)
    {
        $spreadsheet = new Spreadsheet();
        $workSheet = $spreadsheet->getActiveSheet();

        $workSheet->setCellValue('A1', 'Ном');
        $workSheet->setCellValue('B1', 'Дата отзыва');
        $workSheet->setCellValue('C1', 'ФИО слушателя');
        $workSheet->setCellValue('D1', 'Организация');
        $workSheet->setCellValue('E1', 'Курс');
        $workSheet->setCellValue('F1', 'Курс полезен для Вас?');
        $workSheet->setCellValue('G1', 'Насколько материал курса соответствует вашим ожиданиям? Что бы вы предложили изменить/улучшить?');
        $workSheet->setCellValue('H1', 'Вам удобно и понятно пользоваться обучающей платформой?');
        $workSheet->setCellValue('I1', 'Ваши пожелания и предложения по обучающей платформе. Что нам изменить/улучшить в платформе?');

        $rowNom = 2;

        foreach($reportData as $row) {
            $workSheet->setCellValue('A' . $rowNom, $rowNom - 1);
            $workSheet->setCellValue('B' . $rowNom, $row->getCreatedAt()->format('d.m.Y'));
            $workSheet->setCellValue('C' . $rowNom, $row->getUser()->getFullName());
            $workSheet->setCellValue('D' . $rowNom, $row->getUser()->getOrganization());
            $workSheet->setCellValue('E' . $rowNom, $row->getCourse()->getName());
            $workSheet->setCellValue('F' . $rowNom, $row->getQuestion1());
            $workSheet->setCellValue('G' . $rowNom, $row->getQuestion2());
            $workSheet->setCellValue('H' . $rowNom, $row->getQuestion3());
            $workSheet->setCellValue('I' . $rowNom, $row->getQuestion4());

            $workSheet->getRowDimension($rowNom)->setRowHeight(-1);

            $rowNom ++;
        }

        $headerStyleArray = [
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'font' => [
                'bold' => true,
            ],
        ];

        $bodyStyleArray = [
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ];

        $workSheet->getStyle('A1:I' . 1)->applyFromArray($headerStyleArray, false);
        $workSheet->getStyle('A2:I' . $rowNom - 1)->applyFromArray($bodyStyleArray, false);

        $workSheet->getStyle('A1:I' . $rowNom - 1)->getAlignment()->setWrapText(true);

        $workSheet->getColumnDimension('A')->setWidth(5);
        $workSheet->getColumnDimension('B')->setWidth(10);
        $workSheet->getColumnDimension('C')->setWidth(15);
        $workSheet->getColumnDimension('D')->setWidth(25);
        $workSheet->getColumnDimension('E')->setWidth(40);
        $workSheet->getColumnDimension('F')->setWidth(10);
        $workSheet->getColumnDimension('G')->setWidth(30);
        $workSheet->getColumnDimension('H')->setWidth(25);
        $workSheet->getColumnDimension('I')->setWidth(30);

        $fileName = $this->personalPath . DIRECTORY_SEPARATOR . (new DateTime())->format('d-m-Y_H_i_s') . '.xlsx';
        $writer = XlsxFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($fileName);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $fileName;
    }

    /**
     * @param Survey[] $reportData
     */
    private function generateDocxReport(array $reportData)
    {
        $phpWord = new PhpWord();

        $section = $phpWord->addSection();
        $section->addText();

        $table = $section->addTable();

        $table->addRow();
        $table->addCell(500)->addText('Ном');
        $table->addCell(800)->addText('Дата отзыва');
        $table->addCell(1500)->addText('ФИО слушателя');
        $table->addCell(1500)->addText('Организация');
        $table->addCell(1500)->addText('Курс');
        $table->addCell(700)->addText('Курс полезен для Вас?');
        $table->addCell(1000)->addText('Насколько материал курса соответствует вашим ожиданиям? Что бы вы предложили изменить/улучшить?');
        $table->addCell(700)->addText('Вам удобно и понятно пользоваться обучающей платформой?');
        $table->addCell(1000)->addText('Ваши пожелания и предложения по обучающей платформе. Что нам изменить/улучшить в платформе?');

        $rowNom = 1;

        foreach($reportData as $row) {

            $table->addRow();
            $table->addCell(500)->addText($rowNom);
            $table->addCell(800)->addText($row->getCreatedAt()->format('d.m.Y'));
            $table->addCell(1500)->addText($row->getUser()->getFullName());
            $table->addCell(1500)->addText($row->getUser()->getOrganization());
            $table->addCell(1500)->addText($row->getCourse()->getName());
            $table->addCell(700)->addText($row->getQuestion1());
            $table->addCell(1000)->addText($row->getQuestion2());
            $table->addCell(700)->addText($row->getQuestion3());
            $table->addCell(1000)->addText($row->getQuestion4());

            $rowNom ++;
        }

        $fileName = $this->personalPath . DIRECTORY_SEPARATOR . (new DateTime())->format('d-m-Y_H_i_s') . '.docx';
        $objWriter = WordFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($fileName);

        unset($objWriter);

        return $fileName;
    }

    /**
     * @param Survey[] $reportData
     */
    private function generateDataHtml(array $reportData): string
    {
        return $this->twig->render('admin/survey/_report.html.twig', [
            'data' => $reportData,
        ]);
    }
}
