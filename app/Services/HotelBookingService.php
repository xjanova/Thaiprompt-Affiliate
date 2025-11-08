<?php

namespace App\Services;

use App\Models\HotelBooking;
use App\Models\RoomType;
use App\Models\Hotel;
use App\Models\User;
use App\Models\HotelSpecialOffer;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class HotelBookingService
{
    protected $availabilityService;
    protected $pricingService;

    public function __construct(
        RoomAvailabilityService $availabilityService,
        HotelPricingService $pricingService
    ) {
        $this->availabilityService = $availabilityService;
        $this->pricingService = $pricingService;
    }

    /**
     * Create a new booking
     */
    public function createBooking(array $data, User $user)
    {
        DB::beginTransaction();

        try {
            $roomType = RoomType::findOrFail($data['room_type_id']);
            $hotel = $roomType->hotel;

            // Validate availability
            if (!$this->validateAvailability($roomType, $data)) {
                throw new Exception('Selected rooms are not available for the chosen dates.');
            }

            // Calculate pricing
            $pricing = $this->pricingService->calculateBookingPrice(
                $roomType,
                $data['check_in_date'],
                $data['check_out_date'],
                $data['number_of_rooms'] ?? 1,
                $data['number_of_adults'] ?? 2,
                $data['number_of_children'] ?? 0,
                $data['promo_code'] ?? null
            );

            // Get affiliate if exists
            $affiliateId = $user->affiliate_id ?? null;
            $commissionAmount = 0;
            if ($affiliateId && $hotel->commission_rate > 0) {
                $commissionAmount = $pricing['subtotal'] * ($hotel->commission_rate / 100);
            }

            // Create booking
            $booking = HotelBooking::create([
                'hotel_id' => $hotel->id,
                'user_id' => $user->id,
                'room_type_id' => $roomType->id,
                'guest_name' => $data['guest_name'] ?? $user->name,
                'guest_email' => $data['guest_email'] ?? $user->email,
                'guest_phone' => $data['guest_phone'] ?? $user->phone,
                'guest_id_card' => $data['guest_id_card'] ?? null,
                'number_of_adults' => $data['number_of_adults'] ?? 2,
                'number_of_children' => $data['number_of_children'] ?? 0,
                'check_in_date' => $data['check_in_date'],
                'check_out_date' => $data['check_out_date'],
                'number_of_rooms' => $data['number_of_rooms'] ?? 1,
                'room_price' => $pricing['room_price'],
                'subtotal' => $pricing['subtotal'],
                'tax_amount' => $pricing['tax_amount'],
                'service_charge' => $pricing['service_charge'],
                'discount_amount' => $pricing['discount_amount'],
                'total_amount' => $pricing['total_amount'],
                'currency' => 'THB',
                'special_requests' => $data['special_requests'] ?? null,
                'status' => 'pending',
                'payment_status' => 'pending',
                'affiliate_id' => $affiliateId,
                'commission_amount' => $commissionAmount,
                'booking_source' => $data['booking_source'] ?? 'website',
            ]);

            // Decrease availability
            $this->availabilityService->decreaseAvailability($booking);

            DB::commit();

            return $booking;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate room availability
     */
    protected function validateAvailability(RoomType $roomType, array $data)
    {
        $checkIn = $data['check_in_date'];
        $checkOut = $data['check_out_date'];
        $rooms = $data['number_of_rooms'] ?? 1;

        return $roomType->isAvailableForDates($checkIn, $checkOut, $rooms);
    }

    /**
     * Confirm booking (after payment)
     */
    public function confirmBooking(HotelBooking $booking, $paymentTransactionId = null)
    {
        $booking->update([
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_date' => now(),
            'payment_transaction_id' => $paymentTransactionId,
        ]);

        // Increment hotel booking count
        $booking->hotel->incrementBookings();

        // TODO: Send confirmation email

        return $booking;
    }

    /**
     * Cancel booking
     */
    public function cancelBooking(HotelBooking $booking, $reason = null, $refundAmount = 0)
    {
        if (!$booking->canBeCancelled()) {
            throw new Exception('This booking cannot be cancelled.');
        }

        DB::beginTransaction();

        try {
            $booking->cancel($reason, $refundAmount);

            // Restore availability
            $this->availabilityService->increaseAvailability($booking);

            DB::commit();

            // TODO: Send cancellation email
            // TODO: Process refund if applicable

            return $booking;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Check in guest
     */
    public function checkIn(HotelBooking $booking)
    {
        if (!$booking->canBeCheckedIn()) {
            throw new Exception('Guest cannot be checked in at this time.');
        }

        $booking->checkIn();

        // TODO: Send check-in confirmation

        return $booking;
    }

    /**
     * Check out guest
     */
    public function checkOut(HotelBooking $booking)
    {
        if (!$booking->canBeCheckedOut()) {
            throw new Exception('Guest cannot be checked out at this time.');
        }

        $booking->checkOut();

        // TODO: Send checkout email with review request

        return $booking;
    }

    /**
     * Modify booking dates
     */
    public function modifyBooking(HotelBooking $booking, array $data)
    {
        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            throw new Exception('This booking cannot be modified.');
        }

        DB::beginTransaction();

        try {
            // Restore old availability
            $this->availabilityService->increaseAvailability($booking);

            // Validate new dates
            if (!$this->validateAvailability($booking->roomType, $data)) {
                throw new Exception('Selected dates are not available.');
            }

            // Calculate new pricing
            $pricing = $this->pricingService->calculateBookingPrice(
                $booking->roomType,
                $data['check_in_date'],
                $data['check_out_date'],
                $booking->number_of_rooms,
                $data['number_of_adults'] ?? $booking->number_of_adults,
                $data['number_of_children'] ?? $booking->number_of_children
            );

            // Update booking
            $booking->update([
                'check_in_date' => $data['check_in_date'],
                'check_out_date' => $data['check_out_date'],
                'number_of_adults' => $data['number_of_adults'] ?? $booking->number_of_adults,
                'number_of_children' => $data['number_of_children'] ?? $booking->number_of_children,
                'subtotal' => $pricing['subtotal'],
                'tax_amount' => $pricing['tax_amount'],
                'service_charge' => $pricing['service_charge'],
                'total_amount' => $pricing['total_amount'],
            ]);

            // Decrease new availability
            $this->availabilityService->decreaseAvailability($booking);

            DB::commit();

            // TODO: Send modification confirmation email

            return $booking;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get user bookings
     */
    public function getUserBookings(User $user, $status = null)
    {
        $query = $user->hotelBookings()->with(['hotel', 'roomType']);

        if ($status) {
            if ($status === 'upcoming') {
                $query->upcoming();
            } elseif ($status === 'past') {
                $query->past();
            } else {
                $query->where('status', $status);
            }
        }

        return $query->orderBy('created_at', 'DESC')->get();
    }

    /**
     * Calculate refund amount based on cancellation policy
     */
    public function calculateRefund(HotelBooking $booking)
    {
        $now = Carbon::now();
        $checkIn = Carbon::parse($booking->check_in_date);
        $daysUntilCheckIn = $now->diffInDays($checkIn, false);

        // If check-in date has passed, no refund
        if ($daysUntilCheckIn < 0) {
            return 0;
        }

        // Example cancellation policy:
        // - More than 7 days: 100% refund
        // - 3-7 days: 50% refund
        // - Less than 3 days: No refund

        if ($daysUntilCheckIn >= 7) {
            return $booking->total_amount;
        } elseif ($daysUntilCheckIn >= 3) {
            return $booking->total_amount * 0.5;
        }

        return 0;
    }

    /**
     * Get booking statistics for hotel
     */
    public function getHotelStatistics(Hotel $hotel, $startDate = null, $endDate = null)
    {
        $query = $hotel->bookings();

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return [
            'total_bookings' => $query->count(),
            'confirmed_bookings' => $query->where('status', 'confirmed')->count(),
            'cancelled_bookings' => $query->where('status', 'cancelled')->count(),
            'total_revenue' => $query->where('payment_status', 'paid')->sum('total_amount'),
            'average_booking_value' => $query->where('payment_status', 'paid')->avg('total_amount'),
            'occupancy_rate' => $this->calculateOccupancyRate($hotel, $startDate, $endDate),
        ];
    }

    /**
     * Calculate occupancy rate
     */
    protected function calculateOccupancyRate(Hotel $hotel, $startDate, $endDate)
    {
        if (!$startDate || !$endDate) {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
        }

        $totalRooms = $hotel->roomTypes()->sum('total_rooms');
        $days = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate));
        $totalRoomNights = $totalRooms * $days;

        if ($totalRoomNights == 0) {
            return 0;
        }

        $bookedRoomNights = HotelBooking::where('hotel_id', $hotel->id)
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out', 'completed'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('check_in_date', [$startDate, $endDate])
                    ->orWhereBetween('check_out_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('check_in_date', '<=', $startDate)
                            ->where('check_out_date', '>=', $endDate);
                    });
            })
            ->get()
            ->sum(function ($booking) {
                return $booking->number_of_rooms * $booking->number_of_nights;
            });

        return round(($bookedRoomNights / $totalRoomNights) * 100, 2);
    }
}
