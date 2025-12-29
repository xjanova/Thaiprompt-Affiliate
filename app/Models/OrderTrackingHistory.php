<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Order Tracking History Model - ประวัติการติดตามคำสั่งซื้อ
 *
 * @property int $id
 * @property int $order_id
 * @property string $status สถานะ
 * @property string $title หัวข้อ
 * @property string|null $description รายละเอียด
 * @property string|null $location ตำแหน่ง
 * @property string|null $tracking_number เลขพัสดุ
 * @property string|null $shipping_provider บริษัทขนส่ง
 * @property int|null $created_by ผู้สร้าง
 * @property string $created_by_type ประเภทผู้สร้าง
 * @property array|null $meta_data ข้อมูลเพิ่มเติม
 * @property \Carbon\Carbon $tracked_at เวลาที่เกิดเหตุการณ์
 */
class OrderTrackingHistory extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_tracking_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'order_id',
        'status',
        'title',
        'description',
        'location',
        'tracking_number',
        'shipping_provider',
        'created_by',
        'created_by_type',
        'meta_data',
        'tracked_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'meta_data' => 'array',
        'tracked_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ Order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * ความสัมพันธ์กับ User (ผู้สร้าง)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * สร้างประวัติใหม่
     */
    public static function createEntry(
        Order $order,
        string $status,
        string $title,
        array $options = []
    ): static {
        return static::create([
            'order_id' => $order->id,
            'status' => $status,
            'title' => $title,
            'description' => $options['description'] ?? null,
            'location' => $options['location'] ?? null,
            'tracking_number' => $options['tracking_number'] ?? $order->tracking_number,
            'shipping_provider' => $options['shipping_provider'] ?? $order->shipping_provider,
            'created_by' => $options['created_by'] ?? auth()->id(),
            'created_by_type' => $options['created_by_type'] ?? 'system',
            'meta_data' => $options['meta_data'] ?? null,
            'tracked_at' => $options['tracked_at'] ?? now(),
        ]);
    }

    /**
     * Get status icon
     */
    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            'pending' => '🕐',
            'paid' => '💳',
            'processing' => '📦',
            'shipped' => '🚚',
            'in_transit' => '🚛',
            'out_for_delivery' => '🏍️',
            'delivered' => '✅',
            'completed' => '🎉',
            'cancelled' => '❌',
            'refunded' => '💰',
            default => '📋',
        };
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => '#F59E0B',
            'paid' => '#3B82F6',
            'processing' => '#8B5CF6',
            'shipped' => '#06B6D4',
            'in_transit' => '#0EA5E9',
            'out_for_delivery' => '#10B981',
            'delivered' => '#22C55E',
            'completed' => '#22C55E',
            'cancelled' => '#EF4444',
            'refunded' => '#6B7280',
            default => '#9CA3AF',
        };
    }
}
