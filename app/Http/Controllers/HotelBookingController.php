<?php

namespace App\Http\Controllers;

use App\Models\HotelBooking;
use App\Models\RoomType;
use App\Services\HotelBookingService;
use App\Services\HotelPricingService;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HotelBookingController extends Controller
{
    protected $bookingService;
    protected $pricingService;
    protected $paymentService;

    public function __construct(
        HotelBookingService $bookingService,
        HotelPricingService $pricingService,
        PaymentService $paymentService
    ) {
        $this->middleware('auth');
        $this->bookingService = $bookingService;
        $this->pricingService = $pricingService;
        $this->paymentService = $paymentService;
    }

    /**
     * Show booking form
     */
    public function create(Request $request)
    {
        $request->validate([
            'room_type_id' => 'required|exists:room_types,id',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'rooms' => 'nullable|integer|min:1',
            'adults' => 'nullable|integer|min:1',
            'children' => 'nullable|integer|min:0',
        ]);

        $roomType = RoomType::with('hotel')->findOrFail($request->room_type_id);

        // Calculate pricing
        $pricing = $this->pricingService->calculateBookingPrice(
            $roomType,
            $request->check_in,
            $request->check_out,
            $request->rooms ?? 1,
            $request->adults ?? 2,
            $request->children ?? 0,
            $request->promo_code ?? null
        );

        // Get available offers
        $offers = $this->pricingService->getAvailableOffers(
            $roomType,
            $request->check_in,
            $request->check_out,
            $pricing['subtotal']
        );

        return view('hotels.bookings.create', compact(
            'roomType',
            'pricing',
            'offers'
        ))->with('bookingData', $request->all());
    }

    /**
     * Store booking
     */
    public function store(Request $request)
    {
        $request->validate([
            'room_type_id' => 'required|exists:room_types,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'number_of_rooms' => 'required|integer|min:1',
            'number_of_adults' => 'required|integer|min:1',
            'number_of_children' => 'nullable|integer|min:0',
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'required|email',
            'guest_phone' => 'required|string',
            'special_requests' => 'nullable|string',
            'promo_code' => 'nullable|string',
            'payment_method' => 'required|string',
        ]);

        try {
            // Create booking
            $booking = $this->bookingService->createBooking(
                $request->all(),
                Auth::user()
            );

            // Process payment
            if ($request->payment_method === 'wallet') {
                // Pay with wallet
                $payment = $this->paymentService->processWalletPayment(
                    Auth::user(),
                    $booking->total_amount,
                    'hotel_booking',
                    $booking->id
                );

                if ($payment['success']) {
                    $this->bookingService->confirmBooking($booking, $payment['transaction_id']);

                    return redirect()
                        ->route('hotels.bookings.show', $booking->booking_number)
                        ->with('success', 'Booking confirmed successfully!');
                } else {
                    $booking->cancel('Payment failed');
                    return back()->with('error', $payment['message']);
                }
            } else {
                // Redirect to payment page
                return redirect()
                    ->route('hotels.bookings.payment', $booking->booking_number)
                    ->with('success', 'Booking created! Please complete payment.');
            }
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Show booking details
     */
    public function show($bookingNumber)
    {
        $booking = HotelBooking::where('booking_number', $bookingNumber)
            ->with(['hotel', 'roomType', 'user', 'review'])
            ->firstOrFail();

        // Check authorization
        if ($booking->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized access');
        }

        return view('hotels.bookings.show', compact('booking'));
    }

    /**
     * Show payment page
     */
    public function payment($bookingNumber)
    {
        $booking = HotelBooking::where('booking_number', $bookingNumber)
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->firstOrFail();

        // Get available payment methods
        $paymentMethods = $this->paymentService->getAvailableMethods();

        return view('hotels.bookings.payment', compact('booking', 'paymentMethods'));
    }

    /**
     * Process payment
     */
    public function processPayment(Request $request, $bookingNumber)
    {
        $booking = HotelBooking::where('booking_number', $bookingNumber)
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->firstOrFail();

        $request->validate([
            'payment_method' => 'required|string',
        ]);

        try {
            $payment = $this->paymentService->createPayment(
                $request->payment_method,
                $booking->total_amount,
                [
                    'booking_type' => 'hotel',
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                ]
            );

            if ($payment['success']) {
                $this->bookingService->confirmBooking($booking, $payment['transaction_id']);

                return redirect()
                    ->route('hotels.bookings.show', $booking->booking_number)
                    ->with('success', 'Payment successful! Your booking is confirmed.');
            } else {
                return back()->with('error', $payment['message']);
            }
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * User's bookings list
     */
    public function index(Request $request)
    {
        $status = $request->get('status');
        $bookings = $this->bookingService->getUserBookings(Auth::user(), $status);

        return view('hotels.bookings.index', compact('bookings', 'status'));
    }

    /**
     * Cancel booking
     */
    public function cancel(Request $request, $bookingNumber)
    {
        $booking = HotelBooking::where('booking_number', $bookingNumber)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $request->validate([
            'reason' => 'nullable|string',
        ]);

        try {
            // Calculate refund
            $refundAmount = $this->bookingService->calculateRefund($booking);

            // Cancel booking
            $this->bookingService->cancelBooking(
                $booking,
                $request->reason,
                $refundAmount
            );

            // Process refund if applicable
            if ($refundAmount > 0 && $booking->payment_status === 'paid') {
                $this->paymentService->processRefund(
                    $booking->paymentTransaction,
                    $refundAmount
                );
            }

            return redirect()
                ->route('hotels.bookings.show', $booking->booking_number)
                ->with('success', 'Booking cancelled successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Modify booking
     */
    public function edit($bookingNumber)
    {
        $booking = HotelBooking::where('booking_number', $bookingNumber)
            ->where('user_id', Auth::id())
            ->with(['hotel', 'roomType'])
            ->firstOrFail();

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return redirect()
                ->route('hotels.bookings.show', $booking->booking_number)
                ->with('error', 'This booking cannot be modified.');
        }

        return view('hotels.bookings.edit', compact('booking'));
    }

    /**
     * Update booking
     */
    public function update(Request $request, $bookingNumber)
    {
        $booking = HotelBooking::where('booking_number', $bookingNumber)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $request->validate([
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'number_of_adults' => 'required|integer|min:1',
            'number_of_children' => 'nullable|integer|min:0',
        ]);

        try {
            $this->bookingService->modifyBooking($booking, $request->all());

            return redirect()
                ->route('hotels.bookings.show', $booking->booking_number)
                ->with('success', 'Booking updated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Calculate price (AJAX)
     */
    public function calculatePrice(Request $request)
    {
        $request->validate([
            'room_type_id' => 'required|exists:room_types,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'rooms' => 'nullable|integer|min:1',
            'adults' => 'nullable|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'promo_code' => 'nullable|string',
        ]);

        $roomType = RoomType::findOrFail($request->room_type_id);

        $pricing = $this->pricingService->calculateBookingPrice(
            $roomType,
            $request->check_in,
            $request->check_out,
            $request->rooms ?? 1,
            $request->adults ?? 2,
            $request->children ?? 0,
            $request->promo_code ?? null
        );

        return response()->json($pricing);
    }
}
