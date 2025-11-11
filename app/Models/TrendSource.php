<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrendSource extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type',
        'url',
        'api_config',
        'scraping_method',
        'selectors',
        'check_interval',
        'last_checked_at',
        'is_active',
        'priority',
        'metadata',
    ];

    protected $casts = [
        'api_config' => 'array',
        'selectors' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'last_checked_at' => 'datetime',
    ];

    /**
     * Get all trend data from this source
     */
    public function trendData()
    {
        return $this->hasMany(TrendData::class);
    }

    /**
     * Get recent trend data
     */
    public function recentTrendData($limit = 100)
    {
        return $this->trendData()
            ->orderBy('scraped_at', 'desc')
            ->limit($limit);
    }

    /**
     * Check if source is ready to be scraped
     */
    public function isReadyToScrape(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->last_checked_at) {
            return true;
        }

        $minutesSinceLastCheck = now()->diffInMinutes($this->last_checked_at);
        return $minutesSinceLastCheck >= $this->check_interval;
    }

    /**
     * Mark as checked
     */
    public function markAsChecked(): void
    {
        $this->update(['last_checked_at' => now()]);
    }

    /**
     * Scope for active sources
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for sources ready to scrape
     */
    public function scopeReadyToScrape($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('last_checked_at')
                    ->orWhereRaw('TIMESTAMPDIFF(MINUTE, last_checked_at, NOW()) >= check_interval');
            });
    }

    /**
     * Scope by priority
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }
}
