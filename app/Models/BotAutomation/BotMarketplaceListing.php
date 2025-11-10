<?php

namespace App\Models\BotAutomation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BotMarketplaceListing extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'automation_id',
        'category_id',
        'title',
        'description',
        'features',
        'screenshots',
        'demo_video_url',
        'pricing_model',
        'price',
        'monthly_price',
        'yearly_price',
        'per_use_price',
        'commission_rate',
        'is_featured',
        'is_verified',
        'status',
        'total_purchases',
        'total_rentals',
        'active_users',
        'total_revenue',
        'average_rating',
        'total_reviews',
        'view_count',
        'tags',
        'supported_platforms',
        'requirements',
        'metadata',
        'approved_at',
        'published_at',
    ];

    protected $casts = [
        'features' => 'array',
        'screenshots' => 'array',
        'price' => 'decimal:2',
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'per_use_price' => 'decimal:4',
        'commission_rate' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_verified' => 'boolean',
        'total_revenue' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'tags' => 'array',
        'supported_platforms' => 'array',
        'requirements' => 'array',
        'metadata' => 'array',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    /**
     * Get the automation
     */
    public function automation(): BelongsTo
    {
        return $this->belongsTo(BotAutomation::class);
    }

    /**
     * Get the category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BotMarketplaceCategory::class, 'category_id');
    }

    /**
     * Get all reviews
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(BotMarketplaceReview::class, 'listing_id');
    }

    /**
     * Get all subscriptions
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(BotRentalSubscription::class, 'listing_id');
    }

    /**
     * Scope: Only approved listings
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope: Only published listings
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'approved')
                     ->whereNotNull('published_at');
    }

    /**
     * Scope: Featured listings
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: Verified listings
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Increment view count
     */
    public function incrementViews(): void
    {
        $this->increment('view_count');
    }

    /**
     * Record purchase
     */
    public function recordPurchase(float $amount): void
    {
        $this->increment('total_purchases');
        $this->increment('active_users');
        $this->increment('total_revenue', $amount);
    }

    /**
     * Record rental
     */
    public function recordRental(float $amount): void
    {
        $this->increment('total_rentals');
        $this->increment('active_users');
        $this->increment('total_revenue', $amount);
    }

    /**
     * Update rating
     */
    public function updateAverageRating(): void
    {
        $avgRating = $this->reviews()->avg('rating');
        $totalReviews = $this->reviews()->count();

        $this->update([
            'average_rating' => $avgRating ?? 0,
            'total_reviews' => $totalReviews,
        ]);
    }

    /**
     * Approve listing
     */
    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    /**
     * Publish listing
     */
    public function publish(): void
    {
        $this->update([
            'published_at' => now(),
        ]);
    }
}
