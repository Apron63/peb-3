<?php

namespace App\Service;

use App\Entity\ModuleSection;
use App\Entity\Permission;
use App\Entity\User;
use App\Repository\PermissionRepository;

class UserPermissionService
{
    public function __construct(
        readonly PermissionRepository $permissionRepository,
        readonly CourseService $courseService,
    ) { }

    public function checkPermissionForUser(Permission $permission, User $user, bool $canChangeStage): bool
    {

        $permissions = $this->permissionRepository->getPermissionQuery($user)->getResult();
        
        $courseIds = array_map(
            function($row){
                return $row['courseId'];
            }, 
            $permissions
        );

        $result = in_array($permission->getCourse()->getId(), $courseIds);

        if ($result) {
            if ($canChangeStage && null === $permission->getActivatedAt()) {
                $permission->setActivatedAt(new \DateTime())
                    ->setStage(Permission::STAGE_IN_PROGRESS)
                    ->setHistory($this->createHistory($permission));
                $this->permissionRepository->save($permission, true);
            }
        }

        return $result;
    }

    public function checkPermissionHistory(Permission $permission, ModuleSection $moduleSection)
    {
        $currentSection = false;
        $history = $permission->getHistory();


        foreach($permission->getHistory() as $moduleKey => $module) {
            foreach($module['sections'] as $sectionKey => $section) {
                if ($currentSection) {
                    if (false === $section['active']) {
                        $history[$moduleKey]['sections'][$sectionKey]['active'] = true;

                        $permission->setHistory($history);
                        $this->permissionRepository->save($permission, true);
                    }

                    break 2;    
                }

                if (
                    $module['moduleId'] === $moduleSection->getModule()->getId()
                    && $section['id'] === $moduleSection->getId()
                ) {
                    $currentSection = true;
                }
            }
        }
    }

    private function createHistory(Permission $permission): array
    {
        $courseProgress = $this->courseService->checkForCourseStage($permission);

        $history = [];
        $firstBreak = true;

        foreach($courseProgress as $module) {
            $sections = [];

            foreach($module['sections'] as $section) {
                $sections[] = [
                    'id' => $section['id'],
                    'active' => $firstBreak,
                    'time' => 0,
                ];

                if ($firstBreak) {
                    $firstBreak = false;
                }
            }

            $history[] = [
                'moduleId' => $module['id'],
                'sections' => $sections,
            ];    
        }

        return $history;
    }
}
