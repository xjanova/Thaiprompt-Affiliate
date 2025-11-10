<?php

namespace App\Models\BotAutomation;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BotMarketplaceReview extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'listing_id',
        'user_id',
        'rating',
        'review',
        'pros',
        'cons',
        'is_verified_purchase',
        'is_featured',
        'helpful_count',
    ];

    protected $casts = [
        'pros' => 'array',
        'cons' => 'array',
        'is_verified_purchase' => 'boolean',
        'is_featured' => 'boolean',
    ];

    /**
     * Get the listing
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(BotMarketplaceListing::class);
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Verified purchases
     */
    public function scopeVerifiedPurchases($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    /**
     * Scope: Featured reviews
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Increment helpful count
     */
    public function markAsHelpful(): void
    {
        $this->increment('helpful_count');
    }

    /**
     * Boot method to update listing rating
     */
    protected static function booted()
    {
        static::created(function ($review) {
            $review->listing->updateAverageRating();
        });

        static::updated(function ($review) {
            $review->listing->updateAverageRating();
        });

        static::deleted(function ($review) {
            $review->listing->updateAverageRating();
        });
    }
}
