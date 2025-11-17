<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * ThemeSetting Model
 *
 * ตั้งค่าหลักสำหรับ Arrow X Theme System
 *
 * @property int $id
 * @property string $theme_name
 * @property string $theme_version
 * @property bool $is_active
 * @property string|null $logo_path
 * @property string|null $footer_logo_path
 * @property string $footer_logo_animation
 * @property string|null $favicon_path
 * @property string $brand_name
 * @property string|null $brand_tagline
 * @property string $layout_type
 * @property int $sidebar_width
 * @property int $navbar_height
 * @property int $footer_height
 * @property int $global_opacity
 * @property int $sidebar_opacity
 * @property int $navbar_opacity
 * @property int $card_opacity
 * @property int $modal_opacity
 * @property int $card_blur_intensity
 * @property int $card_border_width
 * @property int $card_border_radius
 * @property string $card_shadow_intensity
 * @property string $default_language
 * @property array $available_languages
 * @property bool $rtl_enabled
 * @property bool $bg_effects_enabled
 * @property string $bg_circle1_color1
 * @property string $bg_circle1_color2
 * @property string $bg_circle2_color1
 * @property string $bg_circle2_color2
 * @property string $bg_circle3_color1
 * @property string $bg_circle3_color2
 * @property string $bg_animation_speed
 * @property int $bg_circle_opacity
 * @property int $bg_circle_blur
 * @property int $bg_circle_size
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ThemeSetting extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'theme_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'theme_name',
        'theme_version',
        'is_active',
        'logo_path',
        'footer_logo_path',
        'footer_logo_animation',
        'favicon_path',
        'brand_name',
        'brand_tagline',
        'layout_type',
        'sidebar_width',
        'navbar_height',
        'footer_height',
        'global_opacity',
        'sidebar_opacity',
        'navbar_opacity',
        'card_opacity',
        'modal_opacity',
        'card_blur_intensity',
        'card_border_width',
        'card_border_radius',
        'card_shadow_intensity',
        'default_language',
        'available_languages',
        'rtl_enabled',
        // Background Effects
        'bg_effects_enabled',
        'bg_circle1_color1',
        'bg_circle1_color2',
        'bg_circle2_color1',
        'bg_circle2_color2',
        'bg_circle3_color1',
        'bg_circle3_color2',
        'bg_animation_speed',
        'bg_circle_opacity',
        'bg_circle_blur',
        'bg_circle_size',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'sidebar_width' => 'integer',
        'navbar_height' => 'integer',
        'footer_height' => 'integer',
        'global_opacity' => 'integer',
        'sidebar_opacity' => 'integer',
        'navbar_opacity' => 'integer',
        'card_opacity' => 'integer',
        'modal_opacity' => 'integer',
        'card_blur_intensity' => 'integer',
        'card_border_width' => 'integer',
        'card_border_radius' => 'integer',
        'available_languages' => 'array',
        'rtl_enabled' => 'boolean',
        // Background Effects
        'bg_effects_enabled' => 'boolean',
        'bg_circle_opacity' => 'integer',
        'bg_circle_blur' => 'integer',
        'bg_circle_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ ThemeColor
     *
     * @return HasOne
     */
    public function color(): HasOne
    {
        return $this->hasOne(ThemeColor::class);
    }

    /**
     * ความสัมพันธ์กับ ThemeRgbEffect (หลายเอฟเฟกต์)
     *
     * @return HasMany
     */
    public function rgbEffects(): HasMany
    {
        return $this->hasMany(ThemeRgbEffect::class);
    }

    /**
     * ความสัมพันธ์กับ ThemeTypography
     *
     * @return HasOne
     */
    public function typography(): HasOne
    {
        return $this->hasOne(ThemeTypography::class);
    }

    /**
     * ความสัมพันธ์กับ ThemeComponent (หลาย components)
     *
     * @return HasMany
     */
    public function components(): HasMany
    {
        return $this->hasMany(ThemeComponent::class);
    }

    /**
     * ดึง Theme Setting ที่กำลัง active
     *
     * @return static|null
     */
    public static function active(): ?static
    {
        return static::where('is_active', true)->first();
    }
}
