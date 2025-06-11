<?php

declare (strict_types=1);

namespace App\Command\OneTime;

use App\Entity\PermissionHistory;
use App\Repository\PermissionHistoryRepository;
use App\Repository\PermissionRepository;
use Doctrine\DBAL\Logging\Middleware;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:permission-histoty-init')]
class SetPermissionHistoryCommand
{
    private const int BATCH_SIZE = 100;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PermissionRepository $permissionRepository,
        private readonly PermissionHistoryRepository $permissionHistoryRepository,
    ) {}

    public function __invoke(SymfonyStyle $io): int
    {
        $connection = $this->entityManager->getConnection();
        $connection->getConfiguration()->setMiddlewares([new Middleware(new NullLogger())]);

        $portion = 0;
        while (true) {
            $permissions = $this->permissionRepository->getPermissionsPortion(self::BATCH_SIZE, $portion++);

            if (empty($permissions)) {
                break;
            }

            foreach ($permissions as $permission) {
                $permissionHistories = $this->permissionHistoryRepository->getOnePermissionHistories($permission->getId());

                if (0 === count($permissionHistories)) {
                    $permissionHistory = new PermissionHistory()
                        ->setDuration($permission->getDuration())
                        ->setInitial(true)
                        ->setPermissionId($permission->getId())
                        ->setCreatedBy($permission->getCreatedBy())
                        ->setCreatedAt($permission->getCreatedAt())
                        ->setUpdatedAt($permission->getCreatedAt());

                    $this->permissionHistoryRepository->save($permissionHistory, true);
                }
                else {
                    foreach ($permissionHistories as $key => $permissionHistory) {
                        $prevValue = $permissionHistory->isInitial();

                        if (0 === $key) {
                            $permissionHistory->setInitial(true);
                        }
                        else {
                            $permissionHistory->setInitial(false);
                        }

                        if ($prevValue !== $permissionHistory->isInitial()) {
                            $this->permissionHistoryRepository->save($permissionHistory, true);
                        }
                    }
                }
            }

            gc_collect_cycles();
            $io->writeln('Выполнено: ' . $portion * self::BATCH_SIZE);
        }

        $io->writeln('Выполнено!');
        return Command::SUCCESS;
    }
}
