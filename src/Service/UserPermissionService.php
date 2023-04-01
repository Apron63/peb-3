<?php

namespace App\Service;

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
