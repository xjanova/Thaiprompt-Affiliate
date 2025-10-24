<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hotel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'name',
        'slug',
        'description',
        'type',
        'star_rating',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'latitude',
        'longitude',
        'phone',
        'email',
        'website',
        'main_image',
        'gallery_images',
        'video_url',
        'check_in_time',
        'check_out_time',
        'cancellation_policy',
        'house_rules',
        'payment_methods',
        'amenities',
        'has_parking',
        'has_wifi',
        'has_pool',
        'has_gym',
        'has_restaurant',
        'has_spa',
        'pet_friendly',
        'status',
        'is_featured',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'average_rating',
        'total_reviews',
        'total_bookings',
    ];

    protected $casts = [
        'star_rating' => 'integer',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'gallery_images' => 'array',
        'amenities' => 'array',
        'has_parking' => 'boolean',
        'has_wifi' => 'boolean',
        'has_pool' => 'boolean',
        'has_gym' => 'boolean',
        'has_restaurant' => 'boolean',
        'has_spa' => 'boolean',
        'pet_friendly' => 'boolean',
        'is_featured' => 'boolean',
        'meta_keywords' => 'array',
        'average_rating' => 'decimal:2',
        'total_reviews' => 'integer',
        'total_bookings' => 'integer',
    ];

    /**
     * Get the vendor that owns the hotel
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get all room types for this hotel
     */
    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class);
    }

    /**
     * Get all rooms for this hotel
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * Get all bookings for this hotel
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(HotelBooking::class);
    }

    /**
     * Get all promotions for this hotel
     */
    public function promotions(): HasMany
    {
        return $this->hasMany(HotelPromotion::class);
    }

    /**
     * Get all reviews for this hotel
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(HotelReview::class);
    }

    /**
     * Get approved reviews only
     */
    public function approvedReviews(): HasMany
    {
        return $this->hasMany(HotelReview::class)->where('is_approved', true);
    }

    /**
     * Update average rating
     */
    public function updateRating(): void
    {
        $stats = $this->approvedReviews()
            ->selectRaw('AVG(overall_rating) as avg_rating, COUNT(*) as total')
            ->first();

        $this->update([
            'average_rating' => round($stats->avg_rating ?? 0, 2),
            'total_reviews' => $stats->total ?? 0,
        ]);
    }

    /**
     * Check room availability for date range
     */
    public function checkAvailability($checkIn, $checkOut, $roomTypeId = null)
    {
        $query = $this->rooms();

        if ($roomTypeId) {
            $query->where('room_type_id', $roomTypeId);
        }

        $totalRooms = $query->count();

        // Count booked rooms for the date range
        $bookedRooms = HotelBooking::where('hotel_id', $this->id)
            ->where(function ($q) use ($checkIn, $checkOut) {
                $q->whereBetween('check_in_date', [$checkIn, $checkOut])
                    ->orWhereBetween('check_out_date', [$checkIn, $checkOut])
                    ->orWhere(function ($q2) use ($checkIn, $checkOut) {
                        $q2->where('check_in_date', '<=', $checkIn)
                            ->where('check_out_date', '>=', $checkOut);
                    });
            })
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->when($roomTypeId, function ($q) use ($roomTypeId) {
                return $q->where('room_type_id', $roomTypeId);
            })
            ->sum('rooms_count');

        return $totalRooms - $bookedRooms;
    }

    /**
     * Scope: Active hotels only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Featured hotels
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: Filter by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Search by location
     */
    public function scopeNearLocation($query, $latitude, $longitude, $radiusKm = 10)
    {
        $haversine = "(6371 * acos(cos(radians($latitude))
                    * cos(radians(latitude))
                    * cos(radians(longitude) - radians($longitude))
                    + sin(radians($latitude))
                    * sin(radians(latitude))))";

        return $query->selectRaw("{$haversine} AS distance")
            ->whereRaw("{$haversine} < ?", [$radiusKm])
            ->orderBy('distance');
    }
}
