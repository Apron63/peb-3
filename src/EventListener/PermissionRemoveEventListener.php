<?php

namespace App\EventListener;

use App\Entity\Permission;
use App\Repository\LoggerRepository;
use App\Service\MailingService;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class PermissionRemoveEventListener
{
    public function __construct(
        private readonly LoggerRepository $loggerRepository,
        private readonly MailingService $mailingService,
    ) {}

    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (! $entity instanceof Permission) {
            return;
        }

        $this->loggerRepository->removeLoggerForPermission($entity);
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (! $entity instanceof Permission) {
            return;
        }

        $this->mailingService->addNewPermissionToMailQueue($entity);
    }
}
