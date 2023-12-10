<?php

namespace App\MessageHandler;

use App\Message\QuestionUploadMessage;
use App\Service\JobService;
use App\Service\QuestionUploadService;
use Doctrine\DBAL\Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class QuestionUploadMessageHandler
{
    public function __construct(
        private readonly QuestionUploadService $questionUploadService,
        private readonly JobService $jobService
    ) { }

    /**
     * @throws Exception
     */
    public function __invoke(QuestionUploadMessage $message): void
    {
        $job = $this->jobService->createJob(
            'Загрузка вопросов для курса ' . $message->getContent()['filename'],
            $message->getContent()['userId'],
        );
        
        $this->questionUploadService->readCourseIntoDb(
            $message->getContent()['filename'],
            $message->getContent()['courseId'],
        );

        $this->jobService->finishJob($job);
    }
}
