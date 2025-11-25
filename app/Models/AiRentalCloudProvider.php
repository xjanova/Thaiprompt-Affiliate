<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model สำหรับ Cloud GPU Providers
 *
 * จัดการข้อมูล Cloud providers สำหรับระบบ AI Rental
 * รองรับทั้ง free tier และ paid tier
 *
 * @property int $id
 * @property string $name ชื่อ provider
 * @property string $slug Slug สำหรับ URL
 * @property string|null $description คำอธิบาย
 * @property string|null $logo_url URL logo
 * @property string|null $website_url เว็บไซต์
 * @property string $tier ประเภท (free/paid/both)
 * @property bool $has_free_tier มี free tier หรือไม่
 * @property float|null $min_price_per_hour ราคาต่ำสุดต่อชั่วโมง
 * @property float|null $max_price_per_hour ราคาสูงสุดต่อชั่วโมง
 * @property array|null $gpu_types ประเภท GPU
 * @property int|null $free_gpu_hours ชั่วโมง GPU ฟรี
 * @property int|null $free_storage_gb พื้นที่ฟรี GB
 * @property bool $supports_huggingface รองรับ Hugging Face
 * @property bool $supports_docker รองรับ Docker
 * @property bool $supports_jupyter รองรับ Jupyter
 * @property bool $supports_api รองรับ API
 * @property bool $supports_ssh รองรับ SSH
 * @property int $popularity_score คะแนนความนิยม
 * @property float|null $user_rating คะแนนจากผู้ใช้
 * @property int $total_reviews จำนวนรีวิว
 * @property string|null $setup_guide_url ลิงก์คู่มือ
 * @property array|null $setup_steps ขั้นตอนการตั้งค่า
 * @property string|null $api_documentation_url ลิงก์เอกสาร API
 * @property string|null $limitations ข้อจำกัด
 * @property string|null $requirements ข้อกำหนด
 * @property bool $is_active เปิดใช้งาน
 * @property bool $is_recommended แนะนำ
 * @property int $display_order ลำดับแสดง
 * @property array|null $metadata ข้อมูลเพิ่มเติม
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class AiRentalCloudProvider extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_rental_cloud_providers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo_url',
        'website_url',
        'tier',
        'has_free_tier',
        'min_price_per_hour',
        'max_price_per_hour',
        'gpu_types',
        'free_gpu_hours',
        'free_storage_gb',
        'supports_huggingface',
        'supports_docker',
        'supports_jupyter',
        'supports_api',
        'supports_ssh',
        'popularity_score',
        'user_rating',
        'total_reviews',
        'setup_guide_url',
        'setup_steps',
        'api_documentation_url',
        'limitations',
        'requirements',
        'is_active',
        'is_recommended',
        'display_order',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'has_free_tier' => 'boolean',
        'min_price_per_hour' => 'decimal:4',
        'max_price_per_hour' => 'decimal:4',
        'gpu_types' => 'array',
        'free_gpu_hours' => 'integer',
        'free_storage_gb' => 'integer',
        'supports_huggingface' => 'boolean',
        'supports_docker' => 'boolean',
        'supports_jupyter' => 'boolean',
        'supports_api' => 'boolean',
        'supports_ssh' => 'boolean',
        'popularity_score' => 'integer',
        'user_rating' => 'decimal:2',
        'total_reviews' => 'integer',
        'setup_steps' => 'array',
        'is_active' => 'boolean',
        'is_recommended' => 'boolean',
        'display_order' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ Cloud Configs
     *
     * @return HasMany
     */
    public function cloudConfigs(): HasMany
    {
        return $this->hasMany(AiRentalCloudConfig::class, 'cloud_provider_id');
    }

    /**
     * ความสัมพันธ์กับ Deployments (ผ่าน Cloud Configs)
     *
     * ใช้ HasManyThrough เพื่อเข้าถึง deployments ผ่าน cloud configs
     * Provider -> CloudConfig -> Deployment
     *
     * @return HasManyThrough
     */
    public function deployments(): HasManyThrough
    {
        return $this->hasManyThrough(
            AiRentalDeployment::class,      // Final model
            AiRentalCloudConfig::class,     // Intermediate model
            'cloud_provider_id',             // Foreign key บน intermediate model
            'cloud_config_id',               // Foreign key บน final model
            'id',                            // Local key บน starting model
            'id'                             // Local key บน intermediate model
        );
    }

    /**
     * ตรวจสอบว่าเป็น free tier หรือไม่
     *
     * @return bool
     */
    public function isFree(): bool
    {
        return $this->tier === 'free' || $this->has_free_tier;
    }

    /**
     * ตรวจสอบว่าเป็น paid tier หรือไม่
     *
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->tier === 'paid' || $this->tier === 'both';
    }

    /**
     * ได้รับราคาเฉลี่ย
     *
     * @return float|null
     */
    public function getAveragePriceAttribute(): ?float
    {
        if ($this->min_price_per_hour && $this->max_price_per_hour) {
            return ($this->min_price_per_hour + $this->max_price_per_hour) / 2;
        }

        return $this->min_price_per_hour ?? $this->max_price_per_hour;
    }

    /**
     * Scope: เฉพาะ providers ที่เปิดใช้งาน
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: เฉพาะ free tier
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFreeTier($query)
    {
        return $query->where(function ($q) {
            $q->where('tier', 'free')
                ->orWhere('has_free_tier', true);
        });
    }

    /**
     * Scope: เฉพาะ paid tier
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaidTier($query)
    {
        return $query->whereIn('tier', ['paid', 'both']);
    }

    /**
     * Scope: เฉพาะ providers ที่แนะนำ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecommended($query)
    {
        return $query->where('is_recommended', true);
    }

    /**
     * Scope: เรียงตามความนิยม
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePopular($query)
    {
        return $query->orderBy('popularity_score', 'desc');
    }

    /**
     * Scope: เรียงตามลำดับที่กำหนด
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc')
            ->orderBy('name', 'asc');
    }

    /**
     * ตรวจสอบว่ารองรับ Hugging Face หรือไม่
     *
     * @return bool
     */
    public function supportsHuggingFace(): bool
    {
        return $this->supports_huggingface;
    }

    /**
     * เพิ่มรีวิว
     *
     * @param float $rating คะแนน (0-5)
     * @return void
     */
    public function addReview(float $rating): void
    {
        $totalRating = ($this->user_rating ?? 0) * $this->total_reviews;
        $this->total_reviews++;
        $this->user_rating = ($totalRating + $rating) / $this->total_reviews;
        $this->save();
    }

    /**
     * เพิ่มคะแนนความนิยม
     *
     * @param int $points
     * @return void
     */
    public function incrementPopularity(int $points = 1): void
    {
        $this->increment('popularity_score', $points);
    }
}
