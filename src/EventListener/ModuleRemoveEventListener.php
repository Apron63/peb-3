<?php

namespace App\EventListener;

use App\Entity\Module;
use App\Repository\ModuleSectionRepository;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class ModuleRemoveEventListener
{
    public function __construct(
        private readonly ModuleSectionRepository $moduleSectionRepository,
    ) {}

    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Module) {
            return;
        }

        $this->moduleSectionRepository->removeModuleSection($entity);
    }
}
