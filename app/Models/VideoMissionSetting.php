<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * VideoMissionSetting Model
 *
 * การตั้งค่าระบบภารกิจดูคลิปรับรางวัล
 * ใช้ caching เพื่อเพิ่มประสิทธิภาพ
 *
 * @property int $id
 * @property string $key ชื่อการตั้งค่า
 * @property string|null $value ค่า
 * @property string $type ประเภท (string, integer, boolean, json, decimal)
 * @property string $group กลุ่ม
 */
class VideoMissionSetting extends Model
{
    use HasFactory;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'video_mission_settings';

    /**
     * คอลัมน์ที่สามารถกรอกข้อมูลได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'label_th',
        'description',
        'description_th',
        'is_public',
    ];

    /**
     * การแปลงประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Cache key prefix
     *
     * @var string
     */
    protected const CACHE_PREFIX = 'video_mission_setting:';

    /**
     * Cache TTL (minutes)
     *
     * @var int
     */
    protected const CACHE_TTL = 60;

    // ==================== SCOPES ====================

    /**
     * กรองตามกลุ่ม
     */
    public function scopeInGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * เฉพาะ public
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    // ==================== ACCESSORS ====================

    /**
     * ดึง label ตาม locale
     *
     * @return string
     */
    public function getDisplayLabelAttribute(): string
    {
        $locale = app()->getLocale();
        return ($locale === 'th' && $this->label_th) ? $this->label_th : ($this->label ?? $this->key);
    }

    /**
     * ดึง description ตาม locale
     *
     * @return string|null
     */
    public function getDisplayDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return ($locale === 'th' && $this->description_th) ? $this->description_th : $this->description;
    }

    /**
     * ดึงค่าที่แปลงแล้ว
     *
     * @return mixed
     */
    public function getCastedValueAttribute()
    {
        return $this->castValue($this->value, $this->type);
    }

    // ==================== STATIC METHODS ====================

    /**
     * ดึงค่า setting
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $cacheKey = self::CACHE_PREFIX . $key;

        return Cache::remember($cacheKey, self::CACHE_TTL * 60, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return $setting->casted_value;
        });
    }

    /**
     * ตั้งค่า setting
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $type
     * @return self
     */
    public static function set(string $key, $value, ?string $type = null): self
    {
        $setting = static::firstOrNew(['key' => $key]);

        // แปลงค่า
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
            $type = $type ?? 'json';
        } elseif (is_bool($value)) {
            $value = $value ? 'true' : 'false';
            $type = $type ?? 'boolean';
        }

        $setting->value = $value;

        if ($type) {
            $setting->type = $type;
        }

        $setting->save();

        // Clear cache
        Cache::forget(self::CACHE_PREFIX . $key);

        return $setting;
    }

    /**
     * ดึงหลาย settings พร้อมกัน
     *
     * @param array $keys
     * @return array
     */
    public static function getMany(array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = static::get($key);
        }

        return $result;
    }

    /**
     * ดึง settings ตามกลุ่ม
     *
     * @param string $group
     * @return array
     */
    public static function getGroup(string $group): array
    {
        $cacheKey = self::CACHE_PREFIX . 'group:' . $group;

        return Cache::remember($cacheKey, self::CACHE_TTL * 60, function () use ($group) {
            $settings = static::inGroup($group)->get();

            $result = [];
            foreach ($settings as $setting) {
                $result[$setting->key] = $setting->casted_value;
            }

            return $result;
        });
    }

    /**
     * ดึง settings ทั้งหมด
     *
     * @return array
     */
    public static function getAll(): array
    {
        $cacheKey = self::CACHE_PREFIX . 'all';

        return Cache::remember($cacheKey, self::CACHE_TTL * 60, function () {
            $settings = static::all();

            $result = [];
            foreach ($settings as $setting) {
                $result[$setting->key] = $setting->casted_value;
            }

            return $result;
        });
    }

    /**
     * ดึง public settings (สำหรับ frontend)
     *
     * @return array
     */
    public static function getPublicSettings(): array
    {
        $cacheKey = self::CACHE_PREFIX . 'public';

        return Cache::remember($cacheKey, self::CACHE_TTL * 60, function () {
            $settings = static::public()->get();

            $result = [];
            foreach ($settings as $setting) {
                $result[$setting->key] = $setting->casted_value;
            }

            return $result;
        });
    }

    /**
     * Clear all cache
     *
     * @return void
     */
    public static function clearCache(): void
    {
        $settings = static::all();

        foreach ($settings as $setting) {
            Cache::forget(self::CACHE_PREFIX . $setting->key);
        }

        Cache::forget(self::CACHE_PREFIX . 'all');
        Cache::forget(self::CACHE_PREFIX . 'public');

        // Clear group caches
        $groups = static::distinct('group')->pluck('group');
        foreach ($groups as $group) {
            Cache::forget(self::CACHE_PREFIX . 'group:' . $group);
        }
    }

    /**
     * ตรวจสอบว่าระบบเปิดใช้งานหรือไม่
     *
     * @return bool
     */
    public static function isSystemEnabled(): bool
    {
        return static::get('system_enabled', true);
    }

    /**
     * ตรวจสอบว่าอยู่ในโหมด maintenance หรือไม่
     *
     * @return bool
     */
    public static function isMaintenanceMode(): bool
    {
        return static::get('maintenance_mode', false);
    }

    /**
     * ตรวจสอบว่าระบบป้องกันโกงเปิดหรือไม่
     *
     * @return bool
     */
    public static function isAntiCheatEnabled(): bool
    {
        return static::get('anti_cheat_enabled', true);
    }

    /**
     * ดึง heartbeat interval
     *
     * @return int
     */
    public static function getHeartbeatInterval(): int
    {
        return (int) static::get('heartbeat_interval_seconds', 5);
    }

    /**
     * ดึง max tab switches
     *
     * @return int
     */
    public static function getMaxTabSwitches(): int
    {
        return (int) static::get('max_tab_switches', 3);
    }

    /**
     * ดึง suspicious threshold
     *
     * @return int
     */
    public static function getSuspiciousThreshold(): int
    {
        return (int) static::get('suspicious_threshold_score', 70);
    }

    /**
     * ดึง default daily limit
     *
     * @return int
     */
    public static function getDefaultDailyLimit(): int
    {
        return (int) static::get('default_daily_limit', 10);
    }

    // ==================== HELPER METHODS ====================

    /**
     * แปลงค่าตาม type
     *
     * @param string|null $value
     * @param string $type
     * @return mixed
     */
    protected function castValue(?string $value, string $type)
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'decimal', 'float' => (float) $value,
            'json', 'array' => json_decode($value, true) ?? [],
            default => $value,
        };
    }

    /**
     * Boot method - clear cache เมื่อมีการเปลี่ยนแปลง
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($setting) {
            Cache::forget(self::CACHE_PREFIX . $setting->key);
            Cache::forget(self::CACHE_PREFIX . 'all');
            Cache::forget(self::CACHE_PREFIX . 'public');
            Cache::forget(self::CACHE_PREFIX . 'group:' . $setting->group);
        });

        static::deleted(function ($setting) {
            Cache::forget(self::CACHE_PREFIX . $setting->key);
            Cache::forget(self::CACHE_PREFIX . 'all');
            Cache::forget(self::CACHE_PREFIX . 'public');
            Cache::forget(self::CACHE_PREFIX . 'group:' . $setting->group);
        });
    }
}
