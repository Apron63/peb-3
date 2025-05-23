<?php

declare (strict_types=1);

namespace App\Decorator;

use MobileDetectBundle\DeviceDetector\MobileDetector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class MobileController extends AbstractController
{
    private const VIEW_EXTENSION = '.html.twig';

    public function mobileRender(string $view, array $parameters = [], ?Response $response = null): Response
    {
        $detector = new MobileDetector();

        if ($detector->isMobile()) {
            $position = strpos($view, self::VIEW_EXTENSION);

            $mobileView =  $this->getParameter('view_directory') . '/' . substr($view, 0, $position) . '_mobile' . self::VIEW_EXTENSION;

            if (file_exists($mobileView)) {
                $view = substr($view, 0, $position) . '_mobile' . self::VIEW_EXTENSION;
            }
        }

         return $this->render($view, $parameters, $response);
    }
}
