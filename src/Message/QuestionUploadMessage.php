<?php

namespace App\Message;

readonly class QuestionUploadMessage
{
    public function __construct(
        private string $fileName,
        private int $userId,
        private int $courseId,
        private bool $useCurrentCourseName = false,
    ) {}

    public function getContent(): array
    {
        return [
            'filename' => $this->fileName,
            'userId' => $this->userId,
            'courseId' => $this->courseId,
            'useCurrentCourseName' => $this->useCurrentCourseName,
        ];
    }
}
