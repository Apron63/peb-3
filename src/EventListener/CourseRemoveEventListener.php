<?php

namespace App\EventListener;

use App\Entity\Course;
use App\Repository\CourseInfoRepository;
use App\Repository\CourseThemeRepository;
use App\Repository\ModuleRepository;
use App\Repository\PermissionRepository;
use App\Repository\QuestionsRepository;
use App\Repository\TicketRepository;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class CourseRemoveEventListener
{
    public function __construct(
        private readonly CourseInfoRepository $courseInfoRepository,
        private readonly CourseThemeRepository $courseThemeRepository,
        private readonly QuestionsRepository $questionsRepository,
        private readonly PermissionRepository $permissionRepository,
        private readonly TicketRepository $ticketRepository,
        private readonly ModuleRepository $moduleRepository,
        private readonly string $courseUploadPath,
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

        $this->courseInfoRepository->removeCourseInfoForCourse($entity);
        $this->courseThemeRepository->removeCourseThemeForCourse($entity);
        $this->questionsRepository->removeQuestionsForCourse($entity);
        $this->permissionRepository->removePermissionForCourse($entity);
        $this->ticketRepository->deleteOldTickets($entity);

        $this->removeCourseDirectory($entity);
    }

    private function removeCourseDirectory(Course $course): void
    {
        $path = $this->courseUploadPath . DIRECTORY_SEPARATOR . $course->getId();

        $files = glob($path . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        rmdir($path);
    }
}
