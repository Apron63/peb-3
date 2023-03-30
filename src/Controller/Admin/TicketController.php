<?php

namespace App\Controller\Admin;

use App\Entity\ModuleTicket;
use App\Entity\Ticket;
use App\Service\TicketService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TicketController extends AbstractController
{
    public function __construct(readonly TicketService $ticketService)
    {}

    #[Route('/admin/tickets/create/', name: 'admin_tickets_create', condition: 'request.isXmlHttpRequest()')]
    public function createTickets(Request $request): Response
    {
        $this->ticketService->createTickets(
            $request->get('course'),
            $request->get('ticketCnt', 1),
            $request->get('errCnt', 1),
            $request->get('themes', [])
        );
        $response = new JsonResponse();
        $response->setContent(json_encode(['result' => 'success'], JSON_THROW_ON_ERROR));
        return $response;
    }

    #[Route('/admin/tickets/print/{id<\d+>}/', name: 'admin_tickets_print')]
    public function printTicket(Ticket $ticket)
    {
        $arTicket = [
            'id' => $ticket->getId(),
            'nom' => $ticket->getNom(),
            'text' => $ticket->getText(),
        ];

        $data = $this->ticketService->renderTicket($arTicket, true);

        return $this->render('admin/ticket/print.html.twig', [
            'data' => $data,
        ]);
    }
    
    #[Route('/admin/module-tickets/print/{id<\d+>}/', name: 'admin_module_tickets_print')]
    public function printModuleTicket(ModuleTicket $moduleTicket)
    {
        $arTicket = [
            'id' => $moduleTicket->getId(),
            'nom' => $moduleTicket->getTicketNom(),
            'text' => $moduleTicket->getData(),
        ];

        $data = $this->ticketService->renderModuleTicket($arTicket, true);

        return $this->render('admin/ticket/print-module.html.twig', [
            'data' => $data,
        ]);
    }
}
