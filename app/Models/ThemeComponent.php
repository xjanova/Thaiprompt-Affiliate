<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ThemeComponent Model
 *
 * จัดการ Component Settings สำหรับ Arrow X Theme
 *
 * @property int $id
 * @property int $theme_setting_id
 * @property string $component_name
 * @property string $component_type
 * @property bool $is_enabled
 * @property int|null $height
 * @property string|null $width
 * @property int $padding_x
 * @property int $padding_y
 * @property int $margin_x
 * @property int $margin_y
 * @property int $border_width
 * @property int $border_radius
 * @property string|null $border_color
 * @property string $shadow_size
 * @property bool $hover_shadow_enabled
 * @property bool $neumorphic_enabled
 * @property int $neumorphic_depth
 * @property string|null $neumorphic_light_shadow
 * @property string|null $neumorphic_dark_shadow
 * @property int $opacity
 * @property int $blur_intensity
 * @property float $hover_scale
 * @property int $hover_lift
 * @property int $animation_duration
 * @property string|null $custom_css
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ThemeComponent extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'theme_components';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'theme_setting_id',
        'component_name',
        'component_type',
        'is_enabled',
        'height',
        'width',
        'padding_x',
        'padding_y',
        'margin_x',
        'margin_y',
        'border_width',
        'border_radius',
        'border_color',
        'shadow_size',
        'hover_shadow_enabled',
        'neumorphic_enabled',
        'neumorphic_depth',
        'neumorphic_light_shadow',
        'neumorphic_dark_shadow',
        'opacity',
        'blur_intensity',
        'hover_scale',
        'hover_lift',
        'animation_duration',
        'custom_css',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'theme_setting_id' => 'integer',
        'is_enabled' => 'boolean',
        'height' => 'integer',
        'padding_x' => 'integer',
        'padding_y' => 'integer',
        'margin_x' => 'integer',
        'margin_y' => 'integer',
        'border_width' => 'integer',
        'border_radius' => 'integer',
        'hover_shadow_enabled' => 'boolean',
        'neumorphic_enabled' => 'boolean',
        'neumorphic_depth' => 'integer',
        'opacity' => 'integer',
        'blur_intensity' => 'integer',
        'hover_scale' => 'float',
        'hover_lift' => 'integer',
        'animation_duration' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ ThemeSetting
     *
     * @return BelongsTo
     */
    public function themeSetting(): BelongsTo
    {
        return $this->belongsTo(ThemeSetting::class);
    }
}
