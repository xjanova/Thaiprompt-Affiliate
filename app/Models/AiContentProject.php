<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * AI Content Project Model
 *
 * โปรเจกต์ Content ของผู้ใช้
 * สามารถมีหลาย Generations ในโปรเจกต์เดียว
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int|null $template_id
 * @property string $name
 * @property string|null $description
 * @property string $content_type
 * @property array|null $input_variables
 * @property string|null $selected_content
 * @property string $ai_provider
 * @property string|null $ai_model
 * @property string $status
 * @property string|null $folder
 * @property array|null $tags
 * @property bool $is_favorite
 * @property int $generations_count
 * @property int $words_count
 * @property int $tokens_used
 */
class AiContentProject extends Model
{
    use SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'ai_content_projects';

    /**
     * คอลัมน์ที่สามารถกรอกข้อมูลได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'template_id',
        'name',
        'description',
        'content_type',
        'input_variables',
        'selected_content',
        'ai_provider',
        'ai_model',
        'status',
        'folder',
        'tags',
        'is_favorite',
        'generations_count',
        'words_count',
        'tokens_used',
    ];

    /**
     * การแปลงชนิดข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'input_variables' => 'array',
        'tags' => 'array',
        'is_favorite' => 'boolean',
        'generations_count' => 'integer',
        'words_count' => 'integer',
        'tokens_used' => 'integer',
    ];

    /**
     * สถานะที่รองรับ
     */
    public const STATUSES = [
        'draft' => 'แบบร่าง',
        'generating' => 'กำลังสร้าง',
        'completed' => 'เสร็จสิ้น',
        'failed' => 'ล้มเหลว',
        'archived' => 'เก็บถาวร',
    ];

    /**
     * สีของสถานะ (Tailwind classes)
     */
    public const STATUS_COLORS = [
        'draft' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        'generating' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        'archived' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
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
     * เจ้าของโปรเจกต์
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Template ที่ใช้
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(AiContentTemplate::class, 'template_id');
    }

    /**
     * การ Generate ทั้งหมด
     */
    public function generations(): HasMany
    {
        return $this->hasMany(AiContentGeneration::class, 'project_id');
    }

    /**
     * การ Generate ที่เลือกใช้
     */
    public function selectedGeneration()
    {
        return $this->generations()->where('is_selected', true)->first();
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

    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    public function scopeInFolder($query, string $folder)
    {
        return $query->where('folder', $folder);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('content_type', $type);
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('updated_at');
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
     * ดึง Label ของ Content Type
     */
    public function getContentTypeLabelAttribute(): string
    {
        return AiContentTemplate::CONTENT_TYPES[$this->content_type] ?? $this->content_type;
    }

    /**
     * ดึงตัวย่อของ Content
     */
    public function getContentExcerptAttribute(): string
    {
        if (empty($this->selected_content)) {
            return '';
        }

        return Str::limit(strip_tags($this->selected_content), 150);
    }

    // =========================================
    // Methods
    // =========================================

    /**
     * สร้างโปรเจกต์จาก Template
     *
     * @param AiContentTemplate $template
     * @param array $data
     * @return static
     */
    public static function createFromTemplate(AiContentTemplate $template, array $data): self
    {
        return static::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $data['user_id'],
            'template_id' => $template->id,
            'name' => $data['name'] ?? $template->name . ' - ' . now()->format('d/m/Y H:i'),
            'description' => $data['description'] ?? null,
            'content_type' => $template->content_type,
            'input_variables' => $data['input_variables'] ?? [],
            'ai_provider' => $data['ai_provider'] ?? $template->default_provider,
            'ai_model' => $data['ai_model'] ?? $template->default_model,
            'status' => 'draft',
        ]);
    }

    /**
     * อัพเดทสถิติหลังจากสร้าง Generation
     *
     * @param AiContentGeneration $generation
     */
    public function updateStatsFromGeneration(AiContentGeneration $generation): void
    {
        $this->increment('generations_count');
        $this->increment('tokens_used', $generation->total_tokens);

        if ($generation->generated_content) {
            $wordCount = str_word_count(strip_tags($generation->generated_content));
            $this->words_count = max($this->words_count, $wordCount);
            $this->save();
        }
    }

    /**
     * เลือก Content จาก Generation
     *
     * @param AiContentGeneration $generation
     */
    public function selectGeneration(AiContentGeneration $generation): void
    {
        // ยกเลิกการเลือกอันเก่า
        $this->generations()->update(['is_selected' => false]);

        // เลือกอันใหม่
        $generation->update(['is_selected' => true]);

        // อัพเดท selected_content
        $this->selected_content = $generation->generated_content;
        $this->status = 'completed';
        $this->save();
    }
}
