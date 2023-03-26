<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\CourseInfoRepository;
use App\Repository\PermissionRepository;

class MyProgramsService
{
    public function __construct(
        readonly PermissionRepository $permissionRepository,
        readonly CourseInfoRepository $courseInfoRepository,
        readonly CourseService $courseService
    ) {}

    public function createSideMenuForUser(User $user): array
    {
        $result = [];

        $permissions = $this->permissionRepository->getPermissionLeftMenu($user);

        foreach ($permissions as $permission) {
            $courseInfoUrl = null;

            $courseInfo = $this->courseInfoRepository->findBy(['course' => $permission->getCourse()]);

            if (null !== $courseInfo) {
                $courseInfoUrl = 'url';
            }

            $result[] = [
                'id' => $permission->getId(),
                'name' => $permission->getCourse()->getName(),
                'shortName' =>  $permission->getCourse()->getShortName(),
                'type' => $permission->getCourse()->getType(),
                'courseInfo' => $courseInfo,
                'courseId' => $permission->getCourse()->getId(),
                'courseMenu' => $this->courseService->checkForCourseStage($permission),
            ];
        }

        return $result;
    }
}
