<?php

namespace App\Message;

readonly class CourseCopyMessage
{
    public function __construct(
        private string $courseName,
        private int $userId,
        private int $courseId,
    ) {}

    public function getContent(): array
    {
        return [
            'courseName' => $this->courseName,
            'userId' => $this->userId,
            'courseId' => $this->courseId,
        ];
    }
}
