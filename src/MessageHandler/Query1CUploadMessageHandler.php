<?php

namespace App\MessageHandler;

use App\Message\Query1CUploadMessage;
use App\Service\JobService;
use App\Service\Query1CUploadService;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class Query1CUploadMessageHandler
{
    public function __construct(
        readonly Query1CUploadService $query1CUploadService,
        readonly JobService $jobService
    ) {}

    /**
     * @throws NonUniqueResultException
     */
    public function __invoke(Query1CUploadMessage $message): void
    {
        $job = $this->jobService->createJob(
            'Загрузка заказа-наряда ' . $message->getContent()['name'],
            $message->getContent()['userId']
        );

        $documentLink = $this->query1CUploadService->createUsersAndPermissions();
        $this->jobService->finishJob($job, $documentLink);
    }
}
