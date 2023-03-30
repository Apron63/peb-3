<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\User;
use App\Repository\CourseInfoRepository;
use App\Repository\CourseThemeRepository;
use App\Repository\PermissionRepository;

class MyProgramsService
{
    public function __construct(
        readonly PermissionRepository $permissionRepository,
        readonly CourseInfoRepository $courseInfoRepository,
        readonly CourseThemeRepository $courseThemeRepository,
        readonly CourseService $courseService
    ) {}

    public function createSideMenuForUser(User $user): array
    {
        $result = [];

        $permissions = $this->permissionRepository->getPermissionLeftMenu($user);

        foreach ($permissions as $permission) {
            $courseInfo = $this->courseInfoRepository->findBy(['course' => $permission->getCourse()]);

            $result[] = [
                'id' => $permission->getId(),
                'name' => $permission->getCourse()->getName(),
                'shortName' =>  $permission->getCourse()->getShortName(),
                'type' => $permission->getCourse()->getType(),
                'courseInfo' => $courseInfo,
                'courseId' => $permission->getCourse()->getId(),
                'hasMultipleThemes' => $this->hasMultipleThemes($permission->getCourse()),
                'courseMenu' => $this->courseService->checkForCourseStage($permission, false),
            ];
        }

        return $result;
    }

    private function hasMultipleThemes(Course $course): bool
    {
        $result = false;

        if ($course->getType() === Course::CLASSC) {
            if (count($this->courseThemeRepository->getCourseThemes($course)) > 1) {
                $result = true;
            }
        }

        return $result;
    }
}
