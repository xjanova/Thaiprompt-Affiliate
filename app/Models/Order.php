<?php

namespace App\Models;

use App\Contracts\RiderDeliverable;
use App\Exceptions\ShopException;
use App\Services\Shop\CouponService;
use App\Services\Shop\ShopOrderNotifier;
use App\Support\Shop\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ออเดอร์ร้านค้า e-commerce
 *
 * 🛒 (2026-09-25) Workstream D:
 *   - ออเดอร์ที่สร้างจากแอปแยก 1 ออเดอร์ต่อ 1 ร้าน (store_id) — ออเดอร์เก่าจากเว็บอาจมีหลายผู้ขาย
 *   - ส่งด้วยไรเดอร์ได้ (delivery_method = rider) → implement RiderDeliverable
 *   - ยกเลิก: คืนเงินเฉพาะเมื่อจ่ายแล้วจริง (payment_status = paid) · คืนสต็อกเฉพาะเมื่อตัดไปแล้ว (stock_deducted_at)
 *   - แจ้งผู้ซื้อเมื่อสถานะเปลี่ยน / แจ้งผู้ขายเมื่อมีออเดอร์ที่ต้องทำ ผ่าน ShopOrderNotifier (ในแอป + push)
 */
class Order extends Model implements RiderDeliverable
{
    use SoftDeletes;

    /** สถานะที่ถือว่าจบแล้ว (ไม่ย้อนสถานะตามงานไรเดอร์/ไม่ยกเลิกซ้ำ) */
    public const TERMINAL_STATUSES = ['completed', 'cancelled', 'refunded'];

    public const DELIVERY_PARCEL = 'parcel';

    public const DELIVERY_RIDER = 'rider';

    /**
     * true = อย่าแจ้งผู้ซื้อตอนบันทึกครั้งนี้ (เช่น ระบบไรเดอร์แจ้งเองแล้ว / ผู้ซื้อกดเอง)
     * เป็น property ปกติ ไม่ใช่คอลัมน์
     */
    public bool $suppressStatusNotification = false;

    protected $fillable = [
        'order_number',
        'checkout_group',
        'user_id',
        'store_id',
        'shipping_address_id',
        'status',
        'subtotal',
        'discount_amount',
        'product_discount',
        'shipping_discount',
        'discount_funded_by',
        'shipping_fee',
        'delivery_method',
        'shipping_provider_id',
        'tax_amount',
        'total_amount',
        'platform_commission',
        'seller_earning',
        'payment_method',
        'payment_status',
        'paid_at',
        'stock_deducted_at',
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
        'refund_reason',
        'refunded_at',
        'refunded_by',
        'cancelled_at',
        'cashback_amount',
        'cashback_processed',
        'cashback_processed_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'product_discount' => 'decimal:2',
        'shipping_discount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'platform_commission' => 'decimal:2',
        'seller_earning' => 'decimal:2',
        'cashback_amount' => 'decimal:2',
        'cashback_processed' => 'boolean',
        'cashback_processed_at' => 'datetime',
        'paid_at' => 'datetime',
        'stock_deducted_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'estimated_delivery_at' => 'datetime',
        'last_message_at' => 'datetime',
        'has_unread_messages' => 'boolean',
        'shipping_address_snapshot' => 'array',
        'refunded_at' => 'datetime',
        'cancelled_at' => 'datetime',
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

        // 🔔 แจ้งผู้ซื้อ/ผู้ขายเมื่อสถานะเปลี่ยน (ครอบทุกจุดที่แก้สถานะผ่าน Eloquent: ร้าน, แอดมิน, ไรเดอร์, ระบบจ่ายเงิน)
        //    ห้ามให้การแจ้งเตือนทำให้การบันทึกออเดอร์ล้ม
        static::updated(function (Order $order) {
            try {
                app(ShopOrderNotifier::class)->orderUpdated($order);
            } catch (\Throwable $e) {
                Log::warning('Order status notification failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * 💳 normalize วิธีชำระเงินทุกครั้งที่เขียน (cash_on_delivery → cod, bank → bank_transfer, card → credit_card)
     * คอลัมน์เป็น enum — ค่าที่ไม่ normalize จะทำให้ insert พังใน strict mode
     */
    public function setPaymentMethodAttribute($value): void
    {
        $this->attributes['payment_method'] = is_string($value) ? PaymentMethod::normalize($value) : $value;
    }

    // =====================================================
    // Relations
    // =====================================================

    /**
     * Get the customer
     *
     * 🗑️ (2026-09-25) withTrashed: User ใช้ SoftDeletes แล้ว — ผู้ซื้อที่ลบบัญชี (ข้อมูลถูกปกปิดเป็น
     *    "ผู้ใช้ที่ลบบัญชี") ต้องยังโหลดได้ ไม่งั้นหน้าออเดอร์ที่เรียก $order->user->name พังทั้งหน้า
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    /**
     * ร้านค้าของออเดอร์ (ออเดอร์จากแอปมีร้านเดียวเสมอ)
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
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
     * ชื่อเรียกเดียวกับ shippingProviderRelation — หน้าจัดการการจัดส่งของร้าน/แอดมิน eager load 'shippingProvider'
     * (SELLER-03/SHOP-14: เดิมไม่มี relation นี้ → RelationNotFoundException ทั้งหน้า)
     */
    public function shippingProvider(): BelongsTo
    {
        return $this->shippingProviderRelation();
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
     * งานไรเดอร์ทั้งหมดของออเดอร์นี้ (rider_jobs.source_type/source_id)
     */
    public function riderJobs(): MorphMany
    {
        return $this->morphMany(RiderJob::class, 'source', 'source_type', 'source_id');
    }

    /**
     * งานไรเดอร์ล่าสุดที่ยังไม่จบ (null = ไม่มี)
     */
    public function activeRiderJob(): ?RiderJob
    {
        return RiderJob::forSource($this)->nonTerminal()->latest('id')->first();
    }

    // =====================================================
    // Scopes
    // =====================================================

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
     * Scope: ออเดอร์ที่มีสินค้าของผู้ขายคนนี้ (ตัวกรองสิทธิ์ฝั่งร้าน — กัน IDOR)
     */
    public function scopeForSeller($query, int $sellerId)
    {
        return $query->whereHas('items', fn ($q) => $q->where('seller_id', $sellerId));
    }

    // =====================================================
    // Labels / สถานะ
    // =====================================================

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
            'pending' => $this->isCod() ? 'รอร้านยืนยัน' : 'รอชำระเงิน',
            'paid' => 'ชำระเงินแล้ว รอร้านยืนยัน',
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
     * ชำระเงินแล้วหรือยัง
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * เป็นออเดอร์เก็บเงินปลายทางหรือไม่
     */
    public function isCod(): bool
    {
        return PaymentMethod::normalize($this->payment_method) === PaymentMethod::COD;
    }

    /**
     * ส่งด้วยไรเดอร์หรือไม่
     */
    public function isRiderDelivery(): bool
    {
        return ($this->delivery_method ?? self::DELIVERY_PARCEL) === self::DELIVERY_RIDER;
    }

    /**
     * ร้านเริ่มเตรียม/ส่งของได้หรือยัง: จ่ายแล้ว หรือเป็น COD ที่ยังไม่จบ
     */
    public function isReadyForFulfilment(): bool
    {
        if (in_array($this->status, self::TERMINAL_STATUSES, true)) {
            return false;
        }

        return $this->isPaid() || ($this->isCod() && $this->payment_status === 'pending');
    }

    /**
     * user_id ของผู้ขายทุกคนในออเดอร์
     *
     * @return array<int>
     */
    public function sellerIds(): array
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get(['id', 'seller_id']);

        return $items->pluck('seller_id')->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
    }

    /**
     * ออเดอร์เก่า (เว็บ) ที่มีสินค้าหลายผู้ขายในออเดอร์เดียว
     */
    public function isMultiSeller(): bool
    {
        return count($this->sellerIds()) > 1;
    }

    /**
     * มีไรเดอร์รับของไปแล้ว (ยกเลิกออเดอร์ไม่ได้ ต้องให้ไรเดอร์ส่ง/ส่งไม่สำเร็จก่อน)
     */
    public function hasRiderPastPickup(): bool
    {
        if (! $this->exists) {
            return false;
        }

        return RiderJob::forSource($this)
            ->whereIn('status', ['picked_up', 'delivering', 'delivered', 'completed'])
            ->exists();
    }

    // =====================================================
    // เปลี่ยนสถานะ
    // =====================================================

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
     *
     * ส่งถึงแล้ว = สินค้าทุกชิ้นที่ยังไม่ถูกยกเลิกเปลี่ยนเป็น delivered ด้วย
     * ⚠️ ไม่ตั้ง COD เป็น paid ที่นี่: COD ที่ร้าน/ขนส่งเก็บเงินเอง แพลตฟอร์มไม่ได้รับเงิน
     *    (ถ้าตั้ง paid ระบบแบ่งเงินจะจ่ายร้านซ้ำ) — COD ที่ไรเดอร์เก็บจะถูกตั้ง paid ใน onRiderCodSettled
     *    (หลังหักเงินไรเดอร์เข้าระบบสำเร็จเท่านั้น)
     */
    public function markAsDelivered(): void
    {
        $this->status = 'delivered';
        $this->delivered_at = $this->delivered_at ?? now();
        if (! $this->shipped_at) {
            $this->shipped_at = $this->delivered_at;
        }
        $this->save();

        $this->items()
            ->whereNotIn('status', ['cancelled', 'refunded', 'completed'])
            ->update(['status' => 'delivered']);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(): void
    {
        $this->status = 'completed';
        $this->save();

        $this->items()
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->update(['status' => 'completed']);
    }

    /**
     * ยกเลิกคำสั่งซื้อ
     *
     * 🐛 (2026-09-25) SHOP-06 / CC-05: เดิมคืนเงินตามสถานะ (paid/processing) และคืนสต็อกทุกครั้ง
     *    → ออเดอร์ที่ยังไม่จ่ายแต่สถานะ processing ถูกคืนเงินเข้า wallet (สร้างเงินจากอากาศ)
     *    → สร้างออเดอร์ค้างจ่ายแล้วยกเลิกวนไป สต็อกบวมไม่จำกัด
     *    ตอนนี้:
     *    - คืนเงิน (RefundService) เฉพาะ payment_status = paid และทำครั้งเดียว (ล็อกแถว + เช็คสถานะ)
     *    - คืนสต็อกเฉพาะเมื่อ stock_deducted_at ไม่ว่าง (ตัดไปแล้วจริง)
     *    - คืนสิทธิ์คูปอง + ยกเลิกงานไรเดอร์ที่ยังไม่รับของ
     *    - คืนเงินล้มเหลว → โยน ShopException (ไม่ยกเลิกเงียบๆ ทั้งที่ลูกค้ายังไม่ได้เงินคืน)
     *
     * @param  string|null  $reason  เหตุผลในการยกเลิก
     * @param  int|null  $adminId  Admin ID ถ้ายกเลิกโดย Admin
     * @param  string  $cancelledBy  buyer | seller | admin | system
     * @return array{cancelled: bool, refunded: bool, already: bool}
     *
     * @throws ShopException REFUND_FAILED เมื่อคืนเงินไม่สำเร็จ (ออเดอร์ไม่ถูกเปลี่ยน)
     */
    public function cancel(?string $reason = null, ?int $adminId = null, string $cancelledBy = 'buyer'): array
    {
        $reason = $reason !== null && trim($reason) !== '' ? trim($reason) : 'ยกเลิกโดยลูกค้า';
        $notifySellers = false;

        $result = DB::transaction(function () use ($reason, $adminId, $cancelledBy, &$notifySellers) {
            /** @var Order|null $order */
            $order = static::whereKey($this->getKey())->lockForUpdate()->first();

            if (! $order) {
                return ['cancelled' => false, 'refunded' => false, 'already' => false];
            }

            if (in_array($order->status, ['cancelled', 'refunded'], true)) {
                $this->setRawAttributes($order->getAttributes(), true);

                return ['cancelled' => true, 'refunded' => $order->status === 'refunded', 'already' => true];
            }

            // ผู้ซื้อยกเลิก: ตรวจซ้ำหลังล็อกแถว (กันชนกับร้านที่กดส่งของพร้อมกัน / ออเดอร์หลายร้านที่บางร้านส่งแล้ว)
            if ($cancelledBy === 'buyer' && ! $order->canBeCancelled()) {
                throw ShopException::make(
                    ShopException::ACTION_NOT_ALLOWED,
                    'ยกเลิกคำสั่งซื้อไม่ได้แล้ว เนื่องจากร้านจัดส่งสินค้าบางรายการไปแล้ว กรุณาติดต่อร้านหรือเจ้าหน้าที่',
                    409
                );
            }

            $wasPaid = $order->payment_status === 'paid';
            $notifySellers = $cancelledBy === 'buyer'
                && ($wasPaid || $order->isCod())
                && in_array($order->status, ['pending', 'paid', 'processing'], true);

            // 1) ยกเลิกงานไรเดอร์ที่ยังไม่จบ (รับของแล้ว → failed + แจ้งคืนของที่ร้าน)
            try {
                app(\App\Services\RiderDispatchService::class)->cancelJobsForSource($order, $cancelledBy, $reason);
            } catch (\Throwable $e) {
                Log::warning('Order cancel: cancel rider jobs failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // 2) คืนสต็อกเฉพาะที่ตัดไปแล้วจริง
            $order->restoreStockOnce();

            // 3) คืนสิทธิ์คูปองที่ใช้กับออเดอร์นี้
            app(CouponService::class)->releaseForOrder($order);

            // 4) คืนเงินเฉพาะออเดอร์ที่จ่ายแล้ว
            if ($wasPaid) {
                try {
                    app(\App\Services\RefundService::class)->processFullRefund($order, $adminId, $reason);
                } catch (\Throwable $e) {
                    Log::error('Order cancel refund failed', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);

                    throw ShopException::make(
                        ShopException::REFUND_FAILED,
                        'ยกเลิกคำสั่งซื้อไม่สำเร็จ เนื่องจากคืนเงินไม่ได้ กรุณาติดต่อเจ้าหน้าที่',
                        409
                    );
                }

                $order->refresh();
                $order->status = 'refunded';
                $order->payment_status = 'refunded';
                $order->refunded_at = $order->refunded_at ?? now();
            } else {
                $order->status = 'cancelled';

                // ปิดรายการรอชำระที่ค้างอยู่ — กันเงินโอนที่เข้ามาทีหลังไปจับคู่ แล้วเปิดออเดอร์ที่ยกเลิกแล้วเป็น "จ่ายแล้ว"
                PaymentTransaction::where('order_id', $order->id)
                    ->where('type', 'order_payment')
                    ->whereIn('status', ['pending', 'processing'])
                    ->update(['status' => 'cancelled', 'notes' => 'ออเดอร์ถูกยกเลิก: '.mb_substr($reason, 0, 200)]);
            }

            $order->cancellation_reason = $reason;
            $order->cancelled_at = now();
            // ผู้ซื้อกดยกเลิกเอง ไม่ต้องแจ้งตัวเองซ้ำ
            $order->suppressStatusNotification = $cancelledBy === 'buyer';
            $order->save();

            $order->items()
                ->whereNotIn('status', ['completed'])
                ->update(['status' => $wasPaid ? 'refunded' : 'cancelled']);

            OrderTrackingHistory::createEntry($order, $wasPaid ? 'refunded' : 'cancelled', $wasPaid ? 'ยกเลิกและคืนเงินแล้ว' : 'ยกเลิกคำสั่งซื้อแล้ว', [
                'description' => $reason,
                'created_by' => $adminId,
                'created_by_type' => match ($cancelledBy) {
                    'admin' => 'admin',
                    'seller' => 'seller',
                    'buyer' => 'customer',
                    default => 'system',
                },
            ]);

            $this->setRawAttributes($order->getAttributes(), true);

            return ['cancelled' => true, 'refunded' => $wasPaid, 'already' => false];
        });

        if ($notifySellers && ! $result['already']) {
            try {
                app(ShopOrderNotifier::class)->buyerCancelled($this);
            } catch (\Throwable $e) {
                Log::warning('Order cancel: notify sellers failed', ['order_id' => $this->id, 'error' => $e->getMessage()]);
            }
        }

        return $result;
    }

    /**
     * Get total items count
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * ผู้ซื้อยกเลิกได้หรือไม่: ยังไม่จัดส่ง และไม่มีไรเดอร์รับของไปแล้ว
     *
     * ออเดอร์หลายร้าน: ร้านที่กดส่งแล้วเปลี่ยนเฉพาะ order_items ของตัวเองเป็น shipped
     * (orders.status ยังเป็น processing จนกว่าทุกร้านจะส่ง) → ต้องดูสถานะรายการสินค้าด้วย
     * ไม่งั้นผู้ซื้อยกเลิกได้เงินคืนเต็มทั้งที่ร้านส่งของไปแล้ว
     */
    public function canBeCancelled(): bool
    {
        if (! in_array($this->status, ['pending', 'paid', 'processing'], true)) {
            return false;
        }

        if ($this->shipped_at !== null) {
            return false;
        }

        $shippedStatuses = ['shipped', 'delivered', 'completed'];
        $anyItemShipped = $this->relationLoaded('items')
            ? $this->items->contains(fn ($item) => in_array($item->status, $shippedStatuses, true))
            : ($this->exists && $this->items()->whereIn('status', $shippedStatuses)->exists());

        if ($anyItemShipped) {
            return false;
        }

        return ! $this->hasRiderPastPickup();
    }

    /**
     * Check if order can be refunded
     */
    public function canBeRefunded(): bool
    {
        return in_array($this->status, ['paid', 'processing', 'shipped']);
    }

    /**
     * ตรวจสอบว่าสามารถชำระเงินใหม่ได้หรือไม่
     * ใช้สำหรับคำสั่งซื้อที่ยังไม่ได้ชำระเงิน (pending)
     */
    public function canRetryPayment(): bool
    {
        return $this->status === 'pending' && $this->payment_status === 'pending';
    }

    // =====================================================
    // สต็อก (ตัด/คืน ครั้งเดียว)
    // =====================================================

    /**
     * ตัดสต็อกของสินค้าในออเดอร์ (ครั้งเดียวต่อออเดอร์ — บันทึก stock_deducted_at)
     *
     * @param  bool  $strict  true = สต็อกไม่พอให้โยน ShopException (ใช้ตอน checkout ก่อนรับเงิน)
     *                        false = ห้ามล้ม (ใช้หลังรับเงินแล้ว) → ตัดเหลือ 0 แล้ว log ว่าขายเกิน
     * @return bool true = ตัดครั้งนี้ / false = เคยตัดไปแล้วหรือไม่พบออเดอร์
     *
     * @throws ShopException OUT_OF_STOCK (เฉพาะ strict)
     */
    public function deductStockOnce(bool $strict = true): bool
    {
        return DB::transaction(function () use ($strict) {
            $fresh = static::whereKey($this->getKey())->lockForUpdate()->first();
            if (! $fresh) {
                return false;
            }

            if ($fresh->stock_deducted_at !== null) {
                $this->syncStockDeductedAt($fresh->stock_deducted_at);

                return false;
            }

            $quantities = OrderItem::where('order_id', $fresh->id)
                ->whereNotIn('status', ['cancelled', 'refunded'])
                ->get(['product_id', 'quantity', 'product_name'])
                ->groupBy('product_id')
                ->map(fn ($rows) => ['qty' => (int) $rows->sum('quantity'), 'name' => (string) $rows->first()->product_name]);

            $products = Product::withTrashed()
                ->whereIn('id', $quantities->keys()->all())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($quantities as $productId => $row) {
                $qty = max(0, (int) $row['qty']);
                $product = $products->get($productId);
                if (! $product || $qty === 0) {
                    continue;
                }

                if (! $product->track_inventory) {
                    Product::whereKey($productId)->update(['sales_count' => DB::raw('sales_count + '.$qty)]);

                    continue;
                }

                $affected = Product::whereKey($productId)
                    ->where('stock_quantity', '>=', $qty)
                    ->update([
                        'stock_quantity' => DB::raw('stock_quantity - '.$qty),
                        'sales_count' => DB::raw('sales_count + '.$qty),
                    ]);

                if ($affected === 0) {
                    if ($strict) {
                        throw ShopException::make(
                            ShopException::OUT_OF_STOCK,
                            "สินค้า '{$row['name']}' มีไม่พอ (เหลือ ".max(0, (int) $product->stock_quantity).' ชิ้น)',
                            409,
                            ['product_id' => (int) $productId, 'available' => max(0, (int) $product->stock_quantity)]
                        );
                    }

                    // รับเงินแล้ว ห้ามล้ม → ตัดเหลือ 0 และแจ้งใน log ให้ร้าน/แอดมินตามต่อ
                    Product::whereKey($productId)->update([
                        'stock_quantity' => DB::raw('GREATEST(stock_quantity - '.$qty.', 0)'),
                        'sales_count' => DB::raw('sales_count + '.$qty),
                    ]);
                    Log::warning('Order stock oversold after payment', [
                        'order_id' => $fresh->id,
                        'product_id' => $productId,
                        'ordered_qty' => $qty,
                        'stock_before' => (int) $product->stock_quantity,
                    ]);
                }

                Product::whereKey($productId)
                    ->where('stock_quantity', '<=', 0)
                    ->where('stock_status', 'in_stock')
                    ->update(['stock_status' => 'out_of_stock']);
            }

            $now = now();
            // update ระดับ query: ไม่ยิง model event (ไม่ต้องแจ้งเตือนใคร)
            static::whereKey($fresh->id)->update(['stock_deducted_at' => $now]);
            $this->syncStockDeductedAt($now);

            return true;
        });
    }

    /**
     * คืนสต็อก (เฉพาะออเดอร์ที่ตัดสต็อกไปแล้ว — ครั้งเดียว)
     *
     * @return bool true = คืนครั้งนี้
     */
    public function restoreStockOnce(): bool
    {
        return DB::transaction(function () {
            $fresh = static::whereKey($this->getKey())->lockForUpdate()->first();
            if (! $fresh || $fresh->stock_deducted_at === null) {
                return false;
            }

            $quantities = OrderItem::where('order_id', $fresh->id)
                ->get(['product_id', 'quantity'])
                ->groupBy('product_id')
                ->map(fn ($rows) => (int) $rows->sum('quantity'));

            $products = Product::withTrashed()
                ->whereIn('id', $quantities->keys()->all())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($quantities as $productId => $qty) {
                $qty = max(0, (int) $qty);
                $product = $products->get($productId);
                if (! $product || $qty === 0) {
                    continue;
                }

                $update = ['sales_count' => DB::raw('GREATEST(sales_count - '.$qty.', 0)')];
                if ($product->track_inventory) {
                    $update['stock_quantity'] = DB::raw('stock_quantity + '.$qty);
                }

                Product::whereKey($productId)->update($update);

                if ($product->track_inventory) {
                    Product::whereKey($productId)
                        ->where('stock_quantity', '>', 0)
                        ->where('stock_status', 'out_of_stock')
                        ->update(['stock_status' => 'in_stock']);
                }
            }

            static::whereKey($fresh->id)->update(['stock_deducted_at' => null]);
            $this->syncStockDeductedAt(null);

            return true;
        });
    }

    /**
     * ซิงค์ค่า stock_deducted_at ของ instance นี้โดยไม่ทำให้ model คิดว่ามีการแก้ (dirty)
     */
    private function syncStockDeductedAt($value): void
    {
        $this->stock_deducted_at = $value;
        $this->syncOriginalAttribute('stock_deducted_at');
    }

    // =====================================================
    // RiderDeliverable — ส่งด้วยไรเดอร์ของแพลตฟอร์ม
    // =====================================================

    /**
     * ร้านของออเดอร์: store_id → ร้านของผู้ขาย (ออเดอร์ที่มีผู้ขายคนเดียว)
     */
    public function resolveStore(): ?VendorStore
    {
        if ($this->store_id) {
            $store = $this->relationLoaded('store') ? $this->getRelation('store') : $this->store;
            if ($store instanceof VendorStore) {
                return $store;
            }
        }

        $sellerIds = $this->sellerIds();
        if (count($sellerIds) !== 1) {
            return null;
        }

        return VendorStore::where('user_id', $sellerIds[0])->orderBy('id')->first();
    }

    /**
     * จุดรับของ = จุดรับของที่ร้านตั้งไว้ (vendor_stores.pickup_*)
     */
    public function riderPickupPoint(): array
    {
        $store = $this->resolveStore();
        $notes = 'รับสินค้าออเดอร์ร้านค้า #'.$this->order_number;

        if (! $store) {
            return [
                'name' => 'ร้านค้า',
                'address' => '',
                'latitude' => 0.0,
                'longitude' => 0.0,
                'phone' => null,
                'notes' => $notes,
            ];
        }

        return $store->riderPickupPoint($notes);
    }

    /**
     * จุดส่งของ = ที่อยู่จัดส่ง ณ เวลาสั่ง (snapshot) — พิกัดจาก snapshot ก่อน แล้วค่อยที่อยู่ปัจจุบัน
     */
    public function riderDropoffPoint(): array
    {
        $snap = is_array($this->shipping_address_snapshot) ? $this->shipping_address_snapshot : [];
        $address = $this->shipping_address_id ? $this->shippingAddress : null;

        $lat = $snap['latitude'] ?? $address?->latitude;
        $lng = $snap['longitude'] ?? $address?->longitude;

        $fullAddress = $snap['full_address'] ?? trim(implode(' ', array_filter([
            $snap['address_line_1'] ?? ($snap['address'] ?? null),
            $snap['address_line_2'] ?? null,
            $snap['sub_district'] ?? ($snap['subdistrict'] ?? null),
            $snap['district'] ?? null,
            $snap['province'] ?? null,
            $snap['postal_code'] ?? null,
        ])));

        $area = trim(implode(' ', array_filter([
            $snap['district'] ?? null,
            $snap['province'] ?? null,
        ])));

        $notes = trim(implode(' · ', array_filter([
            $snap['notes'] ?? null,
            $this->customer_notes,
        ])));

        return [
            'name' => (string) ($snap['recipient_name'] ?? ($snap['name'] ?? ($this->user?->name ?? 'ลูกค้า'))),
            'address' => (string) $fullAddress,
            'latitude' => (float) ($lat ?? 0),
            'longitude' => (float) ($lng ?? 0),
            'phone' => $snap['phone_number'] ?? ($snap['phone'] ?? null),
            'notes' => $notes !== '' ? $notes : null,
            'area' => $area !== '' ? $area : null,
        ];
    }

    /**
     * เงินสดที่ไรเดอร์ต้องเก็บ: เฉพาะ COD ที่ยังไม่จ่าย (สินค้า + ค่าส่ง − ส่วนลด)
     */
    public function riderCodAmount(): float
    {
        if ($this->isCod() && $this->payment_status !== 'paid') {
            return round((float) $this->total_amount, 2);
        }

        return 0.0;
    }

    /**
     * ค่าส่งที่ลูกค้าจ่ายจริง → ใช้เป็นค่างานไรเดอร์ (RiderDispatchService::applyChargedFee)
     */
    public function riderDeliveryFeeCharged(): ?float
    {
        if (! $this->isRiderDelivery()) {
            return null;
        }

        $fee = round((float) $this->shipping_fee, 2);

        return $fee > 0 ? $fee : null;
    }

    /**
     * ผู้ซื้อของออเดอร์
     */
    public function riderCustomerUserId(): ?int
    {
        return $this->user_id ? (int) $this->user_id : null;
    }

    /**
     * สรุปสินค้าสั้นๆ ให้ไรเดอร์ เช่น "เสื้อยืด x2, หมวก x1"
     */
    public function riderItemsSummary(): string
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get(['product_name', 'quantity', 'status']);

        $parts = $items
            ->reject(fn ($item) => in_array($item->status, ['cancelled', 'refunded'], true))
            ->map(fn ($item) => trim((string) $item->product_name).' x'.(int) $item->quantity)
            ->filter()
            ->values()
            ->all();

        $summary = implode(', ', $parts);

        return $summary !== '' ? mb_substr($summary, 0, 500) : 'สินค้าจากร้านค้า';
    }

    /**
     * ผู้ซื้อ + ผู้ขายทุกคน (กันไรเดอร์รับงานของตัวเอง)
     */
    public function riderPartyUserIds(): array
    {
        return array_values(array_unique(array_filter(array_merge(
            [(int) $this->user_id],
            $this->sellerIds()
        ))));
    }

    /**
     * งานไรเดอร์เปลี่ยนสถานะ → อัปเดตสถานะออเดอร์ให้ตรง (อยู่ใน transaction ของ RiderJobService)
     *
     * - accepted → ออเดอร์ processing (ถ้ายังไม่ถึง)
     * - picked_up / delivering → shipped
     * - delivered / completed → delivered
     *   ⚠️ COD ยังไม่ตั้ง paid ที่นี่ — ส่งถึง ≠ แพลตฟอร์มได้เงิน (ไรเดอร์ถือเงินสดอยู่)
     *      จะตั้ง paid ใน onRiderCodSettled() หลัง RiderEarningService หักวอลเลตไรเดอร์สำเร็จ
     *      (กันร้านได้เงิน/ผู้ซื้อได้ cashback จากเงินที่ไรเดอร์ยังไม่นำส่ง)
     * - failed → กลับเป็น processing ให้ร้านเรียกไรเดอร์ใหม่หรือยกเลิก
     *
     * idempotent: เรียกซ้ำด้วยสถานะเดิมไม่เปลี่ยนอะไร · ไม่แจ้งผู้ซื้อซ้ำ (ระบบไรเดอร์แจ้งเองแล้ว)
     */
    public function onRiderJobStatusChanged(RiderJob $job, string $fromStatus): void
    {
        /** @var Order|null $order */
        $order = static::whereKey($this->getKey())->lockForUpdate()->first();

        if (! $order) {
            return;
        }

        if (in_array($order->status, self::TERMINAL_STATUSES, true)) {
            $this->setRawAttributes($order->getAttributes(), true);

            return;
        }

        $order->suppressStatusNotification = true;
        $now = now();
        $history = null;

        switch ($job->status) {
            case 'accepted':
                if (in_array($order->status, ['pending', 'paid'], true)) {
                    $order->status = 'processing';
                }
                $history = ['rider_assigned', 'ไรเดอร์รับงานแล้ว', 'กำลังเดินทางไปรับสินค้าที่ร้าน'];
                break;

            case 'picked_up':
            case 'delivering':
                if (! in_array($order->status, ['shipped', 'delivered'], true)) {
                    $order->status = 'shipped';
                    $order->shipped_at = $order->shipped_at ?? $now;
                    $order->shipping_provider = $order->shipping_provider ?: 'ไรเดอร์ Thaiprompt';
                    $history = ['shipped', 'ไรเดอร์รับสินค้าแล้ว', 'กำลังนำส่งถึงผู้รับ'];
                }
                break;

            case 'delivered':
            case 'completed':
                if ($order->status !== 'delivered') {
                    $order->status = 'delivered';
                    $order->delivered_at = $order->delivered_at ?? $now;
                    $order->shipped_at = $order->shipped_at ?? $now;
                    $history = ['delivered', 'ไรเดอร์ส่งสินค้าถึงแล้ว', null];
                }
                // COD: รอ RiderEarningService::settle หักเงินไรเดอร์ก่อน → onRiderCodSettled() ตั้ง paid
                break;

            case 'failed':
                if ($order->status === 'shipped') {
                    $order->status = 'processing';
                }
                $reason = RiderJob::FAILURE_REASONS[$job->failure_reason ?? ''] ?? ($job->failure_reason ?: null);
                $history = ['delivery_failed', 'ไรเดอร์ส่งสินค้าไม่สำเร็จ', $reason];
                break;

            case 'cancelled':
                $history = ['rider_cancelled', 'งานไรเดอร์ถูกยกเลิก', null];
                break;

            default:
                break;
        }

        if ($order->isDirty()) {
            $order->save();
        }

        // สถานะสินค้าในออเดอร์ให้ตรงกับออเดอร์
        if (in_array($order->status, ['shipped', 'delivered'], true)) {
            $order->items()
                ->whereNotIn('status', ['cancelled', 'refunded', 'completed', $order->status])
                ->when($order->status === 'shipped', fn ($q) => $q->where('status', '!=', 'delivered'))
                ->update(['status' => $order->status]);
        } elseif ($order->status === 'processing') {
            $order->items()->where('status', 'pending')->update(['status' => 'processing']);
        }

        if ($history !== null) {
            OrderTrackingHistory::createEntry($order, $history[0], $history[1], [
                'description' => $history[2],
                'created_by_type' => 'system',
                'meta_data' => ['rider_job_id' => $job->id, 'rider_job_status' => $job->status, 'from' => $fromStatus],
            ]);
        }

        $this->setRawAttributes($order->getAttributes(), true);
    }

    /**
     * hook เสริมของ RiderDispatchService: ออเดอร์นี้ยังเรียกไรเดอร์ได้หรือไม่ (ตรวจซ้ำหลังล็อกแถวออเดอร์)
     *
     * ต้องส่งด้วยไรเดอร์ + ยังไม่จบ/ยังไม่ส่งถึง + จ่ายแล้ว หรือเป็น COD ที่ยังรอเก็บเงิน
     * → กันแอดมิน/ร้านสร้างงานให้ออเดอร์ที่ยกเลิกหรือคืนเงินไปแล้ว (ไรเดอร์ไปส่งของ + เก็บเงินสดของออเดอร์ที่ยกเลิก)
     */
    public function riderCanDispatch(): bool
    {
        if (! $this->isRiderDelivery()) {
            return false;
        }

        if (in_array($this->status, array_merge(self::TERMINAL_STATUSES, ['delivered']), true)) {
            return false;
        }

        return $this->isReadyForFulfilment();
    }

    /**
     * hook เสริมของ RiderEarningService: หักเงิน COD จากวอลเลตไรเดอร์เข้าระบบสำเร็จแล้ว (rider_jobs.cod_settled_at)
     *
     * อยู่ใน transaction เดียวกับการเคลียร์เงินไรเดอร์ → ตั้งออเดอร์เป็นจ่ายแล้ว
     * แล้ว OrderObserver จึงแบ่งเงินร้าน (escrow) + จ่าย cashback จากเงินที่เข้าระบบจริงเท่านั้น
     * idempotent: จ่ายแล้วไม่ทำซ้ำ
     */
    public function onRiderCodSettled(RiderJob $job): void
    {
        /** @var Order|null $order */
        $order = static::whereKey($this->getKey())->lockForUpdate()->first();

        if (! $order || ! $order->isCod() || $order->payment_status === 'paid') {
            if ($order) {
                $this->setRawAttributes($order->getAttributes(), true);
            }

            return;
        }

        // ออเดอร์ถูกยกเลิก/คืนเงินไปก่อนไรเดอร์นำส่งเงิน → ไม่เปิดเป็นจ่ายแล้ว ให้แอดมินคืนเงินสดผู้ซื้อเอง
        if (in_array($order->status, ['cancelled', 'refunded'], true)) {
            Log::error('Order: COD settled for a cancelled order — admin must refund the buyer manually', [
                'order_id' => $order->id,
                'rider_job_id' => $job->id,
                'cod_amount' => (float) $job->cod_amount,
            ]);
            $this->setRawAttributes($order->getAttributes(), true);

            return;
        }

        $order->suppressStatusNotification = true;
        $order->forceFill([
            'payment_status' => 'paid',
            'paid_at' => $order->paid_at ?? now(),
            'payment_reference' => $order->payment_reference ?: 'RIDER-COD-'.$job->id,
        ])->save();

        OrderTrackingHistory::createEntry($order, 'paid', 'ได้รับเงินเก็บปลายทางแล้ว', [
            'description' => 'ไรเดอร์นำส่งเงินเก็บปลายทางเข้าระบบเรียบร้อย',
            'created_by_type' => 'system',
            'meta_data' => ['rider_job_id' => $job->id, 'cod_amount' => round((float) $job->cod_amount, 2)],
        ]);

        $this->setRawAttributes($order->getAttributes(), true);
    }
}
