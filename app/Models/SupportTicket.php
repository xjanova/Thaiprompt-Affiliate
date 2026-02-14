<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SupportTicket Model
 *
 * จัดการข้อมูล Ticket สำหรับติดต่อแอดมิน
 *
 * @property int $id
 * @property string $ticket_number
 * @property int $user_id
 * @property string $subject
 * @property string $category
 * @property string $priority
 * @property string $status
 */
class SupportTicket extends Model
{
    use SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'support_tickets';

    /**
     * Fields ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'ticket_number',
        'user_id',
        'subject',
        'category',
        'priority',
        'status',
        'assigned_to',
        'first_response_at',
        'resolved_at',
        'closed_at',
        'last_message_at',
        'satisfaction_rating',
        'satisfaction_comment',
    ];

    /**
     * Casts
     *
     * @var array<string, string>
     */
    protected $casts = [
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'last_message_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ค่าเริ่มต้น
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'category' => 'general',
        'priority' => 'medium',
        'status' => 'open',
    ];

    // =====================================================
    // Boot
    // =====================================================

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // สร้างเลขที่ ticket อัตโนมัติ
        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = self::generateTicketNumber();
            }
        });
    }

    /**
     * สร้างเลขที่ ticket
     */
    public static function generateTicketNumber(): string
    {
        $prefix = 'TKT';
        $date = now()->format('ymd');
        $lastTicket = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        if ($lastTicket) {
            $lastNumber = (int) substr($lastTicket->ticket_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.$date.str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    // =====================================================
    // Relationships
    // =====================================================

    /**
     * ความสัมพันธ์กับ User (ผู้สร้าง ticket)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ User (ผู้รับผิดชอบ)
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * ข้อความใน ticket
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class, 'ticket_id');
    }

    // =====================================================
    // Scopes
    // =====================================================

    /**
     * Scope สำหรับ ticket ที่เปิดอยู่
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope สำหรับ ticket ที่กำลังดำเนินการ
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope สำหรับ ticket ที่ปิดแล้ว
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    /**
     * Scope สำหรับ ticket ตาม priority
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    // =====================================================
    // Accessors
    // =====================================================

    /**
     * ชื่อหมวดหมู่ภาษาไทย
     */
    public function getCategoryTextAttribute(): string
    {
        return match ($this->category) {
            'general' => 'ทั่วไป',
            'account' => 'บัญชี',
            'payment' => 'การชำระเงิน',
            'rider' => 'ไรเดอร์',
            'technical' => 'ปัญหาเทคนิค',
            'complaint' => 'ร้องเรียน',
            'suggestion' => 'ข้อเสนอแนะ',
            'other' => 'อื่นๆ',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * ชื่อ priority ภาษาไทย
     */
    public function getPriorityTextAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'ต่ำ',
            'medium' => 'ปานกลาง',
            'high' => 'สูง',
            'urgent' => 'ด่วนมาก',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * ชื่อสถานะภาษาไทย
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'open' => 'เปิดใหม่',
            'in_progress' => 'กำลังดำเนินการ',
            'waiting' => 'รอผู้ใช้ตอบกลับ',
            'resolved' => 'แก้ไขแล้ว',
            'closed' => 'ปิดแล้ว',
            default => 'ไม่ทราบ',
        };
    }

    /**
     * จำนวนข้อความทั้งหมด
     */
    public function getMessageCountAttribute(): int
    {
        return $this->messages()->count();
    }

    /**
     * มีข้อความที่ยังไม่อ่านจากแอดมินหรือไม่
     */
    public function getHasUnreadAdminMessageAttribute(): bool
    {
        return $this->messages()
            ->where('is_from_admin', true)
            ->where('is_read', false)
            ->exists();
    }

    // =====================================================
    // Methods
    // =====================================================

    /**
     * เพิ่มข้อความ
     */
    public function addMessage(int $userId, string $message, bool $isFromAdmin = false, ?array $attachments = null): SupportTicketMessage
    {
        $ticketMessage = $this->messages()->create([
            'user_id' => $userId,
            'message' => $message,
            'is_from_admin' => $isFromAdmin,
            'attachments' => $attachments,
        ]);

        // อัพเดทเวลาข้อความล่าสุด
        $this->update(['last_message_at' => now()]);

        // ถ้าเป็นข้อความแรกจากแอดมิน
        if ($isFromAdmin && empty($this->first_response_at)) {
            $this->update(['first_response_at' => now()]);
        }

        return $ticketMessage;
    }

    /**
     * มอบหมายให้แอดมิน
     */
    public function assignTo(int $adminId): void
    {
        $this->update([
            'assigned_to' => $adminId,
            'status' => 'in_progress',
        ]);
    }

    /**
     * เปลี่ยนสถานะเป็น resolved
     */
    public function markAsResolved(): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    /**
     * ปิด ticket
     */
    public function close(): void
    {
        $this->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    /**
     * ให้คะแนนความพึงพอใจ
     */
    public function rate(int $rating, ?string $comment = null): void
    {
        $this->update([
            'satisfaction_rating' => $rating,
            'satisfaction_comment' => $comment,
        ]);
    }
}
