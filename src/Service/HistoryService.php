<?php

namespace App\Service;

use App\Repository\LoggerRepository;
use App\Repository\PermissionRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class HistoryService
{
    private const MAX_PAGES_IN_LINE = 30;
    private const PAGES_AT_ONCE = 9;
    private const PAGES_CHANGE_PAGINATOR = 6;
    private const PAGES_IN_INTERVAL_PART = 5;

    public function __construct(
        private readonly PermissionRepository $permissionRepository,
        private readonly LoggerRepository $loggerRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function getHistory(UserInterface $user, int $page, int $perPage): array
    {
        $permissionsWithLogger = [];
        $permissions = $this->permissionRepository->getPermissionForHistory($user, $page, $perPage);
        $totalPermissionsForUser = $this->permissionRepository->getTotalPermissionsForUser($user);
        $maxPages = intdiv($totalPermissionsForUser, $perPage);

        if ($totalPermissionsForUser % $perPage > 0) {
            $maxPages++;
        }

        if ($page > $maxPages) {
            $page = $maxPages;
        }

        foreach ($permissions as $permission) {
            $logger = $this->loggerRepository->findOneBy(
                [
                    'permission' => $permission,
                    'user' => $user,
                ],
                [
                    'beginAt' => 'desc',
                ]
            );

            $permissionsWithLogger[] = [
                'permission' => $permission,
                'logger' => $logger,
            ];
        }

        return [
            'items' => $permissionsWithLogger,
            'paginator' => $this->preparePaginator($page, $perPage, $maxPages),
            'total' => $totalPermissionsForUser,
        ];
    }

    private function preparePaginator(int $page, int $perPage, int $maxPages): array
    {
        $paginator = [];

        if ($maxPages > self::MAX_PAGES_IN_LINE) {
            if ($page <= self::PAGES_CHANGE_PAGINATOR) {
                for ($index = 1; $index <= self::PAGES_AT_ONCE; $index++) {
                    $paginator[] = [
                        'url' => $this->urlGenerator->generate('app_frontend_history', [
                            'page' => $index,
                            'perPage' => $perPage,
                        ]),
                        'title' => $index,
                        'isActive' => $page === $index,
                    ];
                }

                $paginator[] = [
                    'url' => null,
                    'title' => '...',
                    'isActive' => true,
                ];

                $paginator[] = [
                    'url' => $this->urlGenerator->generate('app_frontend_history', [
                        'page' => $maxPages,
                        'perPage' => $perPage,
                    ]),
                    'title' => $maxPages,
                    'isActive' => false,
                ];
            } elseif ($maxPages - $page < self::PAGES_CHANGE_PAGINATOR) {
                $paginator[] = [
                    'url' => $this->urlGenerator->generate('app_frontend_history', [
                        'page' => 1,
                        'perPage' => $perPage,
                    ]),
                    'title' => 1,
                    'isActive' => false,
                ];

                $paginator[] = [
                    'url' => null,
                    'title' => '...',
                    'isActive' => true,
                ];

                for ($index = $maxPages - self::PAGES_AT_ONCE + 1; $index <= $maxPages; $index++) {
                    $paginator[] = [
                        'url' => $this->urlGenerator->generate('app_frontend_history', [
                            'page' => $index,
                            'perPage' => $perPage,
                        ]),
                        'title' => $index,
                        'isActive' => $page === $index,
                    ];
                }
            } else {
                $paginator[] = [
                    'url' => $this->urlGenerator->generate('app_frontend_history', [
                        'page' => 1,
                        'perPage' => $perPage,
                    ]),
                    'title' => 1,
                    'isActive' => false,
                ];

                $paginator[] = [
                    'url' => null,
                    'title' => '...',
                    'isActive' => true,
                ];

                for ($index = $page - self::PAGES_IN_INTERVAL_PART + 2; $index <=  $page + self::PAGES_IN_INTERVAL_PART - 2; $index++) {
                    $paginator[] = [
                        'url' => $this->urlGenerator->generate('app_frontend_history', [
                            'page' => $index,
                            'perPage' => $perPage,
                        ]),
                        'title' => $index,
                        'isActive' => $page === $index,
                    ];
                }

                $paginator[] = [
                    'url' => null,
                    'title' => '...',
                    'isActive' => true,
                ];

                $paginator[] = [
                    'url' => $this->urlGenerator->generate('app_frontend_history', [
                        'page' => $maxPages,
                        'perPage' => $perPage,
                    ]),
                    'title' => $maxPages,
                    'isActive' => false,
                ];
            }
        } else {
            for ($index = 1; $index <= $maxPages; $index++) {
                $paginator[] = [
                    'url' => $this->urlGenerator->generate('app_frontend_history', [
                        'page' => $index,
                        'perPage' => $perPage,
                    ]),
                    'title' => $index,
                    'isActive' => $page === $index
                ];
            }
        }

        return $paginator;
    }
}
