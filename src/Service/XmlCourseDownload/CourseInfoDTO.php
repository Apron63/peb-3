<?php

namespace App\Service\XmlCourseDownload;

readonly class CourseInfoDTO
{
    public function __construct(
        public int $courseId,
        public string $name,
        public ?string $filename,
    ) {}
}
