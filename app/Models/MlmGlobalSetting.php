<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class MlmGlobalSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'description_th',
        'sort_order',
        'is_editable',
        'is_visible',
    ];

    protected $casts = [
        'is_editable' => 'boolean',
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get setting value with proper type casting
     */
    public function getTypedValue()
    {
        $value = $this->value;

        return match($this->type) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'decimal', 'float' => (float) $value,
            'json' => json_decode($value, true),
            'array' => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }

    /**
     * Get a setting value by key with caching
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("mlm_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->getTypedValue() : $default;
        });
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, $value): bool
    {
        $setting = static::where('key', $key)->first();

        if (!$setting) {
            $setting = static::create([
                'key' => $key,
                'value' => is_array($value) || is_object($value) ? json_encode($value) : $value,
                'type' => static::detectType($value),
            ]);
        } else {
            $setting->update([
                'value' => is_array($value) || is_object($value) ? json_encode($value) : $value,
            ]);
        }

        Cache::forget("mlm_setting_{$key}");
        return true;
    }

    /**
     * Get all settings by group
     */
    public static function getByGroup(string $group): array
    {
        return static::where('group', $group)
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(function ($setting) {
                return [$setting->key => $setting->getTypedValue()];
            })
            ->toArray();
    }

    /**
     * Get all settings as array
     */
    public static function getAll(): array
    {
        return Cache::remember('mlm_all_settings', 3600, function () {
            return static::all()
                ->mapWithKeys(function ($setting) {
                    return [$setting->key => $setting->getTypedValue()];
                })
                ->toArray();
        });
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        Cache::flush();
    }

    /**
     * Detect type from value
     */
    protected static function detectType($value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value)) {
            return 'integer';
        }
        if (is_float($value)) {
            return 'decimal';
        }
        if (is_array($value) || is_object($value)) {
            return 'json';
        }
        return 'string';
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            static::clearCache();
        });

        static::deleted(function () {
            static::clearCache();
        });
    }
}
