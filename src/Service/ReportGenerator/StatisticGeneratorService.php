<?php

namespace App\Service\ReportGenerator;

use App\Entity\Permission;
use App\Entity\User;
use App\Repository\LoggerRepository;
use App\Repository\PermissionRepository;
use App\Repository\UserRepository;
use App\Service\EmailService\EmailService;
use DateTime;
use jonasarts\Bundle\TCPDFBundle\TCPDF\TCPDF;
use PhpOffice\PhpSpreadsheet\IOFactory as XlsxFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpWord\IOFactory as WordFactory;
use PhpOffice\PhpWord\PhpWord;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

class StatisticGeneratorService
{
    private ?string $personalPath = null;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PermissionRepository $permissionRepository,
        private readonly LoggerRepository $loggerRepository,
        private readonly Environment $twig,
        private readonly Filesystem $filesystem,
        private readonly string $reportUploadPath,
    ) {}

    public function generateEmail(User $user, string $type, array $criteria): void
    {
        $data = $this->userRepository->getUserSearchQuery($criteria['user_search'], true)->getResult();
        $this->personalPath = $this->getUserUploadDir($user);

        match ($type) {
            'PDF' => $this->generateStatisticPdf($data, $user),
            'DOCX' => $this->generateStatisticDocx($data, $user),
            'XLSX' => $this->generateStatisticXlsx($data, $user),
        };
    }

    public function generateDocument(User $user, string $type, array $criteria): string
    {
        $data = $this->userRepository->getUserSearchQuery($criteria['user_search'], true)->getResult();
        $this->personalPath = $this->getUserUploadDir($user);

        return match ($type) {
            'PDF' => $this->generateStatisticPdf($data),
            'DOCX' => $this->generateStatisticDocx($data),
            'XLSX' => $this->generateStatisticXlsx($data),
        };
    }

    private function generateStatisticPdf(array $data): string
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

        $fileName = $this->personalPath . DIRECTORY_SEPARATOR . (new DateTime())->format('d-m-Y_H_i_s') . '.pdf';
        $pdf->Output($fileName, 'F');
        unset($pdf);

        return $fileName;
    }

    private function generateStatisticDocx(array $data): string
    {
        $data = $this->prepareData($data);
        $localCourse = null;

        $phpWord = new PhpWord();

        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(8);

        $section = $phpWord->addSection();
        $section->addText('');

        $table = $section->addTable();

        $table->addRow();
        $table->addCell(300)->addText('№');
        $table->addCell(950)->addText('Дата доступа');
        $table->addCell(1300)->addText('ФИО');
        $table->addCell(1300)->addText('Организация');
        $table->addCell(950)->addText('Логин');
        $table->addCell(950)->addText('Дата активации');
        $table->addCell(950)->addText('Посл. действие');
        $table->addCell(950)->addText('Дата экзамена');
        $table->addCell(1000)->addText('Длительность обучения');
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
                $cell->getStyle()->setGridSpan(8);
            }

            $table->addRow();
            $table->addCell(300)->addText($rowNom++);
            $table->addCell(950)->addText($row['createdAt']?->format('d.m.Y'));
            $table->addCell(1300)->addText($row['fullName']);
            $table->addCell(1300)->addText($row['organization']);
            $table->addCell(950)->addText($row['login']);
            $table->addCell(950)->addText($row['activatedAt']?->format('d.m.Y'));
            $table->addCell(950)->addText($row['lastAccess']?->format('d.m.Y'));
            $table->addCell(950)->addText($row['lastExam']?->format('d.m.Y'));
            $table->addCell(1000)->addText($row['timeSpent'] ? gmdate('H ч i м', $row['timeSpent']) : null);
            $table->addCell(1000)->addText($row['stage'] === Permission::STAGE_FINISHED ? 'Сдано' : 'Не сдано');
        }

        $fileName = $this->personalPath . DIRECTORY_SEPARATOR . (new DateTime())->format('d-m-Y_H_i_s') . '.docx';
        $objWriter = WordFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($fileName);

        unset($objWriter);

        return $fileName;
    }

    private function generateStatisticXlsx(array $data): string
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
        $activeWorksheet->setCellValue('I1', 'Длительность обучения');
        $activeWorksheet->setCellValue('J1', 'Результат');

        $line = 2;

        foreach($data as $row) {
            if ($localCourse !== $row['shortName']) {
                $localCourse = $row['shortName'];
                $rowNom = 1;

                $activeWorksheet->setCellValue('A' . $line, '');
                $activeWorksheet->setCellValue('B' . $line, 'Курс');
                $activeWorksheet->setCellValue('C' . $line, $row['name']);
                $activeWorksheet->getStyle('C' . $line)->getAlignment()->setWrapText(true);
                $activeWorksheet->getRowDimension($line)->setRowHeight(-1);
                $activeWorksheet->mergeCells('C' . $line . ':J' . $line);

                $activeWorksheet
                    ->getStyle('A' . $line . ':J' . $line)
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('EEEEEE');

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
            $activeWorksheet->setCellValue('I' . $line, $row['timeSpent']
                ? gmdate('H ч i мин', $row['timeSpent'])
                : null
            );
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

        $fileName = $this->personalPath . DIRECTORY_SEPARATOR . (new DateTime())->format('d-m-Y_H_i_s') . '.xlsx';

        $objWriter = XlsxFactory::createWriter($spreadsheet, 'Xlsx');
        $objWriter->save($fileName);

        unset($objWriter);

        return $fileName;
    }

    private function prepareData(array $data): array
    {
        foreach($data as $key => $value) {
            $user = $this->userRepository->find($value['userId']);

            if (! $user instanceof $user) {
                throw new NotFoundHttpException('User Not found: ' . $value['userId']);
            }

            $permission = $this->permissionRepository->findOneBy([
                'id' => $value['permissionId']
            ]);

            if (! $permission instanceof Permission) {
                throw new NotFoundHttpException('Permission not found: ' . $value['permissionId']);
            }

            $testingDate = $this->loggerRepository->getFinalTestingDate($permission, $user);
            $data[$key]['lastExam'] = $testingDate;
        }

        return $data;
    }

    private function generateDataHtml(array $data): string
    {
        return $this->twig->render('admin/user/_report.html.twig', [
            'data' => $data,
        ]);
    }

    public function getUserUploadDir(User $user): string
    {
        $path = $this->reportUploadPath . DIRECTORY_SEPARATOR . 'personal' . DIRECTORY_SEPARATOR . $user->getId();

        if (! $this->filesystem->exists($path)) {
            $this->filesystem->mkdir($path);
        }

        return $path;
    }
}
