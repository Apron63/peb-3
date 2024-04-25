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

    public function autonumerationCourse(Course $course): void
    {
        $moduleSections = $this->moduleSectionRepository->getModuleSectionsListByCourse($course);

        $moduleSectionsByNom = [];
        $nom = 0;

        foreach ($moduleSections as $moduleSection) {
            $moduleSectionsByNom[$nom++] = $moduleSection;
        }

        $first = array_key_first($moduleSectionsByNom);
        $last = array_key_last($moduleSectionsByNom);

        foreach ($moduleSectionsByNom as $key => $moduleSection) {
            if ($key === $last) {
                $moduleSection
                    ->setPrevMaterialId(null)
                    ->setNextMaterialId(null)
                    ->setFinalTestingIsNext(true);
            } else if ($key === $first) {
                $moduleSection
                    ->setPrevMaterialId(null)
                    ->setNextMaterialId($moduleSectionsByNom[$key + 1]->getId())
                    ->setFinalTestingIsNext(false);
            } else {
                $moduleSection
                    ->setPrevMaterialId($moduleSectionsByNom[$key - 1]->getId())
                    ->setNextMaterialId($moduleSectionsByNom[$key + 1]->getId())
                    ->setFinalTestingIsNext(false);
            }

            $this->moduleSectionRepository->save($moduleSection, true);
        }
    }
}
