<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HotelBooking;
use App\Models\Hotel;
use App\Services\HotelBookingService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HotelBookingManagementController extends Controller
{
    protected $bookingService;

    public function __construct(HotelBookingService $bookingService)
    {
        $this->middleware(['auth', 'role:admin']);
        $this->bookingService = $bookingService;
    }

    /**
     * Display bookings list
     */
    public function index(Request $request)
    {
        $query = HotelBooking::with(['hotel', 'roomType', 'user']);

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('booking_number', 'LIKE', "%{$search}%")
                    ->orWhere('guest_name', 'LIKE', "%{$search}%")
                    ->orWhere('guest_email', 'LIKE', "%{$search}%")
                    ->orWhere('guest_phone', 'LIKE', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by hotel
        if ($request->has('hotel_id')) {
            $query->where('hotel_id', $request->hotel_id);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('check_in_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('check_out_date', '<=', $request->date_to);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $bookings = $query->orderBy('created_at', 'DESC')->paginate(20);

        $hotels = Hotel::active()->get();

        $stats = [
            'total' => HotelBooking::count(),
            'pending' => HotelBooking::where('status', 'pending')->count(),
            'confirmed' => HotelBooking::where('status', 'confirmed')->count(),
            'cancelled' => HotelBooking::where('status', 'cancelled')->count(),
            'total_revenue' => HotelBooking::where('payment_status', 'paid')->sum('total_amount'),
        ];

        return view('admin.hotels.bookings.index', compact('bookings', 'hotels', 'stats'));
    }

    /**
     * Show booking details
     */
    public function show($id)
    {
        $booking = HotelBooking::with([
            'hotel',
            'roomType',
            'user',
            'affiliate',
            'paymentTransaction',
            'review'
        ])->findOrFail($id);

        return view('admin.hotels.bookings.show', compact('booking'));
    }

    /**
     * Update booking status
     */
    public function updateStatus(Request $request, $id)
    {
        $booking = HotelBooking::findOrFail($id);

        $request->validate([
            'status' => 'required|in:pending,confirmed,checked_in,checked_out,cancelled,no_show,completed',
            'internal_notes' => 'nullable|string',
        ]);

        try {
            switch ($request->status) {
                case 'confirmed':
                    $this->bookingService->confirmBooking($booking);
                    break;

                case 'checked_in':
                    $this->bookingService->checkIn($booking);
                    break;

                case 'checked_out':
                    $this->bookingService->checkOut($booking);
                    break;

                case 'cancelled':
                    $refund = $this->bookingService->calculateRefund($booking);
                    $this->bookingService->cancelBooking(
                        $booking,
                        $request->input('cancellation_reason', 'Cancelled by admin'),
                        $refund
                    );
                    break;

                default:
                    $booking->update(['status' => $request->status]);
            }

            if ($request->has('internal_notes')) {
                $booking->update(['internal_notes' => $request->internal_notes]);
            }

            return back()->with('success', 'Booking status updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel booking
     */
    public function cancel(Request $request, $id)
    {
        $booking = HotelBooking::findOrFail($id);

        $request->validate([
            'reason' => 'required|string',
            'refund_amount' => 'nullable|numeric|min:0',
        ]);

        try {
            $refundAmount = $request->refund_amount ?? $this->bookingService->calculateRefund($booking);

            $this->bookingService->cancelBooking(
                $booking,
                $request->reason,
                $refundAmount
            );

            return back()->with('success', 'Booking cancelled successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Create manual booking
     */
    public function create()
    {
        $hotels = Hotel::active()->get();

        return view('admin.hotels.bookings.create', compact('hotels'));
    }

    /**
     * Store manual booking
     */
    public function store(Request $request)
    {
        $request->validate([
            'hotel_id' => 'required|exists:hotels,id',
            'room_type_id' => 'required|exists:room_types,id',
            'user_id' => 'nullable|exists:users,id',
            'guest_name' => 'required|string',
            'guest_email' => 'required|email',
            'guest_phone' => 'required|string',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
            'number_of_rooms' => 'required|integer|min:1',
            'number_of_adults' => 'required|integer|min:1',
            'number_of_children' => 'nullable|integer|min:0',
            'special_requests' => 'nullable|string',
            'status' => 'required|in:pending,confirmed',
            'payment_status' => 'required|in:pending,paid',
        ]);

        try {
            $data = $request->all();
            $data['booking_source'] = 'admin';

            $user = $request->user_id
                ? \App\Models\User::find($request->user_id)
                : auth()->user();

            $booking = $this->bookingService->createBooking($data, $user);

            if ($request->status === 'confirmed') {
                $this->bookingService->confirmBooking($booking);
            }

            return redirect()
                ->route('admin.hotels.bookings.show', $booking->id)
                ->with('success', 'Booking created successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Export bookings
     */
    public function export(Request $request)
    {
        // TODO: Implement export functionality (CSV/Excel)
        return back()->with('info', 'Export feature coming soon.');
    }

    /**
     * Booking calendar view
     */
    public function calendar(Request $request)
    {
        $hotelId = $request->get('hotel_id');
        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);

        $query = HotelBooking::with(['hotel', 'roomType'])
            ->whereMonth('check_in_date', $month)
            ->whereYear('check_in_date', $year);

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        $bookings = $query->get();
        $hotels = Hotel::active()->get();

        return view('admin.hotels.bookings.calendar', compact('bookings', 'hotels', 'month', 'year'));
    }

    /**
     * Analytics/Reports
     */
    public function analytics(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth());
        $hotelId = $request->get('hotel_id');

        $query = HotelBooking::whereBetween('created_at', [$startDate, $endDate]);

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        $stats = [
            'total_bookings' => $query->count(),
            'confirmed_bookings' => (clone $query)->where('status', 'confirmed')->count(),
            'cancelled_bookings' => (clone $query)->where('status', 'cancelled')->count(),
            'total_revenue' => (clone $query)->where('payment_status', 'paid')->sum('total_amount'),
            'average_booking_value' => (clone $query)->where('payment_status', 'paid')->avg('total_amount'),
        ];

        // Bookings by day
        $bookingsByDay = (clone $query)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(total_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top hotels
        $topHotels = HotelBooking::whereBetween('created_at', [$startDate, $endDate])
            ->with('hotel')
            ->selectRaw('hotel_id, COUNT(*) as bookings, SUM(total_amount) as revenue')
            ->groupBy('hotel_id')
            ->orderBy('revenue', 'DESC')
            ->limit(10)
            ->get();

        $hotels = Hotel::active()->get();

        return view('admin.hotels.bookings.analytics', compact(
            'stats',
            'bookingsByDay',
            'topHotels',
            'hotels',
            'startDate',
            'endDate'
        ));
    }
}
