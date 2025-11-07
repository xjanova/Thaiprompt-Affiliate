<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class HotelBooking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_number',
        'hotel_id',
        'user_id',
        'room_type_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'guest_id_card',
        'number_of_adults',
        'number_of_children',
        'check_in_date',
        'check_out_date',
        'number_of_nights',
        'number_of_rooms',
        'room_price',
        'subtotal',
        'tax_amount',
        'service_charge',
        'discount_amount',
        'total_amount',
        'currency',
        'special_requests',
        'internal_notes',
        'status',
        'payment_transaction_id',
        'payment_status',
        'payment_date',
        'affiliate_id',
        'commission_amount',
        'commission_paid',
        'cancelled_at',
        'cancellation_reason',
        'refund_amount',
        'checked_in_at',
        'checked_out_at',
        'booking_source',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'room_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'cancelled_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'commission_paid' => 'boolean',
    ];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            if (empty($booking->booking_number)) {
                $booking->booking_number = self::generateBookingNumber();
            }

            // Calculate number of nights
            if ($booking->check_in_date && $booking->check_out_date) {
                $checkIn = Carbon::parse($booking->check_in_date);
                $checkOut = Carbon::parse($booking->check_out_date);
                $booking->number_of_nights = $checkIn->diffInDays($checkOut);
            }
        });
    }

    /**
     * Generate unique booking number
     */
    public static function generateBookingNumber()
    {
        $date = Carbon::now()->format('Ymd');
        $count = self::whereDate('created_at', Carbon::today())->count() + 1;
        return 'HB' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Relationships
     */
    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function paymentTransaction()
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    public function affiliate()
    {
        return $this->belongsTo(User::class, 'affiliate_id');
    }

    public function review()
    {
        return $this->hasOne(HotelReview::class, 'booking_id');
    }

    /**
     * Scopes
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeUpcoming($query)
    {
        return $query->whereIn('status', ['confirmed', 'pending'])
            ->where('check_in_date', '>=', Carbon::now());
    }

    public function scopePast($query)
    {
        return $query->whereIn('status', ['completed', 'checked_out'])
            ->where('check_out_date', '<', Carbon::now());
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['confirmed', 'checked_in']);
    }

    /**
     * Status Methods
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isConfirmed()
    {
        return $this->status === 'confirmed';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['pending', 'confirmed'])
            && $this->check_in_date->gt(Carbon::now());
    }

    public function canBeCheckedIn()
    {
        return $this->status === 'confirmed'
            && $this->check_in_date->lte(Carbon::now());
    }

    public function canBeCheckedOut()
    {
        return $this->status === 'checked_in';
    }

    /**
     * Confirm booking
     */
    public function confirm()
    {
        $this->update([
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_date' => Carbon::now(),
        ]);

        // Update hotel booking count
        $this->hotel->incrementBookings();

        return $this;
    }

    /**
     * Cancel booking
     */
    public function cancel($reason = null, $refundAmount = 0)
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => Carbon::now(),
            'cancellation_reason' => $reason,
            'refund_amount' => $refundAmount,
        ]);

        // Restore room availability
        $this->restoreAvailability();

        return $this;
    }

    /**
     * Check in guest
     */
    public function checkIn()
    {
        $this->update([
            'status' => 'checked_in',
            'checked_in_at' => Carbon::now(),
        ]);

        return $this;
    }

    /**
     * Check out guest
     */
    public function checkOut()
    {
        $this->update([
            'status' => 'checked_out',
            'checked_out_at' => Carbon::now(),
        ]);

        // Mark as completed after checkout
        $this->complete();

        return $this;
    }

    /**
     * Complete booking
     */
    public function complete()
    {
        $this->update(['status' => 'completed']);
        return $this;
    }

    /**
     * Restore room availability (when cancelled)
     */
    public function restoreAvailability()
    {
        $dates = [];
        $current = $this->check_in_date->copy();

        while ($current->lt($this->check_out_date)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        foreach ($dates as $date) {
            RoomAvailability::where('room_type_id', $this->room_type_id)
                ->where('date', $date)
                ->first()
                ?->increaseAvailability($this->number_of_rooms);
        }
    }

    /**
     * Calculate totals
     */
    public function calculateTotals($taxRate = 7, $serviceChargeRate = 10)
    {
        // Tax (VAT)
        $this->tax_amount = $this->subtotal * ($taxRate / 100);

        // Service charge
        $this->service_charge = $this->subtotal * ($serviceChargeRate / 100);

        // Total
        $this->total_amount = $this->subtotal + $this->tax_amount + $this->service_charge - $this->discount_amount;

        $this->save();
    }
}
