<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_id',
        'room_type_id',
        'room_number',
        'floor',
        'status',
        'notes',
    ];

    /**
     * Get the hotel that owns this room
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Get the room type
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * Get all bookings for this room
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(HotelBooking::class);
    }

    /**
     * Check if room is available for date range
     */
    public function isAvailable($checkIn, $checkOut): bool
    {
        if ($this->status !== 'available') {
            return false;
        }

        $conflictingBookings = $this->bookings()
            ->where(function ($q) use ($checkIn, $checkOut) {
                $q->whereBetween('check_in_date', [$checkIn, $checkOut])
                    ->orWhereBetween('check_out_date', [$checkIn, $checkOut])
                    ->orWhere(function ($q2) use ($checkIn, $checkOut) {
                        $q2->where('check_in_date', '<=', $checkIn)
                            ->where('check_out_date', '>=', $checkOut);
                    });
            })
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->exists();

        return !$conflictingBookings;
    }

    /**
     * Update room status
     */
    public function updateStatus($status): void
    {
        $this->update(['status' => $status]);
    }

    /**
     * Scope: Available rooms only
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope: By floor
     */
    public function scopeOnFloor($query, $floor)
    {
        return $query->where('floor', $floor);
    }
}
