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
        'input_type',
        'allowed_values',
        'group',
        'description',
        'description_th',
        'placeholder',
        'unit',
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

        return match ($this->type) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'decimal', 'float' => (float) $value,
            'json' => json_decode($value, true),
            'array' => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }

    /**
     * Get allowed values as array
     */
    public function getAllowedValuesArray()
    {
        if (! $this->allowed_values) {
            return [];
        }

        return is_string($this->allowed_values)
            ? json_decode($this->allowed_values, true)
            : $this->allowed_values;
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
     *
     * ⚠️ ตรวจสอบ PV rate >= 0.01 และ commission_per_pv > 0 อัตโนมัติ
     *
     * @throws \InvalidArgumentException ถ้าค่าไม่ถูกต้อง
     */
    public static function set(string $key, $value): bool
    {
        // ตรวจสอบค่าที่สำคัญก่อนบันทึก
        static::validateCriticalValue($key, $value);

        $setting = static::where('key', $key)->first();

        if (! $setting) {
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
     * ตรวจสอบค่าที่สำคัญก่อนบันทึก
     *
     * ป้องกันการตั้งค่าที่อาจทำให้ระบบ MLM ทำงานผิดพลาด:
     * - global_pv_rate: ต้อง >= 0.01 (ห้ามเป็น 0 หรือลบ)
     * - commission_per_pv: ต้อง > 0
     * - max_commission_percentage: ต้อง > 0 และ <= 100
     *
     * @param  string  $key  ชื่อ setting
     * @param  mixed  $value  ค่าที่จะบันทึก
     *
     * @throws \InvalidArgumentException ถ้าค่าไม่ถูกต้อง
     */
    protected static function validateCriticalValue(string $key, $value): void
    {
        $numericValue = is_numeric($value) ? (float) $value : null;

        match ($key) {
            'global_pv_rate' => $numericValue !== null && $numericValue < 0.01
                ? throw new \InvalidArgumentException("PV Rate ต้อง >= 0.01 (ได้รับ: {$value})")
                : null,
            'commission_per_pv' => $numericValue !== null && $numericValue <= 0
                ? throw new \InvalidArgumentException("Commission Per PV ต้อง > 0 (ได้รับ: {$value})")
                : null,
            'max_commission_percentage' => $numericValue !== null && ($numericValue <= 0 || $numericValue > 100)
                ? throw new \InvalidArgumentException("Max Commission Percentage ต้องอยู่ระหว่าง 0-100 (ได้รับ: {$value})")
                : null,
            default => null,
        };
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
     * ล้าง cache ของ MLM settings ทั้งหมด (เฉพาะคีย์ของ MLM ไม่แตะ cache ส่วนอื่นของแอป)
     *
     * ⚠️ เดิมใช้ Cache::flush() ซึ่งล้าง cache ทั้งแอปทุกครั้งที่บันทึก setting เดียว
     */
    public static function clearCache(): void
    {
        Cache::forget('mlm_all_settings');

        foreach (static::pluck('key') as $key) {
            Cache::forget("mlm_setting_{$key}");
        }
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
