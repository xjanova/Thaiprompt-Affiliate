<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * VideoAutoProject Model
 *
 * โปรเจกต์สร้างวีดีโออัตโนมัติ
 *
 * @property int $id
 * @property string $uuid UUID
 * @property int|null $template_id เทมเพลตที่ใช้
 * @property int|null $created_by ผู้สร้าง
 * @property string $name ชื่อโปรเจกต์
 * @property string|null $description คำอธิบาย
 * @property string $status สถานะ
 * @property string|null $music_genre แนวเพลง
 * @property string|null $music_mood อารมณ์เพลง
 * @property string|null $music_style สไตล์
 * @property int|null $music_duration ความยาว
 * @property string|null $music_prompt Prompt
 * @property string|null $generated_music_url URL เพลง
 * @property string|null $generated_music_path Path เพลง
 * @property string|null $music_title ชื่อเพลง
 * @property array|null $music_metadata Metadata
 * @property string|null $image_style สไตล์ภาพ
 * @property string|null $image_theme ธีมภาพ
 * @property string|null $image_color_scheme โทนสี
 * @property int|null $images_count จำนวนภาพ
 * @property string|null $image_prompt Prompt ภาพ
 * @property array|null $generated_images ภาพที่สร้าง
 * @property int $generated_images_count จำนวนภาพที่สร้าง
 * @property string|null $video_resolution ความละเอียด
 * @property int|null $slide_duration ความยาว slide
 * @property string|null $transition_type Transition
 * @property bool|null $add_intro เพิ่ม intro
 * @property bool|null $add_outro เพิ่ม outro
 * @property string|null $generated_video_url URL วีดีโอ
 * @property string|null $generated_video_path Path วีดีโอ
 * @property int|null $video_duration ความยาววีดีโอ
 * @property int|null $video_size ขนาดไฟล์
 * @property array|null $video_metadata Metadata
 * @property string|null $video_title ชื่อวีดีโอ
 * @property string|null $video_description คำอธิบาย
 * @property array|null $video_tags Tags
 * @property string|null $video_category Category
 * @property string $video_privacy Privacy
 * @property string|null $video_thumbnail Thumbnail
 * @property array|null $target_platforms Platforms
 * @property array|null $published_urls URLs ที่โพส
 * @property array|null $publish_results ผลการโพส
 * @property bool $is_scheduled ตั้งเวลา
 * @property \Carbon\Carbon|null $scheduled_at เวลาโพส
 * @property \Carbon\Carbon|null $published_at เวลาที่โพส
 * @property int $progress ความคืบหน้า
 * @property string|null $current_step ขั้นตอนปัจจุบัน
 * @property \Carbon\Carbon|null $started_at เริ่มทำงาน
 * @property \Carbon\Carbon|null $completed_at เสร็จ
 * @property int|null $processing_time เวลาทำงาน
 * @property string|null $last_error Error
 * @property int $retry_count จำนวน retry
 * @property int $max_retries retry สูงสุด
 * @property array|null $statistics สถิติ
 */
class VideoAutoProject extends Model
{
    use SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'video_auto_projects';

    /**
     * คอลัมน์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'uuid',
        'template_id',
        'created_by',
        'name',
        'description',
        'status',
        // Music Config
        'music_genre',
        'music_mood',
        'music_style',
        'music_duration',
        'music_prompt',
        // Music Output
        'generated_music_url',
        'generated_music_path',
        'music_title',
        'music_metadata',
        // Image Config
        'image_style',
        'image_theme',
        'image_color_scheme',
        'images_count',
        'image_prompt',
        // Image Output
        'generated_images',
        'generated_images_count',
        // Video Config
        'video_resolution',
        'slide_duration',
        'transition_type',
        'add_intro',
        'add_outro',
        // Video Output
        'generated_video_url',
        'generated_video_path',
        'video_duration',
        'video_size',
        'video_metadata',
        // Publishing Config
        'video_title',
        'video_description',
        'video_tags',
        'video_category',
        'video_privacy',
        'video_thumbnail',
        // Publishing Status
        'target_platforms',
        'published_urls',
        'publish_results',
        // Scheduling
        'is_scheduled',
        'scheduled_at',
        'published_at',
        // Progress
        'progress',
        'current_step',
        'started_at',
        'completed_at',
        'processing_time',
        // Error
        'last_error',
        'retry_count',
        'max_retries',
        // Stats
        'statistics',
        // Thumbnail & Cleanup (เพิ่มเติม)
        'thumbnail_path',
        'thumbnail_url',
        'delete_source_after_publish',
        'source_files_deleted',
        'source_deleted_at',
        'publish_count',
        'last_published_at',
        'hashtags',
    ];

    /**
     * การแปลงประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'music_metadata' => 'array',
        'generated_images' => 'array',
        'video_metadata' => 'array',
        'video_tags' => 'array',
        'target_platforms' => 'array',
        'published_urls' => 'array',
        'publish_results' => 'array',
        'statistics' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_scheduled' => 'boolean',
        'add_intro' => 'boolean',
        'add_outro' => 'boolean',
        'music_duration' => 'integer',
        'images_count' => 'integer',
        'generated_images_count' => 'integer',
        'slide_duration' => 'integer',
        'video_duration' => 'integer',
        'video_size' => 'integer',
        'progress' => 'integer',
        'processing_time' => 'integer',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
        'delete_source_after_publish' => 'boolean',
        'source_files_deleted' => 'boolean',
        'source_deleted_at' => 'datetime',
        'publish_count' => 'integer',
        'last_published_at' => 'datetime',
        'hashtags' => 'array',
    ];

    /**
     * สถานะที่เป็นไปได้
     *
     * @var array<string, array>
     */
    public const STATUSES = [
        'draft' => [
            'label' => 'แบบร่าง',
            'color' => 'gray',
            'icon' => 'fa-edit',
        ],
        'pending' => [
            'label' => 'รอดำเนินการ',
            'color' => 'yellow',
            'icon' => 'fa-clock',
        ],
        'generating' => [
            'label' => 'กำลังสร้าง',
            'color' => 'blue',
            'icon' => 'fa-spinner fa-spin',
        ],
        'completed' => [
            'label' => 'สร้างเสร็จ',
            'color' => 'green',
            'icon' => 'fa-check',
        ],
        'publishing' => [
            'label' => 'กำลังโพส',
            'color' => 'purple',
            'icon' => 'fa-upload',
        ],
        'published' => [
            'label' => 'โพสแล้ว',
            'color' => 'green',
            'icon' => 'fa-check-double',
        ],
        'failed' => [
            'label' => 'ล้มเหลว',
            'color' => 'red',
            'icon' => 'fa-times',
        ],
        'cancelled' => [
            'label' => 'ยกเลิก',
            'color' => 'gray',
            'icon' => 'fa-ban',
        ],
    ];

    /**
     * ขั้นตอนการทำงาน
     *
     * @var array<string, array>
     */
    public const STEPS = [
        'initializing' => ['label' => 'กำลังเตรียมการ', 'progress' => 5],
        'generating_music' => ['label' => 'กำลังสร้างเพลง', 'progress' => 20],
        'downloading_music' => ['label' => 'กำลังดาวน์โหลดเพลง', 'progress' => 30],
        'generating_images' => ['label' => 'กำลังสร้างภาพ', 'progress' => 50],
        'downloading_images' => ['label' => 'กำลังดาวน์โหลดภาพ', 'progress' => 60],
        'creating_video' => ['label' => 'กำลังสร้างวีดีโอ', 'progress' => 80],
        'finalizing' => ['label' => 'กำลังตกแต่งขั้นสุดท้าย', 'progress' => 90],
        'uploading' => ['label' => 'กำลังอัปโหลด', 'progress' => 95],
        'completed' => ['label' => 'เสร็จสิ้น', 'progress' => 100],
    ];

    // =========================================
    // Boot
    // =========================================

    /**
     * Boot the model
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // สร้าง UUID อัตโนมัติ
        static::creating(function ($project) {
            if (empty($project->uuid)) {
                $project->uuid = (string) Str::uuid();
            }
        });
    }

    // =========================================
    // Relationships
    // =========================================

    /**
     * เทมเพลตที่ใช้
     *
     * @return BelongsTo
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(VideoAutoTemplate::class, 'template_id');
    }

    /**
     * ผู้สร้าง
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Jobs ที่เกี่ยวข้อง
     *
     * @return HasMany
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(VideoAutoJob::class, 'project_id');
    }

    /**
     * Logs ที่เกี่ยวข้อง
     *
     * @return HasMany
     */
    public function logs(): HasMany
    {
        return $this->hasMany(VideoAutoJobLog::class, 'project_id');
    }

    /**
     * ประวัติการโพสต์
     *
     * @return HasMany
     */
    public function publishHistory(): HasMany
    {
        return $this->hasMany(VideoAutoPublishHistory::class, 'project_id');
    }

    // =========================================
    // Scopes
    // =========================================

    /**
     * Scope: กรองตาม status
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|array $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStatus($query, $status)
    {
        if (is_array($status)) {
            return $query->whereIn('status', $status);
        }

        return $query->where('status', $status);
    }

    /**
     * Scope: รอดำเนินการ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: กำลังทำงาน
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInProgress($query)
    {
        return $query->whereIn('status', ['generating', 'publishing']);
    }

    /**
     * Scope: รอโพสตามตารางเวลา
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeScheduledForNow($query)
    {
        return $query->where('is_scheduled', true)
            ->where('status', 'completed')
            ->where('scheduled_at', '<=', now());
    }

    // =========================================
    // Accessors
    // =========================================

    /**
     * ดึงข้อมูล status
     *
     * @return array|null
     */
    public function getStatusInfoAttribute(): ?array
    {
        return self::STATUSES[$this->status] ?? null;
    }

    /**
     * ดึง label ของ status
     *
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status]['label'] ?? $this->status;
    }

    /**
     * ดึงสีของ status
     *
     * @return string
     */
    public function getStatusColorAttribute(): string
    {
        return self::STATUSES[$this->status]['color'] ?? 'gray';
    }

    /**
     * ดึง icon ของ status
     *
     * @return string
     */
    public function getStatusIconAttribute(): string
    {
        return self::STATUSES[$this->status]['icon'] ?? 'fa-question';
    }

    /**
     * ดึง label ของ current step
     *
     * @return string|null
     */
    public function getCurrentStepLabelAttribute(): ?string
    {
        return self::STEPS[$this->current_step]['label'] ?? $this->current_step;
    }

    /**
     * ตรวจสอบว่าสามารถ retry ได้หรือไม่
     *
     * @return bool
     */
    public function getCanRetryAttribute(): bool
    {
        return $this->status === 'failed' && $this->retry_count < $this->max_retries;
    }

    /**
     * ตรวจสอบว่าสร้างเสร็จแล้วหรือยัง
     *
     * @return bool
     */
    public function getIsCompletedAttribute(): bool
    {
        return in_array($this->status, ['completed', 'published']);
    }

    /**
     * คำนวณขนาดไฟล์แบบ human readable
     *
     * @return string|null
     */
    public function getVideoSizeHumanAttribute(): ?string
    {
        if (!$this->video_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->video_size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * คำนวณเวลาทำงานแบบ human readable
     *
     * @return string|null
     */
    public function getProcessingTimeHumanAttribute(): ?string
    {
        if (!$this->processing_time) {
            return null;
        }

        $minutes = floor($this->processing_time / 60);
        $seconds = $this->processing_time % 60;

        if ($minutes > 0) {
            return "{$minutes} นาที {$seconds} วินาที";
        }

        return "{$seconds} วินาที";
    }

    // =========================================
    // Methods
    // =========================================

    /**
     * อัพเดท status
     *
     * @param string $status
     * @param string|null $step
     * @return void
     */
    public function updateStatus(string $status, ?string $step = null): void
    {
        $this->status = $status;

        if ($step) {
            $this->current_step = $step;
            $this->progress = self::STEPS[$step]['progress'] ?? $this->progress;
        }

        // บันทึกเวลาตาม status
        if ($status === 'generating' && !$this->started_at) {
            $this->started_at = now();
        } elseif (in_array($status, ['completed', 'published', 'failed'])) {
            $this->completed_at = now();

            if ($this->started_at) {
                $this->processing_time = $this->completed_at->diffInSeconds($this->started_at);
            }
        }

        if ($status === 'published') {
            $this->published_at = now();
        }

        $this->save();
    }

    /**
     * อัพเดท progress
     *
     * @param int $progress
     * @param string|null $message
     * @return void
     */
    public function updateProgress(int $progress, ?string $message = null): void
    {
        $this->progress = min(100, max(0, $progress));

        if ($message) {
            $this->current_step = $message;
        }

        $this->save();
    }

    /**
     * บันทึก error
     *
     * @param string $error
     * @return void
     */
    public function recordError(string $error): void
    {
        $this->last_error = $error;
        $this->status = 'failed';
        $this->save();
    }

    /**
     * Retry การทำงาน
     *
     * @return bool
     */
    public function retry(): bool
    {
        if (!$this->can_retry) {
            return false;
        }

        $this->increment('retry_count');
        $this->status = 'pending';
        $this->last_error = null;
        $this->progress = 0;
        $this->current_step = null;
        $this->started_at = null;
        $this->completed_at = null;
        $this->processing_time = null;
        $this->save();

        return true;
    }

    /**
     * ยกเลิกโปรเจกต์
     *
     * @return void
     */
    public function cancel(): void
    {
        $this->status = 'cancelled';
        $this->save();

        // ยกเลิก jobs ที่กำลังทำงาน
        $this->jobs()
            ->whereIn('status', ['pending', 'queued', 'running'])
            ->update(['status' => 'cancelled']);
    }

    /**
     * บันทึกผลการโพส
     *
     * @param string $platform
     * @param bool $success
     * @param array $result
     * @return void
     */
    public function recordPublishResult(string $platform, bool $success, array $result): void
    {
        $results = $this->publish_results ?? [];
        $results[$platform] = array_merge($result, [
            'success' => $success,
            'published_at' => now()->toIso8601String(),
        ]);

        $this->publish_results = $results;

        if ($success && isset($result['url'])) {
            $urls = $this->published_urls ?? [];
            $urls[$platform] = $result['url'];
            $this->published_urls = $urls;
        }

        $this->save();
    }

    /**
     * สร้าง project จาก template
     *
     * @param VideoAutoTemplate $template
     * @param array $overrides
     * @return static
     */
    public static function createFromTemplate(VideoAutoTemplate $template, array $overrides = []): static
    {
        $template->incrementUsage();

        $data = array_merge([
            'template_id' => $template->id,
            'name' => $overrides['name'] ?? 'โปรเจกต์ใหม่ ' . now()->format('Y-m-d H:i'),
            'status' => 'draft',
            // Music
            'music_genre' => $template->music_genre,
            'music_mood' => $template->music_mood,
            'music_style' => $template->music_style,
            'music_duration' => $template->music_duration,
            'music_prompt' => $template->music_prompt,
            // Image
            'image_style' => $template->image_style,
            'image_theme' => $template->image_theme,
            'image_color_scheme' => $template->image_color_scheme,
            'images_count' => $template->images_count,
            'image_prompt' => $template->image_prompt_template,
            // Video
            'video_resolution' => $template->video_resolution,
            'slide_duration' => $template->slide_duration,
            'transition_type' => $template->transition_type,
            'add_intro' => $template->add_intro,
            'add_outro' => $template->add_outro,
            // Publishing
            'video_privacy' => $template->default_privacy,
            'video_tags' => $template->default_tags,
            'video_category' => $template->default_category,
            'target_platforms' => $template->target_platforms,
            // Default cleanup setting
            'delete_source_after_publish' => true,
        ], $overrides);

        return static::create($data);
    }

    /**
     * เพิ่ม publish count
     *
     * @return void
     */
    public function incrementPublishCount(): void
    {
        $this->increment('publish_count');
        $this->last_published_at = now();
        $this->save();
    }

    /**
     * ลบไฟล์ต้นฉบับหลังโพสต์สำเร็จ
     *
     * ⚠️ จะลบได้ต่อเมื่อมีการโพสต์ YouTube สำเร็จแล้วเท่านั้น!
     *
     * @param bool $force บังคับลบแม้ยังไม่โพสต์สำเร็จ
     * @return bool
     */
    public function deleteSourceFiles(bool $force = false): bool
    {
        if ($this->source_files_deleted) {
            return true;
        }

        // ⚠️ ตรวจสอบว่าโพสต์ YouTube สำเร็จแล้ว (ยกเว้น force)
        if (!$force && !$this->hasSuccessfulYouTubePublish()) {
            return false;
        }

        $deleted = [];

        // ลบไฟล์เพลง
        if ($this->generated_music_path) {
            $path = storage_path('app/public/' . $this->generated_music_path);
            if (file_exists($path)) {
                unlink($path);
                $deleted[] = 'music';
            }
        }

        // ลบภาพทั้งหมด
        if ($this->generated_images && is_array($this->generated_images)) {
            foreach ($this->generated_images as $image) {
                $imagePath = $image['path'] ?? $image;
                if (is_string($imagePath)) {
                    $fullPath = storage_path('app/public/' . $imagePath);
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }
            }
            $deleted[] = 'images';
        }

        // ลบวีดีโอ
        if ($this->generated_video_path) {
            $path = storage_path('app/public/' . $this->generated_video_path);
            if (file_exists($path)) {
                unlink($path);
                $deleted[] = 'video';
            }
        }

        // อัพเดทสถานะ
        $this->source_files_deleted = true;
        $this->source_deleted_at = now();
        $this->save();

        return count($deleted) > 0;
    }

    /**
     * ตรวจสอบว่ามีการโพสต์ YouTube สำเร็จหรือไม่
     *
     * @return bool
     */
    public function hasSuccessfulYouTubePublish(): bool
    {
        return $this->publishHistory()
            ->where('platform', 'youtube')
            ->where('status', 'published')
            ->whereNotNull('platform_post_id')
            ->whereNotNull('platform_url')
            ->exists();
    }

    /**
     * ตรวจสอบว่าสามารถลบไฟล์ต้นฉบับได้หรือไม่
     *
     * @return bool
     */
    public function canDeleteSourceFiles(): bool
    {
        // ต้องยังไม่ลบ
        if ($this->source_files_deleted) {
            return false;
        }

        // ต้องมีการโพสต์ YouTube สำเร็จ
        if (!$this->hasSuccessfulYouTubePublish()) {
            return false;
        }

        return true;
    }

    /**
     * ดึง URL ของ thumbnail
     *
     * @return string|null
     */
    public function getThumbnailFullUrlAttribute(): ?string
    {
        if ($this->thumbnail_url) {
            return $this->thumbnail_url;
        }

        if ($this->thumbnail_path) {
            return asset('storage/' . $this->thumbnail_path);
        }

        if ($this->video_thumbnail) {
            return asset('storage/' . $this->video_thumbnail);
        }

        return null;
    }
}
