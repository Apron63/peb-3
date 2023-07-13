<?php

namespace App\Service;

use DateTime;
use App\Entity\User;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory as WordFactory;
use App\Repository\LoaderRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\IOFactory as ExcelFactory;

class LoaderReportService
{
    public function __construct(
        private readonly LoaderRepository $loaderRepository,
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
            . 'Должность;'
            . 'Организация;'
            . 'Логин;'
            . 'Пароль;'
            . 'Курсы;'
            . 'Дней' 
            . PHP_EOL;

        fputs($file, $fileData);

        foreach($this->loaderRepository->getLoaderforCheckedUser($user) as $loader) {
            foreach($loader->getPermissions() as $permission) {
                $fileData = 
                    $loader->getUser()?->getFullName() . ';'
                    . $loader->getPosition() . ';'
                    . $loader->getOrganization() . ';'
                    . $loader->getUser()?->getLogin() . ';'
                    . $loader->getUser()?->getPlainPassword() . ';'
                    . $permission->getCourse()?->getName() . ';'
                    . $permission->getDuration() 
                    . PHP_EOL;

                fputs($file, $fileData);
            }
        }

        fclose($file);

        return $fileName;
    }
    
    public function generateXLSX(User $user): string
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

        $row = 2;

        foreach($this->loaderRepository->getLoaderforCheckedUser($user) as $loader) {
            foreach($loader->getPermissions() as $permission) {
                $workSheet->setCellValue('A' . $row, $loader->getUser()?->getFullName());
                $workSheet->setCellValue('B' . $row, $loader->getPosition());
                $workSheet->setCellValue('C' . $row, $loader->getOrganization());
                $workSheet->setCellValue('D' . $row, $loader->getUser()?->getLogin());
                $workSheet->setCellValue('E' . $row, $loader->getUser()?->getPlainPassword());
                $workSheet->setCellValue('F' . $row, $permission->getCourse()?->getName());
                $workSheet->setCellValue('G' . $row, $permission->getDuration());

                $row++;
            }
        }

        $styleArray = [
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ];
        $workSheet->getStyle('A1:G' . $row - 1)->applyFromArray($styleArray, false);

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

        foreach($this->loaderRepository->getLoaderforCheckedUser($user) as $loader) {
            foreach($loader->getPermissions() as $permission) {
                $fileData = 
                    $loader->getUser()?->getFullName() . PHP_EOL
                    . $loader->getPosition() . PHP_EOL
                    . $loader->getOrganization() . PHP_EOL
                    . $loader->getUser()?->getLogin() . PHP_EOL
                    . $loader->getUser()?->getPlainPassword() . PHP_EOL
                    . $permission->getCourse()?->getName() . PHP_EOL
                    . $permission->getDuration() . PHP_EOL
                    . PHP_EOL;

                fputs($file, $fileData);
            }
        }

        fclose($file);

        return $fileName;
    }
    
    public function generateDOCX(User $user): string
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

        foreach($this->loaderRepository->getLoaderforCheckedUser($user) as $loader) {
            foreach($loader->getPermissions() as $permission) { 
                $table->addRow();
                $table->addCell(1700)->addText($loader->getUser()?->getFullName());
                $table->addCell(1700)->addText($loader->getPosition());
                $table->addCell(1700)->addText($loader->getOrganization());
                $table->addCell(1300)->addText($loader->getUser()?->getLogin());
                $table->addCell(1300)->addText($loader->getUser()?->getPlainPassword());
                $table->addCell(1800)->addText($permission->getCourse()?->getName());
                $table->addCell(500)->addText($permission->getDuration());
            }
        }

        $fileName = $this->reportUploadPath . '/' . (new DateTime())->format('d-m-Y_H_i_s') . '_' . uniqid() . '.docx';
        $objWriter = WordFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($fileName);

        unset($objWriter);

        return $fileName;
    }
}
