<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\CourseTheme;
use App\Entity\User;
use App\Repository\CourseInfoRepository;
use App\Repository\CourseThemeRepository;
use App\Repository\PermissionRepository;

class MyProgramsService
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
        private readonly CourseInfoRepository $courseInfoRepository,
        private readonly CourseThemeRepository $courseThemeRepository,
        private readonly CourseService $courseService
    ) {}

    public function createSideMenuForUser(User $user): array
    {
        $result = [];

        $permissions = $this->permissionRepository->getPermissionLeftMenu($user);

        foreach ($permissions as $permission) {
            $courseInfo = $this->courseInfoRepository->findBy(['course' => $permission->getCourse()]);

            $hasMultipleThemes = $this->courseService->hasMultipleThemes($permission->getCourse());

            $themeId = null;
            if (!$hasMultipleThemes) {
                $courseTheme = $this->courseThemeRepository->findOneBy(['course' => $permission->getCourse()]);

                if ($courseTheme instanceof CourseTheme) {
                    $themeId = $courseTheme->getId();
                }
            }

            $result[] = [
                'id' => $permission->getId(),
                'name' => $permission->getCourse()->getName(),
                'shortName' =>  $permission->getCourse()->getShortName(),
                'type' => $permission->getCourse()->getType(),
                'courseInfo' => $courseInfo,
                'courseId' => $permission->getCourse()->getId(),
                'hasMultipleThemes' => $hasMultipleThemes,
                'themeId' => $themeId,
                'courseMenu' => $this->courseService->checkForCourseStage($permission, false),
                'surveyEnabled' => $permission->isSurveyEnabled(),
            ];
        }

        return $result;
    }
}
