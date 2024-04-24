<?php

namespace App\Service;

use DateTime;
use App\Entity\User;
use PhpOffice\PhpWord\PhpWord;
use App\Repository\PermissionRepository;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpWord\IOFactory as WordFactory;
use PhpOffice\PhpSpreadsheet\IOFactory as ExcelFactory;

class LoaderReportService
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
        private readonly string $reportUploadPath,
    ) {}

    public function generateCSV(User $user): string
    {
        $fileName = $this->reportUploadPath . '/' . (new DateTime())->format('d-m-Y_H_i_s') . '_' . uniqid() . '.csv';
        $file = fopen($fileName, 'w');
        // BOM для корректной работы с кодировкой
        fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

        $fileData =
            'ФИО;'
            . 'Организация;'
            . 'Логин;'
            . 'Пароль;'
            . 'Курсы;'
            . 'Дней'
            . PHP_EOL;

        fputs($file, $fileData);

        foreach($this->permissionRepository->getLoaderByCourse($user) as $loader) {
            $fileData =
                $loader['fullName'] . ';'
                . $loader['organization'] . ';'
                . $loader['login'] . ';'
                . $loader['plainPassword'] . ';'
                . $loader['name'] . ';'
                . $loader['duration']
                . PHP_EOL;

            fputs($file, $fileData);
        }

        fclose($file);

        return $fileName;
    }

    public function generateXLSX(User $user): string
    {
        $spreadsheet = new Spreadsheet();
        $workSheet = $spreadsheet->getActiveSheet();
        $courseShortName = null;

        $workSheet->setCellValue('A1', 'Ном');
        $workSheet->setCellValue('B1', 'ФИО');
        $workSheet->setCellValue('C1', 'Организация');
        $workSheet->setCellValue('D1', 'Логин');
        $workSheet->setCellValue('E1', 'Пароль');
        $workSheet->setCellValue('F1', 'Дней');

        $row = 2;
        $nom = 1;

        foreach($this->permissionRepository->getLoaderByCourse($user) as $loader) {
            if ($courseShortName !== $loader['shortName']) {
                $workSheet->setCellValue('B' . $row, 'Курс : ' . $loader['name']);
                $workSheet->getStyle('B' . $row)->getAlignment()->setWrapText(true);
                $workSheet->getRowDimension($row)->setRowHeight(-1);
                $workSheet->mergeCells('B' . $row . ':F' . $row);

                $workSheet
                    ->getStyle('A' . $row . ':F' . $row)
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('EEEEEE');

                $nom = 1;
                $row ++;
                $courseShortName = $loader['shortName'];
            }

            $workSheet->setCellValue('A' . $row, $nom);
            $workSheet->setCellValue('B' . $row, $loader['fullName']);
            $workSheet->setCellValue('C' . $row, $loader['organization']);
            $workSheet->setCellValue('D' . $row, $loader['login']);
            $workSheet->setCellValue('E' . $row, $loader['plainPassword']);
            $workSheet->setCellValue('F' . $row, $loader['duration']);

            $row ++;
            $nom ++;
        }

        $styleArray = [
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ];
        $workSheet->getStyle('A1:F' . $row - 1)->applyFromArray($styleArray, false);

        $workSheet->getColumnDimension('A')->setAutoSize(true);
        $workSheet->getColumnDimension('B')->setAutoSize(true);
        $workSheet->getColumnDimension('C')->setAutoSize(true);
        $workSheet->getColumnDimension('D')->setAutoSize(true);
        $workSheet->getColumnDimension('E')->setAutoSize(true);
        $workSheet->getColumnDimension('F')->setAutoSize(true);
        $workSheet->getColumnDimension('G')->setAutoSize(true);

        $fileName = $this->reportUploadPath . '/' . (new DateTime())->format('d-m-Y_H_i_s') . '_' . uniqid() . '.xlsx';
        $writer = ExcelFactory::createWriter($spreadsheet, "Xlsx");
        $writer->save($fileName);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $fileName;
    }

    public function generateTXT(User $user): string
    {
        $fileName = $this->reportUploadPath . '/' . (new DateTime())->format('d-m-Y_H_i_s') . '_' . uniqid() . '.txt';
        $file = fopen($fileName, 'w');

        foreach($this->permissionRepository->getLoaderByCourse($user) as $loader) {
            $fileData =
                $loader['fullName'] . PHP_EOL
                . $loader['organization'] . PHP_EOL
                . $loader['login'] . PHP_EOL
                . $loader['plainPassword'] . PHP_EOL
                . $loader['name'] . PHP_EOL
                . $loader['duration'] . PHP_EOL
                . PHP_EOL;

            fputs($file, $fileData);
        }

        fclose($file);

        return $fileName;
    }

    public function generateDOCX(User $user): string
    {
        $courseShortName = null;
        $nom = 1;

        $phpWord = new PhpWord();

        $section = $phpWord->addSection();
        $section->addText();

        $table = $section->addTable();

        $table->addRow();
        $table->addCell(700)->addText('Ном');
        $table->addCell(2500)->addText('ФИО');
        $table->addCell(3500)->addText('Организация');
        $table->addCell(1200)->addText('Логин');
        $table->addCell(1200)->addText('Пароль');
        $table->addCell(700)->addText('Дней');

        foreach($this->permissionRepository->getLoaderByCourse($user) as $loader) {
            if ($courseShortName !== $loader['shortName']) {
                $table->addRow();

                $table->addCell(500, ['bgColor'=>'EEEEEE']);
                $cell = $table->addCell(null, ['bgColor'=>'EEEEEE']);
                $cell->addText('Курс : ' . $loader['name']);
                $cell->getStyle()->setGridSpan(7);

                $courseShortName = $loader['shortName'];
                $nom = 1;
            }

            $table->addRow();
            $table->addCell(700)->addText($nom);
            $table->addCell(2500)->addText($loader['fullName']);
            $table->addCell(3500)->addText($loader['organization']);
            $table->addCell(1200)->addText($loader['login']);
            $table->addCell(1200)->addText($loader['plainPassword']);
            $table->addCell(700)->addText($loader['duration']);

            $nom ++;
        }

        $fileName = $this->reportUploadPath . '/' . (new DateTime())->format('d-m-Y_H_i_s') . '_' . uniqid() . '.docx';
        $objWriter = WordFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($fileName);

        unset($objWriter);

        return $fileName;
    }
}
