<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Facebook OAuth Settings (DB-backed config)
 *
 * เก็บ config สำหรับ Facebook Login ใน DB แทน .env
 * ทำให้ admin แก้ไขจากหน้าหลังบ้านได้โดยไม่ต้อง redeploy
 *
 * Pattern เดียวกับ LineOaSetting
 */
class FacebookOAuthSetting extends Model
{
    use HasFactory;

    /**
     * ชื่อตาราง
     */
    protected $table = 'facebook_oauth_settings';

    protected $fillable = [
        'app_id',
        'app_secret',
        'redirect_uri',
        'graph_version',
        'scopes',
        'is_enabled',
        'auto_create_user',
        'auto_create_wallet',
        'auto_create_mlm_member',
        'last_login_at',
        'total_logins',
    ];

    protected $casts = [
        'scopes' => 'array',
        'is_enabled' => 'boolean',
        'auto_create_user' => 'boolean',
        'auto_create_wallet' => 'boolean',
        'auto_create_mlm_member' => 'boolean',
        'last_login_at' => 'datetime',
        'total_logins' => 'integer',
    ];

    protected $hidden = [
        'app_secret',
    ];

    /**
     * Cache key สำหรับ singleton instance
     */
    protected const CACHE_KEY = 'facebook_oauth_settings';

    /**
     * Cache TTL (วินาที)
     */
    protected const CACHE_TTL = 3600;

    /**
     * ดึง settings (singleton + cached)
     *
     * Fallback chain:
     *   1. DB record (cached)
     *   2. .env values (FACEBOOK_CLIENT_ID, FACEBOOK_CLIENT_SECRET) — สำหรับ migrate from env
     *   3. null (caller ต้องจัดการ)
     */
    public static function getActive(): ?self
    {
        try {
            return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
                $setting = self::first();

                // Auto-create row จาก .env ถ้ายังไม่มีใน DB (migration smooth)
                if (! $setting && env('FACEBOOK_CLIENT_ID')) {
                    $setting = self::create([
                        'app_id' => env('FACEBOOK_CLIENT_ID'),
                        'app_secret' => env('FACEBOOK_CLIENT_SECRET'),
                        'redirect_uri' => env('FACEBOOK_REDIRECT_URI', '/auth/facebook/callback'),
                        'is_enabled' => true,
                    ]);
                }

                return $setting;
            });
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Clear cache (เรียกหลัง update)
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * ตรวจว่า config ครบและเปิดใช้งาน
     */
    public function isReady(): bool
    {
        return $this->is_enabled
            && ! empty($this->app_id)
            && ! empty($this->app_secret);
    }

    /**
     * Static helper — เร็วกว่าการเรียก getActive()->isReady()
     */
    public static function isConfigured(): bool
    {
        $setting = self::getActive();

        return $setting && $setting->isReady();
    }

    /**
     * ดึง redirect URI (absolute URL หรือ path)
     *
     * ถ้าเป็น path เริ่มต้นด้วย / → resolve เป็น full URL ผ่าน app.url
     */
    public function getRedirectUri(): string
    {
        $uri = $this->redirect_uri ?: '/auth/facebook/callback';

        // Already absolute
        if (preg_match('#^https?://#i', $uri)) {
            return $uri;
        }

        return rtrim(config('app.url'), '/') . '/' . ltrim($uri, '/');
    }

    /**
     * ดึง scopes — fallback default
     */
    public function getScopes(): array
    {
        return ! empty($this->scopes) ? $this->scopes : ['email', 'public_profile'];
    }

    /**
     * อัพเดทสถิติ login (เรียกหลัง login สำเร็จ)
     */
    public function recordLogin(): void
    {
        $this->increment('total_logins');
        $this->update(['last_login_at' => now()]);
        self::clearCache();
    }

    /**
     * เมื่อ save → clear cache อัตโนมัติ
     */
    protected static function booted(): void
    {
        static::saved(fn () => self::clearCache());
        static::deleted(fn () => self::clearCache());
    }
}
