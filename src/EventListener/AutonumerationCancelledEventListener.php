<?php

namespace App\EventListener;

use App\Event\AutonumerationCancelledEvent;
use App\Repository\CourseRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: AutonumerationCancelledEvent::class)]
class AutonumerationCancelledEventListener
{
    public function __construct(
        private readonly CourseRepository $courseRepository,
    ) {}

    public function __invoke(AutonumerationCancelledEvent $event): void
    {
        $course = $this->courseRepository->find($event->courseId);

        $course->setAutonumerationCompleted(false);

        $this->courseRepository->save($course, true);
    }
}
