<?php

namespace App\TwigExtension;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use function Symfony\Component\String\u;

class TwigExtension extends AbstractExtension
{

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * @return array|TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('shortDescription', [$this, 'shortDescription']),
            new TwigFunction('getTimingControlUrl', [$this, 'getTimingControlUrl']),
            new TwigFunction('getSheduledTime', [$this, 'getSheduledTime']),
        ];
    }

    public function shortDescription(string $description, int $nom): string
    {
        $shortDescription = strip_tags(u($description)->truncate(1000));
        if ($description === '') {
            $shortDescription = $nom . '.';
        }

        return $shortDescription;
    }

    public function getTimingControlUrl(): string
    {
        return $this->urlGenerator->generate('app_frontend_timing');
    }

    public function getSheduledTime(?int $timeInSeconds): string
    {
        return $timeInSeconds ? gmdate('H ч. i м.', $timeInSeconds) : '';
    }
}
