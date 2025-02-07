<?php

declare (strict_types=1);

namespace App\Service;

use App\Entity\Course;
use App\Entity\MailingQueue;
use App\Entity\ModuleSection;
use App\Entity\Permission;
use App\Entity\User;
use App\Repository\MailingQueueRepository;
use App\Repository\PermissionRepository;
use App\Service\Whatsapp\WhatsappService;
use DateTime;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserPermissionService
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
        private readonly CourseService $courseService,
        private readonly MailingQueueRepository $mailingQueueRepository,
        private readonly WhatsappService $whatsappService,
        private readonly DashboardService $dashboardService,
        private readonly ConfigService $configService,
    ) {}

    public function checkPermissionForUser(Permission $permission, ?User $user, bool $canChangeStage): bool
    {
        if (! $user instanceof User) {
            throw new NotFoundHttpException('User: ' . $user?->getId() . ' not Found');
        }

        if ($permission->getUser() !== $user) {
            throw new AccessDeniedException('Permission: ' . $permission->getId() . ' not available for user: ' . $user->getId());
        }

        $timeNow = new DateTime();

        $permissions = $this->permissionRepository->getPermissionQuery($user)->getResult();

        $courseIds = array_map(
            fn ($permission) => $permission['courseId'],
            array_filter($permissions, fn ($permission) => $permission['isActive'])
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
                        ->setCreatedBy($permission->getCreatedBy())
                        ->setReciever($permission->getUser()->getEmail())
                        ->setContent($content);

                    $this->mailingQueueRepository->save($mailingQueue, true);
                }

                if (null !== $permission->getUser()->getMobilePhone()) {
                    $this->whatsappService->userHasActivatedPermission($permission);
                }
            }

            $permission->setLastAccess($timeNow);

            $this->permissionRepository->save($permission, true);
        }

        return $result;
    }

    public function checkPermissionHistory(Permission $permission, ModuleSection $moduleSection): void
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

    public function createHistory(Permission $permission): array
    {
        $history = [];

        if (Course::INTERACTIVE === $permission->getCourse()->getType()) {
            $courseProgress = $this->courseService->checkForCourseStage($permission);

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
        }

        return $history;
    }
}
