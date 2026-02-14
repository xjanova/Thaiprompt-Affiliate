<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * AI Content Prompt Model
 *
 * Custom Prompts ที่ผู้ใช้สร้างขึ้น
 * สามารถแชร์และนำกลับมาใช้ซ้ำได้
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property string|null $system_prompt
 * @property string $user_prompt
 * @property string $content_type
 * @property string|null $category
 * @property array|null $tags
 * @property array|null $variables
 * @property string|null $recommended_provider
 * @property string|null $recommended_model
 * @property float|null $recommended_temperature
 * @property bool $is_public
 * @property bool $is_approved
 * @property int $usage_count
 * @property int $likes_count
 * @property float|null $average_rating
 * @property bool $is_active
 */
class AiContentPrompt extends Model
{
    use SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'ai_content_prompts';

    /**
     * คอลัมน์ที่สามารถกรอกข้อมูลได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'name',
        'description',
        'system_prompt',
        'user_prompt',
        'content_type',
        'category',
        'tags',
        'variables',
        'recommended_provider',
        'recommended_model',
        'recommended_temperature',
        'is_public',
        'is_approved',
        'usage_count',
        'likes_count',
        'average_rating',
        'is_active',
    ];

    /**
     * การแปลงชนิดข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tags' => 'array',
        'variables' => 'array',
        'recommended_temperature' => 'decimal:2',
        'is_public' => 'boolean',
        'is_approved' => 'boolean',
        'usage_count' => 'integer',
        'likes_count' => 'integer',
        'average_rating' => 'decimal:2',
        'is_active' => 'boolean',
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
     * เจ้าของ Prompt
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // =========================================
    // Scopes
    // =========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true)->where('is_approved', true);
    }

    public function scopeOfUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('content_type', $type);
    }

    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopePopular($query)
    {
        return $query->orderByDesc('usage_count');
    }

    public function scopeTopRated($query)
    {
        return $query->orderByDesc('average_rating');
    }

    // =========================================
    // Accessors
    // =========================================

    /**
     * ดึง Label ของ Content Type
     */
    public function getContentTypeLabelAttribute(): string
    {
        return AiContentTemplate::CONTENT_TYPES[$this->content_type] ?? $this->content_type;
    }

    /**
     * ดึง Label ของ Category
     */
    public function getCategoryLabelAttribute(): string
    {
        return AiContentTemplate::CATEGORIES[$this->category] ?? $this->category ?? '-';
    }

    // =========================================
    // Methods
    // =========================================

    /**
     * สร้าง Prompt จาก Variables
     */
    public function buildPrompt(array $variables): string
    {
        $prompt = $this->user_prompt;

        foreach ($variables as $key => $value) {
            $prompt = str_replace('{{'.$key.'}}', $value, $prompt);
            $prompt = str_replace('{{ '.$key.' }}', $value, $prompt);
        }

        return $prompt;
    }

    /**
     * เพิ่มจำนวนการใช้งาน
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * อัพเดทคะแนนเฉลี่ย
     */
    public function updateAverageRating(float $newRating): void
    {
        // Simple moving average
        if ($this->average_rating === null) {
            $this->average_rating = $newRating;
        } else {
            $totalRatings = $this->usage_count ?: 1;
            $this->average_rating = (($this->average_rating * ($totalRatings - 1)) + $newRating) / $totalRatings;
        }

        $this->save();
    }
}
