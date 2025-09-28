<?php

declare (strict_types=1);

namespace App\Service\EmailService;

use App\Entity\MailingQueue;
use App\Entity\User;
use App\Repository\MailingQueueRepository;
use App\Repository\PermissionRepository;
use App\Service\ConfigService;
use App\Service\DashboardService;

class UserSenderService
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
        private readonly DashboardService $dashboardService,
        private readonly ConfigService $configService,
        private readonly MailingQueueRepository $mailingQueueRepository,
    ) {}

    public function resendToUser(User $user): void
    {
        $activePermissions = $this->permissionRepository->getPermissionLeftMenu($user);

        foreach ($activePermissions as $permission) {
            $content = $this->dashboardService->replaceValue(
                $this->configService->getConfigValue('userHasNewPermission'),
                [
                    '{LOGIN}',
                    '{PASSWORD}',
                    '{DURATION}',
                    '{COURSE}',
                    '{LASTDATE}',
                ],
                [
                    $permission->getUser()->getLogin(),
                    $permission->getUser()->getPlainPassword(),
                    $permission->getDuration(),
                    $permission->getCourse()->getName(),
                    $permission->getEndDate()->format('d.m.Y'),
                ],
                $permission->getCreatedBy(),
            );

            $mail = new MailingQueue()
                ->setUser($permission->getUser())
                ->setSubject('Вам назначен курс : ' . $permission->getCourse()->getShortName())
                ->setCreatedBy($permission->getCreatedBy())
                ->setReciever($permission->getUser()->getEmail())
                ->setContent($content);

            $this->mailingQueueRepository->save($mail, true);
        }
    }
}
