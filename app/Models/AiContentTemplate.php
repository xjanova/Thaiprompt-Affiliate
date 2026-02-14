<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * AI Content Template Model
 *
 * เทมเพลตสำหรับสร้าง Content ต่างๆ
 * เช่น Blog Post, Video Script, Social Caption
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $content_type
 * @property string|null $category
 * @property string|null $system_prompt
 * @property string $user_prompt_template
 * @property array|null $variables
 * @property string $default_provider
 * @property string|null $default_model
 * @property float $temperature
 * @property int $max_tokens
 * @property string $output_format
 * @property string $language
 * @property string|null $tone
 * @property string|null $icon
 * @property string|null $color
 * @property int|null $created_by
 * @property bool $is_active
 * @property bool $is_featured
 * @property bool $is_premium
 * @property int $usage_count
 * @property int $sort_order
 */
class AiContentTemplate extends Model
{
    use SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'ai_content_templates';

    /**
     * คอลัมน์ที่สามารถกรอกข้อมูลได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'content_type',
        'category',
        'system_prompt',
        'user_prompt_template',
        'variables',
        'default_provider',
        'default_model',
        'temperature',
        'max_tokens',
        'output_format',
        'language',
        'tone',
        'icon',
        'color',
        'created_by',
        'is_active',
        'is_featured',
        'is_premium',
        'usage_count',
        'sort_order',
    ];

    /**
     * การแปลงชนิดข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'variables' => 'array',
        'temperature' => 'decimal:2',
        'max_tokens' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_premium' => 'boolean',
        'usage_count' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * ประเภท Content ที่รองรับ
     */
    public const CONTENT_TYPES = [
        'general' => 'ทั่วไป',
        'blog_post' => 'บทความ Blog',
        'video_script' => 'Script วิดีโอ',
        'youtube_title' => 'Title YouTube',
        'youtube_description' => 'Description YouTube',
        'social_caption' => 'Caption Social Media',
        'email' => 'อีเมล',
        'ad_copy' => 'โฆษณา',
        'product_description' => 'รายละเอียดสินค้า',
        'seo_meta' => 'SEO Meta',
        'story' => 'เรื่องราว/นิยาย',
        'summary' => 'สรุปเนื้อหา',
        'translation' => 'แปลภาษา',
        'rewrite' => 'เขียนใหม่',
    ];

    /**
     * หมวดหมู่
     */
    public const CATEGORIES = [
        'marketing' => 'การตลาด',
        'sales' => 'การขาย',
        'content' => 'Content',
        'social' => 'Social Media',
        'video' => 'วิดีโอ',
        'email' => 'อีเมล',
        'seo' => 'SEO',
        'creative' => 'ความคิดสร้างสรรค์',
        'business' => 'ธุรกิจ',
        'education' => 'การศึกษา',
    ];

    /**
     * AI Providers
     */
    public const AI_PROVIDERS = [
        'openai' => 'OpenAI (GPT)',
        'claude' => 'Claude (Anthropic)',
        'gemini' => 'Gemini (Google)',
    ];

    /**
     * Output Formats
     */
    public const OUTPUT_FORMATS = [
        'text' => 'ข้อความธรรมดา',
        'markdown' => 'Markdown',
        'html' => 'HTML',
        'json' => 'JSON',
    ];

    /**
     * Tones
     */
    public const TONES = [
        'professional' => 'เป็นทางการ',
        'casual' => 'ทั่วไป',
        'friendly' => 'เป็นกันเอง',
        'formal' => 'เป็นทางการมาก',
        'humorous' => 'ตลก',
        'serious' => 'จริงจัง',
        'inspirational' => 'สร้างแรงบันดาลใจ',
        'persuasive' => 'ชักจูง',
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
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    // =========================================
    // Relationships
    // =========================================

    /**
     * ผู้สร้าง Template
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * โปรเจกต์ที่ใช้ Template นี้
     */
    public function projects(): HasMany
    {
        return $this->hasMany(AiContentProject::class, 'template_id');
    }

    /**
     * การ Generate ที่ใช้ Template นี้
     */
    public function generations(): HasMany
    {
        return $this->hasMany(AiContentGeneration::class, 'template_id');
    }

    // =========================================
    // Scopes
    // =========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('content_type', $type);
    }

    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopePopular($query)
    {
        return $query->orderByDesc('usage_count');
    }

    // =========================================
    // Accessors
    // =========================================

    /**
     * ดึง Label ของ Content Type
     */
    public function getContentTypeLabelAttribute(): string
    {
        return self::CONTENT_TYPES[$this->content_type] ?? $this->content_type;
    }

    /**
     * ดึง Label ของ Category
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category ?? '-';
    }

    /**
     * ดึง Label ของ Provider
     */
    public function getProviderLabelAttribute(): string
    {
        return self::AI_PROVIDERS[$this->default_provider] ?? $this->default_provider;
    }

    // =========================================
    // Methods
    // =========================================

    /**
     * สร้าง Prompt จาก Template และ Variables
     */
    public function buildPrompt(array $variables): string
    {
        $prompt = $this->user_prompt_template;

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
}
