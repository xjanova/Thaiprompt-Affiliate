<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FortuneHoroscopePost Model
 *
 * ติดตามการโพสดวงรายวันลง Facebook/LINE
 *
 * @property int $id
 * @property int $campaign_id
 * @property string $target_date
 * @property string $platform facebook/line
 * @property string|null $post_content เนื้อหาที่โพส
 * @property array|null $image_urls URLs ของภาพ
 * @property string $status pending/posting/posted/failed
 * @property string|null $platform_post_id
 * @property string|null $platform_post_url
 * @property array|null $platform_response
 * @property int $likes_count
 * @property int $comments_count
 * @property int $shares_count
 * @property int $views_count
 * @property string|null $error_message
 * @property int $retry_count
 * @property \Carbon\Carbon|null $published_at
 */
class FortuneHoroscopePost extends Model
{
    protected $table = 'fortune_horoscope_posts';

    public const STATUS_PENDING = 'pending';

    public const STATUS_POSTING = 'posting';

    public const STATUS_POSTED = 'posted';

    public const STATUS_FAILED = 'failed';

    public const PLATFORM_FACEBOOK = 'facebook';

    public const PLATFORM_LINE = 'line';

    protected $fillable = [
        'campaign_id',
        'target_date',
        'platform',
        'post_content',
        'image_urls',
        'status',
        'platform_post_id',
        'platform_post_url',
        'platform_response',
        'likes_count',
        'comments_count',
        'shares_count',
        'views_count',
        'error_message',
        'retry_count',
        'published_at',
    ];

    protected $casts = [
        'target_date' => 'date',
        'image_urls' => 'array',
        'platform_response' => 'array',
        'likes_count' => 'integer',
        'comments_count' => 'integer',
        'shares_count' => 'integer',
        'views_count' => 'integer',
        'retry_count' => 'integer',
        'published_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
        'retry_count' => 0,
        'likes_count' => 0,
        'comments_count' => 0,
        'shares_count' => 0,
        'views_count' => 0,
    ];

    // ============================================================
    // Relationships
    // ============================================================

    /**
     * แคมเปญที่สังกัด
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(FortuneHoroscopeCampaign::class, 'campaign_id');
    }

    // ============================================================
    // Scopes
    // ============================================================

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePosted($query)
    {
        return $query->where('status', self::STATUS_POSTED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    // ============================================================
    // Methods
    // ============================================================

    /**
     * กำลังโพส
     */
    public function markPosting(): self
    {
        $this->update(['status' => self::STATUS_POSTING]);

        return $this;
    }

    /**
     * โพสสำเร็จ
     */
    public function markPosted(?string $postId = null, ?string $postUrl = null, ?array $response = null): self
    {
        $this->update([
            'status' => self::STATUS_POSTED,
            'platform_post_id' => $postId,
            'platform_post_url' => $postUrl,
            'platform_response' => $response,
            'published_at' => now(),
            'error_message' => null,
        ]);

        return $this;
    }

    /**
     * โพสล้มเหลว
     */
    public function markFailed(string $error): self
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $error,
            'retry_count' => $this->retry_count + 1,
        ]);

        return $this;
    }

    /**
     * Label platform ภาษาไทย
     */
    public function getPlatformLabelAttribute(): string
    {
        return match ($this->platform) {
            self::PLATFORM_FACEBOOK => 'Facebook Page',
            self::PLATFORM_LINE => 'LINE OA',
            default => $this->platform,
        };
    }

    /**
     * Label สถานะภาษาไทย
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'รอโพส',
            self::STATUS_POSTING => 'กำลังโพส',
            self::STATUS_POSTED => 'โพสแล้ว',
            self::STATUS_FAILED => 'ล้มเหลว',
            default => $this->status,
        };
    }
}
