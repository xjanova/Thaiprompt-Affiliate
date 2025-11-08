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
        'position',
        'action_type',
        'action_value',
        'sort_order',
        'is_active',
        'is_dismissible',
        'start_date',
        'end_date',
        'target_audience',
        'target_user_ids',
        'platform',
        'view_count',
        'click_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_dismissible' => 'boolean',
        'sort_order' => 'integer',
        'view_count' => 'integer',
        'click_count' => 'integer',
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
}
