<?php

namespace App\Command\OneTime;

use App\Repository\PermissionRepository;
use DateTime;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:permission-report')]
class PermissionReportCommand extends Command
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
        private readonly string $reportUploadPath,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Отчет по назначенным доступам')
            ->setHelp('Отчет по назначенным доступам');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Задача запущена.');

        $permissions = $this->permissionRepository->getPermissionForReport();

        $spreadsheet = new Spreadsheet();
        $workSheet = $spreadsheet->getActiveSheet();

        $workSheet->setCellValue('A1', 'Дата назначения');
        $workSheet->setCellValue('B1', 'Id курса');
        $workSheet->setCellValue('C1', 'Имя курса');
        $workSheet->setCellValue('D1', 'Фио слушателя');
        $workSheet->setCellValue('E1', 'Логин слушателя');
        $workSheet->setCellValue('F1', '# заказа');

        $workSheet->getColumnDimension('A')->setAutoSize(true);
        $workSheet->getColumnDimension('B')->setAutoSize(true);
        $workSheet->getColumnDimension('C')->setAutoSize(true);
        $workSheet->getColumnDimension('D')->setAutoSize(true);
        $workSheet->getColumnDimension('E')->setAutoSize(true);
        $workSheet->getColumnDimension('F')->setAutoSize(true);

        $item = 2;

        foreach($permissions as $permission) {
            $workSheet->setCellValue('A' . $item, $permission->getCreatedAt()->format('d.m.Y'));
            $workSheet->setCellValue('B' . $item, $permission->getCourse()->getId());
            $workSheet->setCellValue('C' . $item, $permission->getCourse()->getShortName());
            $workSheet->setCellValue('D' . $item, $permission->getUser()->getFullName());
            $workSheet->setCellValue('E' . $item, $permission->getUser()->getLogin());
            $workSheet->setCellValue('F' . $item, $permission->getOrderNom());

            $item ++;
        }

        $fileName = $this->reportUploadPath . DIRECTORY_SEPARATOR . (new DateTime())->format('d-m-Y_H_i_s') . '.xlsx';
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($fileName);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        $output->writeln('Задача завершена.');
        return Command::SUCCESS;
    }
}
