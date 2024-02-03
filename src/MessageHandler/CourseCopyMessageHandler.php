<?php

namespace App\MessageHandler;

use App\Message\CourseCopyMessage;
use App\Service\CourseCopyService;
use App\Service\JobService;
use Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class CourseCopyMessageHandler
{
    public function __construct(
        private JobService $jobService,
        private CourseCopyService $courseCopyService,
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(CourseCopyMessage $message): void
    {
        $job = $this->jobService->createJob(
            'Копирование курса ' . $message->getContent()['courseName'],
            $message->getContent()['userId'],
        );

        $exceptionMessage = null;

        try {
            $this->courseCopyService->copyCourse($message->getContent()['courseId']);
        } catch(Exception $e) {
            $exceptionMessage = $e->getMessage();
        }

        $this->jobService->finishJob($job, $exceptionMessage);
    }
}
