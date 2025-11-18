<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AI Core Feature Model
 *
 * จัดการ registry ของ AI features ทั้งหมดในระบบ
 * เป็น Model หลักที่ควบคุมการเข้าถึง AI features
 *
 * @property int $id
 * @property string $feature_key รหัสเฉพาะของ feature
 * @property string $feature_name ชื่อ feature ภาษาไทย
 * @property string|null $feature_name_en ชื่อ feature ภาษาอังกฤษ
 * @property string|null $description คำอธิบาย feature
 * @property string $category หมวดหมู่
 * @property string|null $icon ชื่อไอคอน
 * @property int $display_order ลำดับการแสดงผล
 * @property bool $is_enabled เปิด/ปิดใช้งาน
 * @property bool $is_visible แสดง/ซ่อนในเมนู
 * @property bool $requires_approval ต้องอนุมัติก่อนใช้งาน
 * @property string $quota_type ประเภทโควต้า
 * @property int|null $default_quota_limit จำนวนโควต้าเริ่มต้น
 * @property string|null $quota_reset_period รอบการรีเซ็ตโควต้า
 * @property string|null $pricing_tier ระดับราคา
 * @property float $base_price ราคาฐาน
 * @property float $usage_price ราคาต่อหน่วย
 * @property array|null $allowed_roles Roles ที่อนุญาต
 * @property array|null $required_permissions Permissions ที่ต้องมี
 * @property array|null $config การตั้งค่าเพิ่มเติม
 * @property array|null $metadata ข้อมูลเพิ่มเติม
 * @property string|null $provider_name ผู้ให้บริการ AI
 * @property string|null $provider_version เวอร์ชั่นของผู้ให้บริการ
 */
class AICoreFeature extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_core_features';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'feature_key',
        'feature_name',
        'feature_name_en',
        'description',
        'category',
        'icon',
        'display_order',
        'is_enabled',
        'is_visible',
        'requires_approval',
        'quota_type',
        'default_quota_limit',
        'quota_reset_period',
        'pricing_tier',
        'base_price',
        'usage_price',
        'allowed_roles',
        'required_permissions',
        'config',
        'metadata',
        'provider_name',
        'provider_version',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_enabled' => 'boolean',
        'is_visible' => 'boolean',
        'requires_approval' => 'boolean',
        'default_quota_limit' => 'integer',
        'display_order' => 'integer',
        'base_price' => 'decimal:2',
        'usage_price' => 'decimal:2',
        'allowed_roles' => 'array',
        'required_permissions' => 'array',
        'config' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ Feature Access
     *
     * @return HasMany
     */
    public function featureAccess(): HasMany
    {
        return $this->hasMany(AICoreFeatureAccess::class, 'feature_id');
    }

    /**
     * ความสัมพันธ์กับ Usage Logs
     *
     * @return HasMany
     */
    public function usageLogs(): HasMany
    {
        return $this->hasMany(AICoreUsageLog::class, 'feature_id');
    }

    /**
     * ความสัมพันธ์กับ Quotas
     *
     * @return HasMany
     */
    public function quotas(): HasMany
    {
        return $this->hasMany(AICoreQuota::class, 'feature_id');
    }

    /**
     * ความสัมพันธ์กับ Schedules
     *
     * @return HasMany
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(AICoreSchedule::class, 'feature_id');
    }

    /**
     * ความสัมพันธ์กับ Alerts
     *
     * @return HasMany
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(AICoreAlert::class, 'feature_id');
    }

    /**
     * ความสัมพันธ์กับ Settings
     *
     * @return HasMany
     */
    public function settings(): HasMany
    {
        return $this->hasMany(AICoreSetting::class, 'feature_id');
    }

    /**
     * Scope: เฉพาะ features ที่เปิดใช้งาน
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope: เฉพาะ features ที่แสดงในเมนู
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope: กรองตามหมวดหมู่
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $category
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: กรองตาม pricing tier
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $tier
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePricingTier($query, string $tier)
    {
        return $query->where('pricing_tier', $tier);
    }

    /**
     * ตรวจสอบว่า feature ต้องมีโควต้าหรือไม่
     *
     * @return bool
     */
    public function hasQuota(): bool
    {
        return $this->quota_type !== 'none';
    }

    /**
     * ตรวจสอบว่า feature ต้องชำระเงินหรือไม่
     *
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->pricing_tier !== 'free' && ($this->base_price > 0 || $this->usage_price > 0);
    }

    /**
     * ตรวจสอบว่าผู้ใช้มีสิทธิ์ใช้ feature นี้หรือไม่ (ตาม role)
     *
     * @param User|null $user
     * @return bool
     */
    public function canAccessByRole(?User $user = null): bool
    {
        if (!$user) {
            return false;
        }

        // ถ้าไม่ได้กำหนด allowed_roles = ทุกคนใช้ได้
        if (empty($this->allowed_roles)) {
            return true;
        }

        // ตรวจสอบ role ของ user
        return $user->hasAnyRole($this->allowed_roles);
    }

    /**
     * ดึงการตั้งค่าเฉพาะจาก config
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getConfig(string $key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * อัปเดตการตั้งค่าเฉพาะใน config
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function setConfig(string $key, $value): bool
    {
        $config = $this->config ?? [];
        data_set($config, $key, $value);
        return $this->update(['config' => $config]);
    }
}
