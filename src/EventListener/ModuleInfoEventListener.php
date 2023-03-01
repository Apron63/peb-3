<?php

namespace App\EventListener;

use App\Entity\ModuleInfo;
use App\Service\StorageService;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class ModuleInfoEventListener
{
    private StorageService $storageService;

    public function __construct(StorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof ModuleInfo) {
            return;
        }

        $courseId = $entity->getModule()->getCourse()->getId();
        $moduleId = $entity->getId();

        $path = getcwd() . "/storage/interactive/$courseId/$moduleId";

        if (is_dir($path)) {
            $this->storageService->removeDirectory($path);
        }
    }
}
