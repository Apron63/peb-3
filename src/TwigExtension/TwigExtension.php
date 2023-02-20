<?php

namespace App\TwigExtension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use function Symfony\Component\String\u;

class TwigExtension extends AbstractExtension
{

    /**
     * TwigExtension constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return array|TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('shortDescription', [$this, 'shortDescription']),
        ];
    }

    /**
     * @param string $description
     * @param int $nom
     * @return string
     */
    public function shortDescription(string $description, int $nom): string
    {
        $shortDescription = strip_tags(u($description)->truncate(100));
        if ($description === '') {
            $description = $nom . '.';
        }
        return $shortDescription;
    }
}
