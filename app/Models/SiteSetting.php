<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Site Setting Model
 *
 * จัดการการตั้งค่าเว็บไซต์
 *
 * @property int $id
 * @property string $site_name ชื่อเว็บไซต์
 * @property string|null $site_description คำอธิบายเว็บไซต์
 * @property string|null $logo Path ของโลโก้
 * @property string|null $logo_dark Path ของโลโก้ dark mode
 * @property bool $logo_spin ให้โลโก้หมุนหรือไม่
 * @property string|null $favicon Path ของ favicon
 * @property string|null $meta_keywords คำค้นหา SEO
 * @property string|null $meta_description คำอธิบาย SEO
 * @property string|null $contact_email อีเมลติดต่อ
 * @property string|null $contact_phone เบอร์ติดต่อ
 * @property string|null $contact_address ที่อยู่ติดต่อ
 * @property string|null $facebook_url Facebook URL
 * @property string|null $twitter_url Twitter URL
 * @property string|null $instagram_url Instagram URL
 * @property string|null $line_url LINE URL
 * @property string|null $youtube_url YouTube URL
 * @property string|null $google_analytics_id Google Analytics ID
 * @property string|null $facebook_pixel_id Facebook Pixel ID
 * @property string|null $google_tag_manager_id Google Tag Manager ID
 * @property bool $maintenance_mode โหมดปิดปรับปรุง
 * @property string|null $maintenance_message ข้อความแจ้งเตือนปิดปรับปรุง
 * @property string|null $header_scripts Scripts สำหรับ head
 * @property string|null $footer_scripts Scripts สำหรับ footer
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class SiteSetting extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'site_settings';

    /**
     * The attributes that are mass assignable.
     *
     * หมายเหตุ: สี Theme ถูกจัดการโดย ThemeSetting model (Custom Theme)
     *
     * @var array<string>
     */
    protected $fillable = [
        'site_name',
        'site_description',
        'logo',
        'logo_dark',
        'logo_spin',
        'favicon',
        'meta_keywords',
        'meta_description',
        // 'primary_color',   // ลบออก - ใช้ Custom Theme แทน
        // 'secondary_color', // ลบออก - ใช้ Custom Theme แทน
        'contact_email',
        'contact_phone',
        'contact_address',
        'facebook_url',
        'twitter_url',
        'instagram_url',
        'line_url',
        'youtube_url',
        'google_analytics_id',
        'facebook_pixel_id',
        'google_tag_manager_id',
        'maintenance_mode',
        'maintenance_message',
        'header_scripts',
        'footer_scripts',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'logo_spin' => 'boolean',
        'maintenance_mode' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ดึงการตั้งค่าเว็บไซต์ (แบบ Singleton พร้อม Cache)
     *
     * @return SiteSetting
     */
    public static function getSetting(): SiteSetting
    {
        return Cache::remember('site_settings', 3600, function () {
            // ดึงข้อมูลการตั้งค่าแรก (ควรมีแค่ 1 row)
            $setting = self::first();

            // ถ้าไม่มี สร้างใหม่
            if (!$setting) {
                $setting = self::create([
                    'site_name' => 'TP-Affiliate',
                    'site_description' => 'ระบบ Affiliate Marketing ที่ทรงพลังที่สุด',
                    'logo_spin' => false,
                    // สีถูกจัดการโดย ThemeSetting model (Custom Theme)
                    'maintenance_mode' => false,
                ]);
            }

            return $setting;
        });
    }

    /**
     * ล้าง Cache หลังจาก update
     *
     * @return void
     */
    public static function clearCache(): void
    {
        Cache::forget('site_settings');
    }

    /**
     * Boot method
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // ล้าง cache เมื่อมีการอัพเดท
        static::saved(function () {
            self::clearCache();
        });

        static::deleted(function () {
            self::clearCache();
        });
    }

    /**
     * ดึง URL ของโลโก้
     *
     * @return string|null
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) {
            return asset('images/logo.png'); // Default logo
        }

        return Storage::disk('public')->url($this->logo);
    }

    /**
     * ดึง URL ของโลโก้ dark mode
     *
     * @return string|null
     */
    public function getLogoDarkUrlAttribute(): ?string
    {
        if (!$this->logo_dark) {
            return $this->logo_url; // ใช้โลโก้ปกติถ้าไม่มี dark logo
        }

        return Storage::disk('public')->url($this->logo_dark);
    }

    /**
     * ดึง URL ของ favicon
     *
     * @return string|null
     */
    public function getFaviconUrlAttribute(): ?string
    {
        if (!$this->favicon) {
            return asset('favicon.ico'); // Default favicon
        }

        return Storage::disk('public')->url($this->favicon);
    }
}
