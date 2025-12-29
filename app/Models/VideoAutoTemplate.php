<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * VideoAutoTemplate Model
 *
 * เทมเพลตสำหรับสร้างวีดีโออัตโนมัติ
 *
 * @property int $id
 * @property string $name ชื่อเทมเพลต
 * @property string $slug Slug
 * @property string|null $description คำอธิบาย
 * @property string|null $thumbnail รูปตัวอย่าง
 * @property string|null $music_genre แนวเพลง
 * @property string|null $music_mood อารมณ์เพลง
 * @property string|null $music_style สไตล์เพลง
 * @property int $music_duration ความยาวเพลง (วินาที)
 * @property string|null $music_prompt Prompt สำหรับ Suno
 * @property array|null $music_tags Tags
 * @property string|null $image_style สไตล์ภาพ
 * @property string|null $image_theme ธีมภาพ
 * @property string|null $image_color_scheme โทนสี
 * @property int $images_count จำนวนภาพ
 * @property string $image_ratio อัตราส่วนภาพ
 * @property string|null $image_prompt_template Template prompt
 * @property array|null $image_keywords Keywords
 * @property string $video_resolution ความละเอียด
 * @property string $video_format รูปแบบไฟล์
 * @property int $slide_duration ความยาว slide (วินาที)
 * @property string $transition_type Transition
 * @property int $transition_duration ความยาว transition (ms)
 * @property bool $add_intro เพิ่ม intro
 * @property bool $add_outro เพิ่ม outro
 * @property string|null $intro_video Path intro
 * @property string|null $outro_video Path outro
 * @property bool $enable_ken_burns Ken Burns effect
 * @property bool $enable_text_overlay ข้อความซ้อน
 * @property array|null $text_overlay_settings ตั้งค่าข้อความ
 * @property bool $enable_watermark Watermark
 * @property string|null $watermark_image รูป watermark
 * @property string $watermark_position ตำแหน่ง watermark
 * @property string|null $default_title_template Template ชื่อ
 * @property string|null $default_description_template Template คำอธิบาย
 * @property array|null $default_tags Tags default
 * @property string|null $default_category Category
 * @property string $default_privacy Privacy
 * @property array|null $target_platforms Platforms
 * @property bool $enable_auto_schedule Auto schedule
 * @property array|null $schedule_settings ตั้งค่า schedule
 * @property bool $is_active เปิดใช้งาน
 * @property bool $is_default ใช้เป็น default
 * @property int $usage_count จำนวนครั้งที่ใช้
 * @property int|null $created_by ผู้สร้าง
 * @property int $sort_order ลำดับ
 */
class VideoAutoTemplate extends Model
{
    use SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'video_auto_templates';

    /**
     * คอลัมน์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'thumbnail',
        // Music
        'music_genre',
        'music_mood',
        'music_style',
        'music_duration',
        'music_prompt',
        'music_tags',
        // Image
        'image_style',
        'image_theme',
        'image_color_scheme',
        'images_count',
        'image_ratio',
        'image_prompt_template',
        'image_keywords',
        // Video
        'video_resolution',
        'video_format',
        'slide_duration',
        'transition_type',
        'transition_duration',
        'add_intro',
        'add_outro',
        'intro_video',
        'outro_video',
        // Effects
        'enable_ken_burns',
        'enable_text_overlay',
        'text_overlay_settings',
        'enable_watermark',
        'watermark_image',
        'watermark_position',
        // Publishing
        'default_title_template',
        'default_description_template',
        'default_tags',
        'default_category',
        'default_privacy',
        'target_platforms',
        // Schedule
        'enable_auto_schedule',
        'schedule_settings',
        // Management
        'is_active',
        'is_default',
        'usage_count',
        'created_by',
        'sort_order',
    ];

    /**
     * การแปลงประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'music_tags' => 'array',
        'image_keywords' => 'array',
        'text_overlay_settings' => 'array',
        'default_tags' => 'array',
        'target_platforms' => 'array',
        'schedule_settings' => 'array',
        'music_duration' => 'integer',
        'images_count' => 'integer',
        'slide_duration' => 'integer',
        'transition_duration' => 'integer',
        'usage_count' => 'integer',
        'sort_order' => 'integer',
        'add_intro' => 'boolean',
        'add_outro' => 'boolean',
        'enable_ken_burns' => 'boolean',
        'enable_text_overlay' => 'boolean',
        'enable_watermark' => 'boolean',
        'enable_auto_schedule' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * แนวเพลงที่รองรับ
     *
     * @var array<string, string>
     */
    public const MUSIC_GENRES = [
        'pop' => 'Pop',
        'rock' => 'Rock',
        'jazz' => 'Jazz',
        'classical' => 'Classical',
        'electronic' => 'Electronic/EDM',
        'lofi' => 'Lo-Fi',
        'ambient' => 'Ambient',
        'hiphop' => 'Hip Hop',
        'rnb' => 'R&B',
        'country' => 'Country',
        'folk' => 'Folk',
        'latin' => 'Latin',
        'reggae' => 'Reggae',
        'blues' => 'Blues',
        'metal' => 'Metal',
        'punk' => 'Punk',
        'indie' => 'Indie',
        'soul' => 'Soul',
        'funk' => 'Funk',
        'disco' => 'Disco',
        'house' => 'House',
        'techno' => 'Techno',
        'trance' => 'Trance',
        'dubstep' => 'Dubstep',
        'chillout' => 'Chillout',
        'meditation' => 'Meditation',
        'cinematic' => 'Cinematic',
        'world' => 'World Music',
    ];

    /**
     * อารมณ์เพลงที่รองรับ
     *
     * @var array<string, string>
     */
    public const MUSIC_MOODS = [
        'happy' => 'Happy / สดใส',
        'sad' => 'Sad / เศร้า',
        'energetic' => 'Energetic / มีพลัง',
        'calm' => 'Calm / สงบ',
        'romantic' => 'Romantic / โรแมนติก',
        'dramatic' => 'Dramatic / ดราม่า',
        'mysterious' => 'Mysterious / ลึกลับ',
        'uplifting' => 'Uplifting / ให้กำลังใจ',
        'melancholic' => 'Melancholic / เศร้าซึ้ง',
        'peaceful' => 'Peaceful / สันติ',
        'aggressive' => 'Aggressive / ดุดัน',
        'playful' => 'Playful / ขี้เล่น',
        'inspiring' => 'Inspiring / สร้างแรงบันดาลใจ',
        'nostalgic' => 'Nostalgic / คิดถึง',
        'dark' => 'Dark / มืดมน',
        'bright' => 'Bright / สว่างไสว',
    ];

    /**
     * สไตล์ภาพที่รองรับ
     *
     * @var array<string, string>
     */
    public const IMAGE_STYLES = [
        'realistic' => 'Realistic / สมจริง',
        'anime' => 'Anime / อนิเมะ',
        '3d' => '3D Render',
        'illustration' => 'Illustration / วาด',
        'watercolor' => 'Watercolor / สีน้ำ',
        'oil_painting' => 'Oil Painting / สีน้ำมัน',
        'pencil' => 'Pencil Sketch / ดินสอ',
        'digital_art' => 'Digital Art',
        'minimalist' => 'Minimalist / มินิมอล',
        'abstract' => 'Abstract / นามธรรม',
        'vintage' => 'Vintage / วินเทจ',
        'cyberpunk' => 'Cyberpunk',
        'fantasy' => 'Fantasy / แฟนตาซี',
        'surreal' => 'Surreal / เหนือจริง',
        'pop_art' => 'Pop Art',
        'noir' => 'Film Noir',
        'pixel' => 'Pixel Art',
        'comic' => 'Comic / การ์ตูน',
    ];

    /**
     * ความละเอียดวีดีโอที่รองรับ
     *
     * @var array<string, array>
     */
    public const VIDEO_RESOLUTIONS = [
        '720p' => ['width' => 1280, 'height' => 720, 'label' => 'HD (720p)'],
        '1080p' => ['width' => 1920, 'height' => 1080, 'label' => 'Full HD (1080p)'],
        '1440p' => ['width' => 2560, 'height' => 1440, 'label' => 'QHD (1440p)'],
        '4k' => ['width' => 3840, 'height' => 2160, 'label' => '4K UHD'],
    ];

    /**
     * Transition ที่รองรับ
     *
     * @var array<string, string>
     */
    public const TRANSITIONS = [
        'fade' => 'Fade / จางหาย',
        'slide_left' => 'Slide Left / เลื่อนซ้าย',
        'slide_right' => 'Slide Right / เลื่อนขวา',
        'slide_up' => 'Slide Up / เลื่อนขึ้น',
        'slide_down' => 'Slide Down / เลื่อนลง',
        'zoom_in' => 'Zoom In / ซูมเข้า',
        'zoom_out' => 'Zoom Out / ซูมออก',
        'dissolve' => 'Dissolve / ละลาย',
        'wipe' => 'Wipe / ลบ',
        'none' => 'None / ไม่มี',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * ผู้สร้างเทมเพลต
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * โปรเจกต์ที่ใช้เทมเพลตนี้
     */
    public function projects(): HasMany
    {
        return $this->hasMany(VideoAutoProject::class, 'template_id');
    }

    /**
     * ตารางเวลาที่ใช้เทมเพลตนี้
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(VideoAutoSchedule::class, 'template_id');
    }

    // =========================================
    // Scopes
    // =========================================

    /**
     * Scope: กรองเฉพาะที่ active
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: เรียงตาม sort_order
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderByDesc('is_default')->orderBy('sort_order');
    }

    /**
     * Scope: กรองตามแนวเพลง
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByGenre($query, string $genre)
    {
        return $query->where('music_genre', $genre);
    }

    // =========================================
    // Accessors & Mutators
    // =========================================

    /**
     * สร้าง slug อัตโนมัติจากชื่อ
     */
    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = $value;

        // สร้าง slug ถ้ายังไม่มี
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    /**
     * ดึง label แนวเพลง
     */
    public function getMusicGenreLabelAttribute(): ?string
    {
        return self::MUSIC_GENRES[$this->music_genre] ?? $this->music_genre;
    }

    /**
     * ดึง label อารมณ์เพลง
     */
    public function getMusicMoodLabelAttribute(): ?string
    {
        return self::MUSIC_MOODS[$this->music_mood] ?? $this->music_mood;
    }

    /**
     * ดึง label สไตล์ภาพ
     */
    public function getImageStyleLabelAttribute(): ?string
    {
        return self::IMAGE_STYLES[$this->image_style] ?? $this->image_style;
    }

    /**
     * คำนวณความยาววีดีโอโดยประมาณ (วินาที)
     */
    public function getEstimatedVideoDurationAttribute(): int
    {
        $slideDuration = $this->images_count * $this->slide_duration;
        $transitionDuration = ($this->images_count - 1) * ($this->transition_duration / 1000);

        return (int) ceil($slideDuration + $transitionDuration);
    }

    // =========================================
    // Methods
    // =========================================

    /**
     * เพิ่ม usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * ตั้งเป็น default template
     */
    public function setAsDefault(): void
    {
        // ยกเลิก default อื่นๆ
        static::where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->is_default = true;
        $this->save();
    }

    /**
     * ทำสำเนาเทมเพลต
     */
    public function duplicate(?string $newName = null): static
    {
        $clone = $this->replicate();
        $clone->name = $newName ?? $this->name.' (สำเนา)';
        $clone->slug = Str::slug($clone->name).'-'.Str::random(5);
        $clone->is_default = false;
        $clone->usage_count = 0;
        $clone->save();

        return $clone;
    }

    /**
     * สร้าง prompt สำหรับ Suno AI
     */
    public function buildMusicPrompt(): string
    {
        if ($this->music_prompt) {
            return $this->music_prompt;
        }

        $parts = [];

        if ($this->music_genre) {
            $parts[] = $this->music_genre.' music';
        }

        if ($this->music_mood) {
            $parts[] = $this->music_mood.' mood';
        }

        if ($this->music_style) {
            $parts[] = $this->music_style.' style';
        }

        if ($this->music_tags) {
            $parts = array_merge($parts, $this->music_tags);
        }

        return implode(', ', $parts);
    }

    /**
     * สร้าง prompt สำหรับ Freepik AI
     */
    public function buildImagePrompt(int $imageIndex = 0): string
    {
        $template = $this->image_prompt_template ?? '{style} image of {theme}, {color_scheme} colors';

        $replacements = [
            '{style}' => $this->image_style ?? 'artistic',
            '{theme}' => $this->image_theme ?? 'abstract',
            '{color_scheme}' => $this->image_color_scheme ?? 'vibrant',
            '{index}' => $imageIndex + 1,
        ];

        // เพิ่ม keywords
        if ($this->image_keywords && isset($this->image_keywords[$imageIndex % count($this->image_keywords)])) {
            $replacements['{keyword}'] = $this->image_keywords[$imageIndex % count($this->image_keywords)];
        }

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}
