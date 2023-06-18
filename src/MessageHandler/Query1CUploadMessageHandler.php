<?php

namespace App\MessageHandler;

use App\Message\Query1CUploadMessage;
use App\Service\JobService;
use App\Service\LoaderService;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class Query1CUploadMessageHandler
{
    public function __construct(
        private readonly LoaderService $loaderService,
        private readonly JobService $jobService,
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

        $this->loaderService->createUsersAndPermissions($message->getContent()['userId']);
        $this->jobService->finishJob($job);
    }
}
