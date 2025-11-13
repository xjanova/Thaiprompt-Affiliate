<?php

namespace App\Observers;

use App\Models\TicketReply;
use App\Services\NotificationService;

class TicketReplyObserver
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the TicketReply "created" event.
     */
    public function created(TicketReply $reply): void
    {
        // Notify ticket owner when someone replies
        $ticket = $reply->ticket;

        if ($ticket && $ticket->user_id) {
            // Don't notify user about their own replies
            if ($reply->user_id !== $ticket->user_id) {
                try {
                    $this->notificationService->notifyTicketReply($ticket->user, $ticket, $reply);
                } catch (\Exception $e) {
                    \Log::error('Failed to notify user about ticket reply: ' . $e->getMessage(), [
                        'reply_id' => $reply->id,
                        'ticket_id' => $ticket->id,
                    ]);
                }
            }
        }
    }

    /**
     * Handle the TicketReply "updated" event.
     */
    public function updated(TicketReply $reply): void
    {
        //
    }

    /**
     * Handle the TicketReply "deleted" event.
     */
    public function deleted(TicketReply $reply): void
    {
        //
    }

    /**
     * Handle the TicketReply "restored" event.
     */
    public function restored(TicketReply $reply): void
    {
        //
    }

    /**
     * Handle the TicketReply "force deleted" event.
     */
    public function forceDeleted(TicketReply $reply): void
    {
        //
    }
}
