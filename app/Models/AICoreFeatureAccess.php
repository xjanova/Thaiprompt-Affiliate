<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AI Core Feature Access Model
 *
 * จัดการสิทธิ์การเข้าถึง AI features ของแต่ละ tenant
 * ควบคุมว่า tenant ไหนสามารถใช้ feature ใดได้บ้าง
 *
 * @property int $id
 * @property int $tenant_id Tenant ที่มีสิทธิ์
 * @property int $feature_id Feature ที่อนุญาต
 * @property bool $is_enabled เปิด/ปิดการใช้งาน
 * @property string $access_level ระดับการเข้าถึง
 * @property int|null $quota_limit จำนวนโควต้า
 * @property string|null $quota_reset_period รอบรีเซ็ตโควต้า
 * @property int $quota_used จำนวนโควต้าที่ใช้แล้ว
 * @property \Carbon\Carbon|null $quota_reset_at วันที่รีเซ็ตโควต้าครั้งถัดไป
 * @property bool $is_trial กำลังทดลองใช้
 * @property \Carbon\Carbon|null $trial_ends_at วันที่ trial หมดอายุ
 * @property \Carbon\Carbon|null $access_starts_at วันที่เริ่มมีสิทธิ์
 * @property \Carbon\Carbon|null $access_ends_at วันที่หมดสิทธิ์
 * @property string $status สถานะการอนุมัติ
 * @property int|null $approved_by ผู้อนุมัติ
 * @property \Carbon\Carbon|null $approved_at วันที่อนุมัติ
 * @property string|null $rejection_reason เหตุผลที่ปฏิเสธ
 * @property array|null $custom_config การตั้งค่าเฉพาะ
 * @property array|null $metadata ข้อมูลเพิ่มเติม
 */
class AICoreFeatureAccess extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_core_feature_access';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'feature_id',
        'is_enabled',
        'access_level',
        'quota_limit',
        'quota_reset_period',
        'quota_used',
        'quota_reset_at',
        'is_trial',
        'trial_ends_at',
        'access_starts_at',
        'access_ends_at',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'custom_config',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_enabled' => 'boolean',
        'is_trial' => 'boolean',
        'quota_limit' => 'integer',
        'quota_used' => 'integer',
        'quota_reset_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'access_starts_at' => 'datetime',
        'access_ends_at' => 'datetime',
        'approved_at' => 'datetime',
        'custom_config' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ Tenant
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(AICoreTenant::class, 'tenant_id');
    }

    /**
     * ความสัมพันธ์กับ Feature
     *
     * @return BelongsTo
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(AICoreFeature::class, 'feature_id');
    }

    /**
     * ความสัมพันธ์กับผู้อนุมัติ
     *
     * @return BelongsTo
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope: เฉพาะ access ที่ active
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_enabled', true)
            ->where('status', 'approved')
            ->where(function ($q) {
                $q->whereNull('access_ends_at')
                    ->orWhere('access_ends_at', '>', now());
            });
    }

    /**
     * Scope: เฉพาะที่กำลัง trial
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTrial($query)
    {
        return $query->where('is_trial', true)
            ->where(function ($q) {
                $q->whereNull('trial_ends_at')
                    ->orWhere('trial_ends_at', '>', now());
            });
    }

    /**
     * Scope: เฉพาะที่รอการอนุมัติ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * ตรวจสอบว่า access ยังใช้งานได้หรือไม่
     *
     * @return bool
     */
    public function isActive(): bool
    {
        // ตรวจสอบว่าเปิดใช้งาน
        if (!$this->is_enabled || $this->status !== 'approved') {
            return false;
        }

        // ตรวจสอบว่า access ยังไม่หมดอายุ
        if ($this->access_ends_at && $this->access_ends_at->isPast()) {
            return false;
        }

        // ตรวจสอบว่า trial ยังไม่หมดอายุ
        if ($this->is_trial && $this->trial_ends_at && $this->trial_ends_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * ตรวจสอบว่ามีโควต้าเหลืออยู่หรือไม่
     *
     * @param int $amount จำนวนที่ต้องการใช้
     * @return bool
     */
    public function hasQuotaRemaining(int $amount = 1): bool
    {
        // ถ้าไม่มีการกำหนดโควต้า = ไม่จำกัด
        if ($this->quota_limit === null) {
            return true;
        }

        return ($this->quota_limit - $this->quota_used) >= $amount;
    }

    /**
     * ใช้โควต้า
     *
     * @param int $amount จำนวนที่ต้องการใช้
     * @return bool
     */
    public function consumeQuota(int $amount = 1): bool
    {
        if (!$this->hasQuotaRemaining($amount)) {
            return false;
        }

        return $this->increment('quota_used', $amount);
    }

    /**
     * รีเซ็ตโควต้า
     *
     * @return bool
     */
    public function resetQuota(): bool
    {
        $updates = ['quota_used' => 0];

        // คำนวณวันที่รีเซ็ตครั้งถัดไป
        if ($this->quota_reset_period) {
            $updates['quota_reset_at'] = match ($this->quota_reset_period) {
                'daily' => now()->addDay(),
                'weekly' => now()->addWeek(),
                'monthly' => now()->addMonth(),
                'yearly' => now()->addYear(),
                default => null,
            };
        }

        return $this->update($updates);
    }

    /**
     * อนุมัติการเข้าถึง
     *
     * @param int $approvedBy User ID ของผู้อนุมัติ
     * @return bool
     */
    public function approve(int $approvedBy): bool
    {
        return $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    /**
     * ปฏิเสธการเข้าถึง
     *
     * @param string $reason เหตุผลที่ปฏิเสธ
     * @return bool
     */
    public function reject(string $reason): bool
    {
        return $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * ระงับการเข้าถึง
     *
     * @return bool
     */
    public function suspend(): bool
    {
        return $this->update([
            'status' => 'suspended',
            'is_enabled' => false,
        ]);
    }

    /**
     * ดึงการตั้งค่าเฉพาะจาก custom_config
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getConfig(string $key, $default = null)
    {
        return data_get($this->custom_config, $key, $default);
    }

    /**
     * อัปเดตการตั้งค่าเฉพาะใน custom_config
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function setConfig(string $key, $value): bool
    {
        $config = $this->custom_config ?? [];
        data_set($config, $key, $value);
        return $this->update(['custom_config' => $config]);
    }
}
