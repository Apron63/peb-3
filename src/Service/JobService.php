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
        readonly QueryJobRepository $queryJobRepository, 
        readonly UserRepository $userRepository
    ) { }

    /**
     * @param string $description
     * @param int $userId
     * @return QueryJob
     */
    public function createJob(string $description, int $userId): QueryJob
    {
        $user = $this->userRepository->find($userId);

        if (!$user instanceof User) {
            throw new NotFoundHttpException('User not found');
        }

        $job = (new QueryJob())
            ->setDescription($description)
            ->setStartAt(new DateTime())
            ->setUser($user);

        $this->queryJobRepository->save($job, true);

        return $job;
    }

    /**
     * @param QueryJob $job
     * @param string|null $documentLink
     */
    public function finishJob(QueryJob $job, ?string $documentLink = null): void
    {
        $job->setEndAt(new DateTime())->setDocumentLink($documentLink);
        $this->queryJobRepository->save($job, true);
    }
}
