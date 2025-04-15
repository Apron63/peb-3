<?php

declare (strict_types=1);

namespace App\EventListener;

use App\Entity\Permission;
use App\Repository\LoggerRepository;
use App\Repository\PreparationHistoryRepository;
use App\Service\MailingService;
use App\Service\Whatsapp\WhatsappService;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class PermissionEventListener
{
    public function __construct(
        private readonly LoggerRepository $loggerRepository,
        private readonly MailingService $mailingService,
        private readonly WhatsappService $whatsappService,
        private readonly PreparationHistoryRepository $preparationHistoryRepository,
    ) {}

    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (! $entity instanceof Permission) {
            return;
        }

        $this->loggerRepository->removeLoggerForPermission($entity);
        $this->preparationHistoryRepository->removePreparationHistoryForPermission($entity);
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (! $entity instanceof Permission) {
            return;
        }

        $this->mailingService->addNewPermissionToMailQueue($entity);

        $this->whatsappService->addNewPermissionToWhatsappQueue($entity);
    }
}
