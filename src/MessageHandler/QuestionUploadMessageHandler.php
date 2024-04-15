<?php

namespace App\MessageHandler;

use App\Entity\Course;
use App\Message\QuestionUploadMessage;
use App\Repository\CourseRepository;
use App\Repository\QuestionsRepository;
use App\Repository\TicketRepository;
use App\Service\JobService;
use App\Service\XmlCourseDownload\XmlDownloader;
use Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

#[AsMessageHandler]
readonly class QuestionUploadMessageHandler
{
    public function __construct(
        private XmlDownloader $xmlDownloader,
        private QuestionsRepository $questionsRepository,
        private TicketRepository $ticketRepository,
        private CourseRepository $courseRepository,
        private JobService $jobService
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(QuestionUploadMessage $message): void
    {
        $job = $this->jobService->createJob(
            'Загрузка вопросов для курса ' . $message->getContent()['filename'],
            $message->getContent()['userId'],
        );

        $course = $this->courseRepository->find($message->getContent()['courseId']);
        if (! $course instanceof Course) {
            throw new Exception('Course with id not found');
        }

        $exceptionMessage = null;

        try {
            $data = $this->xmlDownloader->downloadXml($message->getContent());
            $this->questionsRepository->removeQuestionsForCourse($course);
            $this->ticketRepository->deleteOldTickets($course);
            $this->courseRepository->saveQuestionsToDb($message->getContent()['courseId'], $data['themes']);
        } catch(Throwable $e) {
            $exceptionMessage = $e->getMessage();
        }

        $this->jobService->finishJob($job, $exceptionMessage);
    }
}
