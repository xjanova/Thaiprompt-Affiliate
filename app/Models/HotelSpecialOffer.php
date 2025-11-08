<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class HotelSpecialOffer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'hotel_id',
        'name',
        'code',
        'description',
        'discount_type',
        'discount_value',
        'free_nights',
        'valid_from',
        'valid_until',
        'applicable_days',
        'min_nights',
        'min_amount',
        'max_usage',
        'used_count',
        'room_type_ids',
        'is_active',
        'is_featured',
        'banner_image',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
        'applicable_days' => 'array',
        'room_type_ids' => 'array',
        'discount_value' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('valid_from', '<=', Carbon::now())
            ->where('valid_until', '>=', Carbon::now())
            ->where(function ($q) {
                $q->whereNull('max_usage')
                    ->orWhereColumn('used_count', '<', 'max_usage');
            });
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Check if offer is valid
     */
    public function isValid($checkIn = null, $checkOut = null, $roomTypeId = null, $subtotal = null)
    {
        // Check if active
        if (!$this->is_active) {
            return false;
        }

        // Check dates
        $now = Carbon::now();
        if ($this->valid_from > $now || $this->valid_until < $now) {
            return false;
        }

        // Check usage limit
        if ($this->max_usage && $this->used_count >= $this->max_usage) {
            return false;
        }

        // Check check-in date if provided
        if ($checkIn) {
            $checkInDate = Carbon::parse($checkIn);

            // Check if within valid period
            if ($checkInDate < $this->valid_from || $checkInDate > $this->valid_until) {
                return false;
            }

            // Check applicable days
            if ($this->applicable_days && count($this->applicable_days) > 0) {
                $dayName = strtolower($checkInDate->format('l'));
                if (!in_array($dayName, $this->applicable_days)) {
                    return false;
                }
            }
        }

        // Check minimum nights
        if ($checkIn && $checkOut) {
            $nights = Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));
            if ($nights < $this->min_nights) {
                return false;
            }
        }

        // Check minimum amount
        if ($this->min_amount && $subtotal && $subtotal < $this->min_amount) {
            return false;
        }

        // Check room type restriction
        if ($roomTypeId && $this->room_type_ids && count($this->room_type_ids) > 0) {
            if (!in_array($roomTypeId, $this->room_type_ids)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount($subtotal)
    {
        if ($this->discount_type === 'percentage') {
            return $subtotal * ($this->discount_value / 100);
        } elseif ($this->discount_type === 'fixed') {
            return min($this->discount_value, $subtotal);
        }

        return 0;
    }

    /**
     * Apply offer to booking
     */
    public function apply($subtotal)
    {
        $discount = $this->calculateDiscount($subtotal);

        // Increment usage count
        $this->increment('used_count');

        return $discount;
    }

    /**
     * Get banner image URL
     */
    public function getBannerImageUrlAttribute()
    {
        if ($this->banner_image) {
            return asset('storage/' . $this->banner_image);
        }
        return null;
    }
}
