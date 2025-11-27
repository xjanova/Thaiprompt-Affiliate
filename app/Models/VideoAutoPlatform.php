<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

/**
 * VideoAutoPlatform Model
 *
 * จัดการการเชื่อมต่อกับ Social Platforms ต่างๆ
 *
 * @property int $id
 * @property string $platform_type ประเภท: youtube, facebook, instagram, tiktok, twitter, threads
 * @property string $name ชื่อที่ตั้งเอง
 * @property string|null $channel_id Channel/Page ID
 * @property string|null $channel_name ชื่อ Channel/Page
 * @property string|null $channel_url URL
 * @property string|null $access_token Access Token
 * @property string|null $refresh_token Refresh Token
 * @property string|null $api_key API Key
 * @property string|null $client_id Client ID
 * @property string|null $client_secret Client Secret
 * @property array|null $extra_credentials ข้อมูลเพิ่มเติม
 * @property string $status สถานะ: connected, disconnected, expired, error
 * @property \Carbon\Carbon|null $token_expires_at Token หมดอายุ
 * @property \Carbon\Carbon|null $last_connected_at เชื่อมต่อล่าสุด
 * @property string|null $last_error Error ล่าสุด
 * @property bool $auto_publish โพสอัตโนมัติ
 * @property array|null $publish_settings ตั้งค่าการโพส
 * @property int $total_posts จำนวนโพสทั้งหมด
 * @property int $successful_posts โพสสำเร็จ
 * @property int $failed_posts โพสล้มเหลว
 * @property bool $is_active เปิดใช้งาน
 * @property bool $is_default ใช้เป็น default
 * @property int $priority ลำดับความสำคัญ
 */
class VideoAutoPlatform extends Model
{
    use SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'video_auto_platforms';

    /**
     * คอลัมน์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'platform_type',
        'name',
        'channel_id',
        'channel_name',
        'channel_url',
        'access_token',
        'refresh_token',
        'api_key',
        'client_id',
        'client_secret',
        'extra_credentials',
        'status',
        'token_expires_at',
        'last_connected_at',
        'last_error',
        'auto_publish',
        'publish_settings',
        'total_posts',
        'successful_posts',
        'failed_posts',
        'is_active',
        'is_default',
        'priority',
    ];

    /**
     * การแปลงประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'extra_credentials' => 'array',
        'publish_settings' => 'array',
        'token_expires_at' => 'datetime',
        'last_connected_at' => 'datetime',
        'auto_publish' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'total_posts' => 'integer',
        'successful_posts' => 'integer',
        'failed_posts' => 'integer',
        'priority' => 'integer',
    ];

    /**
     * คอลัมน์ที่ซ่อนจาก JSON
     *
     * @var array<string>
     */
    protected $hidden = [
        'access_token',
        'refresh_token',
        'api_key',
        'client_id',
        'client_secret',
    ];

    /**
     * ประเภท Platform ที่รองรับ
     *
     * @var array<string, array>
     */
    public const PLATFORM_TYPES = [
        'youtube' => [
            'name' => 'YouTube',
            'icon' => 'fab fa-youtube',
            'color' => '#FF0000',
            'supports_video' => true,
            'supports_shorts' => true,
            'max_video_duration' => 43200, // 12 ชั่วโมง
        ],
        'facebook' => [
            'name' => 'Facebook',
            'icon' => 'fab fa-facebook',
            'color' => '#1877F2',
            'supports_video' => true,
            'supports_reels' => true,
            'max_video_duration' => 14400, // 4 ชั่วโมง
        ],
        'instagram' => [
            'name' => 'Instagram',
            'icon' => 'fab fa-instagram',
            'color' => '#E4405F',
            'supports_video' => true,
            'supports_reels' => true,
            'max_video_duration' => 3600, // 60 นาที
        ],
        'tiktok' => [
            'name' => 'TikTok',
            'icon' => 'fab fa-tiktok',
            'color' => '#000000',
            'supports_video' => true,
            'max_video_duration' => 600, // 10 นาที
        ],
        'twitter' => [
            'name' => 'Twitter/X',
            'icon' => 'fab fa-x-twitter',
            'color' => '#000000',
            'supports_video' => true,
            'max_video_duration' => 140, // 2:20 นาที
        ],
        'threads' => [
            'name' => 'Threads',
            'icon' => 'fab fa-threads',
            'color' => '#000000',
            'supports_video' => true,
            'max_video_duration' => 300, // 5 นาที
        ],
        'lemon8' => [
            'name' => 'Lemon8',
            'icon' => 'fas fa-lemon',
            'color' => '#FFE135',
            'supports_video' => true,
            'supports_photo' => true,
            'max_video_duration' => 600, // 10 นาที (เหมือน TikTok)
        ],
        'line_voom' => [
            'name' => 'LINE VOOM',
            'icon' => 'fab fa-line',
            'color' => '#06C755',
            'supports_video' => true,
            'max_video_duration' => 300, // 5 นาที
        ],
    ];

    /**
     * สถานะที่เป็นไปได้
     *
     * @var array<string, string>
     */
    public const STATUSES = [
        'connected' => 'เชื่อมต่อแล้ว',
        'disconnected' => 'ยังไม่เชื่อมต่อ',
        'expired' => 'Token หมดอายุ',
        'error' => 'เกิดข้อผิดพลาด',
    ];

    // =========================================
    // Scopes
    // =========================================

    /**
     * Scope: กรองตาม platform type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('platform_type', $type);
    }

    /**
     * Scope: กรองเฉพาะที่เชื่อมต่อแล้ว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConnected($query)
    {
        return $query->where('status', 'connected');
    }

    /**
     * Scope: กรองเฉพาะที่ active
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: เรียงตาม priority
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderByDesc('is_default')->orderBy('priority');
    }

    // =========================================
    // Accessors & Mutators
    // =========================================

    /**
     * ถอดรหัส access token
     *
     * @return string|null
     */
    public function getDecryptedAccessTokenAttribute(): ?string
    {
        if (empty($this->access_token)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->access_token);
        } catch (\Exception $e) {
            return $this->access_token;
        }
    }

    /**
     * เข้ารหัส access token
     *
     * @param string|null $value
     * @return void
     */
    public function setAccessTokenAttribute(?string $value): void
    {
        $this->attributes['access_token'] = $value
            ? Crypt::encryptString($value)
            : null;
    }

    /**
     * ถอดรหัส refresh token
     *
     * @return string|null
     */
    public function getDecryptedRefreshTokenAttribute(): ?string
    {
        if (empty($this->refresh_token)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->refresh_token);
        } catch (\Exception $e) {
            return $this->refresh_token;
        }
    }

    /**
     * เข้ารหัส refresh token
     *
     * @param string|null $value
     * @return void
     */
    public function setRefreshTokenAttribute(?string $value): void
    {
        $this->attributes['refresh_token'] = $value
            ? Crypt::encryptString($value)
            : null;
    }

    /**
     * ดึงข้อมูล platform
     *
     * @return array|null
     */
    public function getPlatformInfoAttribute(): ?array
    {
        return self::PLATFORM_TYPES[$this->platform_type] ?? null;
    }

    /**
     * ดึง status label
     *
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * ตรวจสอบว่า token หมดอายุหรือไม่
     *
     * @return bool
     */
    public function getIsTokenExpiredAttribute(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->isPast();
    }

    /**
     * คำนวณ success rate
     *
     * @return float
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_posts === 0) {
            return 0;
        }

        return round(($this->successful_posts / $this->total_posts) * 100, 2);
    }

    // =========================================
    // Methods
    // =========================================

    /**
     * อัพเดทสถานะการเชื่อมต่อ
     *
     * @param string $status
     * @param string|null $error
     * @return void
     */
    public function updateConnectionStatus(string $status, ?string $error = null): void
    {
        $this->status = $status;
        $this->last_error = $error;

        if ($status === 'connected') {
            $this->last_connected_at = now();
            $this->last_error = null;
        }

        $this->save();
    }

    /**
     * บันทึกผลการโพส
     *
     * @param bool $success
     * @return void
     */
    public function recordPost(bool $success): void
    {
        $this->increment('total_posts');

        if ($success) {
            $this->increment('successful_posts');
        } else {
            $this->increment('failed_posts');
        }
    }

    /**
     * ตั้งเป็น default platform
     *
     * @return void
     */
    public function setAsDefault(): void
    {
        // ยกเลิก default เดิมของ platform type เดียวกัน
        static::ofType($this->platform_type)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->is_default = true;
        $this->save();
    }

    /**
     * ตรวจสอบว่าพร้อมโพสหรือไม่
     *
     * @return bool
     */
    public function isReadyToPublish(): bool
    {
        return $this->is_active
            && $this->status === 'connected'
            && !$this->is_token_expired
            && $this->auto_publish;
    }

    /**
     * รีเฟรช token
     *
     * @return bool
     */
    public function refreshToken(): bool
    {
        // TODO: implement token refresh logic per platform
        return false;
    }
}
