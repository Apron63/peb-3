<?php

namespace App\Service\XmlCourseDownload;

readonly class AnswerDTO
{
    public function __construct(
        public string $description,
        public bool $isCorrect,
        public int $nom,
    ) {}
}
