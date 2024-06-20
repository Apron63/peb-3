<?php

namespace App\Service;

use App\Repository\LoggerRepository;
use App\Repository\PermissionRepository;
use App\Repository\UserRepository;

class TestingHistoryService
{
    public function __construct (
        private readonly UserRepository $userRepository,
        private readonly LoggerRepository $loggerRepository,
        private readonly PermissionRepository $permissionRepository,
    ) {}

    public function testingHistoryService(): array
    {
        $limit = 100;
        $offset = 0;

        $result = [];
        $usersCount = 0;


        do {
            $usersPortion = $this->userRepository->getUserPortion($limit, $offset);

            $usersCount = count($usersPortion);
            $offset += $limit;

            foreach ($usersPortion as $user) {
                foreach ($this->loggerRepository->findBy(['user' => $user['id']]) as $logger) {
                    $permissionUser = $logger->getPermission()->getUser();

                    if ($permissionUser->getId() !== $user['id']) {
                        $result[] = [
                            'date' => $logger->getBeginAt(),
                            'fullName' => $user['fullName'],
                            'permissionId' => $logger->getPermission()->getId(),
                            'permissionUserFullName' => $permissionUser->getFullName(),
                        ];
                    }
                }
            }

            unset($usersPortion);
            gc_collect_cycles();

            if (count($result) > 10) {
                break;
            }
        }
        while ($usersCount > 0);

        return $result;
    }
}
