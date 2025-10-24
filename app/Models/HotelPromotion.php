<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HotelPromotion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'hotel_id',
        'room_type_id',
        'name',
        'code',
        'description',
        'discount_type',
        'discount_value',
        'free_nights',
        'min_nights',
        'max_nights',
        'min_rooms',
        'min_amount',
        'max_uses',
        'max_uses_per_user',
        'current_uses',
        'valid_from',
        'valid_to',
        'blackout_dates',
        'applicable_days',
        'is_public',
        'combinable',
        'advance_booking_days',
        'target_market',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'free_nights' => 'integer',
        'min_nights' => 'integer',
        'max_nights' => 'integer',
        'min_rooms' => 'integer',
        'min_amount' => 'decimal:2',
        'max_uses' => 'integer',
        'max_uses_per_user' => 'integer',
        'current_uses' => 'integer',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'blackout_dates' => 'array',
        'applicable_days' => 'array',
        'is_public' => 'boolean',
        'combinable' => 'boolean',
        'advance_booking_days' => 'integer',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Get the hotel
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Get the room type (if specific)
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * Get all usage records
     */
    public function usages(): HasMany
    {
        return $this->hasMany(HotelPromotionUsage::class, 'promotion_id');
    }

    /**
     * Check if promotion is valid
     */
    public function isValid($checkInDate = null, $nights = 1, $userId = null): bool
    {
        // Check if active
        if (!$this->is_active) {
            return false;
        }

        // Check dates
        $today = now()->toDateString();
        if ($today < $this->valid_from || $today > $this->valid_to) {
            return false;
        }

        // Check max uses
        if ($this->max_uses && $this->current_uses >= $this->max_uses) {
            return false;
        }

        // Check user usage
        if ($userId && $this->max_uses_per_user) {
            $userUses = $this->usages()->where('user_id', $userId)->count();
            if ($userUses >= $this->max_uses_per_user) {
                return false;
            }
        }

        // Check minimum nights
        if ($nights < $this->min_nights) {
            return false;
        }

        // Check blackout dates
        if ($checkInDate && $this->blackout_dates) {
            if (in_array($checkInDate, $this->blackout_dates)) {
                return false;
            }
        }

        // Check applicable days
        if ($checkInDate && $this->applicable_days) {
            $dayOfWeek = \Carbon\Carbon::parse($checkInDate)->dayOfWeek;
            if (!in_array($dayOfWeek, $this->applicable_days)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount($subtotal, $nights = 1): float
    {
        if ($this->discount_type === 'percentage') {
            return round($subtotal * ($this->discount_value / 100), 2);
        } elseif ($this->discount_type === 'fixed_amount') {
            return $this->discount_value;
        } elseif ($this->discount_type === 'free_nights' && $this->free_nights > 0) {
            $pricePerNight = $subtotal / $nights;
            return round($pricePerNight * $this->free_nights, 2);
        }

        return 0;
    }

    /**
     * Increment usage counter
     */
    public function incrementUsage(): void
    {
        $this->increment('current_uses');
    }

    /**
     * Scope: Active promotions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('valid_from', '<=', now())
            ->where('valid_to', '>=', now());
    }

    /**
     * Scope: Public promotions
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }
}
