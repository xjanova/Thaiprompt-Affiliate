<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Hotel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
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
        'rating',
        'review_count',
        'check_in_time',
        'check_out_time',
        'cancellation_policy',
        'house_rules',
        'payment_policy',
        'type',
        'star_rating',
        'is_active',
        'is_featured',
        'view_count',
        'booking_count',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'owner_id',
        'commission_rate',
    ];

    protected $casts = [
        'gallery_images' => 'array',
        'rating' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
    ];

    /**
     * Boot method for auto-generating slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($hotel) {
            if (empty($hotel->slug)) {
                $hotel->slug = Str::slug($hotel->name);
            }
        });

        static::updating(function ($hotel) {
            if ($hotel->isDirty('name') && empty($hotel->slug)) {
                $hotel->slug = Str::slug($hotel->name);
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Relationships
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function facilities()
    {
        return $this->belongsToMany(HotelFacility::class, 'hotel_facility')
            ->withPivot('description')
            ->withTimestamps();
    }

    public function roomTypes()
    {
        return $this->hasMany(RoomType::class);
    }

    public function bookings()
    {
        return $this->hasMany(HotelBooking::class);
    }

    public function reviews()
    {
        return $this->hasMany(HotelReview::class);
    }

    public function specialOffers()
    {
        return $this->hasMany(HotelSpecialOffer::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCity($query, $city)
    {
        return $query->where('city', $city);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeMinRating($query, $rating)
    {
        return $query->where('rating', '>=', $rating);
    }

    /**
     * Accessors & Mutators
     */
    public function getMainImageUrlAttribute()
    {
        if ($this->main_image) {
            return asset('storage/' . $this->main_image);
        }
        return asset('images/default-hotel.jpg');
    }

    public function getGalleryImagesUrlAttribute()
    {
        if ($this->gallery_images) {
            return collect($this->gallery_images)->map(function ($image) {
                return asset('storage/' . $image);
            })->toArray();
        }
        return [];
    }

    /**
     * Get lowest room price
     */
    public function getLowestPriceAttribute()
    {
        return $this->roomTypes()
            ->where('is_active', true)
            ->min('base_price') ?? 0;
    }

    /**
     * Get active room types count
     */
    public function getActiveRoomTypesCountAttribute()
    {
        return $this->roomTypes()->where('is_active', true)->count();
    }

    /**
     * Check if hotel has availability for given dates
     */
    public function hasAvailability($checkIn, $checkOut, $rooms = 1)
    {
        return $this->roomTypes()
            ->active()
            ->whereHas('availability', function ($query) use ($checkIn, $checkOut, $rooms) {
                $query->whereBetween('date', [$checkIn, $checkOut])
                    ->where('is_available', true)
                    ->where('available_rooms', '>=', $rooms);
            })
            ->exists();
    }

    /**
     * Update rating based on reviews
     */
    public function updateRating()
    {
        $avgRating = $this->reviews()
            ->where('is_approved', true)
            ->avg('overall_rating');

        $reviewCount = $this->reviews()
            ->where('is_approved', true)
            ->count();

        $this->update([
            'rating' => round($avgRating, 2) ?? 0,
            'review_count' => $reviewCount,
        ]);
    }

    /**
     * Increment view count
     */
    public function incrementViews()
    {
        $this->increment('view_count');
    }

    /**
     * Increment booking count
     */
    public function incrementBookings()
    {
        $this->increment('booking_count');
    }
}
