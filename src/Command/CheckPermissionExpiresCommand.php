<?php

namespace App\Command;

use App\Entity\MailingQueue;
use App\Repository\MailingQueueRepository;
use App\Repository\PermissionRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;

#[AsCommand(name: 'app:check-expired-permission')]
class CheckPermissionExpiresCommand extends Command
{
    public function __construct (
        private readonly PermissionRepository $permissionRepository,
        private readonly MailingQueueRepository $mailingQueueRepository,
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
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
            $output->writeln('Найден пользователь: ' . $permission->getUser()->getEmail());

            $mailingQueue = new MailingQueue;
            $mailingQueue
                ->setUser($permission->getUser())
                ->setSubject('Доступ скоро истекает')
                ->setContent($this->twig->render('mail\permission-expired.html.twig', [
                    'permission' => $permission,
                    'lastDate' => date('d.m.Y', strtotime('+' . $permission->getDuration() . ' days', $permission->getActivatedAt()->getTimestamp())),
                ]));

            $this->mailingQueueRepository->save($mailingQueue, true);
        }
        
        $output->writeln('Отправка завершена.');
        return Command::SUCCESS;
    }
}
