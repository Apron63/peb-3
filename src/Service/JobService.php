<?php

namespace App\Service;

use App\Entity\QueryJob;
use App\Entity\User;
use App\Repository\QueryJobRepository;
use App\Repository\UserRepository;
use DateTime;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class JobService
{
    public function __construct(
        private readonly QueryJobRepository $queryJobRepository,
        private readonly UserRepository $userRepository
    ) {}

    public function createJob(string $description, int $userId): QueryJob
    {
        $user = $this->userRepository->find($userId);

        if (! $user instanceof User) {
            throw new NotFoundHttpException('User not found');
        }

        $job = (new QueryJob())
            ->setDescription($description)
            ->setStartAt(new DateTime())
            ->setUser($user);

        $this->queryJobRepository->save($job, true);

        return $job;
    }

    public function finishJob(QueryJob $job, ?string $errorMessage = null): void
    {
        $job->setEndAt(new DateTime())->setDocumentLink($errorMessage);
        $this->queryJobRepository->save($job, true);
    }
}
