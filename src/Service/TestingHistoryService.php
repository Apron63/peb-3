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

        do {
            $usersPortion = $this->userRepository->getUserPortion($limit, $offset);
            if (0 === count($usersPortion)) {
                break;
            }

            $usersPortionIds = array_map(
                fn($user) => $user['id'],
                $usersPortion,
            );

            $loggers = $this->loggerRepository->getLoggersByUsers(...$usersPortionIds);

            $permissionsIds = array_map(
                fn($logger) => $logger['permission_id'],
                $loggers,
            );

            $permissions = $this->permissionRepository->getPermissionsByIds(...$permissionsIds);

            $permissionByIds = [];

            foreach ($permissions as $permission) {
                $permissionByIds[$permission['id']] = $permission;
            }

            foreach ($loggers as $logger) {
                if ($logger['user_id'] !== $permissionByIds[$logger['permission_id']]['user_id']) {
                    $result[] = [
                        'logger_id' => $logger['id'],
                        'date' => $logger['beginAt'],
                        'logger_user_id' =>$logger['user_id'],
                        'permission_id' => $logger['permission_id'],
                        'permission_user_id' => $permissionByIds[$logger['permission_id']]['user_id'],
                    ];
                }
            }

            $offset += $limit;

            unset($usersPortion);
            gc_collect_cycles();
        }
        while (true);

        return $result;
    }
}
