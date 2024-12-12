<?php

declare(strict_types=1);

namespace App\Service\ReportGenerator;

use App\Entity\User;
use App\Repository\UserRepository;
use DateTime;
use PhpOffice\PhpSpreadsheet\IOFactory as XlsxFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpWord\IOFactory as WordFactory;
use PhpOffice\PhpWord\PhpWord;
use Symfony\Component\Filesystem\Filesystem;

class ReportGeneratorService
{
    private ?string $personalPath = null;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly Filesystem $filesystem,
        private readonly string $reportUploadPath,
    ) {}

    public function generateEmail(User $user, string $type, array $criteria): void
    {
        $data = $this->userRepository->getUserSearchQuery($criteria['user_search'], true)->getResult();
        $this->personalPath = $this->getUserUploadDir($user);

        match ($type) {
            'CSV' => $this->generateListCSV($data),
            'XLSX' => $this->generateListXLSX($data),
            'TXT' => $this->generateListTXT($data),
            'DOCX' => $this->generateListDocx($data),
        };
    }

    public function generateDocument(User $user, string $type, array $criteria): string
    {
        $data = $this->userRepository->getUserSearchQuery($criteria['user_search'], true)->getResult();
        $this->personalPath = $this->getUserUploadDir($user);

        return match ($type) {
            'CSV' => $this->generateListCSV($data),
            'XLSX' => $this->generateListXLSX($data),
            'TXT' => $this->generateListTXT($data),
            'DOCX' => $this->generateListDocx($data),
        };
    }

    private function generateListCSV(array $data): string
    {
        $fileName = $this->personalPath . DIRECTORY_SEPARATOR . (new DateTime())->format('d-m-Y_H_i_s') . '.csv';

        $file = fopen($fileName, 'w');
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

        foreach($data as $row) {
            $fileData =
                $row['fullName'] . ';'
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

    private function generateListXLSX(array $data): string
    {
        $spreadsheet = new Spreadsheet();
        $workSheet = $spreadsheet->getActiveSheet();
        $courseName = null;

        $workSheet->setCellValue('A1', 'Ном');
        $workSheet->setCellValue('B1', 'ФИО');
        $workSheet->setCellValue('C1', 'Организация');
        $workSheet->setCellValue('D1', 'Логин');
        $workSheet->setCellValue('E1', 'Пароль');
        $workSheet->setCellValue('F1', 'Дней');

        $item = 2;
        $nom = 1;

        foreach($data as $row) {
            if ($courseName !== $row['shortName']) {
                $workSheet->setCellValue('A' . $item, '');

                $workSheet->setCellValue('B' . $item, 'Курс : ' . $row['name']);
                $workSheet->getStyle('B' . $item)->getAlignment()->setWrapText(true);
                $workSheet->getRowDimension($item)->setRowHeight(-1);

                $workSheet
                    ->getStyle('A' . $item . ':F' . $item)
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('EEEEEE');

                $courseName = $row['shortName'];
                $item ++;
                $nom = 1;
            }

            $workSheet->setCellValue('A' . $item, $nom);
            $workSheet->setCellValue('B' . $item, $row['fullName']);
            $workSheet->setCellValue('C' . $item, $row['organization']);
            $workSheet->setCellValue('D' . $item, $row['login']);
            $workSheet->setCellValue('E' . $item, $row['plainPassword']);
            $workSheet->setCellValue('F' . $item, $row['duration']);

            $item ++;
            $nom ++;
        }

        $styleArray = [
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ];
        $workSheet->getStyle('A1:F' . $item - 1)->applyFromArray($styleArray, false);

        $workSheet->getColumnDimension('A')->setAutoSize(true);
        $workSheet->getColumnDimension('B')->setWidth(110);
        $workSheet->getColumnDimension('C')->setAutoSize(true);
        $workSheet->getColumnDimension('D')->setAutoSize(true);
        $workSheet->getColumnDimension('E')->setAutoSize(true);
        $workSheet->getColumnDimension('F')->setAutoSize(true);

        $fileName = $this->personalPath . DIRECTORY_SEPARATOR . (new DateTime())->format('d-m-Y_H_i_s') . '.xlsx';
        $writer = XlsxFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($fileName);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $fileName;
    }

    private function generateListTXT(array $data): string
    {
        $fileName = $this->personalPath . DIRECTORY_SEPARATOR . (new DateTime())->format('d-m-Y_H_i_s') . '.txt';
        $file = fopen($fileName, 'w');

        foreach($data as $row) {
            $fileData =
                $row['fullName'] . PHP_EOL
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

    private function generateListDocx(array $data): string
    {
        $phpWord = new PhpWord();
        $courseName = null;

        $section = $phpWord->addSection();
        $section->addText();

        $table = $section->addTable(['borderSize' => 1]);

        $table->addRow();
        $table->addCell(500)->addText('Ном');
        $table->addCell(2500)->addText('ФИО');
        $table->addCell(3000)->addText('Организация');
        $table->addCell(1300)->addText('Логин');
        $table->addCell(1300)->addText('Пароль');
        $table->addCell(500)->addText('Дней');

        foreach($data as $row) {
            if ($courseName !== $row['shortName']) {
                $table->addRow();
                $table->addCell(500, ['bgColor'=>'EEEEEE']);
                $cell = $table->addCell(null, ['bgColor'=>'EEEEEE']);
                $cell->addText('Курс ' . $row['name']);
                $cell->getStyle()->setGridSpan(5);

                $courseName = $row['shortName'];
                $nom = 1;
            }

            $table->addRow();
            $table->addCell(500)->addText($nom);
            $table->addCell(2500)->addText($row['fullName']);
            $table->addCell(3000)->addText($row['organization']);
            $table->addCell(1300)->addText($row['login']);
            $table->addCell(1300)->addText($row['plainPassword']);
            $table->addCell(500)->addText($row['duration']);

            $nom ++;
        }

        $fileName = $this->personalPath . DIRECTORY_SEPARATOR . (new DateTime())->format('d-m-Y_H_i_s') . '.docx';
        $objWriter = WordFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($fileName);

        unset($objWriter);

        return $fileName;
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
