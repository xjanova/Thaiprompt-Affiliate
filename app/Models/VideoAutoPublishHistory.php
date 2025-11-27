<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

/**
 * VideoAutoPublishHistory Model
 *
 * ประวัติการโพสต์วีดีโอไปยัง Social Media
 *
 * @property int $id
 * @property string $uuid UUID
 * @property int $project_id โปรเจกต์
 * @property int|null $job_id Job
 * @property int|null $schedule_id Schedule
 * @property string $platform Platform
 * @property string|null $platform_account บัญชี
 * @property string $title ชื่อวีดีโอ
 * @property string|null $description คำอธิบาย
 * @property array|null $hashtags Hashtags
 * @property string|null $thumbnail_path Thumbnail
 * @property string|null $thumbnail_url URL Thumbnail
 * @property string|null $video_path Path วีดีโอ
 * @property string|null $music_title ชื่อเพลง
 * @property string|null $music_genre แนวเพลง
 * @property int|null $video_duration ความยาว
 * @property int|null $video_size ขนาดไฟล์
 * @property string $status สถานะ
 * @property string|null $platform_post_id ID โพสต์
 * @property string|null $platform_url URL โพสต์
 * @property \Carbon\Carbon|null $scheduled_at เวลาตั้งไว้
 * @property \Carbon\Carbon|null $published_at เวลาโพสต์
 * @property string|null $error_message Error
 * @property int $retry_count Retry count
 * @property int $views_count ยอดวิว
 * @property int $likes_count ยอดไลค์
 * @property int $comments_count ยอดคอมเม้นต์
 * @property int $shares_count ยอดแชร์
 * @property \Carbon\Carbon|null $engagement_updated_at
 * @property bool $source_deleted ลบไฟล์แล้ว
 * @property \Carbon\Carbon|null $source_deleted_at
 * @property array|null $api_response
 * @property int|null $published_by ผู้โพสต์
 */
class VideoAutoPublishHistory extends Model
{
    use SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'video_auto_publish_history';

    /**
     * คอลัมน์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'uuid',
        'project_id',
        'job_id',
        'schedule_id',
        'platform',
        'platform_account',
        'title',
        'description',
        'hashtags',
        'thumbnail_path',
        'thumbnail_url',
        'video_path',
        'music_title',
        'music_genre',
        'video_duration',
        'video_size',
        'status',
        'platform_post_id',
        'platform_url',
        'scheduled_at',
        'published_at',
        'error_message',
        'retry_count',
        'views_count',
        'likes_count',
        'comments_count',
        'shares_count',
        'engagement_updated_at',
        'source_deleted',
        'source_deleted_at',
        'api_response',
        'published_by',
    ];

    /**
     * การแปลงประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'hashtags' => 'array',
        'api_response' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'engagement_updated_at' => 'datetime',
        'source_deleted_at' => 'datetime',
        'source_deleted' => 'boolean',
        'video_duration' => 'integer',
        'video_size' => 'integer',
        'views_count' => 'integer',
        'likes_count' => 'integer',
        'comments_count' => 'integer',
        'shares_count' => 'integer',
        'retry_count' => 'integer',
    ];

    /**
     * Platforms ที่รองรับ
     *
     * @var array<string, array>
     */
    public const PLATFORMS = [
        'youtube' => [
            'label' => 'YouTube',
            'icon' => 'fab fa-youtube',
            'color' => 'red',
            'bg' => 'bg-red-500',
        ],
        'facebook' => [
            'label' => 'Facebook',
            'icon' => 'fab fa-facebook',
            'color' => 'blue',
            'bg' => 'bg-blue-600',
        ],
        'instagram' => [
            'label' => 'Instagram',
            'icon' => 'fab fa-instagram',
            'color' => 'pink',
            'bg' => 'bg-gradient-to-r from-purple-500 to-pink-500',
        ],
        'tiktok' => [
            'label' => 'TikTok',
            'icon' => 'fab fa-tiktok',
            'color' => 'black',
            'bg' => 'bg-black',
        ],
        'twitter' => [
            'label' => 'Twitter/X',
            'icon' => 'fab fa-x-twitter',
            'color' => 'black',
            'bg' => 'bg-black',
        ],
        'threads' => [
            'label' => 'Threads',
            'icon' => 'fas fa-at',
            'color' => 'black',
            'bg' => 'bg-black',
        ],
        'line_voom' => [
            'label' => 'LINE VOOM',
            'icon' => 'fab fa-line',
            'color' => 'green',
            'bg' => 'bg-green-500',
        ],
        'other' => [
            'label' => 'อื่นๆ',
            'icon' => 'fas fa-globe',
            'color' => 'gray',
            'bg' => 'bg-gray-500',
        ],
    ];

    /**
     * สถานะที่เป็นไปได้
     *
     * @var array<string, array>
     */
    public const STATUSES = [
        'pending' => [
            'label' => 'รอโพสต์',
            'color' => 'gray',
            'icon' => 'fa-clock',
        ],
        'uploading' => [
            'label' => 'กำลังอัปโหลด',
            'color' => 'blue',
            'icon' => 'fa-upload',
        ],
        'processing' => [
            'label' => 'กำลังประมวลผล',
            'color' => 'yellow',
            'icon' => 'fa-spinner fa-spin',
        ],
        'published' => [
            'label' => 'โพสต์แล้ว',
            'color' => 'green',
            'icon' => 'fa-check',
        ],
        'failed' => [
            'label' => 'ล้มเหลว',
            'color' => 'red',
            'icon' => 'fa-times',
        ],
        'deleted' => [
            'label' => 'ถูกลบ',
            'color' => 'gray',
            'icon' => 'fa-trash',
        ],
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

        static::creating(function ($history) {
            if (empty($history->uuid)) {
                $history->uuid = (string) Str::uuid();
            }
        });
    }

    // =========================================
    // Relationships
    // =========================================

    /**
     * โปรเจกต์ที่เกี่ยวข้อง
     *
     * @return BelongsTo
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(VideoAutoProject::class, 'project_id');
    }

    /**
     * Job ที่เกี่ยวข้อง
     *
     * @return BelongsTo
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(VideoAutoJob::class, 'job_id');
    }

    /**
     * Schedule ที่เกี่ยวข้อง
     *
     * @return BelongsTo
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(VideoAutoSchedule::class, 'schedule_id');
    }

    /**
     * ผู้โพสต์
     *
     * @return BelongsTo
     */
    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    // =========================================
    // Scopes
    // =========================================

    /**
     * Scope: กรองตาม platform
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $platform
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

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
     * Scope: โพสต์สำเร็จ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope: โพสต์วันนี้
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeToday($query)
    {
        return $query->whereDate('published_at', today());
    }

    /**
     * Scope: ยังไม่ลบไฟล์ต้นฉบับ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSourceNotDeleted($query)
    {
        return $query->where('source_deleted', false);
    }

    // =========================================
    // Accessors
    // =========================================

    /**
     * ดึงข้อมูล platform
     *
     * @return array|null
     */
    public function getPlatformInfoAttribute(): ?array
    {
        return self::PLATFORMS[$this->platform] ?? null;
    }

    /**
     * ดึง label ของ platform
     *
     * @return string
     */
    public function getPlatformLabelAttribute(): string
    {
        return self::PLATFORMS[$this->platform]['label'] ?? $this->platform;
    }

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
            return Storage::url($this->thumbnail_path);
        }

        return null;
    }

    /**
     * คำนวณ engagement รวม
     *
     * @return int
     */
    public function getTotalEngagementAttribute(): int
    {
        return $this->views_count + $this->likes_count +
               $this->comments_count + $this->shares_count;
    }

    /**
     * แสดงเวลาโพสต์แบบ human readable
     *
     * @return string|null
     */
    public function getPublishedAtHumanAttribute(): ?string
    {
        return $this->published_at?->diffForHumans();
    }

    /**
     * แสดงขนาดไฟล์แบบ human readable
     *
     * @return string|null
     */
    public function getVideoSizeHumanAttribute(): ?string
    {
        if (!$this->video_size) {
            return null;
        }

        $bytes = $this->video_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    // =========================================
    // Methods
    // =========================================

    /**
     * บันทึกการโพสต์สำเร็จ
     *
     * @param string $postId ID ของโพสต์บน Platform
     * @param string $url URL ของโพสต์
     * @param array $response Response จาก API
     * @return void
     */
    public function markAsPublished(string $postId, string $url, array $response = []): void
    {
        $this->status = 'published';
        $this->platform_post_id = $postId;
        $this->platform_url = $url;
        $this->published_at = now();
        $this->api_response = $response;
        $this->save();

        // อัพเดท project
        $this->project?->incrementPublishCount();
    }

    /**
     * บันทึกการโพสต์ล้มเหลว
     *
     * @param string $error
     * @return void
     */
    public function markAsFailed(string $error): void
    {
        $this->status = 'failed';
        $this->error_message = $error;
        $this->increment('retry_count');
        $this->save();
    }

    /**
     * อัพเดท engagement
     *
     * @param array $data
     * @return void
     */
    public function updateEngagement(array $data): void
    {
        $this->fill([
            'views_count' => $data['views'] ?? $this->views_count,
            'likes_count' => $data['likes'] ?? $this->likes_count,
            'comments_count' => $data['comments'] ?? $this->comments_count,
            'shares_count' => $data['shares'] ?? $this->shares_count,
            'engagement_updated_at' => now(),
        ]);
        $this->save();
    }

    /**
     * ลบไฟล์ต้นฉบับ
     *
     * ⚠️ จะลบได้เฉพาะเมื่อโพสต์สำเร็จเท่านั้น!
     *
     * @param bool $force บังคับลบแม้ยังไม่โพสต์สำเร็จ
     * @return bool
     */
    public function deleteSourceFiles(bool $force = false): bool
    {
        // ถ้าลบไปแล้ว
        if ($this->source_deleted) {
            return true;
        }

        // ⚠️ ห้ามลบถ้ายังไม่โพสต์สำเร็จ (ยกเว้น force)
        if (!$force && $this->status !== 'published') {
            return false;
        }

        // ⚠️ ต้องมี platform_post_id และ platform_url เพื่อยืนยันว่าโพสต์สำเร็จจริง
        if (!$force && (empty($this->platform_post_id) || empty($this->platform_url))) {
            return false;
        }

        // ลบไฟล์วีดีโอถ้ามี
        if ($this->video_path && Storage::exists($this->video_path)) {
            Storage::delete($this->video_path);
        }

        $this->source_deleted = true;
        $this->source_deleted_at = now();
        $this->save();

        return true;
    }

    /**
     * ตรวจสอบว่าสามารถลบไฟล์ต้นฉบับได้หรือไม่
     *
     * @return bool
     */
    public function canDeleteSourceFiles(): bool
    {
        // ต้องโพสต์สำเร็จ
        if ($this->status !== 'published') {
            return false;
        }

        // ต้องมี ID และ URL ของโพสต์
        if (empty($this->platform_post_id) || empty($this->platform_url)) {
            return false;
        }

        // ยังไม่ได้ลบ
        if ($this->source_deleted) {
            return false;
        }

        return true;
    }

    /**
     * สร้างประวัติจาก project
     *
     * @param VideoAutoProject $project
     * @param string $platform
     * @param array $data
     * @return static
     */
    public static function createFromProject(
        VideoAutoProject $project,
        string $platform,
        array $data = []
    ): static {
        return static::create(array_merge([
            'project_id' => $project->id,
            'platform' => $platform,
            'title' => $project->video_title ?? $project->name,
            'description' => $project->video_description,
            'hashtags' => $project->hashtags,
            'thumbnail_path' => $project->thumbnail_path ?? $project->video_thumbnail,
            'video_path' => $project->generated_video_path,
            'music_title' => $project->music_title,
            'music_genre' => $project->music_genre,
            'video_duration' => $project->video_duration,
            'video_size' => $project->video_size,
            'status' => 'pending',
        ], $data));
    }
}
