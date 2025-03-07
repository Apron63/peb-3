<?php

declare (strict_types=1);

namespace App\Command;

use App\Entity\WhatsappQueue;
use App\Repository\WhatsappQueueRepository;
use App\Service\ConfigService;
use App\Service\DashboardService;
use App\Service\Whatsapp\WhatsappService;
use DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(name: 'app:retry-whatsapp')]
class RetryWhatsappSendingCommand extends Command
{
    public function __construct(
        private readonly WhatsappQueueRepository $whatsappQueueRepository,
        private readonly WhatsappService $whatsappService,
        private readonly DashboardService $dashboardService,
        private readonly ConfigService $configService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Повторная рассылка неотправленных сообщений для WhatsApp')
            ->setHelp('Повторная рассылка неотправленных сообщений для WhatsApp');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = new DateTime();

        foreach ($this->whatsappQueueRepository->getWhatsappNotSended() as $whatsappMessage) {
            if (WhatsappQueue::MAX_TRY_COUNT <= $whatsappMessage->getAttempts()) {
                continue;
            }

            $user = $whatsappMessage->getUser();

            $output->writeln('Найден пользователь: ' . $user->getMobilePhone());

            $message = $whatsappMessage->getContent();

            try {
                $this->whatsappService->send($user, $message);

                $whatsappMessage->setStatus('Успешно');
            } catch (Throwable $e) {
                $whatsappMessage->setStatus($e->getMessage());
            }

            $whatsappMessage
                ->setSendedAt($now)
                ->setAttempts($whatsappMessage->getAttempts() + 1);

            $this->whatsappQueueRepository->save($whatsappMessage, true);
        }

        $output->writeln('Отправка завершена.');

        return Command::SUCCESS;
    }
}
