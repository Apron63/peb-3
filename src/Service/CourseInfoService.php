<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\CourseInfo;
use App\Repository\CourseInfoRepository;
use App\Repository\CourseRepository;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class CourseInfoService
{
    public function __construct(
        private readonly string $courseUploadPath,
        private readonly CourseInfoRepository $courseInfoRepository,
        private readonly CourseRepository $courseRepository,
        private readonly FileUploadService $fileUploadService,
    ) {}

    /**
     * @param UploadedFile[] $files
     */
    public function batchUploadCourseInfo(array $files, Course $course): void
    {
        foreach($files as $file) {
            if (UPLOAD_ERR_OK === $file->getError()) {
                $path = $this->courseUploadPath . DIRECTORY_SEPARATOR . $course->getId();

                $originalFilename = $this->courseRepository->shrinkMaterialName(
                    pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
                );

                $newFilename = $this->fileUploadService->uploadFile($file, $path, null, 1000);

                $courseInfo = new CourseInfo();

                $courseInfo
                    ->setCourse($course)
                    ->setFileName($newFilename)
                    ->setName($originalFilename);

                $this->courseInfoRepository->save($courseInfo, true);
            } else {
                throw new RuntimeException($file->getErrorMessage());
            }
        }
    }
}
