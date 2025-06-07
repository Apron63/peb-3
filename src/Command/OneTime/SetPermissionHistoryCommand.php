<?php

namespace App\Command\OneTime;

use App\Entity\PermissionHistory;
use App\Repository\PermissionHistoryRepository;
use App\Repository\PermissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:permission-histoty-init')]
class SetPermissionHistoryCommand
{
    private const int BATCH_SIZE = 10;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PermissionRepository $permissionRepository,
        private readonly PermissionHistoryRepository $permissionHistoryRepository,
    ) {}

    public function __invoke(SymfonyStyle $io): int
    {
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
                        if (0 === $key) {
                            $permissionHistory->setInitial(true);
                        }
                        else {
                            $permissionHistory->setInitial(false);
                        }

                        $this->permissionHistoryRepository->save($permissionHistory, true);
                    }
                }
            }

            $io->writeln('Выполнено: ' . $portion * self::BATCH_SIZE);
        }

        $io->writeln('Выполнено!');
        return Command::SUCCESS;
    }
}
