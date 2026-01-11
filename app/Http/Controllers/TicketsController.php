<?php

namespace OGame\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use OGame\Services\PlayerService;
use OGame\Services\TicketService;

class TicketsController extends OGameController
{
    /**
     * Shows the user's tickets list.
     *
     * @param PlayerService $playerService
     * @param TicketService $ticketService
     * @return View
     */
    public function index(PlayerService $playerService, TicketService $ticketService): View
    {
        $tickets = $ticketService->getUserTickets($playerService->getId());

        return view('ingame.tickets.index')->with([
            'tickets' => $tickets,
        ]);
    }

    /**
     * Shows the create ticket form.
     *
     * @return View
     */
    public function create(): View
    {
        return view('ingame.tickets.create');
    }

    /**
     * Creates a new ticket.
     *
     * @param Request $request
     * @param PlayerService $playerService
     * @param TicketService $ticketService
     * @return RedirectResponse
     */
    public function store(Request $request, PlayerService $playerService, TicketService $ticketService): RedirectResponse
    {
        $request->validate([
            'subject' => 'required|string|min:3|max:255',
            'body' => 'required|string|min:10|max:5000',
        ]);

        $ticket = $ticketService->createTicket(
            $playerService->getId(),
            $request->input('subject'),
            $request->input('body')
        );

        return redirect()->route('tickets.show', $ticket->id)->with('status', __('Ticket created successfully.'));
    }

    /**
     * Shows a single ticket with all messages.
     *
     * @param int $ticketId
     * @param PlayerService $playerService
     * @param TicketService $ticketService
     * @return View|RedirectResponse
     */
    public function show(int $ticketId, PlayerService $playerService, TicketService $ticketService): View|RedirectResponse
    {
        $ticket = $ticketService->getTicket($ticketId);

        if (!$ticket || $ticket->user_id !== $playerService->getId()) {
            return redirect()->route('tickets.index')->with('error', __('Ticket not found.'));
        }

        // Mark admin replies as viewed by user
        $ticketService->markMessagesAsViewed($ticketId, true, false);

        return view('ingame.tickets.show')->with([
            'ticket' => $ticket,
        ]);
    }

    /**
     * Add a user reply to a ticket.
     *
     * @param Request $request
     * @param int $ticketId
     * @param PlayerService $playerService
     * @param TicketService $ticketService
     * @return RedirectResponse
     */
    public function reply(Request $request, int $ticketId, PlayerService $playerService, TicketService $ticketService): RedirectResponse
    {
        $request->validate([
            'body' => 'required|string|min:1|max:5000',
        ]);

        $ticket = $ticketService->getTicket($ticketId);

        if (!$ticket || $ticket->user_id !== $playerService->getId()) {
            return redirect()->route('tickets.index')->with('error', __('Ticket not found.'));
        }

        if ($ticket->status === 'closed') {
            return redirect()->back()->with('error', __('Cannot reply to a closed ticket.'));
        }

        $ticketService->addReply($ticketId, $playerService->getId(), $request->input('body'), false);

        return redirect()->back()->with('status', __('Reply sent successfully.'));
    }

    /**
     * Get tickets for AJAX tab loading in messages view.
     *
     * @param PlayerService $playerService
     * @param TicketService $ticketService
     * @return View
     */
    public function ajaxGetTicketsTab(PlayerService $playerService, TicketService $ticketService): View
    {
        $tickets = $ticketService->getUserTickets($playerService->getId());

        return view('ingame.messages.tabs.tickets.tab')->with([
            'tickets' => $tickets,
        ]);
    }
}
