<?php

namespace App\Controller\Admin;

use App\Entity\Ticket;
use App\Service\TicketService;
use JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TicketController extends AbstractController
{
    public function __construct(
        private readonly TicketService $ticketService
    ) {}

    /**
     * @throws JsonException
     */
    #[Route('/admin/tickets/create/', name: 'admin_tickets_create', condition: 'request.isXmlHttpRequest()')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function createTickets(Request $request): Response
    {
        $this->ticketService->createTickets(
            $request->get('course'),
            $request->get('ticketCnt', 1),
            $request->get('errCnt', 1),
            $request->get('timeLeft', 0),
            $request->get('themes', [])
        );

        $response = new JsonResponse();
        $response->setContent(json_encode(['result' => 'success'], JSON_THROW_ON_ERROR));
        
        return $response;
    }

    #[Route('/admin/tickets/print/{id<\d+>}/', name: 'admin_tickets_print')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function printTicket(Ticket $ticket): Response
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
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function printModuleTicket(Ticket $ticket): Response
    {
        $arTicket = [
            'id' => $ticket->getId(),
            'nom' => $ticket->getNom(),
            'text' => $ticket->getText(),
        ];

        $data = $this->ticketService->renderModuleTicket($arTicket, true);

        return $this->render('admin/ticket/print-module.html.twig', [
            'data' => $data,
        ]);
    }
}
