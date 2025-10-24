<?php

namespace App\Services\Hotel;

use App\Models\Hotel;
use App\Models\HotelBooking;
use App\Models\HotelPromotion;
use App\Models\RoomType;
use App\Models\User;
use App\Models\Vendor;
use App\Services\MlmService;
use App\Services\WalletService;
use Carbon\Carbon;

class BookingService
{
    protected $walletService;
    protected $mlmService;

    public function __construct(WalletService $walletService, MlmService $mlmService)
    {
        $this->walletService = $walletService;
        $this->mlmService = $mlmService;
    }

    /**
     * Create a new booking
     */
    public function createBooking(array $data): HotelBooking
    {
        $hotel = Hotel::findOrFail($data['hotel_id']);
        $roomType = RoomType::findOrFail($data['room_type_id']);
        $user = User::findOrFail($data['user_id']);

        // Validate dates
        $checkIn = Carbon::parse($data['check_in_date']);
        $checkOut = Carbon::parse($data['check_out_date']);

        if ($checkIn->isPast()) {
            throw new \Exception('Check-in date cannot be in the past');
        }

        if ($checkOut->lte($checkIn)) {
            throw new \Exception('Check-out date must be after check-in date');
        }

        $nights = $checkIn->diffInDays($checkOut);

        // Check room availability
        $availableRooms = $roomType->getAvailableRooms($checkIn, $checkOut);
        $requestedRooms = $data['rooms_count'] ?? 1;

        if ($availableRooms < $requestedRooms) {
            throw new \Exception("Only {$availableRooms} rooms available for selected dates");
        }

        // Calculate pricing
        $pricing = $this->calculateBookingPrice(
            $roomType,
            $checkIn,
            $checkOut,
            $requestedRooms,
            $data['adults'] ?? 2,
            $data['children'] ?? 0,
            $data['promo_code'] ?? null
        );

        // Create booking
        $booking = HotelBooking::create([
            'booking_number' => HotelBooking::generateBookingNumber(),
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomType->id,
            'user_id' => $user->id,
            'vendor_id' => $hotel->vendor_id,
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
            'nights' => $nights,
            'adults' => $data['adults'] ?? 2,
            'children' => $data['children'] ?? 0,
            'rooms_count' => $requestedRooms,
            'guest_name' => $data['guest_name'] ?? $user->name,
            'guest_email' => $data['guest_email'] ?? $user->email,
            'guest_phone' => $data['guest_phone'] ?? $user->phone,
            'guest_country' => $data['guest_country'] ?? null,
            'special_requests' => $data['special_requests'] ?? null,
            'room_price' => $pricing['price_per_night'],
            'subtotal' => $pricing['subtotal'],
            'discount_amount' => $pricing['discount'],
            'tax_amount' => $pricing['tax'],
            'service_fee' => $pricing['service_fee'],
            'total_amount' => $pricing['total'],
            'currency' => 'THB',
            'promotion_id' => $pricing['promotion_id'] ?? null,
            'promo_code' => $data['promo_code'] ?? null,
            'payment_method' => $data['payment_method'] ?? 'wallet',
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        // Add guest information if provided
        if (!empty($data['guests'])) {
            foreach ($data['guests'] as $guestData) {
                $booking->guests()->create($guestData);
            }
        }

        return $booking;
    }

    /**
     * Calculate booking price with promotions
     */
    public function calculateBookingPrice(
        RoomType $roomType,
        $checkIn,
        $checkOut,
        int $roomsCount = 1,
        int $adults = 2,
        int $children = 0,
        ?string $promoCode = null
    ): array {
        $checkIn = Carbon::parse($checkIn);
        $checkOut = Carbon::parse($checkOut);
        $nights = $checkIn->diffInDays($checkOut);

        // Calculate base price for all nights
        $totalRoomPrice = 0;
        $currentDate = $checkIn->copy();
        while ($currentDate < $checkOut) {
            $dailyPrice = $roomType->calculatePrice($currentDate);
            $totalRoomPrice += $dailyPrice;
            $currentDate->addDay();
        }

        $averagePrice = $nights > 0 ? $totalRoomPrice / $nights : 0;
        $subtotal = $totalRoomPrice * $roomsCount;

        // Calculate extra charges
        $extraCharges = 0;
        if ($adults > $roomType->max_adults) {
            $extraPersons = $adults - $roomType->max_adults;
            $extraCharges += $extraPersons * $roomType->extra_person_price * $nights * $roomsCount;
        }

        $subtotal += $extraCharges;

        // Apply promotion if provided
        $discount = 0;
        $promotionId = null;

        if ($promoCode) {
            $promotion = HotelPromotion::where('code', $promoCode)
                ->where('hotel_id', $roomType->hotel_id)
                ->where(function ($q) use ($roomType) {
                    $q->whereNull('room_type_id')
                        ->orWhere('room_type_id', $roomType->id);
                })
                ->active()
                ->first();

            if ($promotion && $promotion->isValid($checkIn, $nights)) {
                // Check minimum requirements
                if ($subtotal >= ($promotion->min_amount ?? 0) &&
                    $roomsCount >= $promotion->min_rooms &&
                    $nights >= $promotion->min_nights) {
                    $discount = $promotion->calculateDiscount($subtotal, $nights);
                    $promotionId = $promotion->id;
                }
            }
        }

        // Calculate tax (7% VAT)
        $taxableAmount = $subtotal - $discount;
        $tax = round($taxableAmount * 0.07, 2);

        // Service fee (3% of subtotal)
        $serviceFee = round($subtotal * 0.03, 2);

        $total = $subtotal - $discount + $tax + $serviceFee;

        return [
            'nights' => $nights,
            'rooms_count' => $roomsCount,
            'price_per_night' => round($averagePrice, 2),
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'tax' => $tax,
            'service_fee' => $serviceFee,
            'total' => round($total, 2),
            'promotion_id' => $promotionId,
        ];
    }

    /**
     * Process booking payment
     */
    public function processPayment(HotelBooking $booking, string $paymentMethod, ?string $transactionId = null): bool
    {
        if ($booking->payment_status === 'paid') {
            throw new \Exception('Booking is already paid');
        }

        try {
            $booking->update([
                'payment_method' => $paymentMethod,
                'transaction_id' => $transactionId,
                'payment_status' => 'paid',
                'paid_at' => now(),
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            // Calculate commissions
            $this->calculateCommissions($booking);

            // If promotion was used, increment usage
            if ($booking->promotion_id) {
                $promotion = $booking->promotion;
                $promotion->incrementUsage();

                // Record promotion usage
                $booking->promotion->usages()->create([
                    'booking_id' => $booking->id,
                    'user_id' => $booking->user_id,
                    'discount_amount' => $booking->discount_amount,
                ]);
            }

            // Auto-assign room
            $booking->assignRoom();

            // Update hotel stats
            $booking->hotel->increment('total_bookings');

            return true;
        } catch (\Exception $e) {
            $booking->update(['payment_status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Calculate and distribute commissions
     */
    protected function calculateCommissions(HotelBooking $booking): void
    {
        $vendorCommissionRate = $booking->vendor->commission_rate ?? 70;
        $adminCommissionRate = 100 - $vendorCommissionRate;

        $vendorCommission = round($booking->total_amount * ($vendorCommissionRate / 100), 2);
        $adminCommission = round($booking->total_amount * ($adminCommissionRate / 100), 2);

        $booking->update([
            'vendor_commission' => $vendorCommission,
            'admin_commission' => $adminCommission,
        ]);

        // Distribute MLM commissions
        if ($booking->user->referrer_id) {
            $this->mlmService->distributeCommissions(
                $booking->user,
                $booking->total_amount,
                $booking->id,
                'hotel_booking'
            );
        }

        $booking->update(['commission_paid' => true]);
    }

    /**
     * Cancel booking
     */
    public function cancelBooking(HotelBooking $booking, ?string $reason = null): bool
    {
        if (in_array($booking->status, ['cancelled', 'checked_out'])) {
            throw new \Exception('Cannot cancel this booking');
        }

        $booking->cancel($reason);

        // Process refund if already paid
        if ($booking->payment_status === 'paid') {
            $this->processRefund($booking);
        }

        return true;
    }

    /**
     * Process booking refund
     */
    protected function processRefund(HotelBooking $booking): void
    {
        // Calculate refund amount based on cancellation policy
        $refundAmount = $this->calculateRefundAmount($booking);

        if ($refundAmount > 0) {
            // Refund to wallet
            $this->walletService->credit(
                $booking->user->wallet,
                $refundAmount,
                'refund',
                "Refund for booking {$booking->booking_number}"
            );

            $booking->update([
                'payment_status' => 'refunded',
            ]);
        }
    }

    /**
     * Calculate refund amount based on cancellation policy
     */
    protected function calculateRefundAmount(HotelBooking $booking): float
    {
        $daysUntilCheckIn = now()->diffInDays($booking->check_in_date, false);

        // Default cancellation policy
        if ($daysUntilCheckIn >= 7) {
            return $booking->total_amount; // Full refund
        } elseif ($daysUntilCheckIn >= 3) {
            return $booking->total_amount * 0.5; // 50% refund
        } else {
            return 0; // No refund
        }
    }

    /**
     * Check-in booking
     */
    public function checkIn(HotelBooking $booking): bool
    {
        if ($booking->status !== 'confirmed') {
            throw new \Exception('Only confirmed bookings can be checked in');
        }

        if ($booking->check_in_date->isFuture()) {
            throw new \Exception('Cannot check in before check-in date');
        }

        $booking->checkIn();

        return true;
    }

    /**
     * Check-out booking
     */
    public function checkOut(HotelBooking $booking): bool
    {
        if ($booking->status !== 'checked_in') {
            throw new \Exception('Only checked-in bookings can be checked out');
        }

        $booking->checkOut();

        return true;
    }

    /**
     * Get booking statistics for vendor
     */
    public function getVendorBookingStats(Vendor $vendor, ?string $period = 'month'): array
    {
        $query = HotelBooking::where('vendor_id', $vendor->id);

        // Filter by period
        if ($period === 'today') {
            $query->whereDate('created_at', today());
        } elseif ($period === 'week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($period === 'month') {
            $query->whereMonth('created_at', now()->month);
        } elseif ($period === 'year') {
            $query->whereYear('created_at', now()->year);
        }

        $totalBookings = $query->count();
        $confirmedBookings = (clone $query)->where('status', 'confirmed')->count();
        $checkedInBookings = (clone $query)->where('status', 'checked_in')->count();
        $cancelledBookings = (clone $query)->where('status', 'cancelled')->count();

        $totalRevenue = (clone $query)->where('payment_status', 'paid')->sum('total_amount');
        $averageBookingValue = $totalBookings > 0 ? $totalRevenue / $totalBookings : 0;

        return [
            'total_bookings' => $totalBookings,
            'confirmed_bookings' => $confirmedBookings,
            'checked_in_bookings' => $checkedInBookings,
            'cancelled_bookings' => $cancelledBookings,
            'cancellation_rate' => $totalBookings > 0 ? round(($cancelledBookings / $totalBookings) * 100, 2) : 0,
            'total_revenue' => $totalRevenue,
            'average_booking_value' => round($averageBookingValue, 2),
        ];
    }
}
