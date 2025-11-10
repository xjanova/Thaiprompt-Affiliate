<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComponentSetting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'component_id',
        'component_name',
        'component_name_en',
        'component_type',
        'description',
        'description_en',
        'min_height',
        'max_height',
        'min_width',
        'max_width',
        'fixed_height',
        'fixed_width',
        'padding_top',
        'padding_bottom',
        'padding_left',
        'padding_right',
        'margin_top',
        'margin_bottom',
        'margin_left',
        'margin_right',
        'font_size',
        'font_weight',
        'line_height',
        'text_align',
        'text_decoration',
        'text_transform',
        'background_color',
        'background_dark_color',
        'text_color',
        'text_dark_color',
        'border_color',
        'border_dark_color',
        'border_width',
        'corner_radius',
        'corner_radius_top_left',
        'corner_radius_top_right',
        'corner_radius_bottom_left',
        'corner_radius_bottom_right',
        'shadow_elevation',
        'shadow_color',
        'shadow_offset_x',
        'shadow_offset_y',
        'shadow_blur_radius',
        'hover_background_color',
        'hover_text_color',
        'hover_border_color',
        'active_background_color',
        'active_text_color',
        'active_border_color',
        'disabled_background_color',
        'disabled_text_color',
        'disabled_border_color',
        'disabled_opacity',
        'focus_border_color',
        'focus_border_width',
        'focus_shadow_color',
        'icon_size',
        'icon_color',
        'icon_dark_color',
        'icon_position',
        'animation_enabled',
        'animation_type',
        'animation_duration',
        'opacity',
        'custom_properties',
        'platform',
        'min_app_version',
        'is_enabled',
    ];

    protected $casts = [
        'min_height' => 'integer',
        'max_height' => 'integer',
        'min_width' => 'integer',
        'max_width' => 'integer',
        'fixed_height' => 'integer',
        'fixed_width' => 'integer',
        'padding_top' => 'integer',
        'padding_bottom' => 'integer',
        'padding_left' => 'integer',
        'padding_right' => 'integer',
        'margin_top' => 'integer',
        'margin_bottom' => 'integer',
        'margin_left' => 'integer',
        'margin_right' => 'integer',
        'font_size' => 'integer',
        'line_height' => 'float',
        'border_width' => 'integer',
        'corner_radius' => 'integer',
        'corner_radius_top_left' => 'integer',
        'corner_radius_top_right' => 'integer',
        'corner_radius_bottom_left' => 'integer',
        'corner_radius_bottom_right' => 'integer',
        'shadow_elevation' => 'integer',
        'shadow_offset_x' => 'integer',
        'shadow_offset_y' => 'integer',
        'shadow_blur_radius' => 'integer',
        'disabled_opacity' => 'float',
        'focus_border_width' => 'integer',
        'icon_size' => 'integer',
        'animation_enabled' => 'boolean',
        'animation_duration' => 'integer',
        'opacity' => 'float',
        'custom_properties' => 'array',
        'is_enabled' => 'boolean',
    ];

    /**
     * Scope สำหรับ filter ตาม component type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('component_type', $type);
    }

    /**
     * Scope สำหรับ filter ตาม platform
     */
    public function scopeForPlatform($query, $platform)
    {
        return $query->where(function ($q) use ($platform) {
            $q->where('platform', $platform)
              ->orWhere('platform', 'all');
        });
    }

    /**
     * Scope สำหรับเลือกเฉพาะที่ enabled
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Get padding as array
     */
    public function getPaddingAttribute()
    {
        return [
            'top' => $this->attributes['padding_top'] ?? 0,
            'bottom' => $this->attributes['padding_bottom'] ?? 0,
            'left' => $this->attributes['padding_left'] ?? 0,
            'right' => $this->attributes['padding_right'] ?? 0,
        ];
    }

    /**
     * Get margin as array
     */
    public function getMarginAttribute()
    {
        return [
            'top' => $this->attributes['margin_top'] ?? 0,
            'bottom' => $this->attributes['margin_bottom'] ?? 0,
            'left' => $this->attributes['margin_left'] ?? 0,
            'right' => $this->attributes['margin_right'] ?? 0,
        ];
    }

    /**
     * Get corner radius as array
     */
    public function getCornerRadiusObjectAttribute()
    {
        if ($this->corner_radius !== null) {
            return [
                'top_left' => $this->corner_radius,
                'top_right' => $this->corner_radius,
                'bottom_left' => $this->corner_radius,
                'bottom_right' => $this->corner_radius,
            ];
        }

        return [
            'top_left' => $this->corner_radius_top_left ?? 0,
            'top_right' => $this->corner_radius_top_right ?? 0,
            'bottom_left' => $this->corner_radius_bottom_left ?? 0,
            'bottom_right' => $this->corner_radius_bottom_right ?? 0,
        ];
    }

    /**
     * Get API response format
     */
    public function toApiResponse()
    {
        return [
            'id' => $this->id,
            'component_id' => $this->component_id,
            'component_name' => $this->component_name,
            'component_name_en' => $this->component_name_en,
            'component_type' => $this->component_type,
            'description' => $this->description,
            'description_en' => $this->description_en,
            'size' => [
                'min_height' => $this->min_height,
                'max_height' => $this->max_height,
                'min_width' => $this->min_width,
                'max_width' => $this->max_width,
                'fixed_height' => $this->fixed_height,
                'fixed_width' => $this->fixed_width,
            ],
            'spacing' => [
                'padding' => $this->padding,
                'margin' => $this->margin,
            ],
            'typography' => [
                'font_size' => $this->font_size,
                'font_weight' => $this->font_weight,
                'line_height' => $this->line_height,
                'text_align' => $this->text_align,
                'text_decoration' => $this->text_decoration,
                'text_transform' => $this->text_transform,
            ],
            'colors' => [
                'background' => $this->background_color,
                'background_dark' => $this->background_dark_color,
                'text' => $this->text_color,
                'text_dark' => $this->text_dark_color,
                'border' => $this->border_color,
                'border_dark' => $this->border_dark_color,
            ],
            'border' => [
                'width' => $this->border_width,
                'corner_radius' => $this->corner_radius_object,
            ],
            'shadow' => [
                'elevation' => $this->shadow_elevation,
                'color' => $this->shadow_color,
                'offset' => [
                    'x' => $this->shadow_offset_x,
                    'y' => $this->shadow_offset_y,
                ],
                'blur_radius' => $this->shadow_blur_radius,
            ],
            'states' => [
                'hover' => [
                    'background_color' => $this->hover_background_color,
                    'text_color' => $this->hover_text_color,
                    'border_color' => $this->hover_border_color,
                ],
                'active' => [
                    'background_color' => $this->active_background_color,
                    'text_color' => $this->active_text_color,
                    'border_color' => $this->active_border_color,
                ],
                'disabled' => [
                    'background_color' => $this->disabled_background_color,
                    'text_color' => $this->disabled_text_color,
                    'border_color' => $this->disabled_border_color,
                    'opacity' => $this->disabled_opacity,
                ],
                'focus' => [
                    'border_color' => $this->focus_border_color,
                    'border_width' => $this->focus_border_width,
                    'shadow_color' => $this->focus_shadow_color,
                ],
            ],
            'icon' => [
                'size' => $this->icon_size,
                'color' => $this->icon_color,
                'color_dark' => $this->icon_dark_color,
                'position' => $this->icon_position,
            ],
            'animation' => [
                'enabled' => $this->animation_enabled,
                'type' => $this->animation_type,
                'duration' => $this->animation_duration,
            ],
            'opacity' => $this->opacity,
            'custom_properties' => $this->custom_properties,
            'platform' => $this->platform,
            'min_app_version' => $this->min_app_version,
        ];
    }
}
