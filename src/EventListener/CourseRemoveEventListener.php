<?php

namespace App\EventListener;

use App\Entity\Course;
use App\Repository\ActionRepository;
use App\Repository\CourseInfoRepository;
use App\Repository\CourseThemeRepository;
use App\Repository\LoggerRepository;
use App\Repository\PermissionRepository;
use App\Repository\QuestionsRepository;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class CourseRemoveEventListener
{
    public function __construct(
        readonly ActionRepository $actionRepository,
        readonly CourseInfoRepository $courseInfoRepository,
        readonly CourseThemeRepository $courseThemeRepository,
        readonly QuestionsRepository $questionsRepository,
        readonly PermissionRepository $permissionRepository,
        readonly LoggerRepository $loggerRepository,
    ) {}

    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Course) {
            return;
        }

        $this->actionRepository->removeActionForCourse($entity);
        $this->courseInfoRepository->removeCourseInfoForCourse($entity);
        $this->courseThemeRepository->removeCourseThemeForCourse($entity);
        $this->questionsRepository->removeQuestionsForCourse($entity);
        $this->loggerRepository->removeLoggerForCourse($entity);
        $this->permissionRepository->removePermissionForCourse($entity);
    }
}
