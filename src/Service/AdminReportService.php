<?php

namespace App\Service;

use DateTime;
use App\Entity\Permission;
use PhpOffice\PhpWord\PhpWord;
use App\Repository\UserRepository;
use App\Repository\LoggerRepository;
use App\Repository\PermissionRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use jonasarts\Bundle\TCPDFBundle\TCPDF\TCPDF;
use PhpOffice\PhpWord\IOFactory as WordFactory;
use PhpOffice\PhpSpreadsheet\IOFactory as XlsxFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

class AdminReportService
{
    public function __construct(
        private readonly LoggerRepository $loggerRepository,
        private readonly UserRepository $userRepository,
        private readonly PermissionRepository $permissionRepository,
        private readonly Environment $twig,
        private readonly string $reportUploadPath,
    ) {}

    public function generateStatisticPdf(array $data): string
    {
        $data = $this->prepareData($data);

        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->SetFont('dejavusans', '', 8, '', true);
        $pdf->setCreator(PDF_CREATOR);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->setAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        $pdf->AddPage();
        $pdf->writeHTML($this->generateDataHtml($data), false);
        $pdf->lastPage();
        
        $fileName = $this->reportUploadPath . '/' . (new DateTime())->format('d-m-Y_H_i_s') . '_' . uniqid() . '.pdf';
        $pdf->Output($fileName, 'F');
        unset($pdf);

        return $fileName;
    } 
    
    public function generateStatisticDocx(array $data): string
    {
        $data = $this->prepareData($data);

        $phpWord = new PhpWord();

        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(8);

        $section = $phpWord->addSection();
        $section->addText();

        $table = $section->addTable();

        $rowNom = 1;

        $table->addRow();
        $table->addCell(500)->addText('№');
        $table->addCell(1000)->addText('Дата доступа');
        $table->addCell(1000)->addText('ФИО');
        $table->addCell(1000)->addText('Организация');
        $table->addCell(1000)->addText('Логин');
        $table->addCell(1000)->addText('Курс');
        $table->addCell(1000)->addText('Дата активации');
        $table->addCell(1000)->addText('Посл. действие');
        $table->addCell(1000)->addText('Дата экзамена');
        $table->addCell(1000)->addText('Результат');

        foreach($data as $row) {
            $table->addRow();
            $table->addCell(500)->addText($rowNom++);
            $table->addCell(1000)->addText($row['createdAt']?->format('d.m.Y'));
            $table->addCell(1000)->addText($row['fullName']);
            $table->addCell(1000)->addText($row['organization']);
            $table->addCell(1000)->addText($row['login']);
            $table->addCell(1000)->addText($row['shortName']);
            $table->addCell(1000)->addText($row['activatedAt']?->format('d.m.Y'));
            $table->addCell(1000)->addText($row['lastAccess']?->format('d.m.Y'));
            $table->addCell(1000)->addText($row['lastExam']?->format('d.m.Y'));
            $table->addCell(1000)->addText($row['stage'] === Permission::STAGE_FINISHED ? 'Сдано' : 'Не сдано');
        }

        $fileName = $this->reportUploadPath . '/' . (new DateTime())->format('d-m-Y_H_i_s') . '_' . uniqid() . '.pdf';
        $objWriter = WordFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($fileName);

        unset($objWriter);

        return $fileName;
    }

    public function generateStatisticXlsx(array $data): string
    {
        $data = $this->prepareData($data);

        $spreadsheet = new Spreadsheet();
        $activeWorksheet = $spreadsheet->getActiveSheet();
        
        $activeWorksheet->setCellValue('A1', '№');
        $activeWorksheet->setCellValue('B1', 'Дата доступа');
        $activeWorksheet->setCellValue('C1', 'ФИО');
        $activeWorksheet->setCellValue('D1', 'Организация');
        $activeWorksheet->setCellValue('E1', 'Логин');
        $activeWorksheet->setCellValue('F1', 'Курс');
        $activeWorksheet->setCellValue('G1', 'Дата активации');
        $activeWorksheet->setCellValue('H1', 'Посл. действие');
        $activeWorksheet->setCellValue('I1', 'Дата экзамена');
        $activeWorksheet->setCellValue('J1', 'Результат');

        $rowNom = 1;
        $line = 2;

        foreach($data as $row) {
            $activeWorksheet->setCellValue('A' . $line, $rowNom++);
            $activeWorksheet->setCellValue('B' . $line, $row['createdAt']?->format('d.m.Y'));
            $activeWorksheet->setCellValue('C' . $line, $row['fullName']);
            $activeWorksheet->setCellValue('D' . $line, $row['organization']);
            $activeWorksheet->setCellValue('E' . $line, $row['login']);
            $activeWorksheet->setCellValue('F' . $line, $row['shortName']);
            $activeWorksheet->setCellValue('G' . $line, $row['activatedAt']?->format('d.m.Y'));
            $activeWorksheet->setCellValue('H' . $line, $row['lastAccess']?->format('d.m.Y'));
            $activeWorksheet->setCellValue('I' . $line, $row['lastExam']?->format('d.m.Y'));
            $activeWorksheet->setCellValue('J' . $line, $row['stage'] === Permission::STAGE_FINISHED ? 'Сдано' : 'Не сдано');

            $line++;
        }

        $styleArray = [
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ];

        $activeWorksheet->getStyle('A1:J' . $line - 1)->applyFromArray($styleArray, false);

        $activeWorksheet->getColumnDimension('A')->setAutoSize(true);
        $activeWorksheet->getColumnDimension('B')->setAutoSize(true);
        $activeWorksheet->getColumnDimension('C')->setAutoSize(true);
        $activeWorksheet->getColumnDimension('D')->setAutoSize(true);
        $activeWorksheet->getColumnDimension('E')->setAutoSize(true);
        $activeWorksheet->getColumnDimension('F')->setAutoSize(true);
        $activeWorksheet->getColumnDimension('G')->setAutoSize(true);
        $activeWorksheet->getColumnDimension('H')->setAutoSize(true);
        $activeWorksheet->getColumnDimension('I')->setAutoSize(true);
        $activeWorksheet->getColumnDimension('J')->setAutoSize(true);

        $fileName = $this->reportUploadPath . '/' . (new DateTime())->format('d-m-Y_H_i_s') . '_' . uniqid() . '.xlsx';

        $objWriter = XlsxFactory::createWriter($spreadsheet, 'Xlsx');
        $objWriter->save($fileName);

        unset($objWriter);

        return $fileName;
    }

    public function generateListCSV(array $data): string
    {
        $fileName = $this->reportUploadPath . '/' . (new DateTime())->format('d-m-Y_H_i_s') . '_' . uniqid() . '.csv';
        $file = fopen($fileName, 'w');
        fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

        $fileData = 
            'ФИО;'
            . 'Должность;'
            . 'Организация;'
            . 'Логин;'
            . 'Пароль;'
            . 'Курсы;'
            . 'Дней' 
            . PHP_EOL;

        fputs($file, $fileData);

        foreach($data as $row) {
            $fileData = 
                $row['fullName'] . ';'
                . $row['position'] . ';'
                . $row['organization'] . ';'
                . $row['login'] . ';'
                . $row['plainPassword'] . ';'
                . $row['shortName'] . ';'
                . $row['duration'] 
                . PHP_EOL;

            fputs($file, $fileData);
        }

        fclose($file);

        return $fileName;
    }

    public function generateListXLSX(array $data): string
    {
        $spreadsheet = new Spreadsheet();
        $workSheet = $spreadsheet->getActiveSheet();

        $workSheet->setCellValue('A1', 'ФИО');
        $workSheet->setCellValue('B1', 'Должность');
        $workSheet->setCellValue('C1', 'Организация');
        $workSheet->setCellValue('D1', 'Логин');
        $workSheet->setCellValue('E1', 'Пароль');
        $workSheet->setCellValue('F1', 'Курсы');
        $workSheet->setCellValue('G1', 'Дней');

        $item = 2;
        foreach($data as $row) {
            $workSheet->setCellValue('A' . $item, $row['fullName']);
            $workSheet->setCellValue('B' . $item, $row['position']);
            $workSheet->setCellValue('C' . $item, $row['organization']);
            $workSheet->setCellValue('D' . $item, $row['login']);
            $workSheet->setCellValue('E' . $item, $row['plainPassword']);
            $workSheet->setCellValue('F' . $item, $row['shortName']);
            $workSheet->setCellValue('G' . $item, $row['duration']);

            $item++;
        }

        $styleArray = [
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ];
        $workSheet->getStyle('A1:G' . $item - 1)->applyFromArray($styleArray, false);

        $workSheet->getColumnDimension('A')->setAutoSize(true);
        $workSheet->getColumnDimension('B')->setAutoSize(true);
        $workSheet->getColumnDimension('C')->setAutoSize(true);
        $workSheet->getColumnDimension('D')->setAutoSize(true);
        $workSheet->getColumnDimension('E')->setAutoSize(true);
        $workSheet->getColumnDimension('F')->setAutoSize(true);
        $workSheet->getColumnDimension('G')->setAutoSize(true);

        $fileName = $this->reportUploadPath . '/' . (new DateTime())->format('d-m-Y_H_i_s') . '_' . uniqid() . '.xlsx';
        $writer = XlsxFactory::createWriter($spreadsheet, "Xlsx");
        $writer->save($fileName);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $fileName;
    }

    public function generateListTXT(array $data): string
    {
        $fileName = $this->reportUploadPath . '/' . (new DateTime())->format('d-m-Y_H_i_s') . '_' . uniqid() . '.txt';
        $file = fopen($fileName, 'w');

        foreach($data as $row) {
            $fileData = 
                $row['fullName'] . PHP_EOL
                . $row['position'] . PHP_EOL
                . $row['organization'] . PHP_EOL
                . $row['login'] . PHP_EOL
                . $row['plainPassword'] . PHP_EOL
                . $row['shortName'] . PHP_EOL
                . $row['duration'] . PHP_EOL
                . PHP_EOL;

            fputs($file, $fileData);
        }

        fclose($file);

        return $fileName;
    }

    public function generateListDocx(array $data): string
    {
        $phpWord = new PhpWord();

        $section = $phpWord->addSection();
        $section->addText();

        $table = $section->addTable();

        $table->addRow();
        $table->addCell(1700)->addText('ФИО');
        $table->addCell(1700)->addText('Должность');
        $table->addCell(1700)->addText('Организация');
        $table->addCell(1300)->addText('Логин');
        $table->addCell(1300)->addText('Пароль');
        $table->addCell(1800)->addText('Курсы');
        $table->addCell(500)->addText('Дней');

        foreach($data as $row) {
            $table->addRow();
            $table->addCell(1700)->addText($row['fullName']);
            $table->addCell(1700)->addText($row['position']);
            $table->addCell(1700)->addText($row['organization']);
            $table->addCell(1300)->addText($row['login']);
            $table->addCell(1300)->addText($row['plainPassword']);
            $table->addCell(1800)->addText($row['shortName']);
            $table->addCell(500)->addText($row['duration']);
        }

        $fileName = $this->reportUploadPath . '/' . (new DateTime())->format('d-m-Y_H_i_s') . '_' . uniqid() . '.docx';
        $objWriter = WordFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($fileName);

        unset($objWriter);

        return $fileName;
    }

    private function generateDataHtml(array $data): string
    {
        return $this->twig->render('admin/user/_report.html.twig', [
            'data' => $data,
        ]);
    }

    private function prepareData(array $data): array
    {
        foreach($data as $key => $value) {
            $user = $this->userRepository->find($value['userId']);
            if (! $user instanceof $user) {
                throw new NotFoundHttpException('User Not found: ' . $value['userId']);
            }

            $permission = $this->permissionRepository->find($value['permissionId']);
            if (! $permission instanceof Permission) {
                throw new NotFoundHttpException('Permission not found: ' . $value['permissionId']);
            }

            $testingDate = $this->loggerRepository->getFinalTestingDate($permission, $user);
            $data[$key]['lastExam'] = $testingDate;
        }

        return $data;
    }
}