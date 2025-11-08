<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WindowsUiSetting extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'windows_ui_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    /**
     * Get setting value by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    /**
     * Set setting value
     *
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @return WindowsUiSetting
     */
    public static function set(string $key, $value, string $type = 'string')
    {
        // Convert arrays/objects to JSON before storing
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
            $type = 'json';
        }

        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
            ]
        );
    }

    /**
     * Get all settings as key-value pairs
     *
     * @return array
     */
    public static function getAll(): array
    {
        $settings = self::all();
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->key] = self::castValue($setting->value, $setting->type);
        }

        return $result;
    }

    /**
     * Cast value based on type
     *
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    protected static function castValue($value, $type)
    {
        return match($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'array', 'json' => json_decode($value, true) ?? [],
            'color' => $value, // Return as-is for color values
            default => $value,
        };
    }

    /**
     * Get Start Menu items
     *
     * @return array
     */
    public static function getStartMenuItems(): array
    {
        return self::get('windows_start_menu_items', []);
    }

    /**
     * Get Taskbar Apps
     *
     * @return array
     */
    public static function getTaskbarApps(): array
    {
        return self::get('windows_taskbar_apps', []);
    }

    /**
     * Get System Tray Icons
     *
     * @return array
     */
    public static function getSystemTrayIcons(): array
    {
        return self::get('windows_system_tray_icons', []);
    }

    /**
     * Get RGB Settings
     *
     * @return array
     */
    public static function getRgbSettings(): array
    {
        return [
            'enabled' => self::get('windows_rgb_enabled', true),
            'colors' => self::get('windows_rgb_colors', ['#FF0080', '#00F0FF', '#7F00FF', '#FF3D00']),
            'speed' => self::get('windows_rgb_speed', 3),
            'glow' => self::get('windows_rgb_glow', true),
        ];
    }

    /**
     * Get Taskbar Settings
     *
     * @return array
     */
    public static function getTaskbarSettings(): array
    {
        return [
            'position' => self::get('windows_taskbar_position', 'top'),
            'height' => self::get('windows_taskbar_height', 48),
            'blur' => self::get('windows_taskbar_blur', true),
            'transparency' => self::get('windows_taskbar_transparency', 95),
        ];
    }

    /**
     * Check if spaceship theme is enabled
     *
     * @return bool
     */
    public static function isSpaceshipTheme(): bool
    {
        return self::get('windows_spaceship_theme', true);
    }
}
