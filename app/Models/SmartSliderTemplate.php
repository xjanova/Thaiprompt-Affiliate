<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmartSliderTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'thumbnail',
        'category',
        'template_data',
        'is_premium',
        'is_active',
        'downloads',
    ];

    protected $casts = [
        'template_data' => 'array',
        'is_premium' => 'boolean',
        'is_active' => 'boolean',
        'downloads' => 'integer',
    ];

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeFree($query)
    {
        return $query->where('is_premium', false);
    }

    /**
     * Apply template to slider
     */
    public function applyToSlider(SmartSlider $slider)
    {
        $data = $this->template_data;

        // Update slider settings
        if (isset($data['settings'])) {
            $slider->update($data['settings']);
        }

        // Create slides from template
        if (isset($data['slides'])) {
            foreach ($data['slides'] as $slideData) {
                $slide = $slider->slides()->create($slideData);

                // Create layers
                if (isset($slideData['layers'])) {
                    foreach ($slideData['layers'] as $layerData) {
                        $slide->layers()->create($layerData);
                    }
                }
            }
        }

        // Increment download count
        $this->increment('downloads');

        return $slider;
    }

    /**
     * Get thumbnail URL
     */
    public function getThumbnailUrl()
    {
        if ($this->thumbnail) {
            return asset('storage/' . $this->thumbnail);
        }
        return asset('images/default-template.jpg');
    }
}
