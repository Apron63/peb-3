<?php

namespace App\MessageHandler;

use App\Message\CourseUploadMessage;
use App\Service\CourseUploadService;
use App\Service\JobService;
use Doctrine\DBAL\Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CourseUploadMessageHandler
{
    public function __construct(
        readonly CourseUploadService $courseUploadService,
        readonly JobService $jobService
    ) { }

    /**
     * @throws Exception
     */
    public function __invoke(CourseUploadMessage $message)
    {
        $job = $this->jobService->createJob(
            'Загрузка курса ' . $message->getContent()['filename'],
            $message->getContent()['userId'],
        );
        
        $this->courseUploadService->readCourseIntoDb($message->getContent()['filename']);
        $this->jobService->finishJob($job);
    }
}
