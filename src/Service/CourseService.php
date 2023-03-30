<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Permission;
use App\Repository\ModuleRepository;
use App\Repository\ModuleSectionRepository;
use App\Repository\PermissionRepository;
use DateTime;

class CourseService
{
    public function __construct(
        readonly PermissionRepository $permissionRepository,
        readonly ModuleSectionRepository $moduleSectionRepository,
        readonly ModuleRepository $moduleRepository
    ) {}

    public function checkForCourseStage(Permission $permission, bool $enableChangeStage = false): array
    {
        $courseProgress = [];

        // if ($enableChangeStage && null === $permission->getActivatedAt()) {
        //     $permission
        //         ->setActivatedAt(new DateTime())
        //         ->setStage(Permission::STAGE_IN_PROGRESS);

        //     $this->permissionRepository->save($permission, true);
        // }

        if (Course::INTERACTIVE === $permission->getCourse()->getType()) {
            $courseProgress = $this->synchronizeWithPermission(
                $this->getModuleSectionByCourse($permission->getCourse()), 
                $permission
            );
        }

        return $courseProgress;
    }

    private function getModuleSectionByCourse(Course $course): array
    {
        $data = [];
        $firstBreak = true;

        $modules = $this->moduleRepository->findBy(['course' => $course]);

        if (!empty($modules)) {
            foreach($modules as $module) {
                $moduleSections = $this->moduleSectionRepository->findBy(['module' => $module]);

                if (!empty($moduleSections)) {
                    $sectionData = [];

                    foreach($moduleSections as $section) {
                        $sectionData[] = [
                            'id' => $section->getId(),
                            'name' => $section->getName(),
                            'url' => $section->getUrl(),
                            'urlType' => $section->getUrlType(),
                            'part' => $section->getPart(),
                            'active' => $firstBreak,
                        ];

                        if ($firstBreak) {
                            $firstBreak = false;
                        }
                    }

                    $data[] = [
                        'id' => $module->getId(),
                        'courseId' => $course->getId(),
                        'name' => $module->getName(),
                        'sections' => $sectionData,
                        'active' => false,
                    ];
                }
            }
        }
        
        $data[0]['active'] = true;

        return $data;
    }

    private function synchronizeWithPermission(array $data, Permission $permission): array
    {
        foreach($data as $moduleKey => $module) {
            foreach ($module['sections'] as $sectionKey => $section) {
                if (isset($data[$moduleKey]['sections'][$sectionKey]['active'])) {
                    $data[$moduleKey]['sections'][$sectionKey]['active'] = true;
                }
            }

            $data[$moduleKey]['active'] = true;
        }

        return $data;
    }
}
