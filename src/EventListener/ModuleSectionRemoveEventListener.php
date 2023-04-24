<?php

namespace App\EventListener;

use App\Entity\ModuleSection;
use App\Repository\ModuleSectionPageRepository;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class ModuleSectionRemoveEventListener
{
    public function __construct(
        private readonly ModuleSectionPageRepository $moduleSectionPageRepository,
    ) {}

    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof ModuleSection) {
            return;
        }

        $this->moduleSectionPageRepository->removeModuleSectionPage($entity);
    }
}
