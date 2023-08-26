<?php

namespace App\Command\OneTime;

use App\Repository\ModuleRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:sort-modules')]
class CreateSortOrderForModulesCommand extends Command
{
    public function __construct (
        private readonly ModuleRepository $moduleRepository, 
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Проставляем порядок сортировки для модулей')
            ->setHelp('Проставляем порядок сортировки для модулей');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $courseId = null;
        $moduleSortOrder = 1;

        $output->writeln('Задача запущена.');

        $modules = $this->moduleRepository->findBy([], ['course' => 'asc', 'id' => 'asc']);

        foreach($modules as $module) {
            if ($courseId !== $module->getCourse()->getId()) {
                $courseId = $module->getCourse()->getId();

                $moduleSortOrder = 1;
            }

            $module->setSortOrder($moduleSortOrder);
            $this->moduleRepository->save($module, true);

            $moduleSortOrder ++;
        }
        
        $output->writeln('Задача завершена.');
        return Command::SUCCESS;
    }
}
