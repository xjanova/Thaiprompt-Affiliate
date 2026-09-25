<?php

namespace App\Models;

use App\Exceptions\RiderJobException;
use App\Services\DeliveryFeeCalculator;
use App\Services\RiderNotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * Rider Model
 *
 * จัดการข้อมูลไรเดอร์ในระบบ
 *
 * @property int $id
 * @property int $user_id
 * @property string $full_name
 * @property string $phone
 * @property string|null $id_card_number
 * @property string|null $vehicle_type
 * @property string $status pending|approved|rejected|suspended|inactive
 * @property string $availability online|offline|busy
 * @property bool $gps_permission_granted
 * @property bool $camera_permission_granted
 * @property bool $microphone_permission_granted
 * @property string|null $last_latitude
 * @property string|null $last_longitude
 * @property \Carbon\Carbon|null $last_location_update
 * @property \Carbon\Carbon|null $share_location_consent_at
 * @property \Carbon\Carbon|null $suspended_at
 * @property string|null $suspension_reason
 */
class Rider extends Model
{
    use SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'riders';

    /**
     * Fields ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'line_user_id',
        'fresh_market_linked',
        'full_name',
        'phone',
        'id_card_number',
        'birth_date',
        'address',
        'province',
        'district',
        'vehicle_type',
        'vehicle_plate',
        'vehicle_brand',
        'vehicle_color',
        'id_card_image',
        'driver_license_image',
        'vehicle_registration_image',
        'profile_image',
        'status',
        'rider_type',
        'service_categories',
        'service_provider_id',
        'availability',
        'rejection_reason',
        'rejected_at',
        'rejected_by',
        'suspension_reason',
        'suspended_at',
        'suspended_by',
        'gps_permission_granted',
        'camera_permission_granted',
        'microphone_permission_granted',
        'notification_permission_granted',
        'permissions_granted_at',
        'share_location_consent_at',
        'total_jobs',
        'completed_jobs',
        'cancelled_jobs',
        'rating',
        'rating_count',
        'total_earnings',
        'deposit_amount',
        'deposit_paid_at',
        'deposit_status',
        'deposit_transaction_id',
        'last_latitude',
        'last_longitude',
        'last_location_update',
        'approved_at',
        'approved_by',
        'preferred_job_types',
        'preferred_radius_km',
        'preferred_min_fee',
    ];

    /**
     * Casts
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'date',
        'gps_permission_granted' => 'boolean',
        'camera_permission_granted' => 'boolean',
        'microphone_permission_granted' => 'boolean',
        'notification_permission_granted' => 'boolean',
        'permissions_granted_at' => 'datetime',
        'share_location_consent_at' => 'datetime',
        'last_latitude' => 'decimal:8',
        'last_longitude' => 'decimal:8',
        'last_location_update' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'suspended_at' => 'datetime',
        'rating' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'deposit_paid_at' => 'datetime',
        'fresh_market_linked' => 'boolean',
        'service_categories' => 'array',
        'preferred_job_types' => 'array',
        'preferred_radius_km' => 'decimal:2',
        'preferred_min_fee' => 'decimal:2',
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
        'status' => 'pending',
        'availability' => 'offline',
        'vehicle_type' => 'motorcycle',
        'gps_permission_granted' => false,
        'camera_permission_granted' => false,
        'microphone_permission_granted' => false,
        'notification_permission_granted' => false,
        'total_jobs' => 0,
        'completed_jobs' => 0,
        'cancelled_jobs' => 0,
        'rating' => 0,
        'rating_count' => 0,
        'total_earnings' => 0,
    ];

    // =====================================================
    // Relationships
    // =====================================================

    /**
     * ความสัมพันธ์กับ User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ User ที่อนุมัติ
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * งานทั้งหมดของไรเดอร์
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(RiderJob::class);
    }

    /**
     * ประวัติตำแหน่ง GPS
     */
    public function locations(): HasMany
    {
        return $this->hasMany(RiderLocation::class);
    }

    /**
     * ผู้ให้บริการ (ถ้าเป็นไรเดอร์เซอร์วิส)
     */
    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    /**
     * ผู้ให้บริการที่เชื่อมกับไรเดอร์คนนี้
     */
    public function linkedServiceProvider(): HasOne
    {
        return $this->hasOne(ServiceProvider::class);
    }

    /**
     * งานตลาดสด (ผ่าน rider_id ใน fresh_market_orders)
     */
    public function freshMarketOrders(): HasMany
    {
        return $this->hasMany(FreshMarketOrder::class);
    }

    // =====================================================
    // Scopes
    // =====================================================

    /**
     * Scope สำหรับไรเดอร์ที่อนุมัติแล้ว
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope สำหรับไรเดอร์ที่ออนไลน์
     */
    public function scopeOnline($query)
    {
        return $query->where('availability', 'online');
    }

    /**
     * Scope สำหรับไรเดอร์ที่พร้อมรับงาน
     */
    public function scopeAvailable($query)
    {
        return $query->approved()
            ->where('availability', 'online');
    }

    /**
     * Scope สำหรับไรเดอร์ที่จ่ายค่าประกันแล้ว
     */
    public function scopeDepositPaid($query)
    {
        return $query->where('deposit_status', 'paid');
    }

    /**
     * Scope ไรเดอร์ที่พร้อมรับงานส่งของจริงตอนนี้
     *
     * approved + ไม่ถูกระงับ + online + ส่งพิกัดล่าสุดไม่เกิน rider.location_fresh_minutes
     * + ประเภทส่งของ + ยินยอมแชร์ตำแหน่งแล้ว + (จ่ายมัดจำแล้ว เฉพาะเมื่อเปิด rider.require_deposit)
     */
    public function scopeAvailableForDelivery($query, ?DeliveryFeeCalculator $config = null)
    {
        $config ??= app(DeliveryFeeCalculator::class);

        $query->approved()
            ->whereNull('suspended_at')
            ->where('availability', 'online')
            ->whereNotNull('share_location_consent_at')
            ->where('last_location_update', '>=', now()->subMinutes(max(1, $config->intSetting('rider.location_fresh_minutes'))))
            ->where(function ($q) {
                $q->whereIn('rider_type', ['delivery', 'both'])->orWhereNull('rider_type');
            });

        if ($config->boolSetting('rider.require_deposit')) {
            $query->where('deposit_status', 'paid');
        }

        return $query;
    }

    /**
     * Scope สำหรับไรเดอร์เซอร์วิส
     */
    public function scopeServiceRiders($query)
    {
        return $query->whereIn('rider_type', ['service', 'both']);
    }

    /**
     * Scope สำหรับไรเดอร์ที่เชื่อมกับตลาดสด
     */
    public function scopeFreshMarketLinked($query)
    {
        return $query->where('fresh_market_linked', true);
    }

    /**
     * Scope สำหรับไรเดอร์ที่อยู่ใกล้พิกัดที่กำหนด (MySQL เท่านั้น — ใช้ acos)
     */
    public function scopeNearby($query, $latitude, $longitude, $radiusKm = 5)
    {
        // Haversine formula สำหรับคำนวณระยะทาง
        return $query->selectRaw('*,
            (6371 * acos(cos(radians(?)) * cos(radians(last_latitude)) *
            cos(radians(last_longitude) - radians(?)) +
            sin(radians(?)) * sin(radians(last_latitude)))) AS distance_km',
            [$latitude, $longitude, $latitude])
            ->having('distance_km', '<=', $radiusKm)
            ->orderBy('distance_km');
    }

    // =====================================================
    // Accessors
    // =====================================================

    /**
     * ชื่อไรเดอร์ (alias ของ full_name — โค้ด/วิวเก่าเรียก ->name)
     */
    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['full_name'] ?? '');
    }

    /**
     * ตรวจสอบว่าได้รับสิทธิ์ทั้งหมดหรือยัง
     */
    public function getHasAllPermissionsAttribute(): bool
    {
        return $this->gps_permission_granted
            && $this->camera_permission_granted
            && $this->microphone_permission_granted
            && $this->notification_permission_granted;
    }

    /**
     * ชื่อสถานะภาษาไทย
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'รอตรวจสอบ',
            'approved' => 'อนุมัติแล้ว',
            'rejected' => 'ถูกปฏิเสธ',
            'suspended' => 'ถูกระงับ',
            'inactive' => 'ไม่ใช้งาน',
            default => 'ไม่ทราบ',
        };
    }

    /**
     * ชื่อสถานะพร้อมรับงานภาษาไทย
     */
    public function getAvailabilityTextAttribute(): string
    {
        return match ($this->availability) {
            'online' => 'พร้อมรับงาน',
            'offline' => 'ออฟไลน์',
            'busy' => 'กำลังส่งงาน',
            default => 'ไม่ทราบ',
        };
    }

    /**
     * ชื่อยานพาหนะภาษาไทย
     */
    public function getVehicleTypeTextAttribute(): string
    {
        return match ($this->vehicle_type) {
            'motorcycle' => 'มอเตอร์ไซค์',
            'car' => 'รถยนต์',
            'bicycle' => 'จักรยาน',
            'walk' => 'เดินเท้า',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * อัตราการทำงานสำเร็จ
     */
    public function getCompletionRateAttribute(): float
    {
        if ($this->total_jobs == 0) {
            return 0;
        }

        return round(($this->completed_jobs / $this->total_jobs) * 100, 2);
    }

    // =====================================================
    // สถานะงาน / สิทธิ์รับงาน
    // =====================================================

    /**
     * งานที่กำลังทำอยู่ (accepted → delivering) ถ้ามี
     */
    public function activeJob(): ?RiderJob
    {
        return RiderJob::where('rider_id', $this->id)
            ->whereIn('status', RiderJob::ACTIVE_STATUSES)
            ->latest('id')
            ->first();
    }

    /**
     * มีงานที่กำลังทำอยู่หรือไม่
     */
    public function hasActiveJob(): bool
    {
        return RiderJob::where('rider_id', $this->id)
            ->whereIn('status', RiderJob::ACTIVE_STATUSES)
            ->exists();
    }

    /**
     * ส่งพิกัดล่าสุดภายใน N นาทีหรือไม่ (ค่าเริ่มต้น rider.location_fresh_minutes = 15)
     */
    public function hasFreshLocation(?int $minutes = null): bool
    {
        $minutes ??= app(DeliveryFeeCalculator::class)->intSetting('rider.location_fresh_minutes');

        return $this->last_location_update !== null
            && $this->last_latitude !== null
            && $this->last_longitude !== null
            && $this->last_location_update->greaterThanOrEqualTo(now()->subMinutes(max(1, $minutes)));
    }

    /**
     * ยินยอมแชร์ตำแหน่งให้ลูกค้าระหว่างงานแล้วหรือยัง
     */
    public function hasLocationConsent(): bool
    {
        return $this->share_location_consent_at !== null;
    }

    /**
     * บันทึกความยินยอมแชร์ตำแหน่ง (ครั้งเดียวพอ)
     */
    public function grantLocationConsent(): void
    {
        if (! $this->share_location_consent_at) {
            $this->forceFill(['share_location_consent_at' => now()])->save();
        }
    }

    /**
     * ยอดเงินในวอลเลตของไรเดอร์ (ใช้เป็นวงเงิน COD)
     */
    public function walletBalance(): float
    {
        $balance = Wallet::where('user_id', $this->user_id)->value('balance');

        return round((float) ($balance ?? 0), 2);
    }

    /**
     * วงเงิน COD ที่รับได้ตอนนี้ = ยอดวอลเลต
     * (รับงาน COD ได้เมื่อ ยอดวอลเลต ≥ cod_amount − rider_earnings ของงาน)
     */
    public function codCreditAvailable(): float
    {
        return max(0.0, $this->walletBalance());
    }

    /**
     * เหตุผลที่ "เปิดรับงาน (online)" ไม่ได้ — null = เปิดได้
     *
     * @return array{code: string, message: string}|null
     */
    public function onlineBlockReason(): ?array
    {
        if ($this->status === 'suspended' || $this->suspended_at !== null) {
            return ['code' => 'SUSPENDED', 'message' => 'บัญชีไรเดอร์ถูกระงับ'.($this->suspension_reason ? ': '.$this->suspension_reason : '')];
        }

        if ($this->status === 'pending') {
            return ['code' => 'PENDING_REVIEW', 'message' => 'ใบสมัครไรเดอร์อยู่ระหว่างตรวจสอบ'];
        }

        if ($this->status === 'rejected') {
            return ['code' => 'REJECTED', 'message' => 'ใบสมัครไรเดอร์ไม่ผ่านการอนุมัติ'.($this->rejection_reason ? ': '.$this->rejection_reason : '')];
        }

        if ($this->status !== 'approved') {
            return ['code' => 'NOT_APPROVED', 'message' => 'บัญชีไรเดอร์ยังไม่พร้อมใช้งาน'];
        }

        if ($this->user && $this->user->blocked_at) {
            return ['code' => 'USER_BLOCKED', 'message' => 'บัญชีผู้ใช้ถูกระงับ'];
        }

        // เปลี่ยนยานพาหนะ/บัตรประชาชน/ใบขับขี่/ทะเบียนรถหลังอนุมัติ → รอแอดมินตรวจก่อนรับงานต่อ
        // (กันเปลี่ยนเป็นมอเตอร์ไซค์โดยไม่มีใบขับขี่ หรือเปลี่ยนรูปบัตรเป็นของคนอื่นแล้ววิ่งงานต่อ)
        if ($this->getAttribute('documents_changed_at') !== null) {
            return ['code' => 'DOCUMENTS_REVIEW_PENDING', 'message' => 'เอกสารหรือยานพาหนะที่เปลี่ยนใหม่รอทีมงานตรวจสอบ ระหว่างนี้ยังรับงานไม่ได้'];
        }

        if (app(DeliveryFeeCalculator::class)->boolSetting('rider.require_deposit') && ! $this->hasDeposit()) {
            return ['code' => 'DEPOSIT_REQUIRED', 'message' => 'กรุณาวางเงินประกันไรเดอร์ก่อนเริ่มรับงาน'];
        }

        return null;
    }

    /**
     * เหตุผลที่ "รับงาน" ไม่ได้ตอนนี้ — null = รับได้
     *
     * @return array{code: string, message: string}|null
     */
    public function acceptBlockReason(): ?array
    {
        if ($reason = $this->onlineBlockReason()) {
            return $reason;
        }

        if (! $this->hasLocationConsent()) {
            return ['code' => 'CONSENT_REQUIRED', 'message' => 'กรุณายินยอมให้ลูกค้าเห็นตำแหน่งของคุณระหว่างส่งงานก่อนรับงานแรก'];
        }

        if ($this->availability === 'busy' || $this->hasActiveJob()) {
            return ['code' => 'HAS_ACTIVE_JOB', 'message' => 'คุณมีงานที่ยังไม่เสร็จอยู่'];
        }

        if ($this->availability !== 'online') {
            return ['code' => 'OFFLINE', 'message' => 'กรุณาเปิดรับงานก่อน'];
        }

        if (! $this->hasFreshLocation()) {
            return ['code' => 'LOCATION_STALE', 'message' => 'ยังไม่ได้รับตำแหน่ง GPS ล่าสุด กรุณาเปิด GPS แล้วลองใหม่'];
        }

        return null;
    }

    /**
     * ตรวจสอบว่าไรเดอร์สามารถรับงานได้ตอนนี้
     */
    public function canAcceptJobs(): bool
    {
        return $this->acceptBlockReason() === null;
    }

    /**
     * เปลี่ยนสถานะการรับงาน online|offline (จากปุ่มในแอป)
     *
     * - มีงานค้างอยู่ → เปลี่ยนไม่ได้ (สถานะเป็น busy จนงานจบ)
     * - online ต้องผ่าน onlineBlockReason()
     *
     * @throws RiderJobException
     */
    public function setAvailability(string $availability): void
    {
        if (! in_array($availability, ['online', 'offline'], true)) {
            throw RiderJobException::invalidAvailability();
        }

        if ($active = $this->activeJob()) {
            throw RiderJobException::hasActiveJob($active->id);
        }

        if ($availability === 'online' && ($reason = $this->onlineBlockReason())) {
            throw RiderJobException::notEligible($reason['message'], $reason['code']);
        }

        $this->forceFill(['availability' => $availability])->save();
    }

    /**
     * ตั้งสถานะหลังจบงาน (ไม่ throw): กลับไป online ถ้ายังมีสิทธิ์ ไม่งั้น offline
     */
    public function refreshAvailabilityAfterJob(): void
    {
        $this->refresh();

        if ($this->hasActiveJob()) {
            $availability = 'busy';
        } else {
            $availability = $this->onlineBlockReason() === null ? 'online' : 'offline';
        }

        if ($this->availability !== $availability) {
            $this->forceFill(['availability' => $availability])->save();
        }
    }

    // =====================================================
    // Methods
    // =====================================================

    /**
     * อัปเดตตำแหน่ง GPS
     */
    public function updateLocation(float $latitude, float $longitude): void
    {
        $this->update([
            'last_latitude' => $latitude,
            'last_longitude' => $longitude,
            'last_location_update' => now(),
        ]);
    }

    /**
     * ตั้งค่าสถานะออนไลน์
     *
     * @throws RiderJobException เมื่อยังไม่มีสิทธิ์รับงาน
     */
    public function goOnline(): void
    {
        $this->setAvailability('online');
    }

    /**
     * ตั้งค่าสถานะออฟไลน์ (ใช้โดยระบบ เช่น auto-offline / ระงับบัญชี — ไม่เช็คงานค้าง)
     */
    public function goOffline(): void
    {
        $this->forceFill(['availability' => 'offline'])->save();
    }

    /**
     * ตั้งค่าสถานะกำลังส่งงาน
     */
    public function setBusy(): void
    {
        $this->forceFill(['availability' => 'busy'])->save();
    }

    /**
     * บันทึกสิทธิ์ที่ได้รับ
     */
    public function grantPermissions(array $permissions): void
    {
        $updateData = [];

        if (isset($permissions['gps'])) {
            $updateData['gps_permission_granted'] = $permissions['gps'];
        }
        if (isset($permissions['camera'])) {
            $updateData['camera_permission_granted'] = $permissions['camera'];
        }
        if (isset($permissions['microphone'])) {
            $updateData['microphone_permission_granted'] = $permissions['microphone'];
        }
        if (isset($permissions['notification'])) {
            $updateData['notification_permission_granted'] = $permissions['notification'];
        }

        if (! empty($updateData)) {
            $updateData['permissions_granted_at'] = now();
            $this->update($updateData);
        }
    }

    // =====================================================
    // อนุมัติ / ปฏิเสธ / ระงับ (แอดมิน)
    // =====================================================

    /**
     * อนุมัติไรเดอร์ + แจ้งในแอป
     */
    public function approve(User $admin): void
    {
        $this->forceFill([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'rejection_reason' => null,
            'rejected_at' => null,
            'rejected_by' => null,
            'suspension_reason' => null,
            'suspended_at' => null,
            'suspended_by' => null,
        ])->save();

        app(RiderNotificationService::class)->notifyUser(
            (int) $this->user_id,
            'rider_account',
            'อนุมัติเป็นไรเดอร์แล้ว',
            'ยินดีด้วย! บัญชีไรเดอร์ของคุณได้รับการอนุมัติ เปิดแอปแล้วกด "เริ่มรับงาน" ได้เลย',
            ['type' => 'rider_account', 'event' => 'approved', 'screen' => 'rider'],
        );
    }

    /**
     * ปฏิเสธใบสมัคร + แจ้งในแอป
     */
    public function reject(User $admin, string $reason): void
    {
        $this->forceFill([
            'status' => 'rejected',
            'availability' => 'offline',
            'rejection_reason' => $reason,
            'rejected_at' => now(),
            'rejected_by' => $admin->id,
        ])->save();

        app(RiderNotificationService::class)->notifyUser(
            (int) $this->user_id,
            'rider_account',
            'ใบสมัครไรเดอร์ไม่ผ่าน',
            'เหตุผล: '.$reason.' — แก้ไขข้อมูลแล้วส่งใหม่ได้ในแอป',
            ['type' => 'rider_account', 'event' => 'rejected', 'screen' => 'rider'],
        );
    }

    /**
     * ระงับไรเดอร์: บังคับออฟไลน์ทันที + บันทึกเหตุผล
     *
     * งานที่ค้างอยู่ต้องจัดการต่อด้วย RiderJobService::handleRiderSuspended()
     * (งานก่อนรับของ → คืนเข้าคิว, งานที่รับของแล้ว → แจ้งแอดมินให้มอบหมายใหม่)
     */
    public function suspend(User $admin, string $reason): void
    {
        DB::transaction(function () use ($admin, $reason) {
            $this->forceFill([
                'status' => 'suspended',
                'availability' => $this->hasActiveJob() ? 'busy' : 'offline',
                'suspension_reason' => $reason,
                'suspended_at' => now(),
                'suspended_by' => $admin->id,
            ])->save();
        });

        app(RiderNotificationService::class)->notifyUser(
            (int) $this->user_id,
            'rider_account',
            'บัญชีไรเดอร์ถูกระงับ',
            'เหตุผล: '.$reason.' — ติดต่อทีมงานหากมีข้อสงสัย',
            ['type' => 'rider_account', 'event' => 'suspended', 'screen' => 'rider'],
            null,
            'high',
        );
    }

    /**
     * ยกเลิกการระงับ (กลับเป็น approved แต่ยังออฟไลน์ ให้ไรเดอร์กดเปิดเอง)
     */
    public function unsuspend(User $admin): void
    {
        $this->forceFill([
            'status' => 'approved',
            'availability' => 'offline',
            'suspension_reason' => null,
            'suspended_at' => null,
            'suspended_by' => null,
        ])->save();

        app(RiderNotificationService::class)->notifyUser(
            (int) $this->user_id,
            'rider_account',
            'ยกเลิกการระงับบัญชีไรเดอร์แล้ว',
            'บัญชีไรเดอร์กลับมาใช้งานได้แล้ว กด "เริ่มรับงาน" เพื่อรับงานต่อ',
            ['type' => 'rider_account', 'event' => 'unsuspended', 'screen' => 'rider'],
        );
    }

    // =====================================================
    // Deposit & Integration Methods
    // =====================================================

    /**
     * ตรวจสอบว่าจ่ายค่าประกันแล้วหรือยัง
     */
    public function hasDeposit(): bool
    {
        return $this->deposit_status === 'paid';
    }

    /**
     * ตรวจสอบว่าเป็นไรเดอร์เซอร์วิส (ช่าง)
     */
    public function isServiceRider(): bool
    {
        return in_array($this->rider_type, ['service', 'both']);
    }

    /**
     * ตรวจสอบว่าเป็นไรเดอร์ส่งของ
     */
    public function isDeliveryRider(): bool
    {
        return $this->rider_type === null || in_array($this->rider_type, ['delivery', 'both']);
    }

    /**
     * ดึงหมวดหมู่บริการ
     */
    public function getServiceCategories(): array
    {
        return $this->service_categories ?? [];
    }

    /**
     * ชื่อประเภทไรเดอร์ภาษาไทย
     */
    public function getRiderTypeTextAttribute(): string
    {
        return match ($this->rider_type) {
            'delivery' => 'ไรเดอร์ส่งของ',
            'service' => 'ช่างบริการ',
            'both' => 'ไรเดอร์ + ช่างบริการ',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * ชื่อสถานะค่าประกันภาษาไทย
     */
    public function getDepositStatusTextAttribute(): string
    {
        return match ($this->deposit_status) {
            'pending' => 'รอชำระ',
            'paid' => 'ชำระแล้ว',
            'refunded' => 'คืนเงินแล้ว',
            default => 'ไม่ทราบ',
        };
    }

    /**
     * บันทึกการชำระค่าประกัน
     */
    public function markDepositPaid(string $transactionId, float $amount): void
    {
        $this->update([
            'deposit_amount' => $amount,
            'deposit_status' => 'paid',
            'deposit_paid_at' => now(),
            'deposit_transaction_id' => $transactionId,
        ]);
    }

    /**
     * คืนค่าประกัน
     */
    public function refundDeposit(): void
    {
        $this->update([
            'deposit_status' => 'refunded',
        ]);
    }

    /**
     * เชื่อมกับตลาดสด
     */
    public function linkToFreshMarket(string $lineUserId): void
    {
        $this->update([
            'line_user_id' => $lineUserId,
            'fresh_market_linked' => true,
        ]);
    }

    // =====================================================
    // Preference Methods
    // =====================================================

    /**
     * ตรวจสอบว่าไรเดอร์ยอมรับงานประเภทนี้หรือไม่
     */
    public function acceptsJobType(string $jobType): bool
    {
        // ถ้าไม่ได้ตั้งค่า = รับทุกงาน
        if (empty($this->preferred_job_types)) {
            return true;
        }

        // 'delivery' = ส่งของทั่วไป → รับงานส่งของทุกประเภท
        if (in_array('delivery', $this->preferred_job_types, true)) {
            return true;
        }

        return in_array($jobType, $this->preferred_job_types);
    }

    /**
     * ตรวจสอบว่าระยะทางอยู่ในรัศมีที่ไรเดอร์ยอมรับหรือไม่
     */
    public function acceptsDistance(float $distanceKm): bool
    {
        if (! $this->preferred_radius_km || $this->preferred_radius_km <= 0) {
            return true;
        }

        return $distanceKm <= (float) $this->preferred_radius_km;
    }

    /**
     * ตรวจสอบว่าค่าส่งถึงขั้นต่ำที่ไรเดอร์ยอมรับหรือไม่
     */
    public function acceptsFee(float $fee): bool
    {
        if (! $this->preferred_min_fee || $this->preferred_min_fee <= 0) {
            return true;
        }

        return $fee >= (float) $this->preferred_min_fee;
    }

    /**
     * ตรวจสอบว่าไรเดอร์ตรงตามเงื่อนไขทั้งหมดของงาน
     */
    public function matchesJob(string $jobType, float $distanceKm, float $fee): bool
    {
        return $this->acceptsJobType($jobType)
            && $this->acceptsDistance($distanceKm)
            && $this->acceptsFee($fee);
    }

    /**
     * อัพเดท preferences ของไรเดอร์
     */
    public function updatePreferences(array $preferences): void
    {
        $data = [];

        if (array_key_exists('job_types', $preferences)) {
            $data['preferred_job_types'] = $preferences['job_types'];
        }
        if (array_key_exists('radius_km', $preferences)) {
            $data['preferred_radius_km'] = $preferences['radius_km'];
        }
        if (array_key_exists('min_fee', $preferences)) {
            $data['preferred_min_fee'] = $preferences['min_fee'];
        }

        if (! empty($data)) {
            $this->update($data);
        }
    }
}
