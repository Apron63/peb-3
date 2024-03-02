<?php

namespace App\Command;

use App\Entity\MailingQueue;
use App\Service\ConfigService;
use App\Service\DashboardService;
use App\Repository\PermissionRepository;
use App\Repository\MailingQueueRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:check-expired-permission')]
class CheckPermissionExpiresCommand extends Command
{
    public function __construct (
        private readonly PermissionRepository $permissionRepository,
        private readonly MailingQueueRepository $mailingQueueRepository,
        private readonly DashboardService $dashboardService,
        private readonly ConfigService $configService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Проверка активных доступов на завершение')
            ->setHelp('Рассылка извещений для слушателей, у которых доступы завершаются через 5 дней');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach($this->permissionRepository->getExpiredPermissionsList() as $permission) {
            if (null === $permission->getCreatedBy()) {
                continue;
            }

            $output->writeln('Найден пользователь: ' . $permission->getUser()->getEmail());

            $mailingQueue = new MailingQueue;

            $content = $this->dashboardService->replaceValue(
                $this->configService->getConfigValue('permissionWillEndSoon'),
                [
                    '{COURSE}',
                    '{LASTDATE}'
                ],
                [
                    $permission->getCourse()->getName(),
                    $permission->getEndDate()->format('d.m.Y'),
                ],
                $permission->getCreatedBy(),
            );

            $mailingQueue
                ->setUser($permission->getUser())
                ->setCreatedBy($permission->getCreatedBy())
                ->setReciever($permission->getUser()->getEmail())
                ->setSubject('Доступ скоро истекает')
                ->setContent($content);

            $this->mailingQueueRepository->save($mailingQueue, true);
        }

        $output->writeln('Отправка завершена.');
        return Command::SUCCESS;
    }
}
