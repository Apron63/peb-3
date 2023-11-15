<?php

namespace App\Service;

use App\Entity\Permission;
use App\Repository\PermissionRepository;

class BatchCreatePermissionService
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
    ) {}

    public function batchCreatePermission(array $data): void
    {
        $permissions = $this->permissionRepository->getPermissionLeftMenu($data['user']);

        $activeCoursesIds = array_map(
            fn($permission) => $permission->getCourse()->getId(),
            $permissions
        );

        foreach($data['course'] as $course) {
            if(in_array($course->getId(), $activeCoursesIds)) {
                continue;
            }

            $permission = new Permission();

            $permission
                ->setUser($data['user'])
                ->setCourse($course)
                ->setOrderNom($data['orderNom'])
                ->setDuration($data['duration'])
                ->setCreatedBy($data['creator']);

            $this->permissionRepository->save($permission, true);
        }
    }
}
