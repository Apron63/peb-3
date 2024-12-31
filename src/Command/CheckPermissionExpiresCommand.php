<?php

declare (strict_types=1);

namespace App\Command;

use App\Entity\MailingQueue;
use App\Service\ConfigService;
use App\Service\DashboardService;
use App\Repository\PermissionRepository;
use App\Repository\MailingQueueRepository;
use App\Service\Whatsapp\WhatsappService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:check-expired-permission')]
class CheckPermissionExpiresCommand extends Command
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
        private readonly MailingQueueRepository $mailingQueueRepository,
        private readonly WhatsappService $whatsappService,
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
        foreach ($this->permissionRepository->getExpiredPermissionsList() as $permission) {
            if (null === $permission->getCreatedBy()) {
                continue;
            }

            $user = $permission->getUser();

            $output->writeln('Найден пользователь: ' . $user->getEmail());

            if (null !== $user->getEmail()) {
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

                if (null !== $user->getEmail()) {
                    $mailingQueue
                        ->setUser($user)
                        ->setCreatedBy($permission->getCreatedBy())
                        ->setReciever($user->getEmail())
                        ->setSubject('Доступ скоро истекает')
                        ->setContent($content);

                    $this->mailingQueueRepository->save($mailingQueue, true);
                }
            }

            if (null !== $user->getContact()) {
                $this->whatsappService->permissionWillEndSoon($permission);
            }
        }

        $output->writeln('Отправка завершена.');
        return Command::SUCCESS;
    }
}
