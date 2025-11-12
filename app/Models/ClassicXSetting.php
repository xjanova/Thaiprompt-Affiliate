<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ClassicXSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    /**
     * Get a setting value by key
     *
     * @param string $key Setting key
     * @param mixed $default Default value if setting doesn't exist
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("classic_x_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return static::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting value
     *
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @param string|null $type Value type
     * @return bool
     */
    public static function set(string $key, $value, ?string $type = null): bool
    {
        $setting = static::firstOrNew(['key' => $key]);

        if ($type) {
            $setting->type = $type;
        }

        $setting->value = is_array($value) ? json_encode($value) : $value;
        $result = $setting->save();

        // Clear cache
        Cache::forget("classic_x_setting_{$key}");

        return $result;
    }

    /**
     * Get all settings as an associative array
     *
     * @return array
     */
    public static function getAll(): array
    {
        return Cache::remember('classic_x_settings_all', 3600, function () {
            $settings = static::all();
            $result = [];

            foreach ($settings as $setting) {
                $result[$setting->key] = static::castValue($setting->value, $setting->type);
            }

            return $result;
        });
    }

    /**
     * Cast value based on type
     *
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    protected static function castValue($value, string $type)
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode($value, true),
            'array' => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }

    /**
     * Clear all cached settings
     *
     * @return void
     */
    public static function clearCache(): void
    {
        Cache::forget('classic_x_settings_all');

        $settings = static::all();
        foreach ($settings as $setting) {
            Cache::forget("classic_x_setting_{$setting->key}");
        }
    }

    /**
     * Reset to default settings
     *
     * @return void
     */
    public static function resetToDefaults(): void
    {
        // This will be handled by re-running the migration or manually setting defaults
        static::clearCache();
    }
}
