<?php

declare(strict_types=1);

namespace App\RequestDto;

use Symfony\Component\Validator\Constraints as Assert;

class SurveyDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max:255)]
        public readonly string $question1,

        #[Assert\NotBlank]
        #[Assert\Length(max:5000)]
        public readonly string $question2,

        #[Assert\NotBlank]
        #[Assert\Length(max:255)]
        public readonly string $question3,

        #[Assert\NotBlank]
        #[Assert\Length(max:5000)]
        public readonly string $question4,

        #[Assert\Length(max:5000)]
        public readonly string $question5,
    ) {}
}
