<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ThemeRgbEffect Model
 *
 * จัดการ RGB Effects สำหรับ Arrow X Theme
 *
 * @property int $id
 * @property int $theme_setting_id
 * @property string $effect_name
 * @property bool $is_enabled
 * @property string $target_element
 * @property string|null $custom_selector
 * @property string $trigger_state
 * @property string $animation_type
 * @property array $rgb_colors
 * @property int $animation_duration
 * @property string $animation_timing
 * @property string $intensity
 * @property int $blur_radius
 * @property int $delay
 * @property string $iteration_count
 * @property int $z_index
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ThemeRgbEffect extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'theme_rgb_effects';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'theme_setting_id',
        'effect_name',
        'is_enabled',
        'target_element',
        'custom_selector',
        'trigger_state',
        'animation_type',
        'rgb_colors',
        'animation_duration',
        'animation_timing',
        'intensity',
        'blur_radius',
        'delay',
        'iteration_count',
        'z_index',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'theme_setting_id' => 'integer',
        'is_enabled' => 'boolean',
        'rgb_colors' => 'array',
        'animation_duration' => 'integer',
        'blur_radius' => 'integer',
        'delay' => 'integer',
        'z_index' => 'integer',
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

    /**
     * ดึง CSS Selector สำหรับ element
     *
     * @return string
     */
    public function getCssSelector(): string
    {
        if ($this->target_element === 'custom') {
            return $this->custom_selector ?? '';
        }

        // Map target_element to CSS selectors
        $selectorMap = [
            'sidebar' => '.sidebar, aside',
            'navbar' => '.navbar, nav',
            'menu-items' => '.menu-item, .nav-link',
            'menu-items-active' => '.menu-item.active, .nav-link.active',
            'cards' => '.card',
            'buttons' => '.btn, button',
            'buttons-active' => '.btn.active, .btn:active, button.active, button:active',
            'links-active' => 'a.active, a.router-link-active',
            'headers' => 'header',
            'titles' => 'h1, h2, h3, h4, h5, h6',
            'badges' => '.badge',
            'borders' => '.border',
            'backgrounds' => '.background',
        ];

        return $selectorMap[$this->target_element] ?? '';
    }

    /**
     * ดึง Intensity Value (สำหรับ calculation)
     *
     * @return float
     */
    public function getIntensityValue(): float
    {
        $intensityMap = [
            'subtle' => 0.3,
            'medium' => 0.6,
            'strong' => 0.9,
            'extreme' => 1.0,
        ];

        return $intensityMap[$this->intensity] ?? 0.6;
    }

    /**
     * สร้าง CSS Animation Keyframes
     *
     * @return string
     */
    public function generateKeyframes(): string
    {
        $name = "rgb-{$this->id}-{$this->animation_type}";
        $colors = $this->rgb_colors;

        if (empty($colors)) {
            return '';
        }

        $keyframes = "@keyframes {$name} {\n";

        switch ($this->animation_type) {
            case 'rainbow':
                $step = 100 / count($colors);
                foreach ($colors as $index => $color) {
                    $percentage = round($index * $step);
                    $keyframes .= "    {$percentage}% { background: {$color}; }\n";
                }
                break;

            case 'pulse':
                $keyframes .= "    0%, 100% { box-shadow: 0 0 {$this->blur_radius}px {$colors[0]}; }\n";
                $keyframes .= "    50% { box-shadow: 0 0 " . ($this->blur_radius * 2) . "px {$colors[0]}; }\n";
                break;

            case 'glow':
                $color = $colors[0] ?? '#FF0000';
                $keyframes .= "    0%, 100% { box-shadow: 0 0 {$this->blur_radius}px {$color}; }\n";
                $keyframes .= "    50% { box-shadow: 0 0 " . ($this->blur_radius * 3) . "px {$color}; }\n";
                break;

            // Add more animation types as needed
        }

        $keyframes .= "}\n";

        return $keyframes;
    }
}
