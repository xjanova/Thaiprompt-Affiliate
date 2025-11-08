<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketService
{
    /**
     * Create a new ticket
     */
    public function createTicket(array $data)
    {
        try {
            DB::beginTransaction();

            $ticket = Ticket::create([
                'user_id' => $data['user_id'],
                'category_id' => $data['category_id'] ?? null,
                'priority' => $data['priority'] ?? 'medium',
                'status' => 'open',
                'subject' => $data['subject'],
                'description' => $data['description'],
            ]);

            // Send notification to admins
            $this->notifyAdmins($ticket, 'new');

            DB::commit();

            return $ticket;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create ticket: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Add a reply to a ticket
     */
    public function addReply(Ticket $ticket, array $data)
    {
        try {
            DB::beginTransaction();

            $reply = TicketReply::create([
                'ticket_id' => $ticket->id,
                'user_id' => $data['user_id'],
                'message' => $data['message'],
                'is_internal_note' => $data['is_internal_note'] ?? false,
                'attachments' => $data['attachments'] ?? null,
            ]);

            // If ticket was closed or resolved, reopen it when user replies
            if ($ticket->isClosed() && $ticket->user_id === $data['user_id']) {
                $ticket->update([
                    'status' => 'open',
                    'resolved_at' => null,
                    'closed_at' => null,
                ]);
            }

            // Send notification
            $this->notifyTicketParticipants($ticket, $reply);

            DB::commit();

            return $reply;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to add reply: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Assign ticket to a user
     */
    public function assignTicket(Ticket $ticket, $userId)
    {
        try {
            $ticket->update([
                'assigned_to' => $userId,
            ]);

            // Notify the assigned user
            if ($userId) {
                $this->notifyUser($ticket, $userId, 'assigned');
            }

            return $ticket;
        } catch (\Exception $e) {
            Log::error('Failed to assign ticket: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Change ticket status
     */
    public function changeStatus(Ticket $ticket, string $status, ?string $notes = null)
    {
        try {
            $updateData = ['status' => $status];

            if ($status === 'resolved') {
                $updateData['resolved_at'] = now();
            } elseif ($status === 'closed') {
                $updateData['closed_at'] = now();
                if (!$ticket->resolved_at) {
                    $updateData['resolved_at'] = now();
                }
            }

            if ($notes) {
                $updateData['resolution_notes'] = $notes;
            }

            $ticket->update($updateData);

            // Notify ticket creator
            $this->notifyUser($ticket, $ticket->user_id, 'status_changed');

            return $ticket;
        } catch (\Exception $e) {
            Log::error('Failed to change ticket status: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update ticket priority
     */
    public function updatePriority(Ticket $ticket, string $priority)
    {
        try {
            $ticket->update(['priority' => $priority]);
            return $ticket;
        } catch (\Exception $e) {
            Log::error('Failed to update ticket priority: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get ticket statistics
     */
    public function getStatistics()
    {
        return [
            'total' => Ticket::count(),
            'open' => Ticket::status('open')->count(),
            'in_progress' => Ticket::status('in_progress')->count(),
            'waiting_customer' => Ticket::status('waiting_customer')->count(),
            'resolved' => Ticket::status('resolved')->count(),
            'closed' => Ticket::status('closed')->count(),
            'critical' => Ticket::priority('critical')->open()->count(),
            'high' => Ticket::priority('high')->open()->count(),
            'unassigned' => Ticket::whereNull('assigned_to')->open()->count(),
            'my_tickets' => Ticket::where('assigned_to', auth()->id())->open()->count(),
        ];
    }

    /**
     * Notify admins about new ticket
     */
    private function notifyAdmins(Ticket $ticket, string $type)
    {
        // Get all admin users
        $admins = User::where('is_super_admin', true)
            ->orWhere('role', 'admin')
            ->orWhere('role', 'moderator')
            ->get();

        foreach ($admins as $admin) {
            // Create notification (you can use Laravel's notification system)
            // For now, just log it
            Log::info("Notifying admin {$admin->id} about ticket {$ticket->ticket_number}");
        }
    }

    /**
     * Notify ticket participants about new reply
     */
    private function notifyTicketParticipants(Ticket $ticket, TicketReply $reply)
    {
        // Notify ticket creator if reply is from staff
        if ($reply->isFromStaff() && $ticket->user_id !== $reply->user_id) {
            $this->notifyUser($ticket, $ticket->user_id, 'new_reply');
        }

        // Notify assigned staff if reply is from customer
        if (!$reply->isFromStaff() && $ticket->assigned_to && $ticket->assigned_to !== $reply->user_id) {
            $this->notifyUser($ticket, $ticket->assigned_to, 'new_reply');
        }

        // Notify all admins if ticket is not assigned and reply is from customer
        if (!$reply->isFromStaff() && !$ticket->assigned_to) {
            $this->notifyAdmins($ticket, 'new_reply');
        }
    }

    /**
     * Notify a specific user
     */
    private function notifyUser(Ticket $ticket, $userId, string $type)
    {
        // Create notification
        Log::info("Notifying user {$userId} about ticket {$ticket->ticket_number} - {$type}");
    }

    /**
     * Get tickets with filters
     */
    public function getFilteredTickets(array $filters = [])
    {
        $query = Ticket::with(['user', 'assignedTo', 'category']);

        if (!empty($filters['status'])) {
            $query->status($filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->priority($filters['priority']);
        }

        if (!empty($filters['category_id'])) {
            $query->category($filters['category_id']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->assignedTo($filters['assigned_to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('ticket_number', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('subject', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (isset($filters['unassigned']) && $filters['unassigned']) {
            $query->whereNull('assigned_to');
        }

        // Default ordering: open tickets first, then by priority, then by creation date
        $query->orderByRaw("FIELD(status, 'open', 'in_progress', 'waiting_customer', 'resolved', 'closed')")
            ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')")
            ->orderBy('created_at', 'desc');

        return $query;
    }
}
