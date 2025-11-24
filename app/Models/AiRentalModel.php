<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AI Rental Model
 *
 * Model นี้เก็บข้อมูล AI Models ที่สามารถ deploy บน Cloud GPU ได้
 * เช่น Stable Diffusion, LLaMA, Whisper, etc.
 *
 * @property int $id
 * @property string $name ชื่อ AI Model
 * @property string $slug URL-friendly name
 * @property string|null $description คำอธิบาย
 * @property string|null $version เวอร์ชัน
 * @property string $category หมวดหมู่
 * @property string|null $framework Framework
 * @property string|null $docker_image Docker image path
 * @property array|null $docker_env_vars Environment variables
 * @property string|null $min_gpu_type GPU ต่ำสุด
 * @property int $min_vram_gb VRAM ขั้นต่ำ (GB)
 * @property bool $is_active เปิดใช้งาน
 * @property bool $is_featured แนะนำ
 * @property int $display_order ลำดับการแสดงผล
 * @property int $total_deployments จำนวนครั้งที่ถูก deploy
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class AiRentalModel extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_rental_models';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'version',
        'category',
        'framework',
        'license',
        'docker_image',
        'docker_env_vars',
        'docker_ports',
        'docker_command',
        'min_gpu_type',
        'min_vram_gb',
        'recommended_vram_gb',
        'supported_gpu_types',
        'min_cpu_cores',
        'min_ram_gb',
        'min_storage_gb',
        'avg_inference_time_ms',
        'max_batch_size',
        'base_cost_per_hour',
        'pricing_tiers',
        'official_url',
        'documentation_url',
        'github_url',
        'huggingface_url',
        'demo_url',
        'thumbnail_url',
        'gallery_images',
        'video_url',
        'tags',
        'use_cases',
        'total_deployments',
        'average_rating',
        'total_reviews',
        'view_count',
        'is_active',
        'is_featured',
        'is_new',
        'is_beta',
        'availability',
        'display_order',
        'badge_text',
        'badge_color',
        'metadata',
        'config_options',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'docker_env_vars' => 'array',
        'docker_ports' => 'array',
        'supported_gpu_types' => 'array',
        'pricing_tiers' => 'array',
        'gallery_images' => 'array',
        'tags' => 'array',
        'use_cases' => 'array',
        'metadata' => 'array',
        'config_options' => 'array',
        'min_vram_gb' => 'integer',
        'recommended_vram_gb' => 'integer',
        'min_cpu_cores' => 'integer',
        'min_ram_gb' => 'integer',
        'min_storage_gb' => 'integer',
        'max_batch_size' => 'integer',
        'total_deployments' => 'integer',
        'total_reviews' => 'integer',
        'view_count' => 'integer',
        'display_order' => 'integer',
        'avg_inference_time_ms' => 'decimal:2',
        'base_cost_per_hour' => 'decimal:4',
        'average_rating' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'is_beta' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ Deployments
     *
     * @return HasMany
     */
    public function deployments(): HasMany
    {
        return $this->hasMany(AiRentalDeployment::class, 'model_id');
    }

    /**
     * Scope: เฉพาะ AI models ที่เปิดใช้งาน
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: เรียงตามลำดับการแสดงผล
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
     * Scope: เฉพาะ AI models ที่แนะนำ (featured)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: เฉพาะ AI models ใหม่
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNew($query)
    {
        return $query->where('is_new', true);
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
     * Scope: กรองตาม VRAM ขั้นต่ำ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $maxVram VRAM สูงสุดที่มี
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithinVram($query, int $maxVram)
    {
        return $query->where('min_vram_gb', '<=', $maxVram);
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
     * เพิ่มจำนวน views
     *
     * @return void
     */
    public function incrementViews(): void
    {
        $this->increment('view_count');
    }

    /**
     * ตรวจสอบว่า model นี้รองรับ GPU type หรือไม่
     *
     * @param string $gpuType
     * @return bool
     */
    public function supportsGpuType(string $gpuType): bool
    {
        if (empty($this->supported_gpu_types)) {
            return true; // รองรับทุก GPU
        }

        return in_array($gpuType, $this->supported_gpu_types);
    }

    /**
     * ดึง pricing สำหรับ GPU type
     *
     * @param string $gpuType
     * @return float|null
     */
    public function getPricingForGpu(string $gpuType): ?float
    {
        if (empty($this->pricing_tiers)) {
            return $this->base_cost_per_hour;
        }

        return $this->pricing_tiers[$gpuType] ?? $this->base_cost_per_hour;
    }

    /**
     * ดึงข้อมูล badge
     *
     * @return array|null
     */
    public function getBadge(): ?array
    {
        if (empty($this->badge_text)) {
            // Auto-generate badge
            if ($this->is_new) {
                return [
                    'text' => 'NEW',
                    'color' => '#10B981', // green
                ];
            }

            if ($this->is_beta) {
                return [
                    'text' => 'BETA',
                    'color' => '#F59E0B', // amber
                ];
            }

            if ($this->is_featured) {
                return [
                    'text' => 'POPULAR',
                    'color' => '#3B82F6', // blue
                ];
            }

            return null;
        }

        return [
            'text' => $this->badge_text,
            'color' => $this->badge_color ?? '#6B7280', // gray
        ];
    }

    /**
     * ตรวจสอบว่า model พร้อมใช้งานหรือไม่
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->is_active && $this->availability === 'public';
    }

    /**
     * ดึงชื่อหมวดหมู่แบบอ่านง่าย
     *
     * @return string
     */
    public function getCategoryName(): string
    {
        return match ($this->category) {
            'image_generation' => 'สร้างภาพ',
            'text_generation' => 'สร้างข้อความ',
            'audio_generation' => 'สร้างเสียง',
            'video_generation' => 'สร้างวิดีโอ',
            'code_generation' => 'สร้างโค้ด',
            'translation' => 'แปลภาษา',
            'chatbot' => 'แชทบอท',
            default => 'อื่นๆ',
        };
    }

    /**
     * ดึง Docker command พร้อม default values
     *
     * @return string
     */
    public function getDockerCommand(): string
    {
        if (!empty($this->docker_command)) {
            return $this->docker_command;
        }

        // Default command
        return 'python app.py';
    }

    /**
     * ดึง Docker ports พร้อม default values
     *
     * @return array
     */
    public function getDockerPorts(): array
    {
        if (!empty($this->docker_ports)) {
            return $this->docker_ports;
        }

        // Default ports
        return [
            '8080:8080',
            '7860:7860', // Gradio default port
        ];
    }
}
