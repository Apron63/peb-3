<?php

declare (strict_types=1);

namespace App\Service\EmailService;

use App\Entity\MailingQueue;
use App\Entity\User;
use App\Repository\MailingQueueRepository;
use App\Service\ConfigService;
use App\Service\DashboardService;
use App\Service\ReportGenerator\ReportGeneratorService;
use App\Service\ReportGenerator\StatisticGeneratorService;
use DateTime;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;

class EmailService
{
    public function __construct(
        private readonly ReportGeneratorService $reportGeneratorService,
        private readonly StatisticGeneratorService $statisticGeneratorService,
        private readonly DashboardService $dashboardService,
        private readonly ConfigService $configService,
        private readonly MailingQueueRepository $mailingQueueRepository,
        private readonly Filesystem $filesystem,
        private readonly MailerInterface $mailer,
        private readonly string $reportUploadPath,
    ) {}

    public function createEmailWithReportData(User $user, string $type, array $criteria): MailingQueue
    {
        $email = new MailingQueue()
            ->setCreatedBy($user)
            ->setSubject('Данные')
            ->setContent(
                $this->dashboardService->replaceValue(
                    $this->configService->getConfigValue('emailAttachmentResultText')
                    )
            );

        $this->mailingQueueRepository->save($email, true);
        $this->reportGeneratorService->generateEmail($user, $type, $criteria, $email->getId());

        return $email;
    }

    public function createEmailWithStatisticData(User $user, string $type, array $criteria): MailingQueue
    {
        $email = new MailingQueue();
        $email
            ->setCreatedBy($user)
            ->setSubject('Статистика')
            ->setContent(
                $this->dashboardService->replaceValue(
                    $this->configService->getConfigValue('emailAttachmentStatisticText')
                )
            );

        $this->mailingQueueRepository->save($email, true);
        $this->statisticGeneratorService->generateEmail($user, $type, $criteria, $email->getId());

        return $email;
    }

    public function getUserUploadDir(User $user, int $emailId): string
    {
        $path = $this->reportUploadPath . DIRECTORY_SEPARATOR . 'personal'
            . DIRECTORY_SEPARATOR . $user->getId() . DIRECTORY_SEPARATOR . $emailId;

        if (! $this->filesystem->exists($path)) {
            $this->filesystem->mkdir($path);
        }

        return $path;
    }

    public function getUploadedFiles(User $user, int $emailId): array
    {
        $result = [];

        $path = $this->getUserUploadDir($user, $emailId) ;

        $finder = new Finder();
        $finder->files()->in($path);

        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $result[] = $file->getRelativePathname();
            }
        }

        return $result;
    }

    public function readFile(string $filename, User $user, int $emailId): string
    {
        return $this->getUserUploadDir($user, $emailId) . DIRECTORY_SEPARATOR . $filename;
    }

    public function deleteFile(string $filename, User $user, int $emailId): void
    {
        $path = $this->getUserUploadDir($user, $emailId) . DIRECTORY_SEPARATOR . $filename;

        $this->filesystem->remove($path);
    }

    public function sendEmailWithAttachments(MailingQueue $email): void
    {
        $personalPath = $this->getUserUploadDir($email->getCreatedBy(), $email->getId());

        $allRecievers = array_map(
            fn($address) => trim($address),
            explode(',', $email->getReciever())
        );

        $sendedMail = new Email()
            ->from(
                new Address(
                    $email->getCreatedBy()->getEmail(),
                    $email->getCreatedBy()->getFullName(),
                )
            )
            ->to(...$allRecievers)
            ->subject($email->getSubject())
            ->html($email->getContent());

        $attachments = [];
        $finder = new Finder();
        $finder->files()->in($personalPath);

        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $sendedMail->addPart(
                    new DataPart(
                        new File($file->getRealPath())
                    )
                );

                $attachments[] = $file->getBasename();
            }
        }

        $this->mailer->send($sendedMail);

        $email
            ->setSendedAt(new DateTime())
            ->setAttachment(implode(', ', $attachments));

        $this->mailingQueueRepository->save($email, true);
    }
}
