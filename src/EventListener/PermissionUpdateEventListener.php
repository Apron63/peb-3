<?php

declare (strict_types=1);

namespace App\EventListener;

use App\Entity\Permission;
use App\Entity\PermissionHistory;
use App\Repository\PermissionHistoryRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::postUpdate)]
class PermissionUpdateEventListener
{
    public function __construct(
        private readonly PermissionHistoryRepository $permissionHistoryRepository,
        private Security $security,
    ) {}

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (! $entity instanceof Permission) {
            return;
        }

        $entityManager = $args->getObjectManager();
        /** @disregard Undefined method 'getUnitOfWork'.intelephense(P1013) */
        $unitOfWork = $entityManager->getUnitOfWork();
        $changeSet = $unitOfWork->getEntityChangeSet($entity);

        if (isset($changeSet['duration'])) {
            $duration = $changeSet['duration'][1] - $changeSet['duration'][0];
            $user = $this->security->getUser();

            $permissionHistory = new PermissionHistory()
                ->setPermissionId($entity->getId())
                ->setDuration($duration)
                ->setCreatedBy($user)
                ->setInitial(false);

            $this->permissionHistoryRepository->save($permissionHistory, true);
        }
    }
}
