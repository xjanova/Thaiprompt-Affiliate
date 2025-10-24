<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'service_rating',
        'location_rating',
        'facilities_rating',
        'value_rating',
        'title',
        'comment',
        'pros',
        'cons',
        'travel_type',
        'stay_date',
        'is_verified',
        'is_approved',
        'admin_notes',
        'helpful_count',
        'not_helpful_count',
        'vendor_response',
        'vendor_responded_at',
    ];

    protected $casts = [
        'overall_rating' => 'integer',
        'cleanliness_rating' => 'integer',
        'service_rating' => 'integer',
        'location_rating' => 'integer',
        'facilities_rating' => 'integer',
        'value_rating' => 'integer',
        'pros' => 'array',
        'cons' => 'array',
        'stay_date' => 'date',
        'is_verified' => 'boolean',
        'is_approved' => 'boolean',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
        'vendor_responded_at' => 'datetime',
    ];

    /**
     * Get the hotel
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Get the booking
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(HotelBooking::class);
    }

    /**
     * Get the user who wrote the review
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Approve the review
     */
    public function approve(): void
    {
        $this->update(['is_approved' => true]);
        $this->hotel->updateRating();
    }

    /**
     * Reject the review
     */
    public function reject($reason = null): void
    {
        $this->update([
            'is_approved' => false,
            'admin_notes' => $reason,
        ]);
    }

    /**
     * Add vendor response
     */
    public function addVendorResponse($response): void
    {
        $this->update([
            'vendor_response' => $response,
            'vendor_responded_at' => now(),
        ]);
    }

    /**
     * Mark as helpful
     */
    public function markHelpful(): void
    {
        $this->increment('helpful_count');
    }

    /**
     * Mark as not helpful
     */
    public function markNotHelpful(): void
    {
        $this->increment('not_helpful_count');
    }

    /**
     * Scope: Approved reviews
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope: Verified reviews
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope: Pending reviews
     */
    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }
}
