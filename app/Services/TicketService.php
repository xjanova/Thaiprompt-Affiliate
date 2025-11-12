<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\TicketAttachment;
use App\Models\TicketAssignmentRule;
use App\Models\TicketSlaPolicy;
use App\Models\TicketRating;
use App\Models\TicketRelationship;
use App\Models\TicketNotificationLog;
use App\Models\TicketNotificationSetting;
use App\Models\KbArticle;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

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
                'tags' => $data['tags'] ?? null,
            ]);

            // Apply SLA policy
            $ticket->applySlaPolicy();

            // Auto-assign if rules exist
            TicketAssignmentRule::autoAssignTicket($ticket);

            // Handle file attachments
            if (!empty($data['attachments'])) {
                $this->handleAttachments($ticket, $data['attachments']);
            }

            // Link suggested KB articles
            if (!empty($data['suggested_articles'])) {
                $ticket->kbArticles()->attach($data['suggested_articles']);
            }

            // Send notification to admins
            $this->notifyAdmins($ticket, 'new_ticket');

            DB::commit();

            return $ticket->fresh();
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
            ]);

            // Handle file attachments for reply
            if (!empty($data['attachments'])) {
                $this->handleAttachments($reply, $data['attachments'], $data['user_id']);
            }

            // Record first response time if this is from staff
            $user = User::find($data['user_id']);
            if ($user && ($user->is_super_admin || in_array($user->role, ['admin', 'moderator']))) {
                $ticket->recordFirstResponse();
            }

            // If ticket was closed or resolved, reopen it when user replies
            if ($ticket->isClosed() && $ticket->user_id === $data['user_id']) {
                $ticket->update([
                    'status' => 'open',
                    'resolved_at' => null,
                    'closed_at' => null,
                    'resolution_time_minutes' => null,
                ]);
            }

            // Send notification
            $this->notifyTicketParticipants($ticket, $reply);

            DB::commit();

            return $reply->fresh();
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
                $ticket->recordResolution();
            } elseif ($status === 'closed') {
                $updateData['closed_at'] = now();
                if (!$ticket->resolved_at) {
                    $updateData['resolved_at'] = now();
                    $ticket->recordResolution();
                }

                // Request rating when ticket is closed
                if (!$ticket->isRated()) {
                    $this->requestRating($ticket);
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
            'overdue' => Ticket::overdue()->count(),
            'avg_response_time' => round(Ticket::whereNotNull('response_time_minutes')->avg('response_time_minutes'), 2),
            'avg_resolution_time' => round(Ticket::whereNotNull('resolution_time_minutes')->avg('resolution_time_minutes'), 2),
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
        $query = Ticket::with(['user', 'assignedTo', 'category', 'rating']);

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
            // Use full-text search if available
            if (config('database.default') === 'mysql') {
                try {
                    $query->fullTextSearch($filters['search']);
                } catch (\Exception $e) {
                    // Fallback to LIKE search
                    $query->search($filters['search']);
                }
            } else {
                $query->search($filters['search']);
            }
        }

        if (!empty($filters['tag'])) {
            $query->withTag($filters['tag']);
        }

        if (isset($filters['unassigned']) && $filters['unassigned']) {
            $query->whereNull('assigned_to');
        }

        if (isset($filters['overdue']) && $filters['overdue']) {
            $query->overdue();
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // Default ordering: open tickets first, then by priority, then by creation date
        $query->orderByRaw("FIELD(status, 'open', 'in_progress', 'waiting_customer', 'resolved', 'closed')")
            ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')")
            ->orderBy('created_at', 'desc');

        return $query;
    }

    // ==================== NEW METHODS ====================

    /**
     * Handle file attachments
     */
    public function handleAttachments($attachable, $files, $uploadedBy = null)
    {
        $uploadedBy = $uploadedBy ?? auth()->id();
        $uploadedFiles = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $uploadedFiles[] = $this->uploadFile($attachable, $file, $uploadedBy);
            }
        }

        return $uploadedFiles;
    }

    /**
     * Upload a single file
     */
    private function uploadFile($attachable, UploadedFile $file, $uploadedBy)
    {
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('tickets/attachments', $filename, 'public');

        // Generate thumbnail for images
        $thumbnailPath = null;
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $thumbnailPath = $this->generateThumbnail($file, $filename);
        }

        // Calculate file hash
        $hash = md5_file($file->getRealPath());

        $attachment = $attachable->attachments()->create([
            'uploaded_by' => $uploadedBy,
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'thumbnail_path' => $thumbnailPath,
            'hash' => $hash,
            'is_scanned' => false, // Can integrate virus scanning later
        ]);

        return $attachment;
    }

    /**
     * Generate thumbnail for image
     */
    private function generateThumbnail(UploadedFile $file, $filename)
    {
        // Basic implementation - can be enhanced with image manipulation library
        $thumbnailFilename = 'thumb_' . $filename;
        $thumbnailPath = $file->storeAs('tickets/thumbnails', $thumbnailFilename, 'public');
        return $thumbnailPath;
    }

    /**
     * Merge tickets
     */
    public function mergeTickets(Ticket $sourceTicket, Ticket $targetTicket, $mergedBy = null)
    {
        try {
            DB::beginTransaction();

            $mergedBy = $mergedBy ?? auth()->id();

            // Copy all replies from source to target
            foreach ($sourceTicket->replies as $reply) {
                TicketReply::create([
                    'ticket_id' => $targetTicket->id,
                    'user_id' => $reply->user_id,
                    'message' => $reply->message,
                    'is_internal_note' => $reply->is_internal_note,
                    'created_at' => $reply->created_at,
                ]);

                // Copy reply attachments
                foreach ($reply->fileAttachments as $attachment) {
                    $targetTicket->attachments()->create([
                        'uploaded_by' => $attachment->uploaded_by,
                        'filename' => $attachment->filename,
                        'original_filename' => $attachment->original_filename,
                        'path' => $attachment->path,
                        'mime_type' => $attachment->mime_type,
                        'size' => $attachment->size,
                        'thumbnail_path' => $attachment->thumbnail_path,
                        'hash' => $attachment->hash,
                    ]);
                }
            }

            // Copy attachments from source ticket
            foreach ($sourceTicket->attachments as $attachment) {
                $targetTicket->attachments()->create([
                    'uploaded_by' => $attachment->uploaded_by,
                    'filename' => $attachment->filename,
                    'original_filename' => $attachment->original_filename,
                    'path' => $attachment->path,
                    'mime_type' => $attachment->mime_type,
                    'size' => $attachment->size,
                    'thumbnail_path' => $attachment->thumbnail_path,
                    'hash' => $attachment->hash,
                ]);
            }

            // Add internal note about merge
            TicketReply::create([
                'ticket_id' => $targetTicket->id,
                'user_id' => $mergedBy,
                'message' => "Ticket {$sourceTicket->ticket_number} ถูกรวมเข้ากับ ticket นี้",
                'is_internal_note' => true,
            ]);

            // Mark source ticket as merged
            $sourceTicket->update([
                'merged_into_ticket_id' => $targetTicket->id,
                'status' => 'closed',
                'closed_at' => now(),
            ]);

            // Create relationship
            TicketRelationship::create([
                'ticket_id' => $sourceTicket->id,
                'related_ticket_id' => $targetTicket->id,
                'relationship_type' => 'duplicate',
                'note' => 'Merged ticket',
                'created_by' => $mergedBy,
            ]);

            DB::commit();

            return $targetTicket->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to merge tickets: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Link tickets
     */
    public function linkTickets(Ticket $ticket, Ticket $relatedTicket, $type = 'related', $note = null)
    {
        return TicketRelationship::createBidirectional(
            $ticket->id,
            $relatedTicket->id,
            $type,
            $note
        );
    }

    /**
     * Rate a ticket
     */
    public function rateTicket(Ticket $ticket, array $data)
    {
        return TicketRating::updateOrCreate(
            ['ticket_id' => $ticket->id],
            [
                'user_id' => $data['user_id'],
                'rating' => $data['rating'],
                'feedback' => $data['feedback'] ?? null,
                'response_speed' => $data['response_speed'] ?? null,
                'solution_quality' => $data['solution_quality'] ?? null,
                'staff_friendliness' => $data['staff_friendliness'] ?? null,
            ]
        );
    }

    /**
     * Request rating from user
     */
    private function requestRating(Ticket $ticket)
    {
        $this->notifyUser($ticket, $ticket->user_id, 'rating_request');
    }

    /**
     * Link KB article to ticket
     */
    public function linkKbArticle(Ticket $ticket, $articleId, $wasHelpful = null)
    {
        $ticket->kbArticles()->syncWithoutDetaching([
            $articleId => ['was_helpful' => $wasHelpful]
        ]);
    }

    /**
     * Suggest KB articles for a ticket
     */
    public function suggestKbArticles(Ticket $ticket, $limit = 5)
    {
        // Simple keyword-based suggestion
        $keywords = explode(' ', $ticket->subject . ' ' . $ticket->description);
        $keywords = array_filter($keywords, fn($word) => strlen($word) > 3);

        if (empty($keywords)) {
            return KbArticle::published()
                ->where('category_id', $ticket->category_id)
                ->limit($limit)
                ->get();
        }

        $query = KbArticle::published();

        foreach ($keywords as $keyword) {
            $query->orWhere('title', 'like', "%{$keyword}%")
                  ->orWhere('content', 'like', "%{$keyword}%");
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get advanced analytics
     */
    public function getAdvancedAnalytics($dateFrom = null, $dateTo = null)
    {
        $dateFrom = $dateFrom ?? now()->subDays(30);
        $dateTo = $dateTo ?? now();

        $tickets = Ticket::whereBetween('created_at', [$dateFrom, $dateTo]);

        return [
            'total_tickets' => $tickets->count(),
            'tickets_by_status' => $tickets->get()->groupBy('status')->map->count(),
            'tickets_by_priority' => $tickets->get()->groupBy('priority')->map->count(),
            'tickets_by_category' => $tickets->get()->groupBy('category_id')->map->count(),
            'avg_response_time' => round($tickets->whereNotNull('response_time_minutes')->avg('response_time_minutes'), 2),
            'avg_resolution_time' => round($tickets->whereNotNull('resolution_time_minutes')->avg('resolution_time_minutes'), 2),
            'sla_compliance' => $this->calculateSlaCompliance($dateFrom, $dateTo),
            'customer_satisfaction' => TicketRating::getStatistics(),
            'top_agents' => $this->getTopAgents($dateFrom, $dateTo),
            'busiest_hours' => $this->getBusiestHours($dateFrom, $dateTo),
        ];
    }

    /**
     * Calculate SLA compliance
     */
    private function calculateSlaCompliance($dateFrom, $dateTo)
    {
        $totalTickets = Ticket::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('due_at')
            ->count();

        if ($totalTickets === 0) {
            return 100;
        }

        $breachedTickets = Ticket::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('sla_breached_at')
            ->count();

        return round((($totalTickets - $breachedTickets) / $totalTickets) * 100, 2);
    }

    /**
     * Get top performing agents
     */
    private function getTopAgents($dateFrom, $dateTo, $limit = 10)
    {
        return Ticket::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('assigned_to')
            ->whereIn('status', ['resolved', 'closed'])
            ->select('assigned_to', DB::raw('COUNT(*) as resolved_count'))
            ->groupBy('assigned_to')
            ->orderBy('resolved_count', 'desc')
            ->limit($limit)
            ->with('assignedTo:id,name')
            ->get();
    }

    /**
     * Get busiest hours
     */
    private function getBusiestHours($dateFrom, $dateTo)
    {
        return Ticket::whereBetween('created_at', [$dateFrom, $dateTo])
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
    }

    /**
     * Check overdue tickets and send notifications
     */
    public function checkOverdueTickets()
    {
        $overdueTickets = Ticket::whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->where('is_overdue', false)
            ->open()
            ->get();

        foreach ($overdueTickets as $ticket) {
            $ticket->update([
                'is_overdue' => true,
                'sla_breached_at' => now(),
            ]);

            // Notify assigned user and admins
            if ($ticket->assigned_to) {
                $this->notifyUser($ticket, $ticket->assigned_to, 'sla_breached');
            }
            $this->notifyAdmins($ticket, 'sla_breached');
        }

        return $overdueTickets->count();
    }

    /**
     * Enhanced notification methods
     */
    private function notifyUser(Ticket $ticket, $userId, string $type)
    {
        $settings = TicketNotificationSetting::getOrCreateForUser($userId);

        // Check if user wants email notifications for this event
        if ($settings->shouldNotifyEmail($type)) {
            TicketNotificationLog::createLog($ticket->id, $userId, $type, 'email');
            // Queue email job here
            // dispatch(new SendTicketNotificationEmail($ticket, $userId, $type));
        }

        // Check if user wants in-app notifications
        if ($settings->shouldNotifyInApp($type)) {
            TicketNotificationLog::createLog($ticket->id, $userId, $type, 'in_app');
            // Create in-app notification here
        }

        Log::info("Notifying user {$userId} about ticket {$ticket->ticket_number} - {$type}");
    }
}
