<?php

namespace App\Service;

use App\Entity\Permission;
use App\Repository\PermissionRepository;
use DateTime;

class PermissionService
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
    ) {}

    public function setTimeSpent(Permission $permission, int $startTime): void
    {
        $timeNow = new DateTime();
        $timeSpent = $permission->getTimeSpent();
        $timeSpentNow = $timeNow->getTimestamp() - $startTime;
        $permission->setTimeSpent($timeSpent + $timeSpentNow);
        
        $this->permissionRepository->save($permission, true);
    }
}
