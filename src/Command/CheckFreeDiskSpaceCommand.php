<?php

declare (strict_types=1);

namespace App\Command;

use App\Message\SendEmailMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'app:check-disk-space')]
class CheckFreeDiskSpaceCommand
{
    private const int FREE_DISK_SPACE_MIN = 2_150_000_000;

    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {}

    public function __invoke(SymfonyStyle $io): int
    {
        $freeDiskSpace = disk_free_space('/');
        $diskTotalSpace = disk_total_space('/');

        if ($freeDiskSpace < self::FREE_DISK_SPACE_MIN) {
            $this->bus->dispatch(
                new SendEmailMessage(
                    '1103@safety63.ru',
                    'На сервере СДО заканчивается свободное место',
                    'Осталось свободного места: ' . $this->getSymbolByQuantity($freeDiskSpace) . '<br>'
                    . 'Всего места на диске: ' . $this->getSymbolByQuantity($diskTotalSpace) . '<br>'
                    . 'Должно быть свободно не меньше чем: ' . $this->getSymbolByQuantity(self::FREE_DISK_SPACE_MIN),
                )
            );
        }

        $io->writeln('Выполнено!');
        return Command::SUCCESS;
    }

    private function getSymbolByQuantity($bytes): string
    {
        $symbols =['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $exp = floor(log($bytes)/log(1024));

        return sprintf('%.2f ' . $symbols[$exp], ($bytes/pow(1024, floor($exp))));
    }
}
