<?php

namespace App\Service;

use App\Entity\MailingQueue;
use App\Entity\Permission;
use App\Repository\MailingQueueRepository;

class MailingService
{
    public function __construct(
        private readonly MailingQueueRepository $mailingQueueRepository,
        private readonly DashboardService $dashboardService,
        private readonly ConfigService $configService,
    ) {}

    public function addNewPermissionToMailQueue(Permission $permission): void
    {
        if (
            null === $permission->getId()
            && null !== $permission->getUser()->getEmail()
        ) {
            $content = $this->dashboardService->replaceValue(
                $this->configService->getConfigValue('userHasNewPermission'),
                [
                    '{LOGIN}',
                    '{PASSWORD}',
                    '{DURATION}',
                    '{COURSE}',
                ],
                [
                    $permission->getUser()->getLogin(),
                    $permission->getUser()->getPlainPassword(),
                    $permission->getDuration(),
                    $permission->getCourse()->getName(),
                ],
                $permission->getCreatedBy(),
            );

            $mail = (new MailingQueue())
                ->setUser($permission->getUser())
                ->setSubject('Вам назначен курс : ' . $permission->getCourse()->getShortName())
                ->setCreatedBy($permission->getCreatedBy())
                ->setReciever($permission->getUser()->getEmail())
                ->setContent($content);

            $this->mailingQueueRepository->save($mail, true);
        }
    }
}
