<?php

namespace App\Models;

use App\Contracts\RiderDeliverable;
use App\Services\DeliveryFeeCalculator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * RiderJob Model
 *
 * งานรับ-ส่งของของไรเดอร์ 1 งาน ผูกกับออเดอร์ต้นทางผ่าน source (morph → RiderDeliverable)
 *
 * วงจรชีวิตงาน (ห้ามเปลี่ยนสถานะเองตรงๆ — ใช้ App\Services\RiderJobService เท่านั้น):
 *   pending → accepted → (picking_up) → picked_up → (delivering) → delivered → completed
 *   pending/accepted/picking_up → cancelled
 *   accepted/picking_up → pending (ไรเดอร์คืนงานก่อนรับของ)
 *   picked_up/delivering → failed (ส่งไม่สำเร็จ ต้องให้แอดมินจัดการคืนของ)
 *
 * @property int $id
 * @property string $job_number
 * @property int|null $rider_id
 * @property string $job_type
 * @property string|null $source_type
 * @property int|null $source_id
 * @property string $status
 * @property string $total_fee
 * @property string $rider_earnings
 * @property string $platform_fee
 * @property string $cod_amount
 * @property string|null $dispatch_type broadcast|cascade|manual_needed (ข้อมูลเก่า: auto)
 */
class RiderJob extends Model
{
    use SoftDeletes;

    /**
     * สถานะที่ไรเดอร์กำลังทำงานอยู่ (นับเป็น "งานค้าง" — 1 ไรเดอร์มีได้ครั้งละ 1 งาน)
     */
    public const ACTIVE_STATUSES = ['accepted', 'picking_up', 'picked_up', 'delivering'];

    /**
     * สถานะจบงาน (เปลี่ยนต่อไม่ได้แล้ว)
     */
    public const TERMINAL_STATUSES = ['completed', 'cancelled', 'failed'];

    /**
     * การเปลี่ยนสถานะที่อนุญาต (state machine เดียวของระบบ)
     *
     * @var array<string, array<int, string>>
     */
    public const TRANSITIONS = [
        'pending' => ['accepted', 'cancelled'],
        'accepted' => ['picking_up', 'picked_up', 'pending', 'cancelled'],
        'picking_up' => ['picked_up', 'pending', 'cancelled'],
        'picked_up' => ['delivering', 'delivered', 'failed'],
        'delivering' => ['delivered', 'failed'],
        'delivered' => ['completed'],
        'completed' => [],
        'cancelled' => [],
        'failed' => [],
    ];

    /**
     * รหัสเหตุผล "ส่งไม่สำเร็จ" ที่ไรเดอร์เลือกได้
     *
     * @var array<string, string>
     */
    public const FAILURE_REASONS = [
        'customer_unreachable' => 'ติดต่อลูกค้าไม่ได้',
        'wrong_address' => 'ที่อยู่ไม่ถูกต้อง',
        'customer_refused' => 'ลูกค้าปฏิเสธรับของ',
        'item_damaged' => 'สินค้าเสียหาย',
        'other' => 'อื่นๆ',
        // ใช้ภายในระบบ (ไรเดอร์เลือกเองไม่ได้)
        'order_cancelled' => 'ออเดอร์ถูกยกเลิกระหว่างจัดส่ง',
        'admin_intervention' => 'แอดมินปิดงาน',
    ];

    /**
     * เหตุผลที่ไรเดอร์เลือกเองได้ผ่านแอป
     */
    public const RIDER_FAILURE_REASONS = ['customer_unreachable', 'wrong_address', 'customer_refused', 'item_damaged', 'other'];

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'rider_jobs';

    /**
     * Fields ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'job_number',
        'rider_id',
        'job_type',
        'source_type',
        'source_id',
        'title',
        'description',
        'pickup_address',
        'pickup_latitude',
        'pickup_longitude',
        'pickup_contact_name',
        'pickup_contact_phone',
        'pickup_notes',
        'delivery_address',
        'delivery_latitude',
        'delivery_longitude',
        'delivery_contact_name',
        'delivery_contact_phone',
        'delivery_notes',
        'delivery_area',
        'distance_km',
        'estimated_duration_minutes',
        'base_fee',
        'distance_fee',
        'extra_fee',
        'total_fee',
        'rider_earnings',
        'platform_fee',
        'cod_amount',
        'cod_collected_at',
        'cod_settled_at',
        'earnings_settled_at',
        'status',
        'cancellation_reason',
        'cancelled_by',
        'accepted_at',
        'picked_up_at',
        'delivered_at',
        'delivered_latitude',
        'delivered_longitude',
        'completed_at',
        'cancelled_at',
        'failed_at',
        'failure_reason',
        'failure_note',
        'failure_proof_image',
        'release_count',
        'customer_id',
        'pickup_proof_image',
        'delivery_proof_image',
        'signature_image',
        'customer_rating',
        'customer_review',
        'tracking_token',
        'tracking_expires_at',
        'gps_active',
        'gps_lost_at',
        'gps_warning_count',
        'buyer_line_user_id',
        'customer_share_location',
        'customer_last_latitude',
        'customer_last_longitude',
        'customer_location_at',
        'dispatch_type',
        'dispatch_attempts',
        'current_offer_rider_id',
        'offer_expires_at',
        'offer_sent_at',
        'candidate_riders',
        'dispatch_radius_km',
        'dispatch_round',
        'last_dispatched_at',
    ];

    /**
     * Casts
     *
     * @var array<string, string>
     */
    protected $casts = [
        'pickup_latitude' => 'decimal:8',
        'pickup_longitude' => 'decimal:8',
        'delivery_latitude' => 'decimal:8',
        'delivery_longitude' => 'decimal:8',
        'delivered_latitude' => 'decimal:8',
        'delivered_longitude' => 'decimal:8',
        'customer_last_latitude' => 'decimal:8',
        'customer_last_longitude' => 'decimal:8',
        'distance_km' => 'decimal:2',
        'base_fee' => 'decimal:2',
        'distance_fee' => 'decimal:2',
        'extra_fee' => 'decimal:2',
        'total_fee' => 'decimal:2',
        'rider_earnings' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'cod_amount' => 'decimal:2',
        'dispatch_radius_km' => 'decimal:2',
        'dispatch_round' => 'integer',
        'release_count' => 'integer',
        'accepted_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'failed_at' => 'datetime',
        'cod_collected_at' => 'datetime',
        'cod_settled_at' => 'datetime',
        'earnings_settled_at' => 'datetime',
        'last_dispatched_at' => 'datetime',
        'customer_location_at' => 'datetime',
        'customer_share_location' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'tracking_expires_at' => 'datetime',
        'gps_active' => 'boolean',
        'gps_lost_at' => 'datetime',
        'gps_warning_count' => 'integer',
        'dispatch_attempts' => 'array',
        'candidate_riders' => 'array',
        'offer_expires_at' => 'datetime',
        'offer_sent_at' => 'datetime',
    ];

    /**
     * ค่าเริ่มต้น
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'base_fee' => 0,
        'distance_fee' => 0,
        'extra_fee' => 0,
        'total_fee' => 0,
        'rider_earnings' => 0,
        'platform_fee' => 0,
        'cod_amount' => 0,
        'release_count' => 0,
        'dispatch_round' => 0,
        'customer_share_location' => false,
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

        // สร้างเลขที่งานอัตโนมัติ
        static::creating(function ($job) {
            if (empty($job->job_number)) {
                $job->job_number = self::generateJobNumber();
            }
        });
    }

    /**
     * สร้างเลขที่งาน JOB + yymmdd + เลขสุ่ม 6 หลัก
     *
     * เดิมใช้ "เลขล่าสุดของวัน + 1" → ชนกันเมื่อสร้างพร้อมกันหรือมีแถวที่ถูก soft-delete
     * ตอนนี้สุ่มแล้วเช็ครวมแถวที่ลบแล้ว (withTrashed) + unique index กันชนซ้ำอีกชั้น
     */
    public static function generateJobNumber(): string
    {
        $date = now()->format('ymd');

        for ($i = 0; $i < 8; $i++) {
            $candidate = 'JOB'.$date.str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            if (! static::withTrashed()->where('job_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        return 'JOB'.$date.strtoupper(Str::random(8));
    }

    // =====================================================
    // ภาระเงินเก็บปลายทาง (COD) ของไรเดอร์
    // =====================================================

    /**
     * ยอดที่ต้องกันไว้ในวอลเลตของผู้ใช้ (ในฐานะไรเดอร์) สำหรับนำส่งเงิน COD
     *
     * = Σ (cod_amount − rider_earnings) ของงาน COD ที่ยังไม่เคลียร์เงิน ทั้งงานที่ยังวิ่งอยู่ (accepted → delivered)
     *   และงานที่ส่งเสร็จแล้วแต่หักวอลเลตไม่ได้ (completed, cod_settled_at IS NULL)
     * ใช้กันถอน/โอนเงินออกระหว่างที่ไรเดอร์ถือเงินสดของลูกค้า — 0 = ไม่มีภาระ (ผู้ใช้ทั่วไปได้ 0 เสมอ)
     */
    public static function codReserveForUser(int $userId): float
    {
        $riderIds = Rider::withTrashed()->where('user_id', $userId)->pluck('id');

        if ($riderIds->isEmpty()) {
            return 0.0;
        }

        $rows = static::query()
            ->whereIn('rider_id', $riderIds->all())
            ->where('cod_amount', '>', 0)
            ->whereNull('cod_settled_at')
            ->whereIn('status', array_merge(self::ACTIVE_STATUSES, ['delivered', 'completed']))
            ->get(['cod_amount', 'rider_earnings']);

        return round((float) $rows->sum(
            fn (self $job) => max(0.0, (float) $job->cod_amount - (float) $job->rider_earnings)
        ), 2);
    }

    /**
     * ไรเดอร์มีงาน COD ที่ส่งเสร็จแล้วแต่ยังนำส่งเงินเข้าระบบไม่ได้หรือไม่
     */
    public static function riderHasUnsettledCod(int $riderId): bool
    {
        return static::query()
            ->where('rider_id', $riderId)
            ->where('status', 'completed')
            ->where('cod_amount', '>', 0)
            ->whereNull('cod_settled_at')
            ->exists();
    }

    // =====================================================
    // State machine (ฟังก์ชันล้วน ไม่แตะฐานข้อมูล)
    // =====================================================

    /**
     * เปลี่ยนจากสถานะ $from ไป $to ได้หรือไม่
     */
    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public static function isTerminalStatus(string $status): bool
    {
        return in_array($status, self::TERMINAL_STATUSES, true);
    }

    public static function isActiveStatus(string $status): bool
    {
        return in_array($status, self::ACTIVE_STATUSES, true);
    }

    public function isTerminal(): bool
    {
        return self::isTerminalStatus((string) $this->status);
    }

    /**
     * งานที่ยังไม่มีไรเดอร์และรอคนรับ
     */
    public function isOpen(): bool
    {
        return $this->status === 'pending' && $this->rider_id === null;
    }

    /**
     * ปุ่มที่ผู้ดูคนนี้กดได้ตอนนี้ (ให้แอปใช้แสดงปุ่ม — การตรวจจริงอยู่ที่ RiderJobService)
     *
     * @return array<int, string> accept|release|picking_up|picked_up|delivering|deliver|fail
     */
    public function allowedActionsFor(?Rider $viewer): array
    {
        if (! $viewer) {
            return [];
        }

        if ($this->isOpen()) {
            if ($this->dispatch_type === 'cascade'
                && $this->current_offer_rider_id !== null
                && (int) $this->current_offer_rider_id !== (int) $viewer->id) {
                return [];
            }

            return ['accept'];
        }

        if ((int) $this->rider_id !== (int) $viewer->id) {
            return [];
        }

        return match ($this->status) {
            'accepted' => ['picking_up', 'picked_up', 'release'],
            'picking_up' => ['picked_up', 'release'],
            'picked_up' => ['delivering', 'deliver', 'fail'],
            'delivering' => ['deliver', 'fail'],
            default => [],
        };
    }

    // =====================================================
    // Relationships
    // =====================================================

    /**
     * ความสัมพันธ์กับ Rider (null = ยังไม่มีคนรับ)
     */
    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }

    /**
     * ความสัมพันธ์กับ Customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * ออเดอร์ต้นทาง (FreshMarketOrder, Order ฯลฯ ที่ implement RiderDeliverable)
     */
    public function source(): MorphTo
    {
        return $this->morphTo('source', 'source_type', 'source_id');
    }

    /**
     * ประวัติตำแหน่งของงานนี้
     */
    public function locations(): HasMany
    {
        return $this->hasMany(RiderLocation::class, 'job_id');
    }

    /**
     * คำสั่งซื้อตลาดสดที่เชื่อมกับงานนี้ (ลิงก์เก่าผ่าน fresh_market_orders.rider_job_id)
     */
    public function freshMarketOrder()
    {
        return $this->hasOne(FreshMarketOrder::class, 'rider_job_id');
    }

    /**
     * ออเดอร์ต้นทางในรูป RiderDeliverable (null ถ้าไม่มีหรือไม่รองรับ)
     */
    public function deliverableSource(): ?RiderDeliverable
    {
        if (! $this->source_type || ! $this->source_id || ! class_exists($this->source_type)) {
            return null;
        }

        $source = $this->source;

        return $source instanceof RiderDeliverable ? $source : null;
    }

    /**
     * user_id ของผู้เกี่ยวข้องกับออเดอร์ (ผู้ซื้อ ผู้ขาย) — ใช้กันรับงานตัวเอง + ส่งแจ้งเตือน
     *
     * @return array<int, int>
     */
    public function partyUserIds(): array
    {
        $ids = [];

        $source = $this->deliverableSource();
        if ($source) {
            $ids = $source->riderPartyUserIds();
        }

        if ($this->customer_id) {
            $ids[] = (int) $this->customer_id;
        }

        return array_values(array_unique(array_filter(array_map('intval', $ids), fn ($id) => $id > 0)));
    }

    // =====================================================
    // Scopes
    // =====================================================

    /**
     * Scope สำหรับงานที่กำลังดำเนินการ
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    /**
     * Scope งานที่ยังไม่จบ (รวม pending/delivered)
     */
    public function scopeNonTerminal($query)
    {
        return $query->whereNotIn('status', self::TERMINAL_STATUSES);
    }

    /**
     * Scope งานที่รอคนรับจริงๆ (pending + ยังไม่มีไรเดอร์)
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'pending')->whereNull('rider_id');
    }

    /**
     * Scope งานของออเดอร์ต้นทาง
     */
    public function scopeForSource($query, Model $source)
    {
        return $query->where('source_type', $source->getMorphClass())
            ->where('source_id', $source->getKey());
    }

    /**
     * Scope สำหรับงานที่เสร็จสิ้น
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope สำหรับงานตลาดสด
     */
    public function scopeFreshMarket($query)
    {
        return $query->where('job_type', 'fresh_market');
    }

    /**
     * Scope สำหรับงานที่รอไรเดอร์
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // =====================================================
    // Accessors
    // =====================================================

    /**
     * ชื่อสถานะภาษาไทย
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'รอไรเดอร์รับงาน',
            'accepted' => 'ไรเดอร์รับงานแล้ว',
            'picking_up' => 'กำลังไปรับของ',
            'picked_up' => 'รับของแล้ว',
            'delivering' => 'กำลังจัดส่ง',
            'delivered' => 'ส่งแล้ว',
            'completed' => 'เสร็จสิ้น',
            'cancelled' => 'ยกเลิก',
            'failed' => 'ส่งไม่สำเร็จ',
            default => 'ไม่ทราบ',
        };
    }

    /**
     * ชื่อประเภทงานภาษาไทย
     */
    public function getJobTypeTextAttribute(): string
    {
        return match ($this->job_type) {
            'delivery' => 'ส่งของ',
            'food' => 'ส่งอาหาร',
            'fresh_market' => 'ส่งของตลาดสด',
            'shop_delivery' => 'ส่งสินค้าร้านค้า',
            'document' => 'ส่งเอกสาร',
            'service' => 'ให้บริการ',
            'pickup' => 'รับของ',
            default => 'ส่งของ',
        };
    }

    /**
     * ตรวจสอบว่าสามารถ track GPS ได้หรือไม่
     */
    public function getIsTrackableAttribute(): bool
    {
        return $this->isTrackable();
    }

    // =====================================================
    // GPS Tracking Methods
    // =====================================================

    /**
     * ตรวจสอบว่า tracking token ยังใช้ได้
     */
    public function isTrackingValid(): bool
    {
        return $this->tracking_token
            && ($this->tracking_expires_at === null || $this->tracking_expires_at->isFuture());
    }

    /**
     * ตรวจสอบว่างานกำลังดำเนินอยู่ (ลูกค้าเห็นตำแหน่งไรเดอร์ได้เฉพาะช่วงนี้)
     */
    public function isTrackable(): bool
    {
        return self::isActiveStatus((string) $this->status);
    }

    /**
     * URL สำหรับติดตาม
     */
    public function getTrackingUrlAttribute(): ?string
    {
        if (! $this->tracking_token || ! $this->isTrackingValid()) {
            return null;
        }

        return url('/taladsod/track/'.$this->tracking_token);
    }

    /**
     * หยุดงานเพราะ GPS หาย
     */
    public function pauseForGpsLoss(): void
    {
        $this->update([
            'gps_active' => false,
            'gps_lost_at' => now(),
            'gps_warning_count' => ($this->gps_warning_count ?? 0) + 1,
        ]);
    }

    /**
     * กลับมาทำงานต่อหลัง GPS กลับมา
     */
    public function resumeFromGpsLoss(): void
    {
        $this->update([
            'gps_active' => true,
            'gps_lost_at' => null,
        ]);
    }

    /**
     * ตำแหน่งสดของลูกค้า (เฉพาะเมื่อลูกค้ายินยอม + งานยังวิ่งอยู่ + อัปเดตไม่เกิน 5 นาที)
     *
     * @return array{latitude: float, longitude: float, updated_at: string}|null
     */
    public function customerLiveLocation(): ?array
    {
        if (! $this->customer_share_location
            || ! $this->isTrackable()
            || ! $this->customer_location_at
            || $this->customer_last_latitude === null
            || $this->customer_last_longitude === null
            || $this->customer_location_at->lt(now()->subMinutes(5))) {
            return null;
        }

        return [
            'latitude' => (float) $this->customer_last_latitude,
            'longitude' => (float) $this->customer_last_longitude,
            'updated_at' => $this->customer_location_at->toIso8601String(),
        ];
    }

    // =====================================================
    // Dispatch Tracking Methods
    // =====================================================

    /**
     * บันทึกการเสนองานให้ไรเดอร์ (โหมด cascade)
     */
    public function recordOffer(int $riderId, int $timeoutSeconds = 120): void
    {
        $attempts = $this->dispatch_attempts ?? [];
        $attempts[] = [
            'rider_id' => $riderId,
            'sent_at' => now()->toIso8601String(),
            'status' => 'pending',
        ];

        $this->update([
            'current_offer_rider_id' => $riderId,
            'offer_sent_at' => now(),
            'offer_expires_at' => now()->addSeconds($timeoutSeconds),
            'dispatch_attempts' => $attempts,
        ]);
    }

    /**
     * บันทึกผลตอบรับจากไรเดอร์
     */
    public function recordOfferResponse(int $riderId, string $status): void
    {
        $attempts = $this->dispatch_attempts ?? [];

        foreach ($attempts as &$attempt) {
            if ((int) ($attempt['rider_id'] ?? 0) === $riderId && ($attempt['status'] ?? null) === 'pending') {
                $attempt['status'] = $status; // accepted, rejected, expired
                $attempt['responded_at'] = now()->toIso8601String();
                break;
            }
        }
        unset($attempt);

        $updateData = ['dispatch_attempts' => $attempts];

        // ถ้าไม่ใช่ accepted ให้เคลียร์ current offer
        if ($status !== 'accepted') {
            $updateData['current_offer_rider_id'] = null;
            $updateData['offer_expires_at'] = null;
        }

        $this->update($updateData);
    }

    /**
     * ตรวจสอบว่า offer ปัจจุบันหมดเวลาแล้วหรือยัง
     */
    public function isOfferExpired(): bool
    {
        return $this->offer_expires_at && $this->offer_expires_at->isPast();
    }

    /**
     * ตรวจสอบว่ามี offer ที่รอตอบอยู่หรือไม่
     */
    public function hasPendingOffer(): bool
    {
        return $this->current_offer_rider_id !== null
            && $this->offer_expires_at
            && $this->offer_expires_at->isFuture();
    }

    /**
     * ไรเดอร์ที่เคยได้รับข้อเสนอ/แจ้งเตือนงานนี้แล้ว (candidate_riders + dispatch_attempts)
     *
     * @return array<int, int>
     */
    public function notifiedRiderIds(): array
    {
        $ids = array_map('intval', $this->candidate_riders ?? []);
        foreach ($this->dispatch_attempts ?? [] as $attempt) {
            if (isset($attempt['rider_id'])) {
                $ids[] = (int) $attempt['rider_id'];
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * ไรเดอร์ที่เคยคืนงานนี้ (ไม่เสนอให้ซ้ำ)
     *
     * @return array<int, int>
     */
    public function releasedRiderIds(): array
    {
        $ids = [];
        foreach ($this->dispatch_attempts ?? [] as $attempt) {
            if (($attempt['status'] ?? null) === 'released' && isset($attempt['rider_id'])) {
                $ids[] = (int) $attempt['rider_id'];
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * ดึงไรเดอร์ถัดไปจากรายการ candidates
     */
    public function getNextCandidateRiderId(): ?int
    {
        $candidates = $this->candidate_riders ?? [];
        $attempted = collect($this->dispatch_attempts ?? [])
            ->pluck('rider_id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        foreach ($candidates as $riderId) {
            if (! in_array((int) $riderId, $attempted, true)) {
                return (int) $riderId;
            }
        }

        return null;
    }

    /**
     * ตรวจสอบว่าเป็น broadcast dispatch หรือไม่
     */
    public function isBroadcast(): bool
    {
        return $this->dispatch_type === 'broadcast';
    }

    // =====================================================
    // พื้นที่ปลายทางแบบหยาบ (โชว์ก่อนรับงาน)
    // =====================================================

    /**
     * ดึง "เขต/อำเภอ + จังหวัด" จากที่อยู่ภาษาไทย (ไม่เอาบ้านเลขที่/ซอย/ถนน)
     *
     * เช่น "99/1 ซ.สุขุมวิท 21 แขวงคลองเตยเหนือ เขตวัฒนา กรุงเทพมหานคร 10110" → "เขตวัฒนา กรุงเทพมหานคร"
     */
    public static function deriveArea(?string $address): ?string
    {
        if (! $address) {
            return null;
        }

        $parts = [];

        if (preg_match('/(?:เขต|อำเภอ|อ\.)\s*([ก-๙a-zA-Z]+)/u', $address, $m)) {
            $parts[] = (str_starts_with($m[0], 'เขต') ? 'เขต' : 'อ.').$m[1];
        }

        if (preg_match('/(?:จังหวัด|จ\.)\s*([ก-๙a-zA-Z]+)/u', $address, $m)) {
            $parts[] = 'จ.'.$m[1];
        } elseif (preg_match('/(กรุงเทพมหานคร|กรุงเทพฯ|กทม\.?)/u', $address, $m)) {
            $parts[] = 'กรุงเทพฯ';
        }

        return $parts === [] ? null : implode(' ', $parts);
    }

    // =====================================================
    // Serializers สำหรับ API (ใช้ 2 ตัวนี้เท่านั้น)
    // =====================================================

    /**
     * ข้อมูลงานแบบย่อ (รายการงาน)
     *
     * $viewer = ไรเดอร์ที่กำลังดู (แอปไรเดอร์) | null = ผู้มีสิทธิ์เต็ม (แอดมิน/เจ้าของออเดอร์ — caller ต้องตรวจสิทธิ์เอง)
     *
     * การปิดข้อมูล:
     *   - ก่อนไรเดอร์คนนี้รับงาน: จุดส่ง = พื้นที่หยาบ + พิกัดปัด 2 ตำแหน่ง (~1 กม.) ไม่มีชื่อ/ที่อยู่/เบอร์
     *   - เบอร์โทรทั้งหมดซ่อนหลังงานจบเกิน 1 ชม.
     *
     * @return array<string, mixed>
     */
    public function toApiSummary(?Rider $viewer = null): array
    {
        $isMine = $viewer !== null && $this->rider_id !== null && (int) $this->rider_id === (int) $viewer->id;
        $revealDropoff = $viewer === null || ($isMine && $this->status !== 'pending');
        $revealPhones = $revealDropoff && ! $this->contactExpired();

        // ระยะจากไรเดอร์ถึงจุดรับ: ใช้ค่าที่ RiderDispatchService::availableJobsFor คำนวณจากพิกัดล่าสุดของแอป (ถ้ามี)
        $distanceToPickup = is_numeric($this->getAttribute('distance_to_rider_km'))
            ? round((float) $this->getAttribute('distance_to_rider_km'), 2)
            : null;
        if ($distanceToPickup === null && $viewer && $viewer->last_latitude !== null && $viewer->last_longitude !== null
            && $this->pickup_latitude !== null && $this->pickup_longitude !== null) {
            $distanceToPickup = round(DeliveryFeeCalculator::haversineKm(
                (float) $viewer->last_latitude,
                (float) $viewer->last_longitude,
                (float) $this->pickup_latitude,
                (float) $this->pickup_longitude
            ), 2);
        }

        return [
            'id' => (int) $this->id,
            'job_number' => (string) $this->job_number,
            'job_type' => (string) $this->job_type,
            'job_type_text' => $this->job_type_text,
            'title' => (string) $this->title,
            'items_summary' => $this->description,
            'status' => (string) $this->status,
            'status_text' => $this->status_text,
            'is_mine' => $isMine,
            'dispatch_type' => $this->dispatch_type,
            'pickup' => [
                'name' => $this->pickup_contact_name,
                'address' => $this->pickup_address,
                'latitude' => $this->pickup_latitude !== null ? (float) $this->pickup_latitude : null,
                'longitude' => $this->pickup_longitude !== null ? (float) $this->pickup_longitude : null,
                'phone' => $revealPhones ? $this->pickup_contact_phone : null,
            ],
            'dropoff' => $this->dropoffPayload($revealDropoff, $revealPhones, false),
            'distance_km' => (float) $this->distance_km,
            'distance_to_pickup_km' => $distanceToPickup,
            'estimated_duration_minutes' => (int) $this->estimated_duration_minutes,
            'base_fee' => (float) $this->base_fee,
            'distance_fee' => (float) $this->distance_fee,
            'extra_fee' => (float) $this->extra_fee,
            'total_fee' => (float) $this->total_fee,
            'platform_fee' => (float) $this->platform_fee,
            'rider_earnings' => (float) $this->rider_earnings,
            'cod_amount' => (float) $this->cod_amount,
            'is_cod' => (float) $this->cod_amount > 0,
            'allowed_actions' => $this->allowedActionsFor($viewer),
            'created_at' => $this->created_at?->toIso8601String(),
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];
    }

    /**
     * ข้อมูลงานแบบเต็ม (หน้ารายละเอียดงาน)
     *
     * @return array<string, mixed>
     */
    public function toApiDetail(?Rider $viewer = null): array
    {
        $summary = $this->toApiSummary($viewer);

        $isMine = $summary['is_mine'];
        $revealDropoff = $viewer === null || ($isMine && $this->status !== 'pending');
        $revealPhones = $revealDropoff && ! $this->contactExpired();

        $summary['pickup']['notes'] = $revealDropoff ? $this->pickup_notes : null;
        $summary['dropoff'] = $this->dropoffPayload($revealDropoff, $revealPhones, true);

        $rider = $this->rider_id ? $this->rider : null;

        return array_merge($summary, [
            'description' => $this->description,
            'customer_live_location' => ($viewer === null || $isMine) ? $this->customerLiveLocation() : null,
            'gps_active' => (bool) $this->gps_active,
            'release_count' => (int) $this->release_count,
            'cod' => [
                'amount' => (float) $this->cod_amount,
                'collected_at' => $this->cod_collected_at?->toIso8601String(),
                'settled_at' => $this->cod_settled_at?->toIso8601String(),
            ],
            'earnings_settled' => $this->earnings_settled_at !== null,
            'timeline' => [
                'created_at' => $this->created_at?->toIso8601String(),
                'accepted_at' => $this->accepted_at?->toIso8601String(),
                'picked_up_at' => $this->picked_up_at?->toIso8601String(),
                'delivered_at' => $this->delivered_at?->toIso8601String(),
                'completed_at' => $this->completed_at?->toIso8601String(),
                'cancelled_at' => $this->cancelled_at?->toIso8601String(),
                'failed_at' => $this->failed_at?->toIso8601String(),
            ],
            'photos' => [
                'pickup' => ($viewer === null || $isMine) ? $this->publicFileUrl($this->pickup_proof_image) : null,
                'delivery' => ($viewer === null || $isMine) ? $this->publicFileUrl($this->delivery_proof_image) : null,
                'failure' => ($viewer === null || $isMine) ? $this->publicFileUrl($this->failure_proof_image) : null,
            ],
            'failure' => $this->status === 'failed' ? [
                'reason_code' => $this->failure_reason,
                'reason_text' => self::FAILURE_REASONS[$this->failure_reason] ?? $this->failure_reason,
                'note' => $this->failure_note,
            ] : null,
            'cancellation' => $this->status === 'cancelled' ? [
                'by' => $this->cancelled_by,
                'reason' => $this->cancellation_reason,
            ] : null,
            'rider' => $rider ? [
                'id' => (int) $rider->id,
                'full_name' => $rider->full_name,
                'phone' => ($viewer === null && $this->isTrackable()) ? $rider->phone : null,
                'vehicle_type' => $rider->vehicle_type,
                'vehicle_type_text' => $rider->vehicle_type_text,
                'vehicle_plate' => $rider->vehicle_plate,
                'rating' => (float) $rider->rating,
                // รูปโปรไฟล์อยู่บน private disk (เอกสารไรเดอร์) → ให้ API ฝั่ง controller สร้างลิงก์เอง
                'has_profile_image' => ! empty($rider->profile_image),
            ] : null,
            'tracking_url' => $viewer === null ? $this->tracking_url : null,
        ]);
    }

    /**
     * ข้อมูลจุดส่ง (ปิดบังตามสิทธิ์)
     *
     * @return array<string, mixed>
     */
    private function dropoffPayload(bool $reveal, bool $revealPhones, bool $withNotes): array
    {
        $area = $this->delivery_area ?: self::deriveArea($this->delivery_address);

        if (! $reveal) {
            return [
                'name' => null,
                'address' => null,
                'area' => $area,
                'latitude' => $this->delivery_latitude !== null ? round((float) $this->delivery_latitude, 2) : null,
                'longitude' => $this->delivery_longitude !== null ? round((float) $this->delivery_longitude, 2) : null,
                'phone' => null,
                'notes' => null,
                'is_approximate' => true,
            ];
        }

        return [
            'name' => $this->delivery_contact_name,
            'address' => $this->delivery_address,
            'area' => $area,
            'latitude' => $this->delivery_latitude !== null ? (float) $this->delivery_latitude : null,
            'longitude' => $this->delivery_longitude !== null ? (float) $this->delivery_longitude : null,
            'phone' => $revealPhones ? $this->delivery_contact_phone : null,
            'notes' => $withNotes ? $this->delivery_notes : null,
            'is_approximate' => false,
        ];
    }

    /**
     * งานจบไปเกิน 1 ชม. แล้ว → ไม่เปิดเบอร์โทรอีก
     */
    private function contactExpired(): bool
    {
        if (! $this->isTerminal()) {
            return false;
        }

        $endedAt = $this->completed_at ?? $this->cancelled_at ?? $this->failed_at ?? $this->updated_at;

        return $endedAt !== null && $endedAt->lt(now()->subHour());
    }

    /**
     * URL ไฟล์บน public disk (null ถ้าไม่มีไฟล์)
     */
    private function publicFileUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
