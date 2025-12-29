<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number',
        'user_id',
        'shipping_address_id',
        'status',
        'subtotal',
        'discount_amount',
        'shipping_fee',
        'shipping_provider_id',
        'tax_amount',
        'total_amount',
        'platform_commission',
        'seller_earning',
        'payment_method',
        'payment_status',
        'paid_at',
        'payment_reference',
        'shipping_provider',
        'tracking_number',
        'shipped_at',
        'delivered_at',
        'estimated_delivery_at',
        'shipping_address_snapshot',
        'customer_notes',
        'admin_notes',
        'has_unread_messages',
        'last_message_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'platform_commission' => 'decimal:2',
        'seller_earning' => 'decimal:2',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'estimated_delivery_at' => 'datetime',
        'last_message_at' => 'datetime',
        'has_unread_messages' => 'boolean',
        'shipping_address_snapshot' => 'array',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-'.date('Ymd').'-'.strtoupper(\Illuminate\Support\Str::random(6));
            }
        });
    }

    /**
     * Get the customer
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the shipping address
     */
    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(ShippingAddress::class, 'shipping_address_id');
    }

    /**
     * Get all order items
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /**
     * Get shipping provider relation
     */
    public function shippingProviderRelation(): BelongsTo
    {
        return $this->belongsTo(ShippingProvider::class, 'shipping_provider_id');
    }

    /**
     * Get tracking history
     */
    public function trackingHistory(): HasMany
    {
        return $this->hasMany(OrderTrackingHistory::class, 'order_id')
            ->orderBy('tracked_at', 'desc');
    }

    /**
     * Get messages (chat)
     */
    public function messages(): HasMany
    {
        return $this->hasMany(OrderMessage::class, 'order_id')
            ->orderBy('created_at', 'asc');
    }

    /**
     * Get unread messages count
     */
    public function unreadMessages(): HasMany
    {
        return $this->hasMany(OrderMessage::class, 'order_id')
            ->where('is_read', false);
    }

    /**
     * Get the payment transaction
     */
    public function paymentTransaction(): HasOne
    {
        return $this->hasOne(PaymentTransaction::class, 'order_id')
            ->where('type', 'order_payment')
            ->latest();
    }

    /**
     * Scope: Pending orders
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Paid orders
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Scope: Processing orders
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope: Shipped orders
     */
    public function scopeShipped($query)
    {
        return $query->where('status', 'shipped');
    }

    /**
     * Scope: Completed orders
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Cancelled orders
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'paid' => 'info',
            'processing' => 'primary',
            'shipped' => 'info',
            'delivered' => 'success',
            'completed' => 'success',
            'cancelled' => 'danger',
            'refunded' => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Get status label in Thai
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'รอชำระเงิน',
            'paid' => 'ชำระเงินแล้ว',
            'processing' => 'กำลังเตรียมสินค้า',
            'shipped' => 'จัดส่งแล้ว',
            'delivered' => 'ส่งถึงแล้ว',
            'completed' => 'สำเร็จ',
            'cancelled' => 'ยกเลิก',
            'refunded' => 'คืนเงิน',
            default => 'ไม่ทราบสถานะ',
        };
    }

    /**
     * Mark as paid
     */
    public function markAsPaid(?string $paymentReference = null): void
    {
        $this->payment_status = 'paid';
        $this->status = 'paid';
        $this->paid_at = now();
        if ($paymentReference) {
            $this->payment_reference = $paymentReference;
        }
        $this->save();
    }

    /**
     * Mark as processing
     */
    public function markAsProcessing(): void
    {
        $this->status = 'processing';
        $this->save();
    }

    /**
     * Mark as shipped with tracking info
     */
    public function markAsShipped(
        ?string $trackingNumber = null,
        ?string $shippingProvider = null,
        ?int $shippingProviderId = null
    ): void {
        $this->status = 'shipped';
        $this->shipped_at = now();

        if ($trackingNumber) {
            $this->tracking_number = $trackingNumber;
        }
        if ($shippingProvider) {
            $this->shipping_provider = $shippingProvider;
        }
        if ($shippingProviderId) {
            $this->shipping_provider_id = $shippingProviderId;
        }

        $this->save();

        // บันทึกประวัติการติดตาม
        OrderTrackingHistory::createEntry($this, 'shipped', 'จัดส่งแล้ว', [
            'description' => "หมายเลขพัสดุ: {$trackingNumber}",
            'tracking_number' => $trackingNumber,
            'shipping_provider' => $shippingProvider,
            'created_by_type' => 'seller',
        ]);
    }

    /**
     * อัพเดทข้อมูลการติดตาม
     */
    public function updateTracking(array $data): void
    {
        if (isset($data['tracking_number'])) {
            $this->tracking_number = $data['tracking_number'];
        }
        if (isset($data['shipping_provider'])) {
            $this->shipping_provider = $data['shipping_provider'];
        }
        if (isset($data['shipping_provider_id'])) {
            $this->shipping_provider_id = $data['shipping_provider_id'];
        }
        if (isset($data['estimated_delivery_at'])) {
            $this->estimated_delivery_at = $data['estimated_delivery_at'];
        }
        $this->save();

        // บันทึกประวัติ
        if (isset($data['status']) && isset($data['title'])) {
            OrderTrackingHistory::createEntry($this, $data['status'], $data['title'], $data);
        }
    }

    /**
     * Get tracking URL
     */
    public function getTrackingUrlAttribute(): ?string
    {
        if (! $this->tracking_number) {
            return null;
        }

        if ($this->shippingProviderRelation) {
            return $this->shippingProviderRelation->getTrackingLink($this->tracking_number);
        }

        // Default tracking URLs by provider name
        $trackingUrls = [
            'thaipost' => "https://track.thailandpost.co.th/?trackNumber={$this->tracking_number}",
            'kerry' => "https://th.kerryexpress.com/th/track/?track={$this->tracking_number}",
            'flash' => "https://www.flashexpress.co.th/tracking/?se={$this->tracking_number}",
            'j&t' => "https://www.jtexpress.co.th/service/track?bills={$this->tracking_number}",
            'ninja' => "https://www.ninjavan.co/th-th/tracking?id={$this->tracking_number}",
            'scg' => "https://www.scgexpress.co.th/tracking/?refNo={$this->tracking_number}",
            'best' => "https://www.best-inc.co.th/track?bills={$this->tracking_number}",
        ];

        $provider = strtolower($this->shipping_provider ?? '');

        return $trackingUrls[$provider] ?? null;
    }

    /**
     * Mark as delivered
     */
    public function markAsDelivered(): void
    {
        $this->status = 'delivered';
        $this->delivered_at = now();
        $this->save();
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(): void
    {
        $this->status = 'completed';
        $this->save();
    }

    /**
     * Cancel order
     */
    public function cancel(?string $reason = null): void
    {
        $this->status = 'cancelled';
        $this->cancellation_reason = $reason;
        $this->save();

        // Restore stock for all items
        foreach ($this->items as $item) {
            $item->product->increaseStock($item->quantity);
        }
    }

    /**
     * Get total items count
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'paid', 'processing']);
    }

    /**
     * Check if order can be refunded
     */
    public function canBeRefunded(): bool
    {
        return in_array($this->status, ['paid', 'processing', 'shipped']);
    }
}
