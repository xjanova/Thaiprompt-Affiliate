<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmartSlide extends Model
{
    use HasFactory;

    protected $fillable = [
        'slider_id',
        'title',
        'description',
        'background',
        'link_url',
        'link_target',
        'thumbnail',
        'duration',
        'order',
        'is_active',
    ];

    protected $casts = [
        'background' => 'array',
        'is_active' => 'boolean',
        'duration' => 'integer',
        'order' => 'integer',
    ];

    /**
     * Get default background
     */
    public static function getDefaultBackground(): array
    {
        return [
            'type' => 'color', // image, video, color, gradient
            'color' => '#ffffff',
            'image' => null,
            'video' => null,
            'gradient' => [
                'type' => 'linear',
                'angle' => 135,
                'colors' => ['#667eea', '#764ba2'],
            ],
            'animation' => 'none',
            'position' => 'center center',
            'size' => 'cover',
        ];
    }

    /**
     * Relationships
     */
    public function slider()
    {
        return $this->belongsTo(SmartSlider::class, 'slider_id');
    }

    public function layers()
    {
        return $this->hasMany(SmartSlideLayer::class, 'slide_id')->whereNull('parent_id')->orderBy('order');
    }

    public function allLayers()
    {
        return $this->hasMany(SmartSlideLayer::class, 'slide_id')->orderBy('order');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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

        static::creating(function ($slide) {
            if (empty($slide->background)) {
                $slide->background = self::getDefaultBackground();
            }
        });
    }

    /**
     * Duplicate slide to another slider
     */
    public function duplicateToSlider($sliderId)
    {
        $newSlide = $this->replicate();
        $newSlide->slider_id = $sliderId;
        $newSlide->save();

        // Duplicate layers
        foreach ($this->layers as $layer) {
            $layer->duplicateToSlide($newSlide->id);
        }

        return $newSlide;
    }

    /**
     * Get thumbnail URL
     */
    public function getThumbnailUrl()
    {
        if ($this->thumbnail) {
            return asset('storage/' . $this->thumbnail);
        }

        // Try to get from background
        if (!empty($this->background['image'])) {
            return asset('storage/' . $this->background['image']);
        }

        return asset('images/default-slide.jpg');
    }
}
