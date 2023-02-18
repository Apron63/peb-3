<?php

namespace App\Message;

class CourseUploadMessage
{
    private string $fileName;
    private int $userId;

    public function __construct(string $fileName, int $userId)
    {
        $this->fileName = $fileName;
        $this->userId = $userId;
    }

    public function getContent(): array
    {
        return [
            'filename' => $this->fileName,
            'userId' => $this->userId
        ];
    }
}
