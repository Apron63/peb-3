<?php

namespace App\Service;

use DateTime;
use Twig\Environment;
use App\Entity\Permission;
use PhpOffice\PhpWord\PhpWord;
use Symfony\Component\Mime\Email;
use App\Repository\UserRepository;
use App\Repository\LoggerRepository;
use Symfony\Component\Mime\Part\File;
use App\Repository\PermissionRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\Mime\Part\DataPart;
use PhpOffice\PhpSpreadsheet\Style\Border;
use jonasarts\Bundle\TCPDFBundle\TCPDF\TCPDF;
use Symfony\Component\Mailer\MailerInterface;
use PhpOffice\PhpWord\IOFactory as WordFactory;
use PhpOffice\PhpSpreadsheet\IOFactory as XlsxFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminReportService
{
    public function __construct(
        private readonly LoggerRepository $loggerRepository,
        private readonly UserRepository $userRepository,
        private readonly PermissionRepository $permissionRepository,
        private readonly Environment $twig,
        private readonly MailerInterface $mailer,
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
        $localCourse = null;

        $phpWord = new PhpWord();

        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(8);

        $section = $phpWord->addSection();
        $section->addText();

        $table = $section->addTable();

        $table->addRow();
        $table->addCell(500)->addText('№');
        $table->addCell(1000)->addText('Дата доступа');
        $table->addCell(1500)->addText('ФИО');
        $table->addCell(1500)->addText('Организация');
        $table->addCell(1000)->addText('Логин');
        $table->addCell(1000)->addText('Дата активации');
        $table->addCell(1000)->addText('Посл. действие');
        $table->addCell(1000)->addText('Дата экзамена');
        $table->addCell(1000)->addText('Результат');

        foreach($data as $row) {
            if ($localCourse !== $row['shortName']) {
                $localCourse = $row['shortName'];
                $rowNom = 1;

                $table->addRow();
                $table->addCell(500);
                $table->addCell(1000)->addText('Курс');    
                $cell = $table->addCell();
                $cell->addText($row['name']);
                $cell->getStyle()->setGridSpan(7);
            }

            $table->addRow();
            $table->addCell(500)->addText($rowNom++);
            $table->addCell(1000)->addText($row['createdAt']?->format('d.m.Y'));
            $table->addCell(1500)->addText($row['fullName']);
            $table->addCell(1500)->addText($row['organization']);
            $table->addCell(1000)->addText($row['login']);
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
        $localCourse = null;

        $spreadsheet = new Spreadsheet();
        $activeWorksheet = $spreadsheet->getActiveSheet();
        
        $activeWorksheet->setCellValue('A1', '№');
        $activeWorksheet->setCellValue('B1', 'Дата доступа');
        $activeWorksheet->setCellValue('C1', 'ФИО');
        $activeWorksheet->setCellValue('D1', 'Организация');
        $activeWorksheet->setCellValue('E1', 'Логин');
        $activeWorksheet->setCellValue('F1', 'Дата активации');
        $activeWorksheet->setCellValue('G1', 'Посл. действие');
        $activeWorksheet->setCellValue('H1', 'Дата экзамена');
        $activeWorksheet->setCellValue('I1', 'Результат');

        $line = 2;

        foreach($data as $row) {
            if ($localCourse !== $row['shortName']) {
                $localCourse = $row['shortName'];
                $rowNom = 1;

                $activeWorksheet->setCellValue('A' . $line, '');
                $activeWorksheet->setCellValue('B' . $line, 'Курс');
                $activeWorksheet->setCellValue('C' . $line, $row['name']);
                $activeWorksheet->getStyle('C' . $line, $row['name'])->getAlignment()->setWrapText(true);
                $activeWorksheet->getRowDimension($line)->setRowHeight(-1);
                $activeWorksheet->mergeCells('C' . $line . ':I' . $line);

                $line ++;
            }

            $activeWorksheet->setCellValue('A' . $line, $rowNom++);
            $activeWorksheet->setCellValue('B' . $line, $row['createdAt']?->format('d.m.Y'));
            $activeWorksheet->setCellValue('C' . $line, $row['fullName']);
            $activeWorksheet->setCellValue('D' . $line, $row['organization']);
            $activeWorksheet->setCellValue('E' . $line, $row['login']);
            $activeWorksheet->setCellValue('F' . $line, $row['activatedAt']?->format('d.m.Y'));
            $activeWorksheet->setCellValue('G' . $line, $row['lastAccess']?->format('d.m.Y'));
            $activeWorksheet->setCellValue('H' . $line, $row['lastExam']?->format('d.m.Y'));
            $activeWorksheet->setCellValue('I' . $line, $row['stage'] === Permission::STAGE_FINISHED ? 'Сдано' : 'Не сдано');

            $line++;
        }

        $styleArray = [
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ];

        $activeWorksheet->getStyle('A1:I' . $line - 1)->applyFromArray($styleArray, false);

        $activeWorksheet->getColumnDimension('A')->setAutoSize(true);
        $activeWorksheet->getColumnDimension('B')->setAutoSize(true);
        $activeWorksheet->getColumnDimension('C')->setAutoSize(true);
        $activeWorksheet->getColumnDimension('D')->setAutoSize(true);
        $activeWorksheet->getColumnDimension('E')->setAutoSize(true);
        $activeWorksheet->getColumnDimension('F')->setAutoSize(true);
        $activeWorksheet->getColumnDimension('G')->setAutoSize(true);
        $activeWorksheet->getColumnDimension('H')->setAutoSize(true);
        $activeWorksheet->getColumnDimension('I')->setAutoSize(true);

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
                . $row['name'] . ';'
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
        $courseName = null;

        $workSheet->setCellValue('A1', 'Ном');
        $workSheet->setCellValue('B1', 'ФИО');
        $workSheet->setCellValue('C1', 'Должность');
        $workSheet->setCellValue('D1', 'Организация');
        $workSheet->setCellValue('E1', 'Логин');
        $workSheet->setCellValue('F1', 'Пароль');
        $workSheet->setCellValue('G1', 'Дней');

        $item = 2;
        foreach($data as $row) {
            if ($courseName !== $row['name']) {
                $workSheet->setCellValue('A' . $item, '');

                $workSheet->setCellValue('B' . $item, 'Курс : ' . $row['name']);
                $workSheet->getStyle('B' . $item, $row['name'])->getAlignment()->setWrapText(true);
                $workSheet->getRowDimension($item)->setRowHeight(-1);
                $workSheet->mergeCells('B' . $item . ':G' . $item);

                $workSheet
                    ->getStyle('A' . $item . ':G' . $item)
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('EEEEEE');

                $courseName = $row['name'];
                $item ++;
                $nom = 1;
            }

            $workSheet->setCellValue('A' . $item, $nom);
            $workSheet->setCellValue('B' . $item, $row['fullName']);
            $workSheet->setCellValue('C' . $item, $row['position']);
            $workSheet->setCellValue('D' . $item, $row['organization']);
            $workSheet->setCellValue('E' . $item, $row['login']);
            $workSheet->setCellValue('F' . $item, $row['plainPassword']);
            $workSheet->setCellValue('G' . $item, $row['duration']);

            $item ++;
            $nom ++;
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
                . $row['name'] . PHP_EOL
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
        $courseName = null;

        $section = $phpWord->addSection();
        $section->addText();

        $table = $section->addTable();

        $table->addRow();
        $table->addCell(500)->addText('Ном');
        $table->addCell(2000)->addText('ФИО');
        $table->addCell(2000)->addText('Должность');
        $table->addCell(2000)->addText('Организация');
        $table->addCell(1300)->addText('Логин');
        $table->addCell(1300)->addText('Пароль');
        $table->addCell(500)->addText('Дней');

        foreach($data as $row) {
            if ($courseName !== $row['name']) {
                $table->addRow();
                $table->addCell(500, ['bgColor'=>'EEEEEE']);
                $table->addCell(1000, ['bgColor'=>'EEEEEE'])->addText('Курс');    
                $cell = $table->addCell(null, ['bgColor'=>'EEEEEE']);
                $cell->addText($row['name']);
                $cell->getStyle()->setGridSpan(7);

                $courseName = $row['name'];
                $nom = 1;
            }

            $table->addRow();
            $table->addCell(500)->addText($nom);
            $table->addCell(2000)->addText($row['fullName']);
            $table->addCell(2000)->addText($row['position']);
            $table->addCell(2000)->addText($row['organization']);
            $table->addCell(1300)->addText($row['login']);
            $table->addCell(1300)->addText($row['plainPassword']);
            $table->addCell(500)->addText($row['duration']);

            $nom ++;
        }

        $fileName = $this->reportUploadPath . '/' . (new DateTime())->format('d-m-Y_H_i_s') . '_' . uniqid() . '.docx';
        $objWriter = WordFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($fileName);

        unset($objWriter);

        return $fileName;
    }

    public function generateListAndSend(string $recipient, string $subject, string $comment, string $type, array $data): array
    {
        $totalRecipients = array_map(
            fn($address) => trim($address),
            explode(',', $recipient
        ));

        $success = true;
        $message = '';

        foreach($totalRecipients as $address) {
            if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
                $success = false;

                $message = 'Некорректный email адрес : ' . $address;
                break;
            }
        }

        if ($success) {
            switch($type) {
                case 'CSV':
                    $fileName = $this->generateListCSV($data);
                    break;
                case 'XLSX':
                    $fileName = $this->generateListXLSX($data);
                    break;
                case 'TXT':
                    $fileName = $this->generateListTXT($data);
                    break;
                case 'DOCX':
                    $fileName = $this->generateListDocx($data);
            }

            $mail = (new Email())
                ->from('ucoks@safety63.ru')
                ->to(...$totalRecipients)
                ->subject($subject)
                ->html($comment)
                ->addPart(new DataPart(new File($fileName)));

            $this->mailer->send($mail);
        }

        return [
            'success' => $success,
            'message' => $message,
        ];
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
