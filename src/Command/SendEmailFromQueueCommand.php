<?php

declare (strict_types=1);

namespace App\Command;

use Symfony\Component\Mime\Email;
use App\Repository\MailingQueueRepository;
use DateTime;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:send-email')]
class SendEmailFromQueueCommand extends Command
{
    public function __construct(
        private readonly MailingQueueRepository $mailingQueueRepository,
        private readonly MailerInterface $mailer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Почтовая рассылка с использованием очереди')
            ->setHelp('Рассылка порцией по 1000 писем в связи с ограничениями почтового сервиса');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->mailingQueueRepository->getEmailPortion(1000) as $emailQueue) {
            if (null !== $emailQueue->getUser()?->getEmail()) {
                $output->writeln('Отправляем почту для: ' . $emailQueue->getUser()->getEmail());

                $email = (new Email())
                    ->from('ucoks@safety63.ru')
                    ->to($emailQueue->getUser()->getEmail())
                    ->subject($emailQueue->getSubject())
                    ->html($emailQueue->getContent());

                $this->mailer->send($email);

                $emailQueue->setSendedAt(new DateTime());
                $this->mailingQueueRepository->save($emailQueue, true);
            } else {
                $this->mailingQueueRepository->remove($emailQueue, true);
            }

            sleep(2);
        }

        $output->writeln('Отправка завершена.');
        return Command::SUCCESS;
    }
}
