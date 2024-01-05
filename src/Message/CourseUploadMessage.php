<?php

namespace App\Message;

readonly class CourseUploadMessage
{
    public function __construct(
        private string $fileName,
        private int $userId,
        private int $courseId,
    ) {}

    public function getContent(): array
    {
        return [
            'filename' => $this->fileName,
            'userId' => $this->userId,
            'courseId' => $this->courseId,
        ];
    }
}
