<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * FreshMarketOrder - คำสั่งซื้อในตลาดสด
 *
 * @property int $id
 * @property string $order_number
 * @property int $buyer_id
 * @property int $seller_id
 * @property int|null $listing_id
 * @property int $quantity
 * @property float $unit_price
 * @property float $total_amount
 * @property float $platform_fee
 * @property float $seller_earning
 * @property string $delivery_type
 * @property float $delivery_fee
 * @property string $payment_method
 * @property string $payment_status
 * @property string $order_status
 * @property string|null $escrow_status
 * @property float|null $buyer_latitude
 * @property float|null $buyer_longitude
 * @property string|null $delivery_address
 * @property string|null $delivery_notes
 * @property int|null $rider_job_id
 * @property string|null $shipping_provider
 * @property string|null $tracking_number
 * @property \Carbon\Carbon|null $accepted_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon|null $cancelled_at
 * @property \Carbon\Carbon|null $buyer_confirmed_at
 * @property int|null $buyer_rating
 * @property string|null $buyer_review
 * @property int|null $seller_rating
 * @property string|null $seller_review
 * @property float $cashback_amount
 * @property bool $cashback_processed
 * @property bool $mlm_commission_processed
 * @property string|null $cancel_reason
 * @property string|null $cancelled_by
 */
class FreshMarketOrder extends Model
{
    use SoftDeletes;

    protected $table = 'fresh_market_orders';

    protected $fillable = [
        'order_number',
        'buyer_id',
        'seller_id',
        'listing_id',
        'quantity',
        'unit_price',
        'total_amount',
        'platform_fee',
        'seller_earning',
        'delivery_type',
        'delivery_fee',
        'payment_method',
        'payment_status',
        'order_status',
        'escrow_status',
        'buyer_latitude',
        'buyer_longitude',
        'delivery_address',
        'delivery_notes',
        'rider_job_id',
        'shipping_provider',
        'tracking_number',
        'accepted_at',
        'preparing_at',
        'ready_at',
        'delivered_at',
        'completed_at',
        'cancelled_at',
        'buyer_confirmed_at',
        'buyer_rating',
        'buyer_review',
        'seller_rating',
        'seller_review',
        'cashback_amount',
        'cashback_processed',
        'mlm_commission_processed',
        'cancel_reason',
        'cancelled_by',
        'rider_id',
        'rider_assigned_at',
        'rider_accepted_at',
        'rider_picked_up_at',
        'rider_delivered_at',
        'delivery_distance_km',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'seller_earning' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'buyer_latitude' => 'decimal:8',
        'buyer_longitude' => 'decimal:8',
        'cashback_amount' => 'decimal:2',
        'cashback_processed' => 'boolean',
        'mlm_commission_processed' => 'boolean',
        'quantity' => 'integer',
        'buyer_rating' => 'integer',
        'seller_rating' => 'integer',
        'accepted_at' => 'datetime',
        'preparing_at' => 'datetime',
        'ready_at' => 'datetime',
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'buyer_confirmed_at' => 'datetime',
        'rider_assigned_at' => 'datetime',
        'rider_accepted_at' => 'datetime',
        'rider_picked_up_at' => 'datetime',
        'rider_delivered_at' => 'datetime',
        'delivery_distance_km' => 'decimal:2',
    ];

    /**
     * สร้างเลข order อัตโนมัติ
     */
    protected static function booted(): void
    {
        static::creating(function (self $order) {
            if (empty($order->order_number)) {
                // ใช้ random suffix เพื่อป้องกัน race condition แทนการ count
                $order->order_number = 'TSD' . now()->format('ymd')
                    . strtoupper(\Illuminate\Support\Str::random(5));
            }
        });
    }

    // ===== Relationships =====

    /**
     * ผู้ซื้อ
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * ผู้ขาย
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(FreshMarketSeller::class, 'seller_id');
    }

    /**
     * สินค้า
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(FreshMarketListing::class, 'listing_id');
    }

    /**
     * งาน Rider
     */
    public function riderJob(): BelongsTo
    {
        return $this->belongsTo(RiderJob::class, 'rider_job_id');
    }

    /**
     * ไรเดอร์ที่ถูก assign
     */
    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }

    // ===== Scopes =====

    public function scopeActive($query)
    {
        return $query->whereNotIn('order_status', ['cancelled', 'completed']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('order_status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('order_status', 'pending');
    }

    public function scopeWithBuyerReview($query)
    {
        return $query->whereNotNull('buyer_rating');
    }

    /**
     * ตรวจสอบว่ารีวิวได้หรือยัง (สถานะ delivered/completed + ยังไม่เคยรีวิว)
     */
    public function canBeReviewed(): bool
    {
        return in_array($this->order_status, ['delivered', 'completed'])
            && is_null($this->getRawOriginal('buyer_rating'));
    }

    // ===== Status Methods =====

    /**
     * ผู้ขายรับออเดอร์
     */
    public function markAsAccepted(): void
    {
        $this->update([
            'order_status' => 'accepted',
            'accepted_at' => now(),
        ]);
    }

    /**
     * เริ่มจัดเตรียม
     */
    public function markAsPreparing(): void
    {
        $this->update([
            'order_status' => 'preparing',
            'preparing_at' => now(),
        ]);
    }

    /**
     * พร้อมส่ง/พร้อมรับ
     */
    public function markAsReady(): void
    {
        $this->update([
            'order_status' => 'ready',
            'ready_at' => now(),
        ]);
    }

    /**
     * กำลังจัดส่ง
     */
    public function markAsDelivering(): void
    {
        $this->update(['order_status' => 'delivering']);
    }

    /**
     * ส่งถึงแล้ว
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'order_status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    /**
     * เสร็จสิ้น (ผู้ซื้อยืนยัน)
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'order_status' => 'completed',
            'completed_at' => now(),
            'buyer_confirmed_at' => now(),
        ]);
    }

    /**
     * ยกเลิก
     */
    public function cancel(string $reason, string $cancelledBy = 'system'): void
    {
        $this->update([
            'order_status' => 'cancelled',
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
            'cancelled_by' => $cancelledBy,
        ]);
    }

    /**
     * ตรวจสอบว่ายกเลิกได้หรือไม่
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->order_status, ['pending', 'accepted']);
    }

    /**
     * assign ไรเดอร์ให้ order
     */
    public function assignRider(Rider $rider): void
    {
        $this->update([
            'rider_id' => $rider->id,
            'rider_assigned_at' => now(),
        ]);
    }

    /**
     * ตรวจสอบว่ามีไรเดอร์แล้วหรือยัง
     */
    public function isRiderAssigned(): bool
    {
        return $this->rider_id !== null;
    }

    /**
     * ป้ายสถานะภาษาไทย
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->order_status) {
            'pending' => 'รอการยืนยัน',
            'accepted' => 'รับออเดอร์แล้ว',
            'preparing' => 'กำลังจัดเตรียม',
            'ready' => 'พร้อมส่ง/พร้อมรับ',
            'delivering' => 'กำลังจัดส่ง',
            'delivered' => 'ส่งถึงแล้ว',
            'completed' => 'เสร็จสิ้น',
            'cancelled' => 'ยกเลิก',
            default => $this->order_status,
        };
    }
}
