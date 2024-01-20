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
use Symfony\Component\Filesystem\Filesystem;

readonly class CourseRemoveEventListener
{
    public function __construct(
        private CourseInfoRepository $courseInfoRepository,
        private CourseThemeRepository $courseThemeRepository,
        private QuestionsRepository $questionsRepository,
        private PermissionRepository $permissionRepository,
        private TicketRepository $ticketRepository,
        private ModuleRepository $moduleRepository,
        private Filesystem $filesystem,
        private string $courseUploadPath,
    ) {}

    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (! $entity instanceof Course) {
            return;
        }

        if (Course::INTERACTIVE === $entity->getType()) {
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

        $this->filesystem->remove($path);
    }
}
