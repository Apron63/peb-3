<?php

namespace App\Service;

use App\Entity\Course;
use App\Repository\CourseRepository;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

class CourseDownloadService
{
    public function __construct(
        private readonly CourseRepository $courseRepository,
        private readonly string $courseUploadPath,
        private readonly Filesystem $filesystem,
    ) {}

    public function downloadCourse(UploadedFile $data): Course
    {
        $originalFilename = pathinfo($data->getClientOriginalName(), PATHINFO_FILENAME);
        $course = $this->courseRepository->findOneBy(['shortName' => $originalFilename]);

        if (! $course instanceof Course) {
            $course = (new Course())
                ->setShortName($originalFilename)
                ->setName($originalFilename)
                ->setType(Course::CLASSIC);

            $this->courseRepository->save($course, true);
        }

        $this->downloadXmlFile($data, $course, true);

        return $course;
    }

    public function downloadXmlFile(UploadedFile $data, Course $course, bool $needClear = false): void
    {
        $originalFilename = pathinfo($data->getClientOriginalName(), PATHINFO_FILENAME);
        $path = $this->courseUploadPath . DIRECTORY_SEPARATOR . $course->getId();

        if (! $this->filesystem->exists($path)) {
            $this->filesystem->mkdir($path);
        }

        // Очистим каталог
        if ($needClear) {
            $files = glob($path . '/*');
            $this->filesystem->remove($files);
        }

        // Переносим файл
        try {
            $data->move($path, $originalFilename . '.zip');
        } catch (FileException) {
            throw new RuntimeException('Невозможно переместить файл в каталог загрузки');
        }

        // Распаковать архив
        $zip = new ZipArchive;
        $result = $zip->open($path . DIRECTORY_SEPARATOR . $originalFilename . '.zip');

        if (true === $result) {
            $zip->extractTo($path);
            $zip->close();
        } else {
            throw new RuntimeException('Невозможно распаковать архив');
        }
    }

    public function checkIfCourseExistsInDatabase(int $courseId, ?string $courseName): void
    {
        $course = $this->courseRepository->find($courseId);

        if (! $course instanceof Course) {
            throw new RuntimeException('Курс не найден');
        }

        $this->courseRepository->prepareCourseClear($course);

        if (null !== $courseName) {
            $course->setName($courseName);

            $this->courseRepository->save($course, true);
        }
    }
}
