<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AI Core Tenant Model
 *
 * จัดการข้อมูล tenants สำหรับ multi-tenancy
 * รองรับการให้เช่า AI features แบบ SaaS
 *
 * @property int $id
 * @property int|null $user_id เจ้าของ tenant
 * @property string $tenant_key รหัสเฉพาะของ tenant
 * @property string $tenant_name ชื่อ tenant
 * @property string $tenant_type ประเภท tenant
 * @property string $status สถานะ tenant
 * @property bool $is_enabled เปิด/ปิดการใช้งาน
 * @property string|null $subscription_tier แพ็กเกจที่สมัคร
 * @property \Carbon\Carbon|null $subscription_starts_at วันที่เริ่มสมัคร
 * @property \Carbon\Carbon|null $subscription_ends_at วันที่หมดอายุ
 * @property bool $auto_renew ต่ออายุอัตโนมัติ
 * @property array|null $global_quotas โควต้ารวม
 * @property array|null $feature_limits ข้อจำกัดเฉพาะ feature
 * @property string|null $contact_email อีเมลติดต่อ
 * @property string|null $contact_phone เบอร์โทรติดต่อ
 * @property string|null $billing_address ที่อยู่สำหรับเรียกเก็บเงิน
 * @property string|null $tax_id เลขประจำตัวผู้เสียภาษี
 * @property array|null $settings การตั้งค่า
 * @property array|null $metadata ข้อมูลเพิ่มเติม
 */
class AICoreTenant extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_core_tenants';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'tenant_key',
        'tenant_name',
        'tenant_type',
        'status',
        'is_enabled',
        'subscription_tier',
        'subscription_starts_at',
        'subscription_ends_at',
        'auto_renew',
        'global_quotas',
        'feature_limits',
        'contact_email',
        'contact_phone',
        'billing_address',
        'tax_id',
        'settings',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_enabled' => 'boolean',
        'auto_renew' => 'boolean',
        'global_quotas' => 'array',
        'feature_limits' => 'array',
        'settings' => 'array',
        'metadata' => 'array',
        'subscription_starts_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ User (เจ้าของ tenant)
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ Feature Access
     *
     * @return HasMany
     */
    public function featureAccess(): HasMany
    {
        return $this->hasMany(AICoreFeatureAccess::class, 'tenant_id');
    }

    /**
     * ความสัมพันธ์กับ Usage Logs
     *
     * @return HasMany
     */
    public function usageLogs(): HasMany
    {
        return $this->hasMany(AICoreUsageLog::class, 'tenant_id');
    }

    /**
     * ความสัมพันธ์กับ Quotas
     *
     * @return HasMany
     */
    public function quotas(): HasMany
    {
        return $this->hasMany(AICoreQuota::class, 'tenant_id');
    }

    /**
     * ความสัมพันธ์กับ Schedules
     *
     * @return HasMany
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(AICoreSchedule::class, 'tenant_id');
    }

    /**
     * ความสัมพันธ์กับ Alerts
     *
     * @return HasMany
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(AICoreAlert::class, 'tenant_id');
    }

    /**
     * ความสัมพันธ์กับ Settings
     *
     * @return HasMany
     */
    public function settings(): HasMany
    {
        return $this->hasMany(AICoreSetting::class, 'tenant_id');
    }

    /**
     * Scope: เฉพาะ tenants ที่ active
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('is_enabled', true);
    }

    /**
     * Scope: เฉพาะ tenants ที่ subscription ยังไม่หมดอายุ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSubscriptionActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('subscription_ends_at')
                ->orWhere('subscription_ends_at', '>', now());
        });
    }

    /**
     * Scope: กรองตาม subscription tier
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $tier
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSubscriptionTier($query, string $tier)
    {
        return $query->where('subscription_tier', $tier);
    }

    /**
     * ตรวจสอบว่า tenant active หรือไม่
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->is_enabled;
    }

    /**
     * ตรวจสอบว่า subscription หมดอายุหรือยัง
     *
     * @return bool
     */
    public function isSubscriptionExpired(): bool
    {
        if (!$this->subscription_ends_at) {
            return false;
        }

        return $this->subscription_ends_at->isPast();
    }

    /**
     * ตรวจสอบว่า subscription ใกล้หมดอายุหรือไม่ (ภายใน X วัน)
     *
     * @param int $days จำนวนวันที่ถือว่าใกล้หมดอายุ
     * @return bool
     */
    public function isSubscriptionExpiringSoon(int $days = 7): bool
    {
        if (!$this->subscription_ends_at) {
            return false;
        }

        return $this->subscription_ends_at->diffInDays(now()) <= $days
            && !$this->isSubscriptionExpired();
    }

    /**
     * ตรวจสอบว่ามีสิทธิ์เข้าถึง feature หรือไม่
     *
     * @param string $featureKey
     * @return bool
     */
    public function hasFeatureAccess(string $featureKey): bool
    {
        return $this->featureAccess()
            ->whereHas('feature', function ($query) use ($featureKey) {
                $query->where('feature_key', $featureKey)
                    ->where('is_enabled', true);
            })
            ->where('is_enabled', true)
            ->where('status', 'approved')
            ->exists();
    }

    /**
     * ดึงการตั้งค่าเฉพาะจาก settings
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * อัปเดตการตั้งค่าเฉพาะใน settings
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function setSetting(string $key, $value): bool
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        return $this->update(['settings' => $settings]);
    }
}
