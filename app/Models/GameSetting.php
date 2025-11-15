<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * GameSetting Model
 *
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property string $type
 * @property string $group
 * @property string|null $description
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class GameSetting extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'game_settings';

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
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ดึงค่า setting ตาม key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        // ใช้ cache 1 ชั่วโมง
        return Cache::remember("game_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)
                ->where('is_active', true)
                ->first();

            if (!$setting) {
                return $default;
            }

            // แปลงค่าตาม type
            return self::castValue($setting->value, $setting->type);
        });
    }

    /**
     * ตั้งค่า setting
     *
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @param string $group
     * @return self
     */
    public static function set(string $key, $value, string $type = 'string', string $group = 'general'): self
    {
        // แปลงค่าเป็น string ก่อนเก็บ
        $stringValue = self::toString($value, $type);

        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stringValue,
                'type' => $type,
                'group' => $group,
                'is_active' => true,
            ]
        );

        // ล้าง cache
        Cache::forget("game_setting_{$key}");

        return $setting;
    }

    /**
     * แปลงค่าตาม type
     *
     * @param string|null $value
     * @param string $type
     * @return mixed
     */
    protected static function castValue($value, string $type)
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * แปลงค่าเป็น string สำหรับเก็บ
     *
     * @param mixed $value
     * @param string $type
     * @return string
     */
    protected static function toString($value, string $type): string
    {
        if ($type === 'json') {
            return json_encode($value);
        }

        if ($type === 'boolean') {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    /**
     * ดึง settings ทั้งหมดในกลุ่ม
     *
     * @param string $group
     * @return \Illuminate\Support\Collection
     */
    public static function getGroup(string $group)
    {
        return Cache::remember("game_settings_group_{$group}", 3600, function () use ($group) {
            return self::where('group', $group)
                ->where('is_active', true)
                ->get()
                ->mapWithKeys(function ($setting) {
                    return [$setting->key => self::castValue($setting->value, $setting->type)];
                });
        });
    }

    /**
     * ล้าง cache ทั้งหมด
     *
     * @return void
     */
    public static function clearCache(): void
    {
        // ล้าง cache setting ทั้งหมด
        $settings = self::all();
        foreach ($settings as $setting) {
            Cache::forget("game_setting_{$setting->key}");
        }

        // ล้าง cache กลุ่ม
        $groups = self::distinct('group')->pluck('group');
        foreach ($groups as $group) {
            Cache::forget("game_settings_group_{$group}");
        }
    }
}
