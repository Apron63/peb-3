<?php

namespace App\Message;

class QuestionUploadMessage
{
    private string $fileName;
    private int $userId;
    private int $courseId;

    public function __construct(string $fileName, int $userId, int $courseId)
    {
        $this->fileName = $fileName;
        $this->userId = $userId;
        $this->courseId = $courseId;
    }

    public function getContent(): array
    {
        return [
            'filename' => $this->fileName,
            'userId' => $this->userId,
            'courseId' => $this->courseId,
        ];
    }
}
