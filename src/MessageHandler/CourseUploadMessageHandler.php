<?php

namespace App\MessageHandler;

use App\Message\CourseUploadMessage;
use App\Repository\CourseRepository;
use App\Service\CourseDownloadService;
use App\Service\JobService;
use App\Service\XmlCourseDownload\XmlDownloader;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

#[AsMessageHandler]
readonly class CourseUploadMessageHandler
{
    public function __construct(
        private XmlDownloader $xmlDownloader,
        private CourseDownloadService $courseDownloadService,
        private CourseRepository $courseRepository,
        private JobService $jobService,
    ) {}

    public function __invoke(CourseUploadMessage $message): void
    {
        $job = $this->jobService->createJob(
            'Загрузка курса ' . $message->getContent()['filename'],
            $message->getContent()['userId'],
        );

        $exceptionMessage = null;

        try {
            $data = $this->xmlDownloader->downloadXml($message->getContent());
            $this->courseDownloadService->checkIfCourseExistsInDatabase($message->getContent()['courseId'], $data['courseName']);
            $this->courseRepository->saveCourseToDb($message->getContent()['courseId'], $data['themes']);
        } catch(Throwable $e) {
            $exceptionMessage = $e->getMessage();
        }

        $this->jobService->finishJob($job, $exceptionMessage);
    }
}
