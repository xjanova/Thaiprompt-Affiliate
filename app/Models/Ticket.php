<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'user_id',
        'assigned_to',
        'category_id',
        'priority',
        'status',
        'subject',
        'description',
        'resolution_notes',
        'last_reply_at',
        'resolved_at',
        'closed_at',
        // SLA fields
        'first_response_at',
        'due_at',
        'sla_breached_at',
        'is_overdue',
        'response_time_minutes',
        'resolution_time_minutes',
        // Merging
        'merged_into_ticket_id',
        // Tags
        'tags',
    ];

    protected $casts = [
        'last_reply_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'first_response_at' => 'datetime',
        'due_at' => 'datetime',
        'sla_breached_at' => 'datetime',
        'is_overdue' => 'boolean',
        'response_time_minutes' => 'integer',
        'resolution_time_minutes' => 'integer',
        'tags' => 'array',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = self::generateTicketNumber();
            }
        });
    }

    /**
     * Generate unique ticket number
     */
    public static function generateTicketNumber()
    {
        do {
            $number = 'TKT-' . strtoupper(Str::random(3)) . '-' . date('Ymd') . '-' . rand(1000, 9999);
        } while (self::where('ticket_number', $number)->exists());

        return $number;
    }

    /**
     * Get the user who created the ticket
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user assigned to the ticket
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the category of the ticket
     */
    public function category()
    {
        return $this->belongsTo(TicketCategory::class);
    }

    /**
     * Get all replies for the ticket
     */
    public function replies()
    {
        return $this->hasMany(TicketReply::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get public replies (exclude internal notes)
     */
    public function publicReplies()
    {
        return $this->hasMany(TicketReply::class)->where('is_internal_note', false)->orderBy('created_at', 'asc');
    }

    /**
     * Get internal notes only
     */
    public function internalNotes()
    {
        return $this->hasMany(TicketReply::class)->where('is_internal_note', true)->orderBy('created_at', 'asc');
    }

    /**
     * Get attachments (polymorphic)
     */
    public function attachments()
    {
        return $this->morphMany(TicketAttachment::class, 'attachable');
    }

    /**
     * Get the rating for this ticket
     */
    public function rating()
    {
        return $this->hasOne(TicketRating::class);
    }

    /**
     * Get ticket relationships
     */
    public function relationships()
    {
        return $this->hasMany(TicketRelationship::class, 'ticket_id');
    }

    /**
     * Get related tickets through relationships
     */
    public function relatedTickets()
    {
        return $this->belongsToMany(Ticket::class, 'ticket_relationships', 'ticket_id', 'related_ticket_id')
            ->withPivot('relationship_type', 'note', 'created_by')
            ->withTimestamps();
    }

    /**
     * Get the ticket this was merged into
     */
    public function mergedIntoTicket()
    {
        return $this->belongsTo(Ticket::class, 'merged_into_ticket_id');
    }

    /**
     * Get tickets that were merged into this one
     */
    public function mergedTickets()
    {
        return $this->hasMany(Ticket::class, 'merged_into_ticket_id');
    }

    /**
     * Get linked knowledge base articles
     */
    public function kbArticles()
    {
        return $this->belongsToMany(KbArticle::class, 'kb_article_ticket', 'ticket_id', 'article_id')
            ->withPivot('was_helpful')
            ->withTimestamps();
    }

    /**
     * Get notification logs
     */
    public function notificationLogs()
    {
        return $this->hasMany(TicketNotificationLog::class);
    }

    /**
     * Scope to filter by status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by priority
     */
    public function scopePriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to filter by category
     */
    public function scopeCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope to filter by assigned user
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope to get open tickets
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress', 'waiting_customer']);
    }

    /**
     * Scope to get closed tickets
     */
    public function scopeClosed($query)
    {
        return $query->whereIn('status', ['resolved', 'closed']);
    }

    /**
     * Scope to get overdue tickets
     */
    public function scopeOverdue($query)
    {
        return $query->where('is_overdue', true)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->open();
    }

    /**
     * Scope to get unassigned tickets
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    /**
     * Scope to filter by tags
     */
    public function scopeWithTag($query, $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    /**
     * Scope: Search tickets (full-text)
     */
    public function scopeSearch($query, $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('ticket_number', 'like', "%{$keyword}%")
              ->orWhere('subject', 'like', "%{$keyword}%")
              ->orWhere('description', 'like', "%{$keyword}%");
        });
    }

    /**
     * Scope: Full-text search using MySQL FULLTEXT
     */
    public function scopeFullTextSearch($query, $keyword)
    {
        return $query->whereRaw(
            "MATCH(ticket_number, subject, description) AGAINST(? IN NATURAL LANGUAGE MODE)",
            [$keyword]
        );
    }

    /**
     * Check if ticket is open
     */
    public function isOpen()
    {
        return in_array($this->status, ['open', 'in_progress', 'waiting_customer']);
    }

    /**
     * Check if ticket is closed
     */
    public function isClosed()
    {
        return in_array($this->status, ['resolved', 'closed']);
    }

    /**
     * Get priority badge color
     */
    public function getPriorityColorAttribute()
    {
        return match($this->priority) {
            'low' => 'gray',
            'medium' => 'blue',
            'high' => 'orange',
            'critical' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'open' => 'blue',
            'in_progress' => 'yellow',
            'waiting_customer' => 'purple',
            'resolved' => 'green',
            'closed' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get priority label in Thai
     */
    public function getPriorityLabelAttribute()
    {
        return match($this->priority) {
            'low' => 'ต่ำ',
            'medium' => 'ปานกลาง',
            'high' => 'สูง',
            'critical' => 'วิกฤต',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * Get status label in Thai
     */
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'open' => 'เปิด',
            'in_progress' => 'กำลังดำเนินการ',
            'waiting_customer' => 'รอลูกค้า',
            'resolved' => 'แก้ไขแล้ว',
            'closed' => 'ปิด',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * Get unread replies count for user
     */
    public function getUnreadRepliesCount($userId = null)
    {
        $userId = $userId ?? auth()->id();

        return $this->replies()
            ->where('user_id', '!=', $userId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Check if ticket is overdue
     */
    public function checkOverdue()
    {
        if (!$this->due_at || $this->isClosed()) {
            return false;
        }

        $isOverdue = now()->isAfter($this->due_at);

        if ($isOverdue && !$this->is_overdue) {
            $this->update([
                'is_overdue' => true,
                'sla_breached_at' => now(),
            ]);
        }

        return $isOverdue;
    }

    /**
     * Apply SLA policy to this ticket
     */
    public function applySlaPolicy()
    {
        $policy = TicketSlaPolicy::findMatchingPolicy($this->category_id, $this->priority);

        if ($policy) {
            $dueAt = $policy->calculateDueDate('first_response_time');
            $this->update(['due_at' => $dueAt]);
            return $policy;
        }

        return null;
    }

    /**
     * Record first response
     */
    public function recordFirstResponse()
    {
        if (!$this->first_response_at) {
            $responseTime = now()->diffInMinutes($this->created_at);
            $this->update([
                'first_response_at' => now(),
                'response_time_minutes' => $responseTime,
            ]);
        }
    }

    /**
     * Record resolution
     */
    public function recordResolution()
    {
        if (!$this->resolution_time_minutes) {
            $resolutionTime = now()->diffInMinutes($this->created_at);
            $this->update([
                'resolved_at' => now(),
                'resolution_time_minutes' => $resolutionTime,
            ]);
        }
    }

    /**
     * Get SLA status badge color
     */
    public function getSlaStatusColorAttribute()
    {
        if (!$this->due_at || $this->isClosed()) {
            return 'gray';
        }

        if ($this->is_overdue) {
            return 'red';
        }

        $hoursRemaining = now()->diffInHours($this->due_at, false);

        if ($hoursRemaining < 1) {
            return 'red';
        } elseif ($hoursRemaining < 4) {
            return 'orange';
        } else {
            return 'green';
        }
    }

    /**
     * Get SLA status label
     */
    public function getSlaStatusLabelAttribute()
    {
        if (!$this->due_at || $this->isClosed()) {
            return 'ไม่มี SLA';
        }

        if ($this->is_overdue) {
            return 'เกิน SLA';
        }

        $hoursRemaining = now()->diffInHours($this->due_at, false);

        if ($hoursRemaining < 1) {
            return 'ใกล้เกิน SLA';
        } elseif ($hoursRemaining < 4) {
            return 'เตือน SLA';
        } else {
            return 'ตามกำหนด';
        }
    }

    /**
     * Check if ticket has been rated
     */
    public function isRated()
    {
        return $this->rating()->exists();
    }

    /**
     * Check if ticket is merged
     */
    public function isMerged()
    {
        return !is_null($this->merged_into_ticket_id);
    }

    /**
     * Add tag to ticket
     */
    public function addTag($tag)
    {
        $tags = $this->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->update(['tags' => $tags]);
        }
    }

    /**
     * Remove tag from ticket
     */
    public function removeTag($tag)
    {
        $tags = $this->tags ?? [];
        $tags = array_filter($tags, fn($t) => $t !== $tag);
        $this->update(['tags' => array_values($tags)]);
    }
}
