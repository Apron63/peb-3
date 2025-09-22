<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\CourseTheme;
use App\Entity\User;
use App\Repository\CourseInfoRepository;
use App\Repository\CourseRepository;
use App\Repository\CourseThemeRepository;
use App\Repository\PermissionRepository;

class MyProgramsService
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
        private readonly CourseInfoRepository $courseInfoRepository,
        private readonly CourseThemeRepository $courseThemeRepository,
        private readonly CourseService $courseService,
        private readonly CourseRepository $courseRepository,
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

    public function createSideMenuForDemo(): array
    {
        $result = [];

        $courses = $this->courseRepository->findBy(['forDemo' => true]);
        foreach ($courses as $course) {
            $courseInfo = array_filter(
                $this->courseInfoRepository->findBy(['course' => $course]),
                static fn ($info) => !empty($info->getName()),
            );

            $hasMultipleThemes = $this->courseService->hasMultipleThemes($course);
            $themeId = null;
            if (!$hasMultipleThemes) {
                $courseTheme = $this->courseThemeRepository->findOneBy(['course' => $course]);

                if ($courseTheme instanceof CourseTheme) {
                    $themeId = $courseTheme->getId();
                }
            }

            $result[] = [
                'id' => $course->getId(), // TODO
                'name' => $course->getName(),
                'shortName' =>  $course->getShortName(),
                'type' => $course->getType(),
                'courseInfo' => $courseInfo,
                'courseId' => $course->getId(),
                'hasMultipleThemes' => $hasMultipleThemes,
                'themeId' => $themeId,
                'courseMenu' => $this->courseService->getCourseProgressForDemo($course),
                'surveyEnabled' => false,
            ];
        }

        return $result;
    }
}
