<?php

namespace App\EventListener;

use App\Entity\Permission;
use App\Repository\LoggerRepository;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class PermissionRemoveEventListener
{
    public function __construct(
        private readonly LoggerRepository $loggerRepository
    ) {}

    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Permission) {
            return;
        }

        $this->loggerRepository->removeLoggerForPermission($entity);
    }
}
