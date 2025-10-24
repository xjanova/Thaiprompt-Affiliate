<?php

namespace App\Http\Controllers\Api\Hotel;

use App\Http\Controllers\Controller;
use App\Models\HotelBooking;
use App\Services\Hotel\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    protected $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * Get user's bookings
     */
    public function index()
    {
        $bookings = Auth::user()->hotelBookings()
            ->with(['hotel', 'roomType', 'room'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($bookings);
    }

    /**
     * Get single booking
     */
    public function show($id)
    {
        $booking = HotelBooking::with(['hotel', 'roomType', 'room', 'guests'])
            ->findOrFail($id);

        // Check authorization
        if ($booking->user_id !== Auth::id() &&
            (!Auth::user()->vendor || $booking->vendor_id !== Auth::user()->vendor->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($booking);
    }

    /**
     * Create a new booking
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'hotel_id' => 'required|exists:hotels,id',
            'room_type_id' => 'required|exists:room_types,id',
            'check_in_date' => 'required|date|after:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'adults' => 'required|integer|min:1|max:10',
            'children' => 'nullable|integer|min:0|max:10',
            'rooms_count' => 'required|integer|min:1|max:10',
            'guest_name' => 'required|string',
            'guest_email' => 'required|email',
            'guest_phone' => 'required|string',
            'guest_country' => 'nullable|string',
            'special_requests' => 'nullable|string',
            'promo_code' => 'nullable|string',
            'payment_method' => 'required|in:wallet,stripe,promptpay,bank_transfer',
            'guests' => 'nullable|array',
            'guests.*.first_name' => 'required_with:guests|string',
            'guests.*.last_name' => 'required_with:guests|string',
            'guests.*.id_type' => 'nullable|string',
            'guests.*.id_number' => 'nullable|string',
        ]);

        try {
            $validated['user_id'] = Auth::id();
            $booking = $this->bookingService->createBooking($validated);

            return response()->json($booking, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Calculate booking price
     */
    public function calculatePrice(Request $request)
    {
        $validated = $request->validate([
            'room_type_id' => 'required|exists:room_types,id',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
            'adults' => 'required|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'rooms_count' => 'required|integer|min:1',
            'promo_code' => 'nullable|string',
        ]);

        try {
            $roomType = \App\Models\RoomType::findOrFail($validated['room_type_id']);

            $pricing = $this->bookingService->calculateBookingPrice(
                $roomType,
                $validated['check_in_date'],
                $validated['check_out_date'],
                $validated['rooms_count'],
                $validated['adults'],
                $validated['children'] ?? 0,
                $validated['promo_code'] ?? null
            );

            return response()->json($pricing);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Process payment for booking
     */
    public function processPayment(Request $request, $id)
    {
        $booking = HotelBooking::findOrFail($id);

        if ($booking->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:wallet,stripe,promptpay,bank_transfer',
            'transaction_id' => 'nullable|string',
        ]);

        try {
            $this->bookingService->processPayment(
                $booking,
                $validated['payment_method'],
                $validated['transaction_id'] ?? null
            );

            return response()->json([
                'message' => 'Payment processed successfully',
                'booking' => $booking->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Cancel booking
     */
    public function cancel(Request $request, $id)
    {
        $booking = HotelBooking::findOrFail($id);

        if ($booking->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string',
        ]);

        try {
            $this->bookingService->cancelBooking($booking, $validated['reason'] ?? null);

            return response()->json([
                'message' => 'Booking cancelled successfully',
                'booking' => $booking->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Vendor: Get all bookings for their hotels
     */
    public function vendorBookings()
    {
        $vendor = Auth::user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Not a vendor'], 403);
        }

        $bookings = $vendor->hotelBookings()
            ->with(['hotel', 'roomType', 'room', 'user'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($bookings);
    }

    /**
     * Vendor: Check-in booking
     */
    public function checkIn($id)
    {
        $vendor = Auth::user()->vendor;
        $booking = HotelBooking::findOrFail($id);

        if ($booking->vendor_id !== $vendor->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $this->bookingService->checkIn($booking);

            return response()->json([
                'message' => 'Guest checked in successfully',
                'booking' => $booking->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Vendor: Check-out booking
     */
    public function checkOut($id)
    {
        $vendor = Auth::user()->vendor;
        $booking = HotelBooking::findOrFail($id);

        if ($booking->vendor_id !== $vendor->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $this->bookingService->checkOut($booking);

            return response()->json([
                'message' => 'Guest checked out successfully',
                'booking' => $booking->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Vendor: Get booking statistics
     */
    public function stats(Request $request)
    {
        $vendor = Auth::user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Not a vendor'], 403);
        }

        $period = $request->input('period', 'month');
        $stats = $this->bookingService->getVendorBookingStats($vendor, $period);

        return response()->json($stats);
    }
}
