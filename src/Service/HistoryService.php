<?php

namespace App\Service;

use App\Repository\LoggerRepository;
use Symfony\Component\Security\Core\User\UserInterface;

class HistoryService
{
    public function __construct(
        private readonly LoggerRepository $loggerRepository,
    ) {}

    public function getPermissionTestingResults(array $permissions, UserInterface $user): array
    {
        $result = [];

        foreach($permissions as $permission) {
            $logger = $this->loggerRepository->findOneBy(
                [
                    'permission' => $permission,
                    'user' => $user,
                ],
                [
                    'beginAt' => 'desc',
                ]
            );

            $result[] = [
                'permission' => $permission,
                'logger' => $logger
            ];
        }

        return $result;
    }
}
