<?php

namespace OGame\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use OGame\Http\Controllers\OGameController;
use OGame\Services\PlayerService;
use OGame\Services\TicketService;

class AdminTicketsController extends OGameController
{
    /**
     * Shows the tickets list page.
     *
     * @param Request $request
     * @param TicketService $ticketService
     * @return View
     */
    public function index(Request $request, TicketService $ticketService): View
    {
        // Set active admin tab for the layout
        $request->attributes->set('activeAdminTab', 'tickets');

        $status = $request->input('status', 'open');
        $tickets = $ticketService->getAllTickets($status === 'all' ? null : $status);

        // Get open ticket count for the badge
        $openTicketCount = $ticketService->getOpenTicketCount();

        return view('ingame.admin.tabs.tickets.index')->with([
            'tickets' => $tickets,
            'currentStatus' => $status,
            'openTicketCount' => $openTicketCount,
        ]);
    }

    /**
     * Shows a single ticket with all messages.
     *
     * @param Request $request
     * @param int $ticketId
     * @param TicketService $ticketService
     * @return View|RedirectResponse
     */
    public function show(Request $request, int $ticketId, TicketService $ticketService): View|RedirectResponse
    {
        // Set active admin tab for the layout
        $request->attributes->set('activeAdminTab', 'tickets');

        $ticket = $ticketService->getTicket($ticketId);

        if (!$ticket) {
            return redirect()->route('admin.tickets.index')->with('error', 'Ticket not found.');
        }

        // Mark user messages as viewed by admin
        $ticketService->markMessagesAsViewed($ticketId, false, true);

        return view('ingame.admin.tabs.tickets.show')->with([
            'ticket' => $ticket,
        ]);
    }

    /**
     * Add an admin reply to a ticket.
     *
     * @param Request $request
     * @param int $ticketId
     * @param TicketService $ticketService
     * @param PlayerService $playerService
     * @return RedirectResponse
     */
    public function reply(Request $request, int $ticketId, TicketService $ticketService, PlayerService $playerService): RedirectResponse
    {
        $request->validate([
            'body' => 'required|string|min:1|max:5000',
        ]);

        $ticket = $ticketService->getTicket($ticketId);

        if (!$ticket) {
            return redirect()->route('admin.tickets.index')->with('error', 'Ticket not found.');
        }

        if ($ticket->status === 'closed') {
            return redirect()->back()->with('error', 'Cannot reply to a closed ticket.');
        }

        $ticketService->addReply($ticketId, $playerService->getId(), $request->input('body'), true);

        return redirect()->back()->with('status', 'Reply sent successfully.');
    }

    /**
     * Update ticket status (open/close).
     *
     * @param Request $request
     * @param int $ticketId
     * @param TicketService $ticketService
     * @return RedirectResponse
     */
    public function updateStatus(Request $request, int $ticketId, TicketService $ticketService): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:open,closed',
        ]);

        $ticket = $ticketService->getTicket($ticketId);

        if (!$ticket) {
            return redirect()->route('admin.tickets.index')->with('error', 'Ticket not found.');
        }

        $ticketService->updateStatus($ticketId, $request->input('status'));

        $statusText = $request->input('status') === 'closed' ? 'closed' : 'reopened';

        return redirect()->back()->with('status', "Ticket has been {$statusText}.");
    }
}
