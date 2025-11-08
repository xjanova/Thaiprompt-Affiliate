<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppThemeSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'theme_name',
        'logo_url',
        'logo_dark_url',
        'favicon_url',
        'app_icon_url',
        'splash_screen_url',
        'splash_screen_dark_url',
        'primary_color',
        'primary_dark_color',
        'primary_light_color',
        'secondary_color',
        'secondary_dark_color',
        'secondary_light_color',
        'background_color',
        'background_dark_color',
        'surface_color',
        'surface_dark_color',
        'text_primary_color',
        'text_primary_dark_color',
        'text_secondary_color',
        'text_secondary_dark_color',
        'success_color',
        'warning_color',
        'error_color',
        'info_color',
        'border_color',
        'border_dark_color',
        'divider_color',
        'divider_dark_color',
        'font_family',
        'font_family_en',
        'font_size_base',
        'font_size_small',
        'font_size_large',
        'font_size_xlarge',
        'border_radius_small',
        'border_radius_medium',
        'border_radius_large',
        'border_radius_xlarge',
        'spacing_small',
        'spacing_medium',
        'spacing_large',
        'spacing_xlarge',
        'animation_duration',
        'animation_curve',
        'dark_mode_enabled',
        'dark_mode_default',
        'is_active',
    ];

    protected $casts = [
        'font_size_base' => 'integer',
        'font_size_small' => 'integer',
        'font_size_large' => 'integer',
        'font_size_xlarge' => 'integer',
        'border_radius_small' => 'integer',
        'border_radius_medium' => 'integer',
        'border_radius_large' => 'integer',
        'border_radius_xlarge' => 'integer',
        'spacing_small' => 'integer',
        'spacing_medium' => 'integer',
        'spacing_large' => 'integer',
        'spacing_xlarge' => 'integer',
        'animation_duration' => 'integer',
        'dark_mode_enabled' => 'boolean',
        'dark_mode_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the singleton instance
     */
    public static function getInstance()
    {
        return static::firstOrCreate([], [
            'theme_name' => 'default',
            'primary_color' => '#3B82F6',
            'is_active' => true,
        ]);
    }
}
