<?php

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class ActionLogEvent extends Event
{
    public function __construct(
        public readonly User $createdBy,
        public readonly string $description
    ) {}
}
