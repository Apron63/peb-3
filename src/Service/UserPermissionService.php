<?php

namespace App\Service;

use DateTime;
use App\Entity\User;
use App\Entity\Permission;
use App\Entity\MailingQueue;
use App\Entity\ModuleSection;
use App\Repository\PermissionRepository;
use App\Repository\MailingQueueRepository;

class UserPermissionService
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
        private readonly CourseService $courseService,
        private readonly MailingQueueRepository $mailingQueueRepository,
        private readonly DashboardService $dashboardService,
        private readonly ConfigService $configService,
    ) {}

    public function checkPermissionForUser(Permission $permission, User $user, bool $canChangeStage): bool
    {
        $timeNow = new DateTime();

        $permissions = $this->permissionRepository->getPermissionQuery($user)->getResult();
        
        $courseIds = array_map(
            fn($permission) => $permission['courseId'],
            array_filter($permissions, fn($permission) => $permission['isActive'])
        );

        $result = in_array($permission->getCourse()->getId(), $courseIds);

        if ($result && $canChangeStage) {
            if (null === $permission->getActivatedAt()) {
                $permission->setActivatedAt($timeNow)
                    ->setStage(Permission::STAGE_IN_PROGRESS)
                    ->setHistory($this->createHistory($permission));

                if (null !== $permission->getUser()->getEmail()) {
                    $mailingQueue = new MailingQueue;

                    $content = $this->dashboardService->replaceValue(
                        $this->configService->getConfigValue('userHasActivatedPermission'),
                        [
                            '{COURSE}',
                            '{LASTDATE}',
                        ],
                        [
                            $permission->getCourse()->getName(),
                            $permission->getEndDate()->format('d.m.Y'),
                        ],
                        $permission->getCreatedBy()
                    );

                    $mailingQueue
                        ->setUser($permission->getUser())
                        ->setSubject('Активирован учебный курс')
                        ->setContent($content);
    
                    $this->mailingQueueRepository->save($mailingQueue, true);
                }
            }

            $permission->setLastAccess($timeNow);

            $this->permissionRepository->save($permission, true);
        }

        return $result;
    }

    public function checkPermissionHistory(Permission $permission, ModuleSection $moduleSection)
    {
        if ($moduleSection->getType() === ModuleSection::TYPE_INTERMEDIATE) {
            $history = $permission->getHistory();
            
            $moduleId = $moduleSection->getModule()->getId();

            foreach ($permission->getHistory() as $moduleKey => $module) {
                if ($module['moduleId'] === $moduleId) {
                    $history[$moduleKey]['active'] = true;

                    $permission->setHistory($history);
                    $this->permissionRepository->save($permission, true);
                    break;
                }
            }
        }
    }

    private function createHistory(Permission $permission): array
    {
        $courseProgress = $this->courseService->checkForCourseStage($permission);

        $history = [];

        foreach ($courseProgress as $module) {
            $sections = [];

            foreach ($module['sections'] as $section) {
                $sections[] = [
                    'id' => $section['id'],
                    'time' => 0,
                ];
            }

            $history[] = [
                'moduleId' => $module['id'],
                'sections' => $sections,
                'active' => false,
            ];    
        }

        return $history;
    }
}
