<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppControlSection extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'section_id',
        'section_name',
        'section_name_en',
        'description',
        'description_en',
        'is_visible',
        'display_position',
        'sort_order',
        'layout_type',
        'min_height',
        'max_height',
        'min_width',
        'max_width',
        'background_color',
        'background_dark_color',
        'border_color',
        'border_dark_color',
        'border_width',
        'border_radius',
        'elevation',
        'opacity',
        'padding_top',
        'padding_bottom',
        'padding_left',
        'padding_right',
        'margin_top',
        'margin_bottom',
        'margin_left',
        'margin_right',
        'animation_enabled',
        'animation_type',
        'animation_duration',
        'custom_properties',
        'platform',
        'min_app_version',
        'is_active',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
        'min_height' => 'integer',
        'max_height' => 'integer',
        'min_width' => 'integer',
        'max_width' => 'integer',
        'border_width' => 'integer',
        'border_radius' => 'integer',
        'elevation' => 'integer',
        'opacity' => 'float',
        'padding_top' => 'integer',
        'padding_bottom' => 'integer',
        'padding_left' => 'integer',
        'padding_right' => 'integer',
        'margin_top' => 'integer',
        'margin_bottom' => 'integer',
        'margin_left' => 'integer',
        'margin_right' => 'integer',
        'animation_enabled' => 'boolean',
        'animation_duration' => 'integer',
        'custom_properties' => 'array',
        'is_active' => 'boolean',
    ];

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
     * Scope สำหรับเลือกเฉพาะที่ active
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope สำหรับเลือกเฉพาะที่ visible
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope สำหรับเรียงลำดับ
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    /**
     * Get padding as array
     */
    public function getPaddingAttribute()
    {
        return [
            'top' => $this->padding_top ?? 0,
            'bottom' => $this->padding_bottom ?? 0,
            'left' => $this->padding_left ?? 0,
            'right' => $this->padding_right ?? 0,
        ];
    }

    /**
     * Get margin as array
     */
    public function getMarginAttribute()
    {
        return [
            'top' => $this->margin_top ?? 0,
            'bottom' => $this->margin_bottom ?? 0,
            'left' => $this->margin_left ?? 0,
            'right' => $this->margin_right ?? 0,
        ];
    }

    /**
     * Get API response format
     */
    public function toApiResponse()
    {
        return [
            'id' => $this->id,
            'section_id' => $this->section_id,
            'section_name' => $this->section_name,
            'section_name_en' => $this->section_name_en,
            'description' => $this->description,
            'description_en' => $this->description_en,
            'is_visible' => $this->is_visible,
            'display_position' => $this->display_position,
            'sort_order' => $this->sort_order,
            'layout' => [
                'type' => $this->layout_type,
                'min_height' => $this->min_height,
                'max_height' => $this->max_height,
                'min_width' => $this->min_width,
                'max_width' => $this->max_width,
            ],
            'style' => [
                'background_color' => $this->background_color,
                'background_dark_color' => $this->background_dark_color,
                'border_color' => $this->border_color,
                'border_dark_color' => $this->border_dark_color,
                'border_width' => $this->border_width,
                'border_radius' => $this->border_radius,
                'elevation' => $this->elevation,
                'opacity' => $this->opacity,
            ],
            'spacing' => [
                'padding' => $this->padding,
                'margin' => $this->margin,
            ],
            'animation' => [
                'enabled' => $this->animation_enabled,
                'type' => $this->animation_type,
                'duration' => $this->animation_duration,
            ],
            'custom_properties' => $this->custom_properties,
            'platform' => $this->platform,
            'min_app_version' => $this->min_app_version,
        ];
    }
}
