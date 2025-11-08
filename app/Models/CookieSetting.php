<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CookieSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    /**
     * ดึงค่า setting
     */
    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($setting->value, true),
            'integer' => (int) $setting->value,
            default => $setting->value,
        };
    }

    /**
     * ตั้งค่า setting
     */
    public static function set(string $key, $value, string $type = 'text', ?string $description = null): void
    {
        $settingValue = match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };

        self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $settingValue,
                'type' => $type,
                'description' => $description,
            ]
        );
    }
}
