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
        $result = [];

        foreach ($this->userRepository->findAll() as $user) {
            foreach ($this->loggerRepository->findBy(['user' => $user]) as $logger) {
                $permissionUser = $logger->getPermission()->getUser();

                if ($permissionUser !== $user) {
                    $result[] = [
                        'date' => $logger->getBeginAt(),
                        'user' => $user,
                        'permission' => $logger->getPermission(),
                        'permissionUser' => $permissionUser,
                    ];
                }
            }
        }

        return $result;
    }
}
