<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmartSlider extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'alias',
        'description',
        'type',
        'width',
        'height',
        'responsive_mode',
        'settings',
        'controls',
        'animations',
        'is_published',
        'order',
    ];

    protected $casts = [
        'settings' => 'array',
        'controls' => 'array',
        'animations' => 'array',
        'is_published' => 'boolean',
        'width' => 'integer',
        'height' => 'integer',
        'order' => 'integer',
    ];

    /**
     * Get default settings
     */
    public static function getDefaultSettings(): array
    {
        return [
            'slide_duration' => 5000, // milliseconds
            'animation_duration' => 800,
            'animation_type' => 'horizontal', // horizontal, vertical, fade
            'autoplay' => true,
            'loop' => true,
            'pause_on_hover' => true,
            'keyboard_navigation' => true,
            'touch_swipe' => true,
        ];
    }

    /**
     * Get default controls
     */
    public static function getDefaultControls(): array
    {
        return [
            'arrows' => [
                'enabled' => true,
                'style' => 'default',
                'position' => 'inside', // inside, outside
            ],
            'bullets' => [
                'enabled' => true,
                'style' => 'default',
                'position' => 'bottom',
            ],
            'autoplay_button' => [
                'enabled' => false,
            ],
            'thumbnails' => [
                'enabled' => false,
                'width' => 100,
                'height' => 60,
                'position' => 'bottom',
            ],
            'progress_bar' => [
                'enabled' => false,
                'position' => 'top',
            ],
        ];
    }

    /**
     * Get default animations
     */
    public static function getDefaultAnimations(): array
    {
        return [
            'slide_animation' => 'slide', // slide, fade, cube, flip, coverflow
            'background_animation' => 'none',
            'layer_animation_preset' => 'fade-in',
        ];
    }

    /**
     * Relationships
     */
    public function slides()
    {
        return $this->hasMany(SmartSlide::class, 'slider_id')->orderBy('order');
    }

    public function analytics()
    {
        return $this->hasMany(SmartSliderAnalytics::class, 'slider_id');
    }

    /**
     * Scopes
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($slider) {
            if (empty($slider->settings)) {
                $slider->settings = self::getDefaultSettings();
            }
            if (empty($slider->controls)) {
                $slider->controls = self::getDefaultControls();
            }
            if (empty($slider->animations)) {
                $slider->animations = self::getDefaultAnimations();
            }
            if (empty($slider->alias)) {
                $slider->alias = \Illuminate\Support\Str::slug($slider->name);
            }
        });
    }

    /**
     * Get slider by alias
     */
    public static function findByAlias(string $alias)
    {
        return self::where('alias', $alias)->first();
    }

    /**
     * Get total views
     */
    public function getTotalViews()
    {
        return $this->analytics()->where('event_type', 'view')->count();
    }

    /**
     * Get total clicks
     */
    public function getTotalClicks()
    {
        return $this->analytics()->where('event_type', 'click')->count();
    }

    /**
     * Duplicate slider
     */
    public function duplicate()
    {
        $newSlider = $this->replicate();
        $newSlider->name = $this->name . ' (Copy)';
        $newSlider->alias = $this->alias . '-copy-' . time();
        $newSlider->is_published = false;
        $newSlider->save();

        // Duplicate slides
        foreach ($this->slides as $slide) {
            $slide->duplicateToSlider($newSlider->id);
        }

        return $newSlider;
    }
}
