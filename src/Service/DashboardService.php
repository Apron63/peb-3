<?php

namespace App\Service;

use App\Repository\LoggerRepository;
use App\Repository\MailingQueueRepository;

class DashboardService
{
    public function __construct(
        private readonly LoggerRepository $loggerRepository,
        private readonly MailingQueueRepository $mailingQueueRepository,
    ) {}

    public function prepareData(): array
    {
        $freeDiskSpace = disk_free_space('/');
        $diskTotalSpace = disk_total_space('/');
        $usedSpace = (int) (($diskTotalSpace - $freeDiskSpace) / $diskTotalSpace * 100);

        return [
            'freeDiskSpace' => $this->getSymbolByQuantity($freeDiskSpace),
            'diskTotalSpace' => $this->getSymbolByQuantity($diskTotalSpace),
            'usedSpace' => $usedSpace,
            'mailCreatedToday' => $this->mailingQueueRepository->getMailCreatedToday(),
            'mailNotSended' => $this->mailingQueueRepository->getMailNotSended(),
        ];
    }

    private function getSymbolByQuantity($bytes): string
    {
        $symbols =['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $exp = floor(log($bytes)/log(1024));
    
        return sprintf('%.2f ' . $symbols[$exp], ($bytes/pow(1024, floor($exp))));
    }
}
