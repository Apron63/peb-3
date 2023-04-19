<?php

namespace App\EventListener;

use App\Entity\Course;
use App\Repository\ActionRepository;
use App\Repository\CourseInfoRepository;
use App\Repository\CourseThemeRepository;
use App\Repository\LoggerRepository;
use App\Repository\ModuleRepository;
use App\Repository\PermissionRepository;
use App\Repository\QuestionsRepository;
use App\Repository\TicketRepository;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class CourseRemoveEventListener
{
    public function __construct(
        private readonly ActionRepository $actionRepository,
        private readonly CourseInfoRepository $courseInfoRepository,
        private readonly CourseThemeRepository $courseThemeRepository,
        private readonly QuestionsRepository $questionsRepository,
        private readonly PermissionRepository $permissionRepository,
        private readonly LoggerRepository $loggerRepository,
        private readonly TicketRepository $ticketRepository,
        private readonly ModuleRepository $moduleRepository,
    ) {}

    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Course) {
            return;
        }

        if ($entity->getType() === Course::INTERACTIVE) {
            $this->moduleRepository->removeModuleFromCourse($entity);
        }

        $this->actionRepository->removeActionForCourse($entity);
        $this->courseInfoRepository->removeCourseInfoForCourse($entity);
        $this->courseThemeRepository->removeCourseThemeForCourse($entity);
        $this->questionsRepository->removeQuestionsForCourse($entity);
        $this->loggerRepository->removeLoggerForCourse($entity);
        $this->permissionRepository->removePermissionForCourse($entity);
        $this->ticketRepository->deleteOldTickets($entity);
    }
}
