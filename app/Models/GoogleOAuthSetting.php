<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * ตั้งค่า "เข้าสู่ระบบด้วย Google" (เก็บใน DB แก้จากหลังบ้านได้ ไม่ต้อง redeploy)
 *
 * แบบเดียวกับ FacebookOAuthSetting แต่:
 *   - client_secret เข้ารหัสในฐานข้อมูล (cast encrypted)
 *   - redirect URI ไม่ให้แก้ — ใช้ route('google.callback') ตัวเดียวเสมอ
 *     (Google เทียบ URI แบบตรงตัวอักษร ให้แอดมินคัดลอกจากหน้าตั้งค่าไปวางใน Google Cloud)
 *
 * ยังไม่ตั้งค่า / ปิดอยู่ → isConfigured() = false → ปุ่ม Google ทุกที่ถูกซ่อน
 *
 * @property int $id
 * @property string|null $client_id
 * @property string|null $client_secret
 * @property bool $is_active
 * @property \Carbon\Carbon|null $last_login_at
 * @property int $total_logins
 */
class GoogleOAuthSetting extends Model
{
    /**
     * ชื่อตาราง
     */
    protected $table = 'google_oauth_settings';

    protected $fillable = [
        'client_id',
        'client_secret',
        'is_active',
        'last_login_at',
        'total_logins',
    ];

    protected $casts = [
        'client_secret' => 'encrypted',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'total_logins' => 'integer',
    ];

    /**
     * ห้ามหลุดออกไปกับ toArray()/JSON
     */
    protected $hidden = [
        'client_secret',
    ];

    /**
     * cache key ของแถวตั้งค่า (มีแถวเดียว)
     */
    protected const CACHE_KEY = 'google_oauth_settings';

    /**
     * อายุ cache (วินาที)
     */
    protected const CACHE_TTL = 3600;

    /**
     * ดึงแถวตั้งค่า (cache ไว้ 1 ชม. — บันทึกเมื่อไหร่ล้าง cache ให้เอง)
     *
     * ตารางยังไม่มี / DB ล่ม → null (ปุ่ม Google ซ่อน ไม่ทำให้หน้าเว็บพัง)
     */
    public static function getActive(): ?self
    {
        try {
            return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => self::first());
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * ล้าง cache (เรียกหลังแก้ไข)
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * เปิดใช้งาน + มี client id/secret ครบ
     *
     * ถอดรหัส secret ไม่ได้ (เช่น APP_KEY เปลี่ยน) = ถือว่ายังไม่พร้อม
     */
    public function isReady(): bool
    {
        try {
            return (bool) $this->is_active
                && trim((string) $this->client_id) !== ''
                && trim((string) $this->client_secret) !== '';
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * ทางลัด: Google Login พร้อมใช้หรือยัง (ใช้ตัดสินว่าจะแสดงปุ่มไหม)
     */
    public static function isConfigured(): bool
    {
        $setting = self::getActive();

        return $setting !== null && $setting->isReady();
    }

    /**
     * Redirect URI ที่ต้องลงทะเบียนใน Google Cloud (Authorized redirect URIs)
     */
    public static function redirectUri(): string
    {
        return route('google.callback');
    }

    /**
     * นับสถิติการล็อกอิน (เรียกหลังล็อกอินสำเร็จ)
     */
    public function recordLogin(): void
    {
        $this->increment('total_logins');
        $this->update(['last_login_at' => now()]);
    }

    /**
     * บันทึก/ลบเมื่อไหร่ → ล้าง cache อัตโนมัติ
     */
    protected static function booted(): void
    {
        static::saved(fn () => self::clearCache());
        static::deleted(fn () => self::clearCache());
    }
}
