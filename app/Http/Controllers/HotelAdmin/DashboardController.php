<?php

namespace App\Http\Controllers\HotelAdmin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\HotelBooking;
use App\Models\HotelReview;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display hotel admin dashboard
     */
    public function index()
    {
        $user = Auth::user();

        // Super admin can see all hotels, hotel admin sees only their managed hotel
        if ($user->is_admin) {
            $hotels = Hotel::with(['bookings', 'reviews'])->get();
            $hotel = null; // Super admin doesn't have a single managed hotel
        } else {
            $hotel = Hotel::with(['bookings', 'reviews'])
                ->findOrFail($user->managed_hotel_id);
            $hotels = collect([$hotel]);
        }

        // Calculate statistics
        $statistics = $this->calculateStatistics($hotels);

        // Get recent bookings
        $recentBookings = $this->getRecentBookings($hotels);

        // Get recent reviews
        $recentReviews = $this->getRecentReviews($hotels);

        // Monthly booking trends
        $bookingTrends = $this->getBookingTrends($hotels);

        // Revenue statistics
        $revenueStats = $this->getRevenueStatistics($hotels);

        return view('hotel-admin.dashboard', compact(
            'hotel',
            'hotels',
            'statistics',
            'recentBookings',
            'recentReviews',
            'bookingTrends',
            'revenueStats'
        ));
    }

    /**
     * Calculate dashboard statistics
     */
    private function calculateStatistics($hotels)
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        $allBookings = HotelBooking::whereIn('hotel_id', $hotels->pluck('id'))->get();
        $allReviews = HotelReview::whereIn('hotel_id', $hotels->pluck('id'))->get();

        return [
            'total_bookings' => $allBookings->count(),
            'total_bookings_today' => $allBookings->where('created_at', '>=', $today)->count(),
            'total_bookings_this_month' => $allBookings->where('created_at', '>=', $thisMonth)->count(),

            'pending_bookings' => $allBookings->where('status', 'pending')->count(),
            'confirmed_bookings' => $allBookings->where('status', 'confirmed')->count(),
            'checked_in_bookings' => $allBookings->where('status', 'checked_in')->count(),
            'completed_bookings' => $allBookings->where('status', 'completed')->count(),
            'cancelled_bookings' => $allBookings->where('status', 'cancelled')->count(),

            'total_revenue' => $allBookings->whereIn('status', ['confirmed', 'checked_in', 'completed'])->sum('total_amount'),
            'revenue_today' => $allBookings->where('created_at', '>=', $today)
                ->whereIn('status', ['confirmed', 'checked_in', 'completed'])
                ->sum('total_amount'),
            'revenue_this_month' => $allBookings->where('created_at', '>=', $thisMonth)
                ->whereIn('status', ['confirmed', 'checked_in', 'completed'])
                ->sum('total_amount'),

            'total_reviews' => $allReviews->count(),
            'pending_reviews' => $allReviews->where('is_approved', false)->count(),
            'average_rating' => $allReviews->where('is_approved', true)->avg('overall_rating') ?? 0,

            'total_hotels' => $hotels->count(),
            'active_hotels' => $hotels->where('is_active', true)->count(),
        ];
    }

    /**
     * Get recent bookings
     */
    private function getRecentBookings($hotels)
    {
        return HotelBooking::with(['hotel', 'user', 'roomType'])
            ->whereIn('hotel_id', $hotels->pluck('id'))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get recent reviews
     */
    private function getRecentReviews($hotels)
    {
        return HotelReview::with(['hotel', 'user'])
            ->whereIn('hotel_id', $hotels->pluck('id'))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get booking trends for the last 12 months
     */
    private function getBookingTrends($hotels)
    {
        $trends = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();

            $count = HotelBooking::whereIn('hotel_id', $hotels->pluck('id'))
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count();

            $revenue = HotelBooking::whereIn('hotel_id', $hotels->pluck('id'))
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->whereIn('status', ['confirmed', 'checked_in', 'completed'])
                ->sum('total_amount');

            $trends[] = [
                'month' => $date->format('M Y'),
                'bookings' => $count,
                'revenue' => $revenue,
            ];
        }

        return $trends;
    }

    /**
     * Get revenue statistics
     */
    private function getRevenueStatistics($hotels)
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $thisWeek = Carbon::now()->startOfWeek();
        $lastWeek = Carbon::now()->subWeek()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        return [
            'today' => HotelBooking::whereIn('hotel_id', $hotels->pluck('id'))
                ->where('created_at', '>=', $today)
                ->whereIn('status', ['confirmed', 'checked_in', 'completed'])
                ->sum('total_amount'),

            'yesterday' => HotelBooking::whereIn('hotel_id', $hotels->pluck('id'))
                ->whereBetween('created_at', [$yesterday, $today])
                ->whereIn('status', ['confirmed', 'checked_in', 'completed'])
                ->sum('total_amount'),

            'this_week' => HotelBooking::whereIn('hotel_id', $hotels->pluck('id'))
                ->where('created_at', '>=', $thisWeek)
                ->whereIn('status', ['confirmed', 'checked_in', 'completed'])
                ->sum('total_amount'),

            'last_week' => HotelBooking::whereIn('hotel_id', $hotels->pluck('id'))
                ->whereBetween('created_at', [$lastWeek, $thisWeek])
                ->whereIn('status', ['confirmed', 'checked_in', 'completed'])
                ->sum('total_amount'),

            'this_month' => HotelBooking::whereIn('hotel_id', $hotels->pluck('id'))
                ->where('created_at', '>=', $thisMonth)
                ->whereIn('status', ['confirmed', 'checked_in', 'completed'])
                ->sum('total_amount'),

            'last_month' => HotelBooking::whereIn('hotel_id', $hotels->pluck('id'))
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->whereIn('status', ['confirmed', 'checked_in', 'completed'])
                ->sum('total_amount'),
        ];
    }

    /**
     * Get hotel location data for map
     */
    public function getMapData()
    {
        $user = Auth::user();

        if ($user->is_admin) {
            $hotels = Hotel::where('is_active', true)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->select('id', 'name', 'address', 'latitude', 'longitude', 'rating', 'main_image')
                ->get();
        } else {
            $hotels = Hotel::where('id', $user->managed_hotel_id)
                ->where('is_active', true)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->select('id', 'name', 'address', 'latitude', 'longitude', 'rating', 'main_image')
                ->get();
        }

        return response()->json([
            'success' => true,
            'hotels' => $hotels,
        ]);
    }
}
