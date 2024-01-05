<?php

namespace App\Service\XmlCourseDownload;

readonly class CourseThemeDTO
{
    public function __construct(
        public int $courseId,
        public string $name,
        public string $description,
        /** @var QuestionDTO[] $questions */
        public array $questions = [],
        /** @var CourseInfoDTO[] $materials */
        public array $materials = [],
    ) {}
}
