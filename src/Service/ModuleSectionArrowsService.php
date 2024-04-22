<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Permission;
use App\Repository\ModuleRepository;
use App\Repository\ModuleSectionRepository;
use App\Service\CourseService;

class ModuleSectionArrowsService
{
    private array $choices = [];
    private array $groups = [];

    public function __construct(
        private readonly ModuleSectionRepository $moduleSectionRepository,
        private readonly ModuleRepository $moduleRepository,
        private readonly CourseService $courseService,
    ) {}

    public function getModuleSectionList(Course $course): array
    {
        $moduleSections = $this->moduleSectionRepository->getModuleSectionsListByCourse($course);
        $modules = $this->moduleRepository->getModules($course);

        $modulesById = [];
        foreach ($modules as $module) {
            $modulesById[$module['id']] = $module['name'];
        }

        $this->groups = [];
        foreach ($moduleSections as $moduleSection) {
            $this->groups[$moduleSection->getId()] = $modulesById[$moduleSection->getModule()->getId()];
        }

        $this->choices['Не задано'] = null;

        foreach ($moduleSections as $moduleSection) {
            $this->choices[$moduleSection->getName()] = $moduleSection->getId();
        }

        return $this->choices;
    }

    public function getModuleSectionGroup(?int $choice): ?string
    {
        return $this->groups[$choice] ?? null;
    }

    public function isFinalTestingEnabled(Permission $permission): bool
    {
        $courseProgressItems = $this->courseService->checkForCourseStage($permission);

        foreach ($courseProgressItems as $item) {
            if (! $item['active']) {
                return false;
            }
        }

        return true;
    }
}
