<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingGuest extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'first_name',
        'last_name',
        'id_type',
        'id_number',
        'date_of_birth',
        'nationality',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    /**
     * Get the booking
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(HotelBooking::class, 'booking_id');
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
