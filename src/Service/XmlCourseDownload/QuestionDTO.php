<?php

namespace App\Service\XmlCourseDownload;

readonly class QuestionDTO
{
    public function __construct(
        public int $courseId,
        public int $parentId,
        public string $description,
        public ?string $help,
        public int $nom,
        public int $type,
        /** @var AnswerDTO[] $answers */
        public array $answers = [],
    ) {}
}
