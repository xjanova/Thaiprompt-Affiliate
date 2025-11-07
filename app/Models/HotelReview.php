<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HotelReview extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'hotel_id',
        'booking_id',
        'user_id',
        'overall_rating',
        'cleanliness_rating',
        'staff_rating',
        'facilities_rating',
        'location_rating',
        'value_rating',
        'title',
        'comment',
        'pros',
        'cons',
        'guest_name',
        'guest_type',
        'stayed_date',
        'images',
        'hotel_response',
        'hotel_response_date',
        'is_verified',
        'is_approved',
        'is_featured',
        'helpful_count',
        'not_helpful_count',
    ];

    protected $casts = [
        'images' => 'array',
        'stayed_date' => 'date',
        'hotel_response_date' => 'datetime',
        'is_verified' => 'boolean',
        'is_approved' => 'boolean',
        'is_featured' => 'boolean',
    ];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($review) {
            // Update hotel rating
            $review->hotel->updateRating();
        });

        static::updated(function ($review) {
            // Update hotel rating
            $review->hotel->updateRating();
        });

        static::deleted(function ($review) {
            // Update hotel rating
            $review->hotel->updateRating();
        });
    }

    /**
     * Relationships
     */
    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function booking()
    {
        return $this->belongsTo(HotelBooking::class, 'booking_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function votes()
    {
        return $this->hasMany(HotelReviewVote::class, 'review_id');
    }

    /**
     * Scopes
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeMinRating($query, $rating)
    {
        return $query->where('overall_rating', '>=', $rating);
    }

    /**
     * Accessors
     */
    public function getAverageRatingAttribute()
    {
        $ratings = collect([
            $this->cleanliness_rating,
            $this->staff_rating,
            $this->facilities_rating,
            $this->location_rating,
            $this->value_rating,
        ])->filter();

        return $ratings->avg() ?? $this->overall_rating;
    }

    public function getImagesUrlAttribute()
    {
        if ($this->images) {
            return collect($this->images)->map(function ($image) {
                return asset('storage/' . $image);
            })->toArray();
        }
        return [];
    }

    /**
     * Check if user found this review helpful
     */
    public function isHelpfulByUser($userId)
    {
        return $this->votes()
            ->where('user_id', $userId)
            ->where('is_helpful', true)
            ->exists();
    }

    /**
     * Mark as helpful
     */
    public function markAsHelpful($userId)
    {
        $vote = $this->votes()->updateOrCreate(
            ['user_id' => $userId],
            ['is_helpful' => true]
        );

        $this->updateHelpfulCounts();
    }

    /**
     * Mark as not helpful
     */
    public function markAsNotHelpful($userId)
    {
        $vote = $this->votes()->updateOrCreate(
            ['user_id' => $userId],
            ['is_helpful' => false]
        );

        $this->updateHelpfulCounts();
    }

    /**
     * Update helpful counts
     */
    public function updateHelpfulCounts()
    {
        $this->helpful_count = $this->votes()->where('is_helpful', true)->count();
        $this->not_helpful_count = $this->votes()->where('is_helpful', false)->count();
        $this->save();
    }

    /**
     * Add hotel response
     */
    public function addHotelResponse($response)
    {
        $this->update([
            'hotel_response' => $response,
            'hotel_response_date' => now(),
        ]);
    }
}
