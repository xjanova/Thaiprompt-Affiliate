<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

/**
 * Model สำหรับ Cloud GPU Configurations
 *
 * จัดการการตั้งค่า Cloud GPU สำหรับแต่ละผู้ใช้
 *
 * @property int $id
 * @property int $user_id ID ผู้ใช้
 * @property int $cloud_provider_id ID provider
 * @property string $config_name ชื่อ config
 * @property string|null $description คำอธิบาย
 * @property string|null $api_key API key (encrypted)
 * @property string|null $api_secret API secret (encrypted)
 * @property string|null $api_endpoint API endpoint
 * @property string|null $gpu_type ประเภท GPU
 * @property int $gpu_count จำนวน GPU
 * @property int|null $memory_gb RAM GB
 * @property int|null $storage_gb พื้นที่ GB
 * @property string $python_version Python version
 * @property array|null $installed_packages Packages ที่ติดตั้ง
 * @property string|null $cuda_version CUDA version
 * @property string $status สถานะ
 * @property bool $is_default Config หลักหรือไม่
 * @property \Carbon\Carbon|null $last_used_at ใช้งานล่าสุดเมื่อ
 * @property int $total_deployments จำนวน deployments
 * @property float $total_cost_usd ค่าใช้จ่ายรวม USD
 * @property int $total_gpu_hours ชั่วโมง GPU รวม
 * @property array|null $settings การตั้งค่าเพิ่มเติม
 * @property string|null $notes บันทึก
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class AiRentalCloudConfig extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_rental_cloud_configs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'cloud_provider_id',
        'config_name',
        'description',
        'api_key',
        'api_secret',
        'api_endpoint',
        'gpu_type',
        'gpu_count',
        'memory_gb',
        'storage_gb',
        'python_version',
        'installed_packages',
        'cuda_version',
        'status',
        'is_default',
        'last_used_at',
        'total_deployments',
        'total_cost_usd',
        'total_gpu_hours',
        'settings',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
        'cloud_provider_id' => 'integer',
        'gpu_count' => 'integer',
        'memory_gb' => 'integer',
        'storage_gb' => 'integer',
        'installed_packages' => 'array',
        'is_default' => 'boolean',
        'last_used_at' => 'datetime',
        'total_deployments' => 'integer',
        'total_cost_usd' => 'decimal:2',
        'total_gpu_hours' => 'integer',
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ User
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ Cloud Provider
     *
     * @return BelongsTo
     */
    public function cloudProvider(): BelongsTo
    {
        return $this->belongsTo(AiRentalCloudProvider::class, 'cloud_provider_id');
    }

    /**
     * ความสัมพันธ์กับ Deployments
     *
     * @return HasMany
     */
    public function deployments(): HasMany
    {
        return $this->hasMany(AiRentalDeployment::class, 'cloud_config_id');
    }

    /**
     * Set API key (with encryption)
     *
     * @param string|null $value
     * @return void
     */
    public function setApiKeyAttribute(?string $value): void
    {
        $this->attributes['api_key'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get API key (with decryption)
     *
     * @param string|null $value
     * @return string|null
     */
    public function getApiKeyAttribute(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set API secret (with encryption)
     *
     * @param string|null $value
     * @return void
     */
    public function setApiSecretAttribute(?string $value): void
    {
        $this->attributes['api_secret'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get API secret (with decryption)
     *
     * @param string|null $value
     * @return string|null
     */
    public function getApiSecretAttribute(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * ตรวจสอบว่า config พร้อมใช้งานหรือไม่
     *
     * @return bool
     */
    public function isReady(): bool
    {
        return $this->status === 'active' && !empty($this->api_key);
    }

    /**
     * ตรวจสอบว่ามี error หรือไม่
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return $this->status === 'error';
    }

    /**
     * ตั้งเป็น config หลัก
     *
     * @return void
     */
    public function setAsDefault(): void
    {
        // เอา default flag ออกจาก configs อื่นของ user คนเดียวกัน
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        // ตั้ง config นี้เป็น default
        $this->is_default = true;
        $this->save();
    }

    /**
     * บันทึกการใช้งาน
     *
     * @return void
     */
    public function recordUsage(): void
    {
        $this->last_used_at = now();
        $this->save();
    }

    /**
     * เพิ่มจำนวน deployments
     *
     * @return void
     */
    public function incrementDeployments(): void
    {
        $this->increment('total_deployments');
    }

    /**
     * เพิ่มค่าใช้จ่าย
     *
     * @param float $cost ค่าใช้จ่าย USD
     * @param int $hours ชั่วโมง GPU
     * @return void
     */
    public function addCost(float $cost, int $hours = 0): void
    {
        $this->increment('total_cost_usd', $cost);
        if ($hours > 0) {
            $this->increment('total_gpu_hours', $hours);
        }
    }

    /**
     * Scope: เฉพาะ configs ที่พร้อมใช้งาน
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: เฉพาะ default configs
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope: ของ user ที่ระบุ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
