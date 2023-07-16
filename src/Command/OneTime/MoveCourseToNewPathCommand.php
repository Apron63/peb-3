<?php

namespace App\Command\OneTime;

use App\Entity\Course;
use App\Repository\CourseRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:move-course-new-path')]
class MoveCourseToNewPathCommand extends Command
{
    public function __construct (
        private readonly CourseRepository $courseRepository,
        private readonly string $courseUploadPath,
        private readonly string $interactiveUploadPath,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Перенос материалов для курсов в новое расположение');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach($this->courseRepository->findAll() as $course) {
            $output->writeln('Переносим курсы : ' . $course->getShortName());
            $output->writeln('Тип : ' . $course->getType());

            if (Course::CLASSC === $course->getType()) {
                $oldDir = $this->courseUploadPath . '/' . $course->getShortName();
            } else {
                $oldDir = $this->interactiveUploadPath . '/' . $course->getId();
            }

            if (file_exists($oldDir)) {
                $newDir = $this->courseUploadPath . '/' . $course->getId();

                if (file_exists($newDir)) {
                    $output->writeln('Каталог : ' . $newDir . ' уже существует, пропущено');
                } else {
                    rename($oldDir, $newDir);
                    $output->writeln('Каталог : ' . $newDir . ' успешно переименован');
                }
            }
        }

        $output->writeln('Курсы успешно перенесены');
        return Command::SUCCESS;
    }
}
