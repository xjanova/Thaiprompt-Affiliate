<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AI Core Setting Model
 *
 * จัดการการตั้งค่าต่างๆ ของระบบ AI Core
 * รองรับการตั้งค่าแบบ global และ tenant-specific
 *
 * @property int $id
 * @property int|null $tenant_id Tenant เฉพาะ (null = global)
 * @property int|null $feature_id Feature เฉพาะ (null = general)
 * @property string $setting_key รหัสการตั้งค่า
 * @property string|null $setting_group กลุ่มการตั้งค่า
 * @property string $setting_name ชื่อการตั้งค่า
 * @property string|null $description คำอธิบาย
 * @property string $value_type ประเภทข้อมูล
 * @property string|null $value ค่าที่ตั้ง
 * @property string|null $default_value ค่า default
 * @property string|null $validation_rules กฎการตรวจสอบ
 * @property array|null $allowed_values ค่าที่อนุญาต
 * @property string|null $min_value ค่าต่ำสุด
 * @property string|null $max_value ค่าสูงสุด
 * @property string $input_type ประเภท input
 * @property int $display_order ลำดับการแสดง
 * @property bool $is_visible แสดงใน UI
 * @property bool $is_editable สามารถแก้ไขได้
 * @property bool $is_sensitive ข้อมูลละเอียดอ่อน
 * @property array|null $required_permissions Permissions ที่ต้องมี
 * @property bool $can_be_overridden สามารถ override ได้
 * @property int|null $overridden_from Setting ต้นฉบับ
 * @property string|null $environment Environment เฉพาะ
 * @property int|null $last_updated_by ผู้แก้ไขล่าสุด
 * @property string|null $change_reason เหตุผลในการเปลี่ยนแปลง
 * @property array|null $metadata Metadata
 */
class AICoreSetting extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_core_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'feature_id',
        'setting_key',
        'setting_group',
        'setting_name',
        'description',
        'value_type',
        'value',
        'default_value',
        'validation_rules',
        'allowed_values',
        'min_value',
        'max_value',
        'input_type',
        'display_order',
        'is_visible',
        'is_editable',
        'is_sensitive',
        'required_permissions',
        'can_be_overridden',
        'overridden_from',
        'environment',
        'last_updated_by',
        'change_reason',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_visible' => 'boolean',
        'is_editable' => 'boolean',
        'is_sensitive' => 'boolean',
        'can_be_overridden' => 'boolean',
        'display_order' => 'integer',
        'allowed_values' => 'array',
        'required_permissions' => 'array',
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
     * ความสัมพันธ์กับผู้แก้ไขล่าสุด
     *
     * @return BelongsTo
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    /**
     * ความสัมพันธ์กับ setting ต้นฉบับที่ override มา
     *
     * @return BelongsTo
     */
    public function parentSetting(): BelongsTo
    {
        return $this->belongsTo(AICoreSetting::class, 'overridden_from');
    }

    /**
     * Scope: เฉพาะ global settings
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('tenant_id')->whereNull('feature_id');
    }

    /**
     * Scope: กรองตาม setting group
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $group
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGroup($query, string $group)
    {
        return $query->where('setting_group', $group);
    }

    /**
     * Scope: เฉพาะ settings ที่แสดงใน UI
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope: เฉพาะ settings ที่แก้ไขได้
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEditable($query)
    {
        return $query->where('is_editable', true);
    }

    /**
     * ตรวจสอบว่าเป็น global setting หรือไม่
     *
     * @return bool
     */
    public function isGlobal(): bool
    {
        return $this->tenant_id === null && $this->feature_id === null;
    }

    /**
     * ตรวจสอบว่าเป็น tenant-specific setting หรือไม่
     *
     * @return bool
     */
    public function isTenantSpecific(): bool
    {
        return $this->tenant_id !== null;
    }

    /**
     * ตรวจสอบว่าเป็น feature-specific setting หรือไม่
     *
     * @return bool
     */
    public function isFeatureSpecific(): bool
    {
        return $this->feature_id !== null;
    }

    /**
     * ดึงค่าที่ตั้งไว้ (cast ตามประเภท)
     *
     * @return mixed
     */
    public function getCastedValue()
    {
        $value = $this->value ?? $this->default_value;

        if ($value === null) {
            return null;
        }

        return match ($this->value_type) {
            'integer' => (int) $value,
            'float' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json', 'array' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * ตั้งค่าใหม่ (cast ก่อนบันทึก)
     *
     * @param mixed $value
     * @param int|null $userId ผู้แก้ไข
     * @param string|null $reason เหตุผล
     * @return bool
     */
    public function setValue($value, ?int $userId = null, ?string $reason = null): bool
    {
        // Cast ค่าให้ตรงกับประเภท
        $castedValue = match ($this->value_type) {
            'integer' => (string) (int) $value,
            'float' => (string) (float) $value,
            'boolean' => $value ? '1' : '0',
            'json', 'array' => is_string($value) ? $value : json_encode($value),
            default => (string) $value,
        };

        $updates = ['value' => $castedValue];

        if ($userId) {
            $updates['last_updated_by'] = $userId;
        }

        if ($reason) {
            $updates['change_reason'] = $reason;
        }

        return $this->update($updates);
    }

    /**
     * ตรวจสอบว่าค่าที่ใส่มาถูกต้องหรือไม่
     *
     * @param mixed $value
     * @return bool
     */
    public function isValidValue($value): bool
    {
        // ตรวจสอบ allowed_values
        if ($this->allowed_values && !in_array($value, $this->allowed_values)) {
            return false;
        }

        // ตรวจสอบ min/max สำหรับ integer/float
        if (in_array($this->value_type, ['integer', 'float'])) {
            if ($this->min_value !== null && $value < $this->min_value) {
                return false;
            }

            if ($this->max_value !== null && $value > $this->max_value) {
                return false;
            }
        }

        // TODO: Implement validation_rules checking

        return true;
    }

    /**
     * รีเซ็ตค่ากลับเป็น default
     *
     * @return bool
     */
    public function resetToDefault(): bool
    {
        return $this->update(['value' => $this->default_value]);
    }

    /**
     * สร้าง override setting ใหม่
     *
     * @param int|null $tenantId
     * @param int|null $featureId
     * @param mixed $value
     * @return static|null
     */
    public function createOverride(?int $tenantId = null, ?int $featureId = null, $value = null): ?static
    {
        if (!$this->can_be_overridden) {
            return null;
        }

        $attributes = $this->only([
            'setting_key',
            'setting_group',
            'setting_name',
            'description',
            'value_type',
            'default_value',
            'validation_rules',
            'allowed_values',
            'min_value',
            'max_value',
            'input_type',
            'is_sensitive',
            'required_permissions',
        ]);

        $attributes['tenant_id'] = $tenantId;
        $attributes['feature_id'] = $featureId;
        $attributes['value'] = $value;
        $attributes['overridden_from'] = $this->id;
        $attributes['can_be_overridden'] = false; // ป้องกัน override ซ้อน

        return static::create($attributes);
    }

    /**
     * ดึงค่า setting โดยคำนึงถึง hierarchy (feature > tenant > global)
     *
     * @param string $key
     * @param int|null $tenantId
     * @param int|null $featureId
     * @param mixed $default
     * @return mixed
     */
    public static function getValue(string $key, ?int $tenantId = null, ?int $featureId = null, $default = null)
    {
        // ลำดับความสำคัญ: feature-specific > tenant-specific > global
        $query = static::where('setting_key', $key);

        // 1. ลอง feature-specific ก่อน
        if ($featureId) {
            $setting = (clone $query)
                ->where('feature_id', $featureId)
                ->where('tenant_id', $tenantId)
                ->first();

            if ($setting) {
                return $setting->getCastedValue();
            }
        }

        // 2. ลอง tenant-specific
        if ($tenantId) {
            $setting = (clone $query)
                ->where('tenant_id', $tenantId)
                ->whereNull('feature_id')
                ->first();

            if ($setting) {
                return $setting->getCastedValue();
            }
        }

        // 3. ใช้ global
        $setting = $query->global()->first();

        if ($setting) {
            return $setting->getCastedValue();
        }

        return $default;
    }
}
