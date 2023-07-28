<?php

namespace App\Message;

class CourseUploadMessage
{
    public function __construct(
        private readonly string $fileName, 
        private readonly int $userId, 
        private readonly int $courseId,
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
