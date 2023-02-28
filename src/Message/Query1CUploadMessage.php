<?php

namespace App\Message;

class Query1CUploadMessage
{
    private int $userId;
    private string $name;

    public function __construct(string $name, int $userId)
    {
        $this->userId = $userId;
        $this->name = $name;
    }

    public function getContent(): array
    {
        return [
            'userId' => $this->userId,
            'name' => $this->name,
        ];
    }
}
