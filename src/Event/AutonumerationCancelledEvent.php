<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class AutonumerationCancelledEvent extends Event
{
    public function __construct(
        public readonly int $courseId,
    ) {}
}
