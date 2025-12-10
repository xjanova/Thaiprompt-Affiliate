<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Order Message Model - ระบบแชทคำสั่งซื้อ
 *
 * ใช้สำหรับสื่อสารระหว่าง:
 * - ลูกค้า <-> ร้านค้า
 * - ลูกค้า <-> Admin
 * - ร้านค้า <-> Admin
 *
 * @property int $id
 * @property int $order_id
 * @property int $sender_id
 * @property string $sender_type ประเภทผู้ส่ง: customer, seller, admin
 * @property string $message ข้อความ
 * @property string|null $attachment ไฟล์แนบ
 * @property string|null $attachment_type ประเภทไฟล์
 * @property bool $is_read อ่านแล้วหรือยัง
 * @property \Carbon\Carbon|null $read_at เวลาที่อ่าน
 * @property int|null $read_by ผู้ที่อ่าน
 * @property bool $is_system_message ข้อความจากระบบ
 */
class OrderMessage extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_messages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'order_id',
        'sender_id',
        'sender_type',
        'message',
        'attachment',
        'attachment_type',
        'is_read',
        'read_at',
        'read_by',
        'is_system_message',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_read' => 'boolean',
        'is_system_message' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ Order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * ความสัมพันธ์กับ User (ผู้ส่ง)
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * ความสัมพันธ์กับ User (ผู้อ่าน)
     */
    public function reader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'read_by');
    }

    /**
     * Scope: Unread messages
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope: From customer
     */
    public function scopeFromCustomer($query)
    {
        return $query->where('sender_type', 'customer');
    }

    /**
     * Scope: From seller
     */
    public function scopeFromSeller($query)
    {
        return $query->where('sender_type', 'seller');
    }

    /**
     * Scope: From admin
     */
    public function scopeFromAdmin($query)
    {
        return $query->where('sender_type', 'admin');
    }

    /**
     * Scope: System messages
     */
    public function scopeSystemMessages($query)
    {
        return $query->where('is_system_message', true);
    }

    /**
     * ทำเครื่องหมายว่าอ่านแล้ว
     *
     * @param int|null $userId
     * @return void
     */
    public function markAsRead(?int $userId = null): void
    {
        if ($this->is_read) {
            return;
        }

        $this->is_read = true;
        $this->read_at = now();
        $this->read_by = $userId ?? auth()->id();
        $this->save();

        // อัพเดทสถานะ unread ของ order
        $this->updateOrderUnreadStatus();
    }

    /**
     * อัพเดทสถานะข้อความที่ยังไม่ได้อ่านของ Order
     */
    protected function updateOrderUnreadStatus(): void
    {
        $hasUnread = static::where('order_id', $this->order_id)
            ->where('is_read', false)
            ->exists();

        $this->order->update(['has_unread_messages' => $hasUnread]);
    }

    /**
     * ส่งข้อความใหม่
     *
     * @param Order $order
     * @param int $senderId
     * @param string $senderType
     * @param string $message
     * @param array $options
     * @return static
     */
    public static function send(
        Order $order,
        int $senderId,
        string $senderType,
        string $message,
        array $options = []
    ): static {
        $orderMessage = static::create([
            'order_id' => $order->id,
            'sender_id' => $senderId,
            'sender_type' => $senderType,
            'message' => $message,
            'attachment' => $options['attachment'] ?? null,
            'attachment_type' => $options['attachment_type'] ?? null,
            'is_system_message' => $options['is_system_message'] ?? false,
        ]);

        // อัพเดท order
        $order->update([
            'has_unread_messages' => true,
            'last_message_at' => now(),
        ]);

        return $orderMessage;
    }

    /**
     * ส่งข้อความจากระบบ
     *
     * @param Order $order
     * @param string $message
     * @return static
     */
    public static function sendSystemMessage(Order $order, string $message): static
    {
        return static::create([
            'order_id' => $order->id,
            'sender_id' => 1, // System user ID
            'sender_type' => 'admin',
            'message' => $message,
            'is_read' => true, // ข้อความระบบถือว่าอ่านแล้ว
            'is_system_message' => true,
        ]);
    }

    /**
     * Get sender name
     */
    public function getSenderNameAttribute(): string
    {
        if ($this->is_system_message) {
            return 'ระบบ';
        }

        return match ($this->sender_type) {
            'customer' => $this->sender->name ?? 'ลูกค้า',
            'seller' => $this->sender->name ?? 'ร้านค้า',
            'admin' => 'ทีมงาน ' . config('app.name'),
            default => 'ไม่ระบุ',
        };
    }

    /**
     * Get sender avatar
     */
    public function getSenderAvatarAttribute(): ?string
    {
        if ($this->is_system_message) {
            return null;
        }

        return $this->sender->avatar ?? null;
    }

    /**
     * Get attachment URL
     */
    public function getAttachmentUrlAttribute(): ?string
    {
        if (!$this->attachment) {
            return null;
        }

        return asset('storage/' . $this->attachment);
    }
}
