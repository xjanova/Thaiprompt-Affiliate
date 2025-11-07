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
    ];

    protected $casts = [
        'last_reply_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
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
}
