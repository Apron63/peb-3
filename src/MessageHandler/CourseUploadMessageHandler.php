<?php

namespace App\MessageHandler;

use App\Message\CourseUploadMessage;
use App\Service\CourseUploadService;
use App\Service\JobService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CourseUploadMessageHandler
{
    public function __construct(
        private readonly CourseUploadService $courseUploadService,
        private readonly JobService $jobService,
    ) {}

    public function __invoke(CourseUploadMessage $message): void
    {
        $job = $this->jobService->createJob(
            'Загрузка курса ' . $message->getContent()['filename'],
            $message->getContent()['userId'],
        );
        
        $this->courseUploadService->readCourseIntoDb($message->getContent());
        $this->jobService->finishJob($job);
    }
}
