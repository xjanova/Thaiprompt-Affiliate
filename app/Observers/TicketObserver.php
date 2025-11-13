<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Services\NotificationService;

class TicketObserver
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the Ticket "created" event.
     */
    public function created(Ticket $ticket): void
    {
        // Notify admins about new support ticket
        if ($ticket->status === 'open') {
            try {
                $this->notificationService->notifyAdminNewTicket($ticket);
            } catch (\Exception $e) {
                \Log::error('Failed to notify admin about new ticket: ' . $e->getMessage(), [
                    'ticket_id' => $ticket->id,
                ]);
            }
        }
    }

    /**
     * Handle the Ticket "updated" event.
     */
    public function updated(Ticket $ticket): void
    {
        // Check if high/critical priority ticket becomes unassigned
        if ($ticket->isDirty('assigned_to') &&
            $ticket->assigned_to === null &&
            in_array($ticket->priority, ['high', 'critical'])) {
            try {
                $this->notificationService->notifyAdminUnassignedTicket($ticket);
            } catch (\Exception $e) {
                \Log::error('Failed to notify admin about unassigned ticket: ' . $e->getMessage(), [
                    'ticket_id' => $ticket->id,
                ]);
            }
        }

        // Notify user when ticket status changes
        if ($ticket->isDirty('status') && $ticket->status === 'resolved') {
            // Can add notification for resolved tickets if needed
        }
    }

    /**
     * Handle the Ticket "deleted" event.
     */
    public function deleted(Ticket $ticket): void
    {
        //
    }

    /**
     * Handle the Ticket "restored" event.
     */
    public function restored(Ticket $ticket): void
    {
        //
    }

    /**
     * Handle the Ticket "force deleted" event.
     */
    public function forceDeleted(Ticket $ticket): void
    {
        //
    }
}
