<?php

declare (strict_types=1);

namespace App\Service;

use App\Entity\Course;
use App\Entity\User;
use App\Event\ActionLogEvent;
use App\Repository\CourseRepository;
use App\Repository\ModuleRepository;
use App\Repository\ModuleSectionRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ModuleSectionArrowsService
{
    private array $choices = [];
    private array $groups = [];

    public function __construct(
        private readonly ModuleSectionRepository $moduleSectionRepository,
        private readonly ModuleRepository $moduleRepository,
        private readonly CourseRepository $courseRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
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

    public function autonumerationCourse(Course $course, User $user): void
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
                    ->setPrevMaterialId($moduleSectionsByNom[$key - 1]->getId())
                    ->setNextMaterialId(null);
            }
            elseif ($key === $first) {
                $moduleSection
                    ->setPrevMaterialId(null)
                    ->setNextMaterialId($moduleSectionsByNom[$key + 1]->getId());
            }
            else {
                $moduleSection
                    ->setPrevMaterialId($moduleSectionsByNom[$key - 1]->getId())
                    ->setNextMaterialId($moduleSectionsByNom[$key + 1]->getId());
            }

            $this->moduleSectionRepository->save($moduleSection, true);
        }

        $course->setAutonumerationCompleted(true);
        $this->courseRepository->save($course, true);

        $this->eventDispatcher->dispatch(new ActionLogEvent(
            $user,
            'Выполнена автонумерация курса: ' . $course->getShortName(),
        ));
    }
}
