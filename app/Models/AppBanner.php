<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AppBanner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'title_en',
        'description',
        'description_en',
        'image_url',
        'image_url_dark',
        'type',
        'display_type',
        'display_position',
        'icon',
        'size',
        'background_color',
        'text_color',
        'border_color',
        'position',
        'action_type',
        'action_value',
        'button_text',
        'button_text_en',
        'button_color',
        'secondary_button_text',
        'secondary_button_text_en',
        'secondary_button_action',
        'sort_order',
        'priority',
        'is_active',
        'is_dismissible',
        'show_once',
        'require_action',
        'fullscreen',
        'start_date',
        'end_date',
        'target_audience',
        'target_user_ids',
        'platform',
        'scroll_speed',
        'scroll_direction',
        'auto_dismiss_seconds',
        'sound_enabled',
        'sound_type',
        'sound_url',
        'animation_style',
        'animation_duration',
        'view_count',
        'click_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_dismissible' => 'boolean',
        'show_once' => 'boolean',
        'require_action' => 'boolean',
        'fullscreen' => 'boolean',
        'sound_enabled' => 'boolean',
        'sort_order' => 'integer',
        'priority' => 'integer',
        'view_count' => 'integer',
        'click_count' => 'integer',
        'scroll_speed' => 'integer',
        'auto_dismiss_seconds' => 'integer',
        'animation_duration' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'target_user_ids' => 'array',
    ];

    /**
     * Scope to get only active banners
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->where(function ($sq) {
                            $sq->whereNull('start_date')
                               ->orWhere('start_date', '<=', Carbon::now());
                        })
                        ->where(function ($sq) {
                            $sq->whereNull('end_date')
                               ->orWhere('end_date', '>=', Carbon::now());
                        });
                    });
    }

    /**
     * Scope to get banners by position
     */
    public function scopeByPosition($query, $position)
    {
        return $query->where('position', $position);
    }

    /**
     * Scope to get banners by platform
     */
    public function scopeByPlatform($query, $platform)
    {
        return $query->where(function ($q) use ($platform) {
            $q->where('platform', $platform)
              ->orWhere('platform', 'all')
              ->orWhereNull('platform');
        });
    }

    /**
     * Check if banner is visible for user
     */
    public function isVisibleForUser($userId = null, $userType = 'all')
    {
        if (!$this->is_active) {
            return false;
        }

        // Check date range
        if ($this->start_date && Carbon::now()->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && Carbon::now()->gt($this->end_date)) {
            return false;
        }

        // Check target audience
        if ($this->target_audience !== 'all' && $this->target_audience !== $userType) {
            return false;
        }

        // Check specific user IDs
        if ($this->target_user_ids && !in_array($userId, $this->target_user_ids)) {
            return false;
        }

        return true;
    }

    /**
     * Increment view count
     */
    public function incrementViews()
    {
        $this->increment('view_count');
    }

    /**
     * Increment click count
     */
    public function incrementClicks()
    {
        $this->increment('click_count');
    }

    /**
     * Check if banner should display as popup
     */
    public function shouldShowPopup()
    {
        return in_array($this->display_type, ['popup', 'popup_and_banner', 'popup_and_marquee', 'all']);
    }

    /**
     * Check if banner should display as banner
     */
    public function shouldShowBanner()
    {
        return in_array($this->display_type, ['banner', 'popup_and_banner', 'banner_and_marquee', 'all']);
    }

    /**
     * Check if banner should display as marquee
     */
    public function shouldShowMarquee()
    {
        return in_array($this->display_type, ['marquee', 'popup_and_marquee', 'banner_and_marquee', 'all']);
    }

    /**
     * Get banner styles as CSS
     */
    public function getStylesAttribute()
    {
        $styles = [];

        if ($this->background_color) {
            $styles[] = "background-color: {$this->background_color}";
        }

        if ($this->text_color) {
            $styles[] = "color: {$this->text_color}";
        }

        if ($this->border_color) {
            $styles[] = "border-color: {$this->border_color}";
        }

        return implode('; ', $styles);
    }

    /**
     * Get size class for Tailwind
     */
    public function getSizeClass()
    {
        return match($this->size) {
            'small' => 'text-sm py-2 px-4',
            'medium' => 'text-base py-3 px-6',
            'large' => 'text-lg py-4 px-8',
            'full' => 'text-xl py-6 px-10 min-h-screen',
            default => 'text-base py-3 px-6',
        };
    }

    /**
     * Get display position class
     */
    public function getPositionClass()
    {
        return match($this->display_position) {
            'top' => 'top-0 left-0 right-0',
            'bottom' => 'bottom-0 left-0 right-0',
            'top-right' => 'top-4 right-4',
            'top-left' => 'top-4 left-4',
            'bottom-right' => 'bottom-4 right-4',
            'bottom-left' => 'bottom-4 left-4',
            'center' => 'top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2',
            default => 'top-0 left-0 right-0',
        };
    }

    /**
     * Get type color class
     */
    public function getTypeColorClass()
    {
        return match($this->type) {
            'info' => 'bg-blue-500 text-white border-blue-600',
            'warning' => 'bg-yellow-500 text-gray-900 border-yellow-600',
            'success' => 'bg-green-500 text-white border-green-600',
            'promotion' => 'bg-purple-500 text-white border-purple-600',
            'announcement' => 'bg-indigo-500 text-white border-indigo-600',
            default => 'bg-gray-500 text-white border-gray-600',
        };
    }

    /**
     * Get animation class
     */
    public function getAnimationClass()
    {
        return match($this->animation_style) {
            'fade' => 'animate-fade-in',
            'slide' => 'animate-slide-in',
            'bounce' => 'animate-bounce-in',
            'zoom' => 'animate-zoom-in',
            'shake' => 'animate-shake',
            default => 'animate-fade-in',
        };
    }
}
