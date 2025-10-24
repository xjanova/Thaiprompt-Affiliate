<?php

namespace App\Http\Controllers\Api\Hotel;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Services\Hotel\HotelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HotelController extends Controller
{
    protected $hotelService;

    public function __construct(HotelService $hotelService)
    {
        $this->hotelService = $hotelService;
    }

    /**
     * Get all hotels (public)
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'city', 'country', 'type', 'min_stars', 'amenities',
            'min_price', 'max_price', 'check_in', 'check_out',
            'sort_by', 'per_page'
        ]);

        $hotels = $this->hotelService->searchHotels($filters);

        return response()->json($hotels);
    }

    /**
     * Get single hotel details
     */
    public function show($id)
    {
        $hotel = Hotel::with(['vendor', 'roomTypes', 'approvedReviews.user'])
            ->findOrFail($id);

        return response()->json($hotel);
    }

    /**
     * Get hotel availability
     */
    public function availability($id, Request $request)
    {
        $request->validate([
            'check_in' => 'required|date|after:today',
            'check_out' => 'required|date|after:check_in',
        ]);

        $hotel = Hotel::findOrFail($id);
        $availability = $this->hotelService->getAvailability(
            $hotel,
            $request->check_in,
            $request->check_out
        );

        return response()->json($availability);
    }

    /**
     * Get vendor's hotels
     */
    public function vendorHotels()
    {
        $vendor = Auth::user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Not a vendor'], 403);
        }

        if (!$vendor->hasHotelManagement()) {
            return response()->json(['message' => 'Hotel management addon not purchased'], 403);
        }

        $hotels = $vendor->hotels()->with('roomTypes')->get();

        return response()->json($hotels);
    }

    /**
     * Create a new hotel
     */
    public function store(Request $request)
    {
        $vendor = Auth::user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Not a vendor'], 403);
        }

        if (!$vendor->hasHotelManagement()) {
            return response()->json(['message' => 'Hotel management addon required'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:hotel,resort,hostel,apartment,villa',
            'star_rating' => 'nullable|integer|min:1|max:5',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'website' => 'nullable|url',
            'main_image' => 'nullable|string',
            'gallery_images' => 'nullable|array',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i',
            'cancellation_policy' => 'nullable|string',
            'house_rules' => 'nullable|string',
            'amenities' => 'nullable|array',
        ]);

        try {
            $hotel = $this->hotelService->createHotel($vendor, $validated);
            return response()->json($hotel, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Update hotel
     */
    public function update(Request $request, $id)
    {
        $vendor = Auth::user()->vendor;
        $hotel = Hotel::findOrFail($id);

        if ($hotel->vendor_id !== $vendor->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:hotel,resort,hostel,apartment,villa',
            'star_rating' => 'nullable|integer|min:1|max:5',
            'status' => 'sometimes|in:active,inactive,maintenance',
            'city' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
        ]);

        try {
            $hotel = $this->hotelService->updateHotel($hotel, $validated);
            return response()->json($hotel);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete hotel
     */
    public function destroy($id)
    {
        $vendor = Auth::user()->vendor;
        $hotel = Hotel::findOrFail($id);

        if ($hotel->vendor_id !== $vendor->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $hotel->delete();

        return response()->json(['message' => 'Hotel deleted successfully']);
    }

    /**
     * Get hotel statistics
     */
    public function stats($id)
    {
        $vendor = Auth::user()->vendor;
        $hotel = Hotel::findOrFail($id);

        if ($hotel->vendor_id !== $vendor->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $stats = $this->hotelService->getHotelStats($hotel);

        return response()->json($stats);
    }
}
