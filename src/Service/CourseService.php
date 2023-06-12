<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\CourseTheme;
use App\Entity\ModuleSection;
use App\Entity\Permission;
use App\Repository\CourseThemeRepository;
use App\Repository\ModuleRepository;
use App\Repository\ModuleSectionRepository;
use App\Repository\PermissionRepository;

class CourseService
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
        private readonly ModuleSectionRepository $moduleSectionRepository,
        private readonly ModuleRepository $moduleRepository,
        private readonly CourseThemeRepository $courseThemeRepository,
    ) {}

    public function getClassicCourseTheme(Course $course): ?int
    {
        $themeId = null;

        if (! $this->hasMultipleThemes($course)) {
            $courseTheme = $this->courseThemeRepository->findOneBy(['course' => $course]);

            if ($courseTheme instanceof CourseTheme) {
                $themeId = $courseTheme->getId();
            }
        }

        return $themeId;
    }

    public function hasMultipleThemes(Course $course): bool
    {
        $result = false;

        if ($course->getType() === Course::CLASSC) {
            if (count($this->courseThemeRepository->getCourseThemes($course)) > 1) {
                $result = true;
            }
        }

        return $result;
    }
    
    public function checkForCourseStage(Permission $permission, bool $enableChangeStage = false): array
    {
        $courseProgress = [];

        if (Course::INTERACTIVE === $permission->getCourse()->getType()) {
            $courseProgress = $this->synchronizeWithPermission(
                $this->getModuleSectionByCourse($permission->getCourse()), 
                $permission
            );
        }

        return $courseProgress;
    }

    public function getCourseProgressForDemo(Course $course): array
    {
        return $this->getModuleSectionByCourse($course);
    }

    private function getModuleSectionByCourse(Course $course): array
    {
        $data = [];

        $modules = $this->moduleRepository->findBy(['course' => $course]);

        if (!empty($modules)) {
            foreach($modules as $module) {
                $moduleSections = $this->moduleSectionRepository->findBy(['module' => $module]);

                if (!empty($moduleSections)) {
                    $sectionData = [];
                    $isActive = true;

                    foreach($moduleSections as $section) {
                        if ($section->getType() === ModuleSection::TYPE_INTERMEDIATE) {
                            $isActive = false;
                        }
                        $sectionData[] = [
                            'id' => $section->getId(),
                            'name' => $section->getName(),
                        ];
                    }

                    $data[] = [
                        'id' => $module->getId(),
                        'courseId' => $course->getId(),
                        'name' => $module->getName(),
                        'sections' => $sectionData,
                        'active' => $isActive,
                    ];
                }
            }
        }

        return $data;
    }

    private function synchronizeWithPermission(array $data, Permission $permission): array
    {
        $history = $permission->getHistory();

        foreach($data as $moduleKey => $module) {
            foreach ($history as $row) {
                if ($row['moduleId'] === $module['id']) {
                    if ($row['active']) {
                        $data[$moduleKey]['active'] = true;
                    }

                    break;
                }
            }
        }

        return $data;
    }
}
