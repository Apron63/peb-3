<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\User;
use App\Repository\PermissionRepository;

class UserPermissionService
{
    public function __construct(
        readonly PermissionRepository $permissionRepository
    ) { }

    public function checkPermissionForUser(Course $course, User $user): bool
    {
        $permissions = $this->permissionRepository->getPermissionQuery($user)->getResult();
        $courseIds = array_map(function($row){
            return $row['courseId'];
        }, $permissions);

        return in_array($course->getId(), $courseIds);
    }
}
