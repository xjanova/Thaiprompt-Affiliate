<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * AI Content Generation Model
 *
 * เก็บประวัติการสร้าง Content แต่ละครั้ง
 * รวมถึง Input, Output, และ Usage Stats
 *
 * @property int $id
 * @property string $uuid
 * @property int $project_id
 * @property int|null $template_id
 * @property int $user_id
 * @property string|null $system_prompt
 * @property string $user_prompt
 * @property array|null $input_variables
 * @property string|null $generated_content
 * @property string $output_format
 * @property string $ai_provider
 * @property string $ai_model
 * @property float $temperature
 * @property int|null $max_tokens
 * @property int $prompt_tokens
 * @property int $completion_tokens
 * @property int $total_tokens
 * @property float $cost
 * @property int|null $generation_time_ms
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property string $status
 * @property string|null $error_message
 * @property string|null $error_code
 * @property int|null $rating
 * @property string|null $feedback
 * @property bool $is_selected
 * @property int $version
 */
class AiContentGeneration extends Model
{
    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'ai_content_generations';

    /**
     * คอลัมน์ที่สามารถกรอกข้อมูลได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'uuid',
        'project_id',
        'template_id',
        'user_id',
        'system_prompt',
        'user_prompt',
        'input_variables',
        'generated_content',
        'output_format',
        'ai_provider',
        'ai_model',
        'temperature',
        'max_tokens',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'cost',
        'generation_time_ms',
        'started_at',
        'completed_at',
        'status',
        'error_message',
        'error_code',
        'rating',
        'feedback',
        'is_selected',
        'version',
    ];

    /**
     * การแปลงชนิดข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'input_variables' => 'array',
        'temperature' => 'decimal:2',
        'max_tokens' => 'integer',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'cost' => 'decimal:6',
        'generation_time_ms' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'rating' => 'integer',
        'is_selected' => 'boolean',
        'version' => 'integer',
    ];

    /**
     * สถานะที่รองรับ
     */
    public const STATUSES = [
        'pending' => 'รอดำเนินการ',
        'processing' => 'กำลังประมวลผล',
        'completed' => 'เสร็จสิ้น',
        'failed' => 'ล้มเหลว',
        'cancelled' => 'ยกเลิก',
    ];

    /**
     * สีของสถานะ
     */
    public const STATUS_COLORS = [
        'pending' => 'bg-yellow-100 text-yellow-700',
        'processing' => 'bg-blue-100 text-blue-700',
        'completed' => 'bg-green-100 text-green-700',
        'failed' => 'bg-red-100 text-red-700',
        'cancelled' => 'bg-gray-100 text-gray-700',
    ];

    // =========================================
    // Boot
    // =========================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // =========================================
    // Relationships
    // =========================================

    /**
     * โปรเจกต์ที่เป็นเจ้าของ
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(AiContentProject::class, 'project_id');
    }

    /**
     * Template ที่ใช้
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(AiContentTemplate::class, 'template_id');
    }

    /**
     * ผู้ใช้ที่สร้าง
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // =========================================
    // Scopes
    // =========================================

    public function scopeOfUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeSelected($query)
    {
        return $query->where('is_selected', true);
    }

    public function scopeOfProvider($query, string $provider)
    {
        return $query->where('ai_provider', $provider);
    }

    // =========================================
    // Accessors
    // =========================================

    /**
     * ดึง Label ของสถานะ
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * ดึงสี CSS ของสถานะ
     */
    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-gray-100 text-gray-700';
    }

    /**
     * ดึงเวลาที่ใช้ในการสร้าง (วินาที)
     */
    public function getGenerationTimeSecondsAttribute(): ?float
    {
        if (!$this->generation_time_ms) {
            return null;
        }

        return round($this->generation_time_ms / 1000, 2);
    }

    /**
     * ดึงจำนวนคำ
     */
    public function getWordCountAttribute(): int
    {
        if (empty($this->generated_content)) {
            return 0;
        }

        return str_word_count(strip_tags($this->generated_content));
    }

    /**
     * ดึงตัวย่อของ Content
     */
    public function getContentExcerptAttribute(): string
    {
        if (empty($this->generated_content)) {
            return '';
        }

        return Str::limit(strip_tags($this->generated_content), 200);
    }

    // =========================================
    // Methods
    // =========================================

    /**
     * เริ่มการประมวลผล
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    /**
     * สำเร็จ
     *
     * @param string $content
     * @param array $usage
     */
    public function markAsCompleted(string $content, array $usage = []): void
    {
        $this->update([
            'status' => 'completed',
            'generated_content' => $content,
            'completed_at' => now(),
            'generation_time_ms' => $this->started_at
                ? now()->diffInMilliseconds($this->started_at)
                : null,
            'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
            'completion_tokens' => $usage['completion_tokens'] ?? 0,
            'total_tokens' => $usage['total_tokens'] ?? 0,
            'cost' => $usage['cost'] ?? 0,
        ]);

        // อัพเดทสถิติโปรเจกต์
        $this->project->updateStatsFromGeneration($this);
    }

    /**
     * ล้มเหลว
     *
     * @param string $message
     * @param string|null $code
     */
    public function markAsFailed(string $message, ?string $code = null): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $message,
            'error_code' => $code,
            'completed_at' => now(),
        ]);
    }

    /**
     * ให้คะแนน
     *
     * @param int $rating
     * @param string|null $feedback
     */
    public function rate(int $rating, ?string $feedback = null): void
    {
        $this->update([
            'rating' => min(5, max(1, $rating)),
            'feedback' => $feedback,
        ]);
    }
}
