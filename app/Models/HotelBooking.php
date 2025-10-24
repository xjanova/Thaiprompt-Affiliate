<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HotelBooking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_number',
        'hotel_id',
        'room_type_id',
        'room_id',
        'user_id',
        'vendor_id',
        'check_in_date',
        'check_out_date',
        'nights',
        'adults',
        'children',
        'rooms_count',
        'guest_name',
        'guest_email',
        'guest_phone',
        'guest_country',
        'special_requests',
        'room_price',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'service_fee',
        'total_amount',
        'currency',
        'promotion_id',
        'promo_code',
        'payment_method',
        'payment_status',
        'transaction_id',
        'paid_at',
        'status',
        'confirmed_at',
        'checked_in_at',
        'checked_out_at',
        'cancelled_at',
        'cancellation_reason',
        'commission_paid',
        'vendor_commission',
        'admin_commission',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'nights' => 'integer',
        'adults' => 'integer',
        'children' => 'integer',
        'rooms_count' => 'integer',
        'room_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'vendor_commission' => 'decimal:2',
        'admin_commission' => 'decimal:2',
        'commission_paid' => 'boolean',
        'paid_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the hotel for this booking
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Get the room type for this booking
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * Get the assigned room
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the user who made the booking
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the vendor
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the promotion used
     */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(HotelPromotion::class);
    }

    /**
     * Get all guests for this booking
     */
    public function guests(): HasMany
    {
        return $this->hasMany(BookingGuest::class, 'booking_id');
    }

    /**
     * Generate unique booking number
     */
    public static function generateBookingNumber(): string
    {
        do {
            $number = 'HTL-' . strtoupper(uniqid());
        } while (self::where('booking_number', $number)->exists());

        return $number;
    }

    /**
     * Confirm booking
     */
    public function confirm(): void
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        // Assign room if not yet assigned
        if (!$this->room_id) {
            $this->assignRoom();
        }
    }

    /**
     * Check in
     */
    public function checkIn(): void
    {
        $this->update([
            'status' => 'checked_in',
            'checked_in_at' => now(),
        ]);

        if ($this->room) {
            $this->room->updateStatus('occupied');
        }
    }

    /**
     * Check out
     */
    public function checkOut(): void
    {
        $this->update([
            'status' => 'checked_out',
            'checked_out_at' => now(),
        ]);

        if ($this->room) {
            $this->room->updateStatus('cleaning');
        }
    }

    /**
     * Cancel booking
     */
    public function cancel($reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        if ($this->room) {
            $this->room->updateStatus('available');
        }
    }

    /**
     * Assign room automatically
     */
    public function assignRoom(): ?Room
    {
        $room = Room::where('hotel_id', $this->hotel_id)
            ->where('room_type_id', $this->room_type_id)
            ->where('status', 'available')
            ->whereDoesntHave('bookings', function ($q) {
                $q->where(function ($q2) {
                    $q2->whereBetween('check_in_date', [$this->check_in_date, $this->check_out_date])
                        ->orWhereBetween('check_out_date', [$this->check_in_date, $this->check_out_date]);
                })->whereIn('status', ['confirmed', 'checked_in']);
            })
            ->first();

        if ($room) {
            $this->update(['room_id' => $room->id]);
        }

        return $room;
    }

    /**
     * Scope: Filter by status
     */
    public function scopeOfStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Upcoming bookings
     */
    public function scopeUpcoming($query)
    {
        return $query->where('check_in_date', '>=', now())
            ->whereIn('status', ['confirmed', 'pending']);
    }

    /**
     * Scope: Active bookings (checked in)
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'checked_in');
    }

    /**
     * Scope: Past bookings
     */
    public function scopePast($query)
    {
        return $query->where('check_out_date', '<', now())
            ->whereIn('status', ['checked_out', 'completed']);
    }
}
