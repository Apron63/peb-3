<?php

declare (strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\MailingQueueRepository;
use App\Repository\QueryUserRepository;
use App\Repository\WhatsappQueueRepository;
use Symfony\Bundle\SecurityBundle\Security;

class DashboardService
{
    private const FREE_DISK_SPACE_MIN = 2_150_000_000;

    public function __construct(
        private readonly MailingQueueRepository $mailingQueueRepository,
        private readonly WhatsappQueueRepository $whatsappQueueRepository,
        private readonly QueryUserRepository $queryUserRepository,
        private readonly Security $security,
    ) {}

    public function prepareData(): array
    {
        $freeDiskSpace = disk_free_space('/');
        $diskTotalSpace = disk_total_space('/');
        $usedSpace = (int) (($diskTotalSpace - $freeDiskSpace) / $diskTotalSpace * 100);

        return [
            'freeDiskSpace' => $this->getSymbolByQuantity($freeDiskSpace),
            'freeDiskSpaceMin' => $this->getSymbolByQuantity(self::FREE_DISK_SPACE_MIN),
            'diskTotalSpace' => $this->getSymbolByQuantity($diskTotalSpace),
            'usedSpace' => $usedSpace,
            'mailCreatedToday' => $this->mailingQueueRepository->getMailCreatedToday(),
            'mailNotSended' => $this->mailingQueueRepository->getMailNotSended(),
            'whatsappCreatedToday' => $this->whatsappQueueRepository->getWhatsappCreatedToday(),
            'whatsappNotSended' => $this->whatsappQueueRepository->getWhatsappNotSendedCount(),
            'queryJobNewCount' => $this->queryUserRepository->getQueryJobNewCount(),
        ];
    }

    public function queryUserClear(): void
    {
        $this->queryUserRepository->queryUserClear();
    }

    public function replaceValue(string $source, array $from = [], array $target = [], ?User $user = null): string
    {
        $result = $source;

        if (! $user instanceof User) {
            $user = $this->security->getUser();
        }

        if ($user instanceof User) {
            $result = str_replace(
                array_merge(
                    [
                        '{FIO}',
                        '{PHONE}',
                        '{EMAIL}',
                    ],
                    $from
                ),
                array_merge(
                    [
                        $user->getFullName(),
                        $user->getContact(),
                        $user->getEmail(),
                    ],
                    $target
                ),
                $source
            );
        }

        return $result;
    }

    private function getSymbolByQuantity($bytes): string
    {
        $symbols =['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $exp = floor(log($bytes)/log(1024));

        return sprintf('%.2f ' . $symbols[$exp], ($bytes/pow(1024, floor($exp))));
    }
}
