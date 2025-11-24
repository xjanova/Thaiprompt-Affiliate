<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * LineSignupSetting Model
 *
 * จัดการการตั้งค่าสำหรับระบบสมัครสมาชิกผ่าน LINE
 *
 * @property int $id
 * @property string $key คีย์การตั้งค่า
 * @property string|null $value ค่าการตั้งค่า
 * @property string $type ประเภทข้อมูล (string, boolean, integer, json)
 * @property string $group กลุ่มการตั้งค่า
 * @property string|null $description คำอธิบาย
 * @property bool $is_public แสดงสาธารณะได้หรือไม่
 * @property bool $is_editable แก้ไขได้หรือไม่
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class LineSignupSetting extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'line_signup_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'is_public',
        'is_editable',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_public' => 'boolean',
        'is_editable' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Cache key prefix
     */
    const CACHE_PREFIX = 'line_signup_setting:';

    /**
     * Cache duration (1 hour)
     */
    const CACHE_TTL = 3600;

    /**
     * Setting groups
     */
    const GROUP_SIGNUP = 'signup';
    const GROUP_OTP = 'otp';
    const GROUP_VALIDATION = 'validation';
    const GROUP_REWARDS = 'rewards';
    const GROUP_GAMIFICATION = 'gamification';
    const GROUP_INTEGRATION = 'integration';
    const GROUP_SESSION = 'session';
    const GROUP_GENERAL = 'general';

    /**
     * Setting types
     */
    const TYPE_STRING = 'string';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_INTEGER = 'integer';
    const TYPE_JSON = 'json';

    /**
     * ดึงค่า setting โดยใช้ cache
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember(
            self::CACHE_PREFIX . $key,
            self::CACHE_TTL,
            function () use ($key, $default) {
                $setting = self::where('key', $key)->first();

                if (!$setting) {
                    return $default;
                }

                return $setting->getCastedValue();
            }
        );
    }

    /**
     * บันทึกค่า setting และ clear cache
     *
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @param string $group
     * @param string|null $description
     * @return self
     */
    public static function set(
        string $key,
        $value,
        string $type = self::TYPE_STRING,
        string $group = self::GROUP_GENERAL,
        ?string $description = null
    ): self {
        // แปลงค่าเป็น string สำหรับเก็บใน database
        $stringValue = self::castToString($value, $type);

        // อัพเดทหรือสร้างใหม่
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stringValue,
                'type' => $type,
                'group' => $group,
                'description' => $description,
            ]
        );

        // Clear cache
        Cache::forget(self::CACHE_PREFIX . $key);

        return $setting;
    }

    /**
     * ลบ setting และ clear cache
     *
     * @param string $key
     * @return bool
     */
    public static function remove(string $key): bool
    {
        $deleted = self::where('key', $key)->delete();

        if ($deleted) {
            Cache::forget(self::CACHE_PREFIX . $key);
        }

        return (bool) $deleted;
    }

    /**
     * ดึงค่าทั้งหมดในกลุ่ม
     *
     * @param string $group
     * @return array
     */
    public static function getGroup(string $group): array
    {
        $settings = self::where('group', $group)->get();

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->getCastedValue();
        }

        return $result;
    }

    /**
     * Clear cache ทั้งหมด
     *
     * @return void
     */
    public static function clearCache(): void
    {
        $settings = self::all();

        foreach ($settings as $setting) {
            Cache::forget(self::CACHE_PREFIX . $setting->key);
        }
    }

    /**
     * แปลงค่าจาก string เป็นประเภทที่ถูกต้อง
     *
     * @return mixed
     */
    public function getCastedValue()
    {
        return match ($this->type) {
            self::TYPE_BOOLEAN => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            self::TYPE_INTEGER => (int) $this->value,
            self::TYPE_JSON => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * แปลงค่าเป็น string สำหรับเก็บใน database
     *
     * @param mixed $value
     * @param string $type
     * @return string|null
     */
    protected static function castToString($value, string $type): ?string
    {
        return match ($type) {
            self::TYPE_BOOLEAN => $value ? '1' : '0',
            self::TYPE_INTEGER => (string) $value,
            self::TYPE_JSON => json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * Boot the model
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Clear cache เมื่อมีการแก้ไข
        static::updated(function ($setting) {
            Cache::forget(self::CACHE_PREFIX . $setting->key);
        });

        // Clear cache เมื่อมีการลบ
        static::deleted(function ($setting) {
            Cache::forget(self::CACHE_PREFIX . $setting->key);
        });
    }
}
