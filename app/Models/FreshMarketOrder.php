<?php

namespace App\Models;

use App\Contracts\RiderDeliverable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FreshMarketOrder - คำสั่งซื้อในตลาดสด
 *
 * วงจรชีวิต (order_status):
 *   pending → accepted → preparing → ready → (ไรเดอร์: delivering) → delivered → completed
 *   ออกนอกเส้นทางได้: cancelled, delivery_failed
 *
 * ตารางการเปลี่ยนสถานะอยู่ที่ ACTIONS ที่เดียว — เว็บ, API, LINE, แอดมิน, ตัวกวาดอัตโนมัติ ใช้ชุดเดียวกัน
 * การเปลี่ยนสถานะจริง + เงินทุกบาท ต้องผ่าน FreshMarketService เท่านั้น (lock + idempotent)
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
 * @property float|null $gp_rate
 * @property float $seller_earning
 * @property float $refunded_amount
 * @property string $delivery_type pickup|rider
 * @property float $delivery_fee
 * @property float|null $delivery_distance_km
 * @property string $payment_method wallet|cod (ข้อมูลเก่าอาจเป็น escrow/transfer)
 * @property string $payment_status pending|paid|refunded
 * @property \Carbon\Carbon|null $paid_at
 * @property string $order_status
 * @property string|null $escrow_status held|released|refunded
 * @property float|null $buyer_latitude
 * @property float|null $buyer_longitude
 * @property string|null $delivery_address
 * @property string|null $delivery_notes
 * @property int|null $rider_job_id
 * @property int|null $rider_id
 * @property \Carbon\Carbon|null $accepted_at
 * @property \Carbon\Carbon|null $delivered_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon|null $cancelled_at
 * @property \Carbon\Carbon|null $buyer_confirmed_at
 * @property int|null $buyer_rating
 * @property string|null $buyer_review
 * @property float $cashback_amount
 * @property bool $cashback_processed
 * @property bool $mlm_commission_processed
 * @property string|null $cancel_reason
 * @property string|null $cancelled_by buyer|seller|admin|system
 * @property array|null $status_history
 */
class FreshMarketOrder extends Model implements RiderDeliverable
{
    use SoftDeletes;

    protected $table = 'fresh_market_orders';

    // ===== สถานะออเดอร์ =====
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_PREPARING = 'preparing';

    public const STATUS_READY = 'ready';

    public const STATUS_DELIVERING = 'delivering';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_DELIVERY_FAILED = 'delivery_failed';

    /** สถานะทั้งหมด (ใช้กรองรายการ) */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACCEPTED,
        self::STATUS_PREPARING,
        self::STATUS_READY,
        self::STATUS_DELIVERING,
        self::STATUS_DELIVERED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
        self::STATUS_DELIVERY_FAILED,
    ];

    /** สถานะสิ้นสุด — ไม่เปลี่ยนต่อแล้ว */
    public const TERMINAL_STATUSES = [self::STATUS_COMPLETED, self::STATUS_CANCELLED];

    /** ชื่อสถานะเก่าที่หน้าเว็บ/แอปเคยส่งมา → สถานะจริง */
    public const LEGACY_STATUS_ALIASES = [
        'confirmed' => self::STATUS_ACCEPTED,
        'ready_for_pickup' => self::STATUS_READY,
        'shipping' => self::STATUS_DELIVERING,
    ];

    /**
     * ตารางการเปลี่ยนสถานะ (ที่เดียวของระบบ)
     *
     * action => [
     *   'to'   => สถานะปลายทาง,
     *   'from' => [role => [สถานะต้นทางที่อนุญาต]],
     *   'delivery_types' => (optional) ใช้ได้เฉพาะประเภทจัดส่งเหล่านี้,
     *   'label' => ชื่อการกระทำภาษาไทย,
     * ]
     *
     * role: buyer | seller | admin | system
     */
    public const ACTIONS = [
        'accept' => [
            'to' => self::STATUS_ACCEPTED,
            'from' => ['seller' => [self::STATUS_PENDING], 'admin' => [self::STATUS_PENDING]],
            'label' => 'รับออเดอร์',
        ],
        'prepare' => [
            'to' => self::STATUS_PREPARING,
            'from' => ['seller' => [self::STATUS_ACCEPTED], 'admin' => [self::STATUS_ACCEPTED]],
            'label' => 'เริ่มเตรียมสินค้า',
        ],
        'ready' => [
            'to' => self::STATUS_READY,
            'from' => [
                'seller' => [self::STATUS_ACCEPTED, self::STATUS_PREPARING],
                'admin' => [self::STATUS_ACCEPTED, self::STATUS_PREPARING],
            ],
            'label' => 'สินค้าพร้อมส่ง/พร้อมรับ',
        ],
        // ส่งมอบสินค้าให้ผู้ซื้อที่มารับเอง (เฉพาะนัดรับ — แบบไรเดอร์ สถานะมาจากงานไรเดอร์)
        'handover' => [
            'to' => self::STATUS_DELIVERED,
            'from' => ['seller' => [self::STATUS_READY], 'admin' => [self::STATUS_READY]],
            'delivery_types' => ['pickup'],
            'label' => 'ส่งมอบสินค้าแล้ว',
        ],
        // ผู้ซื้อยืนยันรับของ → ปิดออเดอร์ + ปล่อยเงิน (นัดรับที่จ่ายผ่าน wallet ยืนยันได้ตั้งแต่ ready)
        // นัดรับแบบ COD: ร้านต้องกด "ส่งมอบสินค้าแล้ว" (= ได้รับเงินสดแล้ว) ก่อน ผู้ซื้อจึงยืนยันได้
        // ไม่งั้นผู้ซื้อปิดออเดอร์เองได้ทั้งที่ไม่ได้มารับ/ไม่ได้จ่าย → ร้านโดนหัก GP + ผู้ซื้อได้ cashback ฟรี
        'confirm' => [
            'to' => self::STATUS_COMPLETED,
            'from' => ['buyer' => [self::STATUS_DELIVERED, self::STATUS_READY]],
            'ready_only_for' => ['pickup'],
            'ready_prepaid_only' => true,
            'label' => 'ยืนยันรับสินค้า',
        ],
        // ปิดออเดอร์โดยแอดมิน/ระบบ (ผู้ซื้อไม่กดยืนยันเกินเวลา)
        'complete' => [
            'to' => self::STATUS_COMPLETED,
            'from' => [
                'admin' => [self::STATUS_DELIVERED, self::STATUS_READY],
                'system' => [self::STATUS_DELIVERED],
            ],
            'ready_only_for' => ['pickup'],
            'label' => 'ปิดออเดอร์',
        ],
        'cancel' => [
            'to' => self::STATUS_CANCELLED,
            'from' => [
                'buyer' => [self::STATUS_PENDING],
                'seller' => [self::STATUS_PENDING, self::STATUS_ACCEPTED, self::STATUS_PREPARING, self::STATUS_READY],
                'system' => [self::STATUS_PENDING],
                'admin' => [
                    self::STATUS_PENDING, self::STATUS_ACCEPTED, self::STATUS_PREPARING, self::STATUS_READY,
                    self::STATUS_DELIVERED, self::STATUS_DELIVERY_FAILED,
                ],
            ],
            'label' => 'ยกเลิกออเดอร์',
        ],
    ];

    protected $fillable = [
        'order_number',
        'buyer_id',
        'seller_id',
        'listing_id',
        'quantity',
        'unit_price',
        'total_amount',
        'platform_fee',
        'gp_rate',
        'seller_earning',
        'refunded_amount',
        'delivery_type',
        'delivery_fee',
        'payment_method',
        'payment_status',
        'paid_at',
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
        'status_history',
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
        'gp_rate' => 'decimal:2',
        'seller_earning' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'buyer_latitude' => 'decimal:8',
        'buyer_longitude' => 'decimal:8',
        'cashback_amount' => 'decimal:2',
        'cashback_processed' => 'boolean',
        'mlm_commission_processed' => 'boolean',
        'quantity' => 'integer',
        'buyer_rating' => 'integer',
        'seller_rating' => 'integer',
        'status_history' => 'array',
        'paid_at' => 'datetime',
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
                $order->order_number = 'TSD'.now()->format('ymd')
                    .strtoupper(\Illuminate\Support\Str::random(5));
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
     * สินค้า (รวมที่ถูกลบแบบ soft delete — ออเดอร์เก่ายังต้องแสดงชื่อสินค้าได้)
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(FreshMarketListing::class, 'listing_id')->withTrashed();
    }

    /**
     * งาน Rider ล่าสุดของออเดอร์นี้
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
        return $query->whereNotIn('order_status', self::TERMINAL_STATUSES);
    }

    public function scopeCompleted($query)
    {
        return $query->where('order_status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('order_status', self::STATUS_PENDING);
    }

    public function scopeWithBuyerReview($query)
    {
        return $query->whereNotNull('buyer_rating');
    }

    /**
     * กรองตามสถานะ (รับชื่อสถานะเก่าที่หน้าเว็บเคยใช้ด้วย) — สถานะที่ไม่รู้จักจะถูกข้าม
     */
    public function scopeStatusFilter($query, ?string $status)
    {
        $normalized = self::normalizeStatus($status);

        return $normalized ? $query->where('order_status', $normalized) : $query;
    }

    // ===== State machine =====

    /**
     * แปลงชื่อสถานะจาก request ให้เป็นสถานะจริง (null = ไม่กรอง)
     */
    public static function normalizeStatus(?string $status): ?string
    {
        if ($status === null || $status === '' || $status === 'all') {
            return null;
        }

        $status = self::LEGACY_STATUS_ALIASES[$status] ?? $status;

        return in_array($status, self::STATUSES, true) ? $status : null;
    }

    /**
     * สถานะสิ้นสุดแล้วหรือยัง
     */
    public function isTerminal(): bool
    {
        return in_array($this->order_status, self::TERMINAL_STATUSES, true);
    }

    /**
     * role นี้ทำ action นี้กับออเดอร์ในสถานะปัจจุบันได้หรือไม่
     *
     * @param  string  $action  accept|prepare|ready|handover|confirm|complete|cancel
     * @param  string  $role  buyer|seller|admin|system
     */
    public function canTransition(string $action, string $role): bool
    {
        $rule = self::ACTIONS[$action] ?? null;

        if (! $rule) {
            return false;
        }

        $allowedFrom = $rule['from'][$role] ?? [];

        if (! in_array($this->order_status, $allowedFrom, true)) {
            return false;
        }

        // action ที่ใช้ได้เฉพาะบางประเภทจัดส่ง
        if (isset($rule['delivery_types']) && ! in_array($this->delivery_type, $rule['delivery_types'], true)) {
            return false;
        }

        // ยืนยัน/ปิดจากสถานะ ready ได้เฉพาะแบบนัดรับ (แบบไรเดอร์ต้องรอส่งถึงก่อน)
        if ($this->order_status === self::STATUS_READY && isset($rule['ready_only_for'])
            && ! in_array($this->delivery_type, $rule['ready_only_for'], true)) {
            return false;
        }

        // ข้ามขั้นส่งมอบได้เฉพาะออเดอร์ที่จ่ายเข้าระบบแล้ว (COD ต้องให้ร้านยืนยันรับเงินผ่าน handover ก่อน)
        if ($this->order_status === self::STATUS_READY && ! empty($rule['ready_prepaid_only'])
            && $this->payment_method === 'cod') {
            return false;
        }

        return true;
    }

    /**
     * รายการ action ที่ role นี้ทำได้ตอนนี้ (ใช้แสดงปุ่มบนเว็บ/แอป)
     *
     * @return array<int, string>
     */
    public function allowedActions(string $role): array
    {
        $actions = [];

        foreach (array_keys(self::ACTIONS) as $action) {
            if ($this->canTransition($action, $role)) {
                $actions[] = $action;
            }
        }

        return $actions;
    }

    /**
     * ชื่อ action ภาษาไทย
     */
    public static function actionLabel(string $action): string
    {
        return self::ACTIONS[$action]['label'] ?? $action;
    }

    /**
     * เพิ่มประวัติสถานะ (ยังไม่ save — ผู้เรียก save ใน transaction ที่ lock แถวไว้แล้ว)
     *
     * @param  array{action?: string, from?: ?string, to?: ?string, by?: string, user_id?: ?int, reason?: ?string, meta?: array}  $entry
     */
    public function appendHistory(array $entry): void
    {
        $history = $this->status_history ?? [];
        $history[] = array_merge([
            'at' => now()->toIso8601String(),
        ], array_filter($entry, fn ($v) => $v !== null && $v !== []));

        // เก็บแค่ 100 รายการล่าสุด กันคอลัมน์บวม
        $this->status_history = array_slice($history, -100);
    }

    /**
     * ตรวจสอบว่ารีวิวได้หรือยัง (ส่งถึง/เสร็จสิ้น + ยังไม่เคยรีวิว)
     */
    public function canBeReviewed(): bool
    {
        return in_array($this->order_status, [self::STATUS_DELIVERED, self::STATUS_COMPLETED], true)
            && is_null($this->getRawOriginal('buyer_rating'));
    }

    /**
     * ตรวจสอบว่ายกเลิกได้หรือไม่ (ค่าเริ่มต้นมองจากฝั่งผู้ซื้อ)
     */
    public function canBeCancelled(string $role = 'buyer'): bool
    {
        return $this->canTransition('cancel', $role);
    }

    /**
     * ตรวจสอบว่ามีไรเดอร์แล้วหรือยัง
     */
    public function isRiderAssigned(): bool
    {
        return $this->rider_id !== null;
    }

    /**
     * ชื่อเดิมที่หน้าเว็บเก่าเรียก ($order->status) → order_status (อ่านอย่างเดียว ไม่มีคอลัมน์จริง)
     */
    public function getStatusAttribute(): ?string
    {
        return $this->order_status;
    }

    /**
     * ชื่อเดิมที่หน้ารายงานเก่าเรียก ($order->seller_amount) → seller_earning
     */
    public function getSellerAmountAttribute(): float
    {
        return (float) $this->seller_earning;
    }

    /**
     * ยอดรวมที่ผู้ซื้อต้องจ่าย (สินค้า + ค่าส่ง)
     */
    public function getGrandTotalAttribute(): float
    {
        return round((float) $this->total_amount + (float) $this->delivery_fee, 2);
    }

    /**
     * ป้ายสถานะภาษาไทย
     */
    public function getStatusLabelAttribute(): string
    {
        return self::statusLabel($this->order_status);
    }

    /**
     * ป้ายสถานะภาษาไทยจากชื่อสถานะ
     */
    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'รอร้านยืนยัน',
            self::STATUS_ACCEPTED => 'ร้านรับออเดอร์แล้ว',
            self::STATUS_PREPARING => 'กำลังจัดเตรียม',
            self::STATUS_READY => 'พร้อมส่ง/พร้อมรับ',
            self::STATUS_DELIVERING => 'กำลังจัดส่ง',
            self::STATUS_DELIVERED => 'ส่งถึงแล้ว',
            self::STATUS_COMPLETED => 'เสร็จสิ้น',
            self::STATUS_CANCELLED => 'ยกเลิกแล้ว',
            self::STATUS_DELIVERY_FAILED => 'จัดส่งไม่สำเร็จ',
            default => (string) $status,
        };
    }

    /**
     * ป้ายวิธีชำระเงินภาษาไทย
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'wallet', 'escrow' => 'Wallet (ระบบถือเงินไว้จนได้รับของ)',
            'cod' => 'เก็บเงินปลายทาง',
            'transfer' => 'โอนเงิน',
            default => (string) $this->payment_method,
        };
    }

    /**
     * ป้ายสถานะการชำระเงินภาษาไทย
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            'pending' => $this->payment_method === 'cod' ? 'ชำระตอนรับสินค้า' : 'รอชำระ',
            'paid' => 'ชำระแล้ว',
            'released' => 'โอนเงินให้ร้านแล้ว',
            'refunded' => 'คืนเงินแล้ว',
            default => (string) $this->payment_status,
        };
    }

    /**
     * ข้อมูลออเดอร์สำหรับ API (ตัว serializer เดียวของแอป)
     *
     * @param  string  $viewerRole  buyer|seller|admin — ซ่อนข้อมูลที่อีกฝั่งไม่ควรเห็น
     */
    public function toApiArray(string $viewerRole = 'buyer'): array
    {
        $this->loadMissing(['listing', 'seller', 'buyer', 'riderJob.rider']);

        $listing = $this->listing;
        $seller = $this->seller;
        $buyer = $this->buyer;
        $job = $this->riderJob;
        $contactVisible = ! in_array($this->order_status, [self::STATUS_PENDING, self::STATUS_CANCELLED], true);

        $data = [
            'id' => (int) $this->id,
            'order_number' => $this->order_number,
            'order_status' => $this->order_status,
            'status_label' => $this->status_label,
            'payment_method' => $this->payment_method,
            'payment_method_label' => $this->payment_method_label,
            'payment_status' => $this->payment_status,
            'payment_status_label' => $this->payment_status_label,
            'escrow_status' => $this->escrow_status,
            'delivery_type' => $this->delivery_type,
            'quantity' => (int) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'total_amount' => (float) $this->total_amount,
            'delivery_fee' => (float) $this->delivery_fee,
            'grand_total' => $this->grand_total,
            'delivery_distance_km' => $this->delivery_distance_km !== null ? (float) $this->delivery_distance_km : null,
            'cashback_amount' => (float) $this->cashback_amount,
            'refunded_amount' => (float) ($this->refunded_amount ?? 0),
            'delivery_address' => $this->delivery_address,
            'delivery_notes' => $this->delivery_notes,
            'cancel_reason' => $this->cancel_reason,
            'cancelled_by' => $this->cancelled_by,
            'buyer_rating' => $this->buyer_rating !== null ? (int) $this->buyer_rating : null,
            'buyer_review' => $this->buyer_review,
            'can_review' => $viewerRole === 'buyer' && $this->canBeReviewed(),
            'allowed_actions' => in_array($viewerRole, ['buyer', 'seller', 'admin'], true)
                ? $this->allowedActions($viewerRole) : [],
            'listing' => $listing ? [
                'id' => (int) $listing->id,
                'slug' => $listing->slug,
                'title' => $listing->title,
                'unit' => $listing->unit,
                'image' => $listing->primary_image,
            ] : null,
            'seller' => $seller ? [
                'id' => (int) $seller->id,
                'shop_name' => $seller->shop_name,
                'phone' => ($viewerRole !== 'buyer' || $contactVisible) ? $seller->phone : null,
                'address' => $seller->address,
                'latitude' => $seller->latitude !== null ? (float) $seller->latitude : null,
                'longitude' => $seller->longitude !== null ? (float) $seller->longitude : null,
            ] : null,
            'rider_job' => $job ? [
                'id' => (int) $job->id,
                'status' => $job->status,
                'rider_name' => $job->rider?->full_name,
                'rider_phone' => in_array($job->status, ['accepted', 'picking_up', 'picked_up', 'delivering'], true)
                    ? $job->rider?->phone : null,
                'tracking_url' => $this->safeTrackingUrl($job),
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'ready_at' => $this->ready_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
        ];

        // ฝั่งผู้ขาย/แอดมิน: เห็นส่วนแบ่ง GP + ข้อมูลผู้ซื้อสำหรับติดต่อ
        if (in_array($viewerRole, ['seller', 'admin'], true)) {
            $data['gp_rate'] = $this->gp_rate !== null ? (float) $this->gp_rate : null;
            $data['platform_fee'] = (float) $this->platform_fee;
            $data['seller_earning'] = (float) $this->seller_earning;
            $data['buyer'] = $buyer ? [
                'id' => (int) $buyer->id,
                'name' => $buyer->name,
                'phone' => $contactVisible ? ($buyer->phone ?? null) : null,
            ] : null;
        }

        // ฝั่งผู้ซื้อ: พิกัดจัดส่งของตัวเอง
        if ($viewerRole === 'buyer' || $viewerRole === 'admin') {
            $data['buyer_latitude'] = $this->buyer_latitude !== null ? (float) $this->buyer_latitude : null;
            $data['buyer_longitude'] = $this->buyer_longitude !== null ? (float) $this->buyer_longitude : null;
        }

        return $data;
    }

    /**
     * ลิงก์ติดตามไรเดอร์ (ไม่ให้ error ของ accessor ทำ API ล้ม)
     */
    protected function safeTrackingUrl(RiderJob $job): ?string
    {
        try {
            return $job->tracking_url ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    // ===== RiderDeliverable =====

    /**
     * จุดรับของ = ร้านค้า (ถ้าร้านไม่มีพิกัด ใช้พิกัดที่ลงไว้กับสินค้า)
     */
    public function riderPickupPoint(): array
    {
        $seller = $this->seller;
        $listing = $this->listing;

        $lat = $seller?->latitude ?? $listing?->latitude;
        $lng = $seller?->longitude ?? $listing?->longitude;

        return [
            'name' => $seller?->shop_name ?? 'ร้านค้าตลาดสด',
            'address' => $seller?->address ?: ($seller?->shop_name ?? 'ร้านค้าตลาดสด'),
            'latitude' => (float) ($lat ?? 0),
            'longitude' => (float) ($lng ?? 0),
            'phone' => $seller?->phone ?: ($seller?->user?->phone ?? null),
            'notes' => 'รับสินค้าออเดอร์ตลาดสด #'.$this->order_number,
        ];
    }

    /**
     * จุดส่งของ = ตำแหน่งที่ผู้ซื้อปักหมุดตอนสั่ง
     */
    public function riderDropoffPoint(): array
    {
        $buyer = $this->buyer;

        return [
            'name' => $buyer?->name ?? 'ผู้ซื้อ',
            'address' => $this->delivery_address ?: 'ตามพิกัดที่ผู้ซื้อปักหมุด',
            'latitude' => (float) ($this->buyer_latitude ?? 0),
            'longitude' => (float) ($this->buyer_longitude ?? 0),
            'phone' => $buyer?->phone ?? null,
            'notes' => $this->delivery_notes,
        ];
    }

    /**
     * เงินสดที่ไรเดอร์ต้องเก็บ: เฉพาะเก็บเงินปลายทางที่ยังไม่ได้จ่าย (สินค้า + ค่าส่ง)
     */
    public function riderCodAmount(): float
    {
        if ($this->payment_method === 'cod' && $this->payment_status !== 'paid') {
            return $this->grand_total;
        }

        return 0.0;
    }

    /**
     * ค่าส่งที่ผู้ซื้อจ่ายจริง (RiderDispatchService ใช้เป็น total_fee ของงาน — ให้ตรงกับที่เก็บจากลูกค้า)
     */
    public function riderDeliveryFeeCharged(): ?float
    {
        $fee = round((float) $this->delivery_fee, 2);

        return $fee > 0 ? $fee : null;
    }

    /**
     * ผู้ซื้อ (ลูกค้าของงานไรเดอร์)
     */
    public function riderCustomerUserId(): ?int
    {
        return $this->buyer_id ? (int) $this->buyer_id : null;
    }

    /**
     * สรุปรายการสินค้าสั้นๆ ให้ไรเดอร์
     */
    public function riderItemsSummary(): string
    {
        $title = $this->listing?->title ?? 'สินค้าตลาดสด';
        $unit = $this->listing?->unit ?? 'ชิ้น';

        return "{$title} x{$this->quantity} {$unit}";
    }

    /**
     * ผู้เกี่ยวข้อง (กันไรเดอร์รับงานของตัวเอง)
     */
    public function riderPartyUserIds(): array
    {
        return array_values(array_unique(array_filter([
            (int) $this->buyer_id,
            (int) ($this->seller?->user_id ?? 0),
        ])));
    }

    /**
     * งานไรเดอร์เปลี่ยนสถานะ → อัปเดตสถานะออเดอร์ให้ตรงกัน
     *
     * ถูกเรียกภายใน transaction ของ RiderJobService — lock แถวออเดอร์ก่อนแก้เสมอ
     * idempotent: เรียกซ้ำด้วยสถานะเดิมไม่เปลี่ยนอะไร
     */
    public function onRiderJobStatusChanged(RiderJob $job, string $fromStatus): void
    {
        $order = static::whereKey($this->id)->lockForUpdate()->first();

        if (! $order) {
            return;
        }

        // ออเดอร์จบไปแล้ว (ยกเลิก/เสร็จ) → ไม่ย้อนสถานะตามงานไรเดอร์
        if ($order->isTerminal()) {
            $this->setRawAttributes($order->getAttributes(), true);

            return;
        }

        // งานเก่าที่ถูกแทนด้วยงานใหม่แล้ว → ไม่สนใจ
        if ($order->rider_job_id && (int) $order->rider_job_id !== (int) $job->id) {
            $this->setRawAttributes($order->getAttributes(), true);

            return;
        }

        $now = now();
        $event = null;
        $fromOrderStatus = $order->order_status;

        switch ($job->status) {
            case 'accepted':
                $order->rider_job_id = $job->id;
                $order->rider_id = $job->rider_id;
                $order->rider_assigned_at = $order->rider_assigned_at ?? $now;
                $order->rider_accepted_at = $now;
                $event = 'rider_accepted';
                break;

            case 'pending':
                // ไรเดอร์คืนงาน → รอไรเดอร์คนใหม่
                if ($order->rider_id) {
                    $order->rider_id = null;
                    $event = 'rider_released';
                }
                break;

            case 'picked_up':
            case 'delivering':
                if (in_array($order->order_status, [self::STATUS_ACCEPTED, self::STATUS_PREPARING, self::STATUS_READY], true)) {
                    $order->order_status = self::STATUS_DELIVERING;
                    $order->rider_picked_up_at = $order->rider_picked_up_at ?? $now;
                    $event = 'delivering';
                }
                break;

            case 'delivered':
            case 'completed':
                if ($order->order_status !== self::STATUS_DELIVERED) {
                    $order->order_status = self::STATUS_DELIVERED;
                    $order->delivered_at = $order->delivered_at ?? $now;
                    $order->rider_delivered_at = $order->rider_delivered_at ?? $now;
                    $event = 'delivered';
                }

                // เก็บเงินปลายทางผ่านไรเดอร์: ส่งถึง ≠ ระบบได้เงิน (ไรเดอร์ยังถือเงินสด)
                // → ตั้งจ่ายแล้ว/ถือเงิน (escrow held) ใน onRiderCodSettled() หลังหักวอลเลตไรเดอร์สำเร็จเท่านั้น
                break;

            case 'failed':
                if ($order->order_status !== self::STATUS_DELIVERY_FAILED) {
                    $order->order_status = self::STATUS_DELIVERY_FAILED;
                    $event = 'delivery_failed';
                }
                break;

            case 'cancelled':
                // งานถูกยกเลิก (แอดมิน/ระบบ) ก่อนไรเดอร์รับของ → ออเดอร์กลับไปรอเรียกไรเดอร์ใหม่
                $order->rider_id = null;
                $event = 'rider_job_cancelled';
                break;
        }

        if ($event === null && ! $order->isDirty()) {
            $this->setRawAttributes($order->getAttributes(), true);

            return;
        }

        $order->appendHistory([
            'action' => 'rider_'.$job->status,
            'from' => $fromOrderStatus,
            'to' => $order->order_status,
            'by' => 'rider',
            'meta' => ['rider_job_id' => (int) $job->id, 'job_from' => $fromStatus],
        ]);
        $order->save();

        // ให้ instance ที่ผู้เรียกถืออยู่เห็นค่าล่าสุดด้วย
        $this->setRawAttributes($order->getAttributes(), true);

        if ($event) {
            try {
                app(\App\Services\FreshMarketOrderNotifier::class)->riderEvent($order, $event);
            } catch (\Throwable $e) {
                Log::warning('FreshMarket: แจ้งเตือนสถานะไรเดอร์ล้มเหลว', [
                    'order_id' => $order->id,
                    'event' => $event,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * hook เสริมของ RiderDispatchService: ออเดอร์นี้ยังเรียกไรเดอร์ได้หรือไม่ (ตรวจซ้ำหลังล็อกแถวออเดอร์)
     *
     * เรียกได้เฉพาะออเดอร์ส่งด้วยไรเดอร์ที่ร้านรับแล้วแต่ของยังไม่ออกจากร้าน หรือส่งไม่สำเร็จ (ให้แอดมินเรียกใหม่)
     * → กันสร้างงานให้ออเดอร์ที่ยกเลิก/ปิดไปแล้ว ระหว่างที่มีคำสั่งยกเลิกเข้ามาพร้อมกัน
     */
    public function riderCanDispatch(): bool
    {
        return $this->delivery_type === 'rider' && in_array($this->order_status, [
            self::STATUS_ACCEPTED,
            self::STATUS_PREPARING,
            self::STATUS_READY,
            self::STATUS_DELIVERY_FAILED,
        ], true);
    }

    /**
     * hook เสริมของ RiderEarningService: เงินเก็บปลายทางที่ไรเดอร์ถือไว้ถูกหักเข้าระบบแล้ว (rider_jobs.cod_settled_at)
     *
     * อยู่ใน transaction เดียวกับการเคลียร์เงินไรเดอร์ → ระบบถือเงินค่าสินค้าไว้ (escrow held) รอปิดออเดอร์
     * idempotent: จ่ายแล้วไม่ทำซ้ำ · งานเก่าที่ถูกแทนแล้วไม่สนใจ
     */
    public function onRiderCodSettled(RiderJob $job): void
    {
        $order = static::whereKey($this->id)->lockForUpdate()->first();

        if (! $order || $order->payment_method !== 'cod' || $order->payment_status === 'paid') {
            if ($order) {
                $this->setRawAttributes($order->getAttributes(), true);
            }

            return;
        }

        if ($order->rider_job_id && (int) $order->rider_job_id !== (int) $job->id) {
            $this->setRawAttributes($order->getAttributes(), true);

            return;
        }

        // ออเดอร์ถูกยกเลิกไปก่อนไรเดอร์นำส่งเงิน → ไม่เปิดเป็นจ่ายแล้ว ให้แอดมินคืนเงินสดผู้ซื้อเอง
        if ($order->order_status === self::STATUS_CANCELLED) {
            Log::error('FreshMarket: COD settled for a cancelled order — admin must refund the buyer manually', [
                'order_id' => $order->id,
                'rider_job_id' => $job->id,
                'cod_amount' => (float) $job->cod_amount,
            ]);
            $this->setRawAttributes($order->getAttributes(), true);

            return;
        }

        $order->payment_status = 'paid';
        $order->escrow_status = 'held';
        $order->paid_at = $order->paid_at ?? now();
        $order->appendHistory([
            'action' => 'cod_settled',
            'from' => $order->order_status,
            'to' => $order->order_status,
            'by' => 'system',
            'meta' => ['rider_job_id' => (int) $job->id, 'cod_amount' => round((float) $job->cod_amount, 2)],
        ]);
        $order->save();

        $this->setRawAttributes($order->getAttributes(), true);
    }

    /**
     * ยอดเงินที่ผู้ซื้อถูกหักจาก wallet สำหรับออเดอร์นี้ (สุทธิ)
     */
    public function walletPaidAmount(): float
    {
        return round((float) DB::table('wallet_transactions')
            ->where('reference_type', \App\Services\FreshMarketService::REF_PAYMENT)
            ->where('reference_id', $this->id)
            ->where('user_id', $this->buyer_id)
            ->where('status', 'completed')
            ->sum('amount'), 2);
    }
}
