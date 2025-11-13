<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Game extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        // Gallery/Selector fields (for game cards display)
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
        'card_style',

        // Playable game fields (for actual games)
        'slug',
        'name',
        'thumbnail',
        'category',
        'min_level_required',
        'requires_auth',
        'settings',

        // Common fields
        'is_active',
        'meta_data',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_auth' => 'boolean',
        'order' => 'integer',
        'min_level_required' => 'integer',
        'settings' => 'array',
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

    /**
     * User progress relationship (for playable games)
     */
    public function userProgress()
    {
        return $this->hasMany(UserGameProgress::class);
    }

    /**
     * Leaderboard relationship (for playable games)
     */
    public function leaderboard()
    {
        return $this->hasMany(GameLeaderboard::class)
            ->orderBy('score', 'desc')
            ->limit(100);
    }

    /**
     * Achievements relationship (for playable games)
     */
    public function achievements()
    {
        return $this->hasMany(GameAchievement::class);
    }

    /**
     * Get top scores (for playable games)
     */
    public function getTopScores($limit = 10)
    {
        return $this->leaderboard()
            ->with('user:id,name,profile_picture')
            ->limit($limit)
            ->get();
    }
}
