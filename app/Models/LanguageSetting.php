<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class LanguageSetting extends Model
{
    protected $fillable = [
        'code',
        'name',
        'native_name',
        'flag_emoji',
        'flag_image_url',
        'is_enabled',
        'sort_order',
        'is_default',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Clear cache when language settings are updated
        static::saved(function () {
            Cache::forget('language_settings_enabled');
            Cache::forget('language_settings_all');
        });

        static::deleted(function () {
            Cache::forget('language_settings_enabled');
            Cache::forget('language_settings_all');
        });
    }

    /**
     * Get all enabled languages ordered by sort_order
     */
    public static function getEnabled()
    {
        return Cache::remember('language_settings_enabled', 3600, function () {
            return self::where('is_enabled', true)
                ->orderBy('sort_order')
                ->get();
        });
    }

    /**
     * Get all languages ordered by sort_order
     */
    public static function getAll()
    {
        return Cache::remember('language_settings_all', 3600, function () {
            return self::orderBy('sort_order')->get();
        });
    }

    /**
     * Get default language
     */
    public static function getDefault()
    {
        return self::where('is_default', true)->first();
    }

    /**
     * Get language by code
     */
    public static function getByCode(string $code)
    {
        return self::where('code', $code)->first();
    }

    /**
     * Set as default language
     */
    public function setAsDefault()
    {
        // Remove default from all other languages
        self::where('is_default', true)->update(['is_default' => false]);

        // Set this as default
        $this->is_default = true;
        $this->save();
    }

    /**
     * Get flag display (emoji or image)
     */
    public function getFlagDisplay(int $size = 24): string
    {
        if ($this->flag_image_url) {
            return sprintf(
                '<img src="%s" alt="%s" width="%d" height="%d" class="inline-block">',
                e($this->flag_image_url),
                e($this->name),
                $size,
                $size
            );
        }

        if ($this->flag_emoji) {
            return sprintf(
                '<span class="inline-block" style="font-size: %dpx;">%s</span>',
                $size,
                $this->flag_emoji
            );
        }

        return '🌐';
    }

    /**
     * Get available language codes
     */
    public static function getEnabledCodes(): array
    {
        return self::getEnabled()->pluck('code')->toArray();
    }

    /**
     * Format as array for API response
     */
    public function toApiArray(int $flagSize = 24, bool $showFlag = true, bool $showName = true): array
    {
        $data = [
            'code' => $this->code,
        ];

        if ($showFlag) {
            $data['flag'] = $this->flag_emoji ?? '🌐';
            if ($this->flag_image_url) {
                $data['flag_url'] = $this->flag_image_url;
            }
        }

        if ($showName) {
            $data['name'] = $this->name;
            $data['native_name'] = $this->native_name;
        }

        $data['is_default'] = $this->is_default;

        return $data;
    }
}
