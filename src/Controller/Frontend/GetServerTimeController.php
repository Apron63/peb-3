<?php

namespace App\Controller\Frontend;

use DateTime;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class GetServerTimeController extends AbstractController
{
    #[Route('/frontend/get-server-time/', name: 'app_frontend_get_server_time', methods: 'GET')]
    public function index(): Response
    {
        return (new Response())->setContent((new DateTime())->getTimestamp());
    }

    #[Route('/demo/get-server-time/', name: 'app_frontend_demo_get_server_time', methods: 'GET')]
    public function indexDemo(): Response
    {
        return (new Response())->setContent((new DateTime())->getTimestamp());
    }
}
