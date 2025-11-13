<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Game extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'title_en',
        'description',
        'description_en',
        'icon',
        'image',
        'url',
        'primary_color',
        'secondary_color',
        'glow_color',
        'order',
        'is_active',
        'card_style',
        'meta_data',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'meta_data' => 'array',
    ];

    /**
     * Get active games
     */
    public static function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get games ordered by order field
     */
    public static function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc')->orderBy('created_at', 'desc');
    }

    /**
     * Get the title based on current locale
     */
    public function getLocalizedTitleAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'en' && $this->title_en ? $this->title_en : $this->title;
    }

    /**
     * Get the description based on current locale
     */
    public function getLocalizedDescriptionAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'en' && $this->description_en ? $this->description_en : $this->description;
    }

    /**
     * Get the image URL
     */
    public function getImageUrlAttribute()
    {
        if ($this->image && file_exists(public_path($this->image))) {
            return asset($this->image);
        }
        return null;
    }
}
