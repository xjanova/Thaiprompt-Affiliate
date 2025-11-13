<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumerScan extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'food_product_id',
        'user_id',
        'session_id',
        'scanned_at',
        'scan_location',
        'device_info',
        'viewed_journey',
        'viewed_quality',
        'viewed_carbon',
        'time_spent_seconds',
        'consumer_rating',
        'feedback',
        'verified_purchase',
        'purchase_receipt_url',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'scan_location' => 'array',
        'device_info' => 'array',
        'viewed_journey' => 'boolean',
        'viewed_quality' => 'boolean',
        'viewed_carbon' => 'boolean',
        'verified_purchase' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($scan) {
            $scan->scanned_at = $scan->scanned_at ?? now();
        });
    }

    /**
     * Get the food product that was scanned
     */
    public function foodProduct(): BelongsTo
    {
        return $this->belongsTo(FoodProduct::class);
    }

    /**
     * Get the user who scanned (if logged in)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if scan is from registered user
     */
    public function isRegisteredUser(): bool
    {
        return !is_null($this->user_id);
    }

    /**
     * Check if all sections were viewed
     */
    public function viewedAllSections(): bool
    {
        return $this->viewed_journey
            && $this->viewed_quality
            && $this->viewed_carbon;
    }

    /**
     * Get engagement score (0-100)
     */
    public function getEngagementScoreAttribute(): int
    {
        $score = 0;

        // +25 for each section viewed
        if ($this->viewed_journey) $score += 25;
        if ($this->viewed_quality) $score += 25;
        if ($this->viewed_carbon) $score += 25;

        // +10 for spending > 30 seconds
        if ($this->time_spent_seconds && $this->time_spent_seconds > 30) {
            $score += 10;
        }

        // +15 for leaving feedback
        if ($this->consumer_rating || $this->feedback) {
            $score += 15;
        }

        return min(100, $score);
    }

    /**
     * Scope: By product
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('food_product_id', $productId);
    }

    /**
     * Scope: Registered users only
     */
    public function scopeRegisteredUsers($query)
    {
        return $query->whereNotNull('user_id');
    }

    /**
     * Scope: Anonymous scans
     */
    public function scopeAnonymous($query)
    {
        return $query->whereNull('user_id');
    }

    /**
     * Scope: With feedback
     */
    public function scopeWithFeedback($query)
    {
        return $query->where(function($q) {
            $q->whereNotNull('consumer_rating')
              ->orWhereNotNull('feedback');
        });
    }

    /**
     * Scope: High engagement (score >= 75)
     */
    public function scopeHighEngagement($query)
    {
        return $query->where(function($q) {
            $q->where('viewed_journey', true)
              ->where('viewed_quality', true)
              ->where('viewed_carbon', true);
        });
    }

    /**
     * Scope: Recent scans (within last N days)
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('scanned_at', '>=', now()->subDays($days));
    }
}
