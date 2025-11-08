<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Carbon\Carbon;

class RoomType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'hotel_id',
        'name',
        'slug',
        'description',
        'size_sqm',
        'max_adults',
        'max_children',
        'max_occupancy',
        'total_rooms',
        'bed_types',
        'base_price',
        'weekend_price',
        'extra_adult_price',
        'extra_child_price',
        'main_image',
        'gallery_images',
        'amenities',
        'special_features',
        'view_type',
        'smoking_policy',
        'is_active',
        'is_popular',
        'sort_order',
    ];

    protected $casts = [
        'bed_types' => 'array',
        'gallery_images' => 'array',
        'amenities' => 'array',
        'base_price' => 'decimal:2',
        'weekend_price' => 'decimal:2',
        'extra_adult_price' => 'decimal:2',
        'extra_child_price' => 'decimal:2',
        'size_sqm' => 'decimal:2',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
    ];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($roomType) {
            if (empty($roomType->slug)) {
                $roomType->slug = Str::slug($roomType->name);
            }
        });
    }

    /**
     * Relationships
     */
    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function availability()
    {
        return $this->hasMany(RoomAvailability::class);
    }

    public function bookings()
    {
        return $this->hasMany(HotelBooking::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    public function scopeForOccupancy($query, $adults, $children = 0)
    {
        return $query->where('max_adults', '>=', $adults)
            ->where('max_children', '>=', $children);
    }

    public function scopePriceRange($query, $min, $max)
    {
        return $query->whereBetween('base_price', [$min, $max]);
    }

    /**
     * Accessors
     */
    public function getMainImageUrlAttribute()
    {
        if ($this->main_image) {
            return asset('storage/' . $this->main_image);
        }
        return asset('images/default-room.jpg');
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
     * Get price for specific date
     */
    public function getPriceForDate($date)
    {
        $availability = $this->availability()
            ->where('date', $date)
            ->where('is_available', true)
            ->first();

        if ($availability) {
            return $availability->price;
        }

        // Check if it's weekend
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        if (($dayOfWeek == Carbon::SATURDAY || $dayOfWeek == Carbon::SUNDAY) && $this->weekend_price) {
            return $this->weekend_price;
        }

        return $this->base_price;
    }

    /**
     * Check availability for date range
     */
    public function isAvailableForDates($checkIn, $checkOut, $roomsNeeded = 1)
    {
        $dates = [];
        $current = Carbon::parse($checkIn);
        $end = Carbon::parse($checkOut);

        while ($current->lt($end)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        foreach ($dates as $date) {
            $available = $this->getAvailableRoomsForDate($date);
            if ($available < $roomsNeeded) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get available rooms for specific date
     */
    public function getAvailableRoomsForDate($date)
    {
        $availability = $this->availability()
            ->where('date', $date)
            ->where('is_available', true)
            ->first();

        if ($availability) {
            return $availability->available_rooms;
        }

        // Get booked rooms for this date
        $bookedRooms = $this->bookings()
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->where('check_in_date', '<=', $date)
            ->where('check_out_date', '>', $date)
            ->sum('number_of_rooms');

        return max(0, $this->total_rooms - $bookedRooms);
    }

    /**
     * Calculate total price for stay
     */
    public function calculatePrice($checkIn, $checkOut, $rooms = 1, $adults = 2, $children = 0)
    {
        $dates = [];
        $current = Carbon::parse($checkIn);
        $end = Carbon::parse($checkOut);
        $totalPrice = 0;

        while ($current->lt($end)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        foreach ($dates as $date) {
            $totalPrice += $this->getPriceForDate($date) * $rooms;
        }

        // Add extra adult charges
        if ($adults > $this->max_adults) {
            $extraAdults = $adults - $this->max_adults;
            $totalPrice += $extraAdults * $this->extra_adult_price * count($dates) * $rooms;
        }

        // Add extra child charges
        if ($children > $this->max_children) {
            $extraChildren = $children - $this->max_children;
            $totalPrice += $extraChildren * $this->extra_child_price * count($dates) * $rooms;
        }

        return $totalPrice;
    }
}
