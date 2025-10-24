<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelPromotionUsage extends Model
{
    use HasFactory;

    protected $table = 'hotel_promotion_usage';

    protected $fillable = [
        'promotion_id',
        'booking_id',
        'user_id',
        'discount_amount',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
    ];

    /**
     * Get the promotion
     */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(HotelPromotion::class);
    }

    /**
     * Get the booking
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(HotelBooking::class);
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
