<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model สำหรับ Trending Models จาก Hugging Face
 *
 * จัดการข้อมูล trending models และสถิติต่างๆ
 *
 * @property int $id
 * @property string $model_id Model ID
 * @property string $model_name ชื่อ model
 * @property string|null $description คำอธิบาย
 * @property string|null $task Task ของ model
 * @property string|null $library Library
 * @property array|null $tags Tags
 * @property array|null $pipeline_tag Pipeline tags
 * @property string|null $author ผู้สร้าง
 * @property string|null $organization องค์กร
 * @property int $downloads จำนวน downloads
 * @property int $downloads_last_month Downloads เดือนล่าสุด
 * @property int $likes จำนวน likes
 * @property int $trending_score คะแนน trending
 * @property string|null $model_size ขนาด model
 * @property int|null $model_size_bytes ขนาด bytes
 * @property array|null $supported_languages ภาษาที่รองรับ
 * @property string|null $license License
 * @property string|null $min_gpu_memory GPU memory ขั้นต่ำ
 * @property string|null $recommended_gpu GPU ที่แนะนำ
 * @property array|null $hardware_requirements ข้อกำหนด hardware
 * @property array|null $benchmark_scores คะแนน benchmark
 * @property float|null $avg_inference_time_ms เวลา inference เฉลี่ย
 * @property string|null $performance_tier ระดับประสิทธิภาพ
 * @property string|null $model_url ลิงก์ model
 * @property string|null $paper_url ลิงก์ paper
 * @property string|null $demo_url ลิงก์ demo
 * @property string|null $documentation_url ลิงก์เอกสาร
 * @property bool $supports_inference_api รองรับ Inference API
 * @property bool $supports_spaces รองรับ Spaces
 * @property bool $can_run_locally รันในเครื่องได้
 * @property array|null $compatible_cloud_providers Cloud providers ที่เข้ากันได้
 * @property float|null $estimated_cost_per_hour ค่าใช้จ่ายประมาณต่อชั่วโมง
 * @property int|null $rank_overall อันดับโดยรวม
 * @property int|null $rank_in_category อันดับในหมวดหมู่
 * @property string|null $category หมวดหมู่
 * @property bool $is_featured แนะนำ
 * @property bool $is_recommended แนะนำสำหรับผู้เริ่มต้น
 * @property bool $is_production_ready พร้อม production
 * @property string $difficulty_level ระดับความยาก
 * @property \Carbon\Carbon|null $first_seen_at เห็นครั้งแรกเมื่อ
 * @property \Carbon\Carbon|null $last_updated_at อัพเดทล่าสุด
 * @property \Carbon\Carbon|null $featured_at เริ่มแสดงเด่นเมื่อ
 * @property array|null $metadata ข้อมูลเพิ่มเติม
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class HuggingFaceTrendingModel extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'huggingface_trending_models';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'model_id',
        'model_name',
        'description',
        'task',
        'library',
        'tags',
        'pipeline_tag',
        'author',
        'organization',
        'downloads',
        'downloads_last_month',
        'likes',
        'trending_score',
        'model_size',
        'model_size_bytes',
        'supported_languages',
        'license',
        'min_gpu_memory',
        'recommended_gpu',
        'hardware_requirements',
        'benchmark_scores',
        'avg_inference_time_ms',
        'performance_tier',
        'model_url',
        'paper_url',
        'demo_url',
        'documentation_url',
        'supports_inference_api',
        'supports_spaces',
        'can_run_locally',
        'compatible_cloud_providers',
        'estimated_cost_per_hour',
        'rank_overall',
        'rank_in_category',
        'category',
        'is_featured',
        'is_recommended',
        'is_production_ready',
        'difficulty_level',
        'first_seen_at',
        'last_updated_at',
        'featured_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tags' => 'array',
        'pipeline_tag' => 'array',
        'downloads' => 'integer',
        'downloads_last_month' => 'integer',
        'likes' => 'integer',
        'trending_score' => 'integer',
        'model_size_bytes' => 'integer',
        'supported_languages' => 'array',
        'hardware_requirements' => 'array',
        'benchmark_scores' => 'array',
        'avg_inference_time_ms' => 'decimal:2',
        'supports_inference_api' => 'boolean',
        'supports_spaces' => 'boolean',
        'can_run_locally' => 'boolean',
        'compatible_cloud_providers' => 'array',
        'estimated_cost_per_hour' => 'decimal:4',
        'rank_overall' => 'integer',
        'rank_in_category' => 'integer',
        'is_featured' => 'boolean',
        'is_recommended' => 'boolean',
        'is_production_ready' => 'boolean',
        'first_seen_at' => 'datetime',
        'last_updated_at' => 'datetime',
        'featured_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * คำนวณคะแนน trending จากสถิติต่างๆ
     *
     * @return int
     */
    public function calculateTrendingScore(): int
    {
        $score = 0;

        // คะแนนจาก downloads (สูงสุด 40 คะแนน)
        $downloadsScore = min(40, ($this->downloads_last_month / 10000));
        $score += $downloadsScore;

        // คะแนนจาก likes (สูงสุด 30 คะแนน)
        $likesScore = min(30, ($this->likes / 100));
        $score += $likesScore;

        // คะแนนจากการอัพเดทล่าสุด (สูงสุด 20 คะแนน)
        if ($this->last_updated_at) {
            $daysAgo = now()->diffInDays($this->last_updated_at);
            $recencyScore = max(0, 20 - ($daysAgo / 2));
            $score += $recencyScore;
        }

        // คะแนนจากความสมบูรณ์ (สูงสุด 10 คะแนน)
        $completeness = 0;
        $completeness += $this->paper_url ? 2 : 0;
        $completeness += $this->demo_url ? 2 : 0;
        $completeness += $this->documentation_url ? 2 : 0;
        $completeness += $this->supports_inference_api ? 2 : 0;
        $completeness += $this->is_production_ready ? 2 : 0;
        $score += $completeness;

        return (int) round($score);
    }

    /**
     * อัพเดทคะแนน trending
     *
     * @return void
     */
    public function updateTrendingScore(): void
    {
        $this->trending_score = $this->calculateTrendingScore();
        $this->save();
    }

    /**
     * ตรวจสอบว่าเป็น model ที่ hot หรือไม่
     *
     * @return bool
     */
    public function isHot(): bool
    {
        return $this->trending_score >= 70;
    }

    /**
     * ตรวจสอบว่า model ใหม่หรือไม่ (อายุไม่เกิน 30 วัน)
     *
     * @return bool
     */
    public function isNew(): bool
    {
        if (!$this->first_seen_at) {
            return false;
        }

        return $this->first_seen_at->diffInDays(now()) <= 30;
    }

    /**
     * ตรวจสอบว่าเหมาะกับผู้เริ่มต้นหรือไม่
     *
     * @return bool
     */
    public function isBeginnerFriendly(): bool
    {
        return $this->difficulty_level === 'beginner' || $this->is_recommended;
    }

    /**
     * ได้รับขนาดไฟล์ที่อ่านง่าย
     *
     * @return string|null
     */
    public function getReadableSizeAttribute(): ?string
    {
        if (!$this->model_size_bytes) {
            return $this->model_size;
        }

        $bytes = $this->model_size_bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Scope: เฉพาะ models ที่แนะนำ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: เฉพาะ models ที่แนะนำสำหรับผู้เริ่มต้น
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecommended($query)
    {
        return $query->where('is_recommended', true);
    }

    /**
     * Scope: เฉพาะ models ที่พร้อม production
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProductionReady($query)
    {
        return $query->where('is_production_ready', true);
    }

    /**
     * Scope: เรียงตามคะแนน trending
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTrending($query)
    {
        return $query->orderBy('trending_score', 'desc');
    }

    /**
     * Scope: เรียงตามความนิยม (downloads)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePopular($query)
    {
        return $query->orderBy('downloads', 'desc');
    }

    /**
     * Scope: ตามหมวดหมู่/task
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $task
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForTask($query, string $task)
    {
        return $query->where('task', $task);
    }

    /**
     * Scope: ตามระดับความยาก
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $level
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDifficulty($query, string $level)
    {
        return $query->where('difficulty_level', $level);
    }

    /**
     * Scope: รองรับ Inference API
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSupportsInferenceApi($query)
    {
        return $query->where('supports_inference_api', true);
    }

    /**
     * Scope: รันในเครื่องได้
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCanRunLocally($query)
    {
        return $query->where('can_run_locally', true);
    }

    /**
     * Scope: ค้นหาข้อความ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('model_name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('author', 'like', "%{$search}%")
                ->orWhere('organization', 'like', "%{$search}%");
        });
    }

    /**
     * ได้รับ URL ของ model บน Hugging Face
     *
     * @return string
     */
    public function getHuggingFaceUrlAttribute(): string
    {
        return $this->model_url ?? "https://huggingface.co/{$this->model_id}";
    }
}
