<?php

declare (strict_types=1);

namespace App\EventListener;

use App\Entity\Permission;
use App\Entity\PermissionHistory;
use App\Entity\User;
use App\Repository\LoggerRepository;
use App\Repository\PermissionHistoryRepository;
use App\Repository\PreparationHistoryRepository;
use App\Service\MailingService;
use App\Service\Whatsapp\WhatsappService;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Bundle\SecurityBundle\Security;

class PermissionEventListener
{
    public function __construct(
        private readonly LoggerRepository $loggerRepository,
        private readonly MailingService $mailingService,
        private readonly WhatsappService $whatsappService,
        private readonly PermissionHistoryRepository $permissionHistoryRepository,
        private readonly PreparationHistoryRepository $preparationHistoryRepository,
        private Security $security,
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

    public function postPersist($args): void
    {
        $entity = $args->getObject();

        if (! $entity instanceof Permission) {
            return;
        }

        $user = $this->security->getUser();

        if (! $user instanceof  User) {
            $user = $entity->getCreatedBy();
        }

        $permissionHistory = new PermissionHistory;
        $permissionHistory
            ->setPermissionId($entity->getId())
            ->setDuration($entity->getDuration())
            ->setCreatedBy($user);

        $this->permissionHistoryRepository->save($permissionHistory, true);
    }
}
