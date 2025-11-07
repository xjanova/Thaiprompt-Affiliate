<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomAvailability extends Model
{
    use HasFactory;

    protected $table = 'room_availability';

    protected $fillable = [
        'room_type_id',
        'date',
        'available_rooms',
        'price',
        'is_available',
        'min_stay',
        'max_stay',
    ];

    protected $casts = [
        'date' => 'date',
        'price' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * Scopes
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)
            ->where('available_rooms', '>', 0);
    }

    public function scopeForDateRange($query, $start, $end)
    {
        return $query->whereBetween('date', [$start, $end]);
    }

    /**
     * Decrease available rooms (when booking is made)
     */
    public function decreaseAvailability($rooms = 1)
    {
        $this->decrement('available_rooms', $rooms);

        if ($this->available_rooms <= 0) {
            $this->update(['is_available' => false]);
        }
    }

    /**
     * Increase available rooms (when booking is cancelled)
     */
    public function increaseAvailability($rooms = 1)
    {
        $this->increment('available_rooms', $rooms);

        if ($this->available_rooms > 0) {
            $this->update(['is_available' => true]);
        }
    }
}
