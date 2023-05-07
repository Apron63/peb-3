<?php

namespace App\TwigExtension;

use App\Entity\Logger;
use App\Entity\Permission;
use App\Repository\LoggerRepository;
use App\Repository\PermissionRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class HistoryTwigExtension extends AbstractExtension
{

    /**
     * TwigExtension constructor.
     */
    public function __construct(
        private readonly LoggerRepository $loggerRepository,
        private readonly PermissionRepository $permissionRepository,
    ) {}

    /**
     * @return array|TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('getResultDescription', [$this, 'getResultDescription']),
        ];
    }

    public function getResultDescription(int $permissionId): array
    {
        $testingEndDate = '-';
        $testingResult = 'не сдано';
        $testingDuration = '';

        $permission = $this->permissionRepository->find($permissionId);

        if ($permission instanceof Permission) {
            $logger = $this->loggerRepository->findFirstSuccessfullyLogger($permission, $permission->getUser());

            if ($logger instanceof Logger) {
                $testingEndDate = $logger->getEndAt()->format('d.m.Y');
                $testingResult = 'сдано';
            }

            $spentHours = (int)($permission->getTimeSpent() / 3600);
            $spentMinutes = (int)(($permission->getTimeSpent() - $spentHours * 3600) / 60);

            if ($spentHours > 0) {
                $testingDuration .= $spentHours . ' ч. ';
            }
            
            if ($spentMinutes > 0) {
                $testingDuration .= $spentMinutes . ' м.';
            }
        }

        return [
            'endDate' => $testingEndDate,
            'result' => $testingResult,
            'duration' => $testingDuration,
        ];
    }
}
