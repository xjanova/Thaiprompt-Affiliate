<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'hotel_id',
        'name',
        'slug',
        'description',
        'size_sqm',
        'bed_type',
        'max_adults',
        'max_children',
        'max_occupancy',
        'base_price',
        'weekend_price',
        'extra_bed_price',
        'extra_person_price',
        'amenities',
        'has_balcony',
        'has_sea_view',
        'has_city_view',
        'has_bathtub',
        'has_kitchen',
        'main_image',
        'gallery_images',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'size_sqm' => 'decimal:2',
        'max_adults' => 'integer',
        'max_children' => 'integer',
        'max_occupancy' => 'integer',
        'base_price' => 'decimal:2',
        'weekend_price' => 'decimal:2',
        'extra_bed_price' => 'decimal:2',
        'extra_person_price' => 'decimal:2',
        'amenities' => 'array',
        'has_balcony' => 'boolean',
        'has_sea_view' => 'boolean',
        'has_city_view' => 'boolean',
        'has_bathtub' => 'boolean',
        'has_kitchen' => 'boolean',
        'gallery_images' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the hotel that owns this room type
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Get all rooms of this type
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * Get all pricing rules for this room type
     */
    public function pricingRules(): HasMany
    {
        return $this->hasMany(RoomPricingRule::class);
    }

    /**
     * Get all bookings for this room type
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(HotelBooking::class);
    }

    /**
     * Calculate price for specific date
     */
    public function calculatePrice($date)
    {
        $date = \Carbon\Carbon::parse($date);
        $basePrice = $this->base_price;

        // Check for pricing rules
        $applicableRules = $this->pricingRules()
            ->where('is_active', true)
            ->where(function ($q) use ($date) {
                // Date range rules
                $q->where(function ($q2) use ($date) {
                    $q2->where('rule_type', 'date_range')
                        ->where('start_date', '<=', $date)
                        ->where('end_date', '>=', $date);
                })
                // Day of week rules
                ->orWhere(function ($q2) use ($date) {
                    $q2->where('rule_type', 'day_of_week')
                        ->whereRaw('JSON_CONTAINS(applicable_days, ?)', [$date->dayOfWeek]);
                });
            })
            ->orderByDesc('priority')
            ->first();

        if ($applicableRules) {
            if ($applicableRules->adjustment_type === 'percentage') {
                $basePrice += ($basePrice * $applicableRules->price_adjustment / 100);
            } else {
                $basePrice += $applicableRules->price_adjustment;
            }
        } else if ($this->weekend_price && in_array($date->dayOfWeek, [5, 6])) {
            // Friday and Saturday
            $basePrice = $this->weekend_price;
        }

        return round($basePrice, 2);
    }

    /**
     * Get available rooms count for date range
     */
    public function getAvailableRooms($checkIn, $checkOut)
    {
        $totalRooms = $this->rooms()->where('status', 'available')->count();

        $bookedRooms = HotelBooking::where('room_type_id', $this->id)
            ->where(function ($q) use ($checkIn, $checkOut) {
                $q->whereBetween('check_in_date', [$checkIn, $checkOut])
                    ->orWhereBetween('check_out_date', [$checkIn, $checkOut])
                    ->orWhere(function ($q2) use ($checkIn, $checkOut) {
                        $q2->where('check_in_date', '<=', $checkIn)
                            ->where('check_out_date', '>=', $checkOut);
                    });
            })
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->sum('rooms_count');

        return max(0, $totalRooms - $bookedRooms);
    }

    /**
     * Scope: Active room types only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
