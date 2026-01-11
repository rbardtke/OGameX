<?php

namespace OGame\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use OGame\Models\Ticket;
use OGame\Models\TicketMessage;

/**
 * Class TicketService
 *
 * Handles all ticket-related operations for the support system.
 */
class TicketService
{
    /**
     * Create a new ticket.
     *
     * @param int $userId
     * @param string $subject
     * @param string $body
     * @return Ticket
     */
    public function createTicket(int $userId, string $subject, string $body): Ticket
    {
        $ticket = Ticket::create([
            'user_id' => $userId,
            'subject' => $subject,
            'status' => 'open',
        ]);

        // Create the initial message
        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $userId,
            'is_admin_reply' => false,
            'body' => $body,
            'viewed' => false,
        ]);

        return $ticket;
    }

    /**
     * Get all tickets with optional status filter.
     *
     * @param string|null $status
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllTickets(?string $status = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = Ticket::with(['user', 'latestMessage'])
            ->orderBy('created_at', 'desc');

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get tickets for a specific user.
     *
     * @param int $userId
     * @return Collection
     */
    public function getUserTickets(int $userId): Collection
    {
        return Ticket::with(['messages'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get a single ticket with all messages.
     *
     * @param int $ticketId
     * @return Ticket|null
     */
    public function getTicket(int $ticketId): ?Ticket
    {
        return Ticket::with(['user', 'messages.user'])
            ->find($ticketId);
    }

    /**
     * Add a reply to a ticket.
     *
     * @param int $ticketId
     * @param int $userId
     * @param string $body
     * @param bool $isAdmin
     * @return TicketMessage
     */
    public function addReply(int $ticketId, int $userId, string $body, bool $isAdmin = false): TicketMessage
    {
        $message = TicketMessage::create([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'is_admin_reply' => $isAdmin,
            'body' => $body,
            'viewed' => false,
        ]);

        // Update ticket timestamp
        $ticket = Ticket::find($ticketId);
        if ($ticket) {
            $ticket->touch();
        }

        return $message;
    }

    /**
     * Update ticket status.
     *
     * @param int $ticketId
     * @param string $status
     * @return Ticket|null
     */
    public function updateStatus(int $ticketId, string $status): ?Ticket
    {
        $ticket = Ticket::find($ticketId);

        if ($ticket) {
            $ticket->status = $status;

            if ($status === 'closed') {
                $ticket->closed_at = now();
            } else {
                $ticket->closed_at = null;
            }

            $ticket->save();
        }

        return $ticket;
    }

    /**
     * Get count of open tickets.
     *
     * @return int
     */
    public function getOpenTicketCount(): int
    {
        // Check if the tickets table exists before querying
        if (!\Schema::hasTable('tickets')) {
            return 0;
        }

        return Ticket::where('status', 'open')->count();
    }

    /**
     * Get count of unread messages for a user.
     *
     * @param int $userId
     * @return int
     */
    public function getUnreadCountForUser(int $userId): int
    {
        // Check if the tables exist before querying
        if (!\Schema::hasTable('tickets') || !\Schema::hasTable('ticket_messages')) {
            return 0;
        }

        return TicketMessage::whereHas('ticket', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->where('is_admin_reply', true)
            ->where('viewed', false)
            ->count();
    }

    /**
     * Mark messages as viewed for a ticket.
     *
     * @param int $ticketId
     * @param bool $markAdminReplies Mark admin replies as viewed (for users)
     * @param bool $markUserReplies Mark user replies as viewed (for admins)
     * @return void
     */
    public function markMessagesAsViewed(int $ticketId, bool $markAdminReplies = false, bool $markUserReplies = false): void
    {
        $query = TicketMessage::where('ticket_id', $ticketId)->where('viewed', false);

        if ($markAdminReplies && !$markUserReplies) {
            $query->where('is_admin_reply', true);
        } elseif ($markUserReplies && !$markAdminReplies) {
            $query->where('is_admin_reply', false);
        }

        $query->update(['viewed' => true]);
    }
}
