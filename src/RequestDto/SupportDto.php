<?php

declare(strict_types=1);

namespace App\RequestDto;

use Symfony\Component\Validator\Constraints as Assert;

class SupportDto
{
    public function __construct(
        #[Assert\NotBlank]
        public readonly string $name,

        #[Assert\NotBlank]
        public readonly string $email,

        #[Assert\NotBlank]
        public readonly string $phone,

        #[Assert\NotBlank]
        public readonly string $course,

        #[Assert\NotBlank]
        public readonly string $question,

        #[Assert\NotBlank]
        public readonly string $_token,
    ) {}
}
