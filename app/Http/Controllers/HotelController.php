<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\HotelFacility;
use App\Services\HotelSearchService;
use App\Services\RoomAvailabilityService;
use Illuminate\Http\Request;

class HotelController extends Controller
{
    protected $searchService;
    protected $availabilityService;

    public function __construct(
        HotelSearchService $searchService,
        RoomAvailabilityService $availabilityService
    ) {
        $this->searchService = $searchService;
        $this->availabilityService = $availabilityService;
    }

    /**
     * Display hotel search page
     */
    public function index(Request $request)
    {
        $hotels = $this->searchService->search($request->all());
        $facilities = HotelFacility::active()->ordered()->get();
        $popularDestinations = $this->searchService->getPopularDestinations();

        return view('hotels.index', compact('hotels', 'facilities', 'popularDestinations'));
    }

    /**
     * Display hotel details
     */
    public function show(Request $request, $slug)
    {
        $hotel = Hotel::where('slug', $slug)
            ->with([
                'roomTypes' => function ($query) {
                    $query->active()->orderBy('base_price', 'asc');
                },
                'facilities',
                'reviews' => function ($query) {
                    $query->approved()->orderBy('created_at', 'desc')->limit(10);
                }
            ])
            ->firstOrFail();

        // Increment view count
        $hotel->incrementViews();

        // Get availability if dates provided
        $availability = [];
        if ($request->has('check_in') && $request->has('check_out')) {
            foreach ($hotel->roomTypes as $roomType) {
                $availability[$roomType->id] = $this->availabilityService->checkAvailability(
                    $roomType,
                    $request->check_in,
                    $request->check_out,
                    $request->rooms ?? 1
                );
            }
        }

        // Get related hotels
        $relatedHotels = $this->searchService->getRelatedHotels($hotel);

        return view('hotels.show', compact('hotel', 'availability', 'relatedHotels'));
    }

    /**
     * Search hotels (AJAX)
     */
    public function search(Request $request)
    {
        $hotels = $this->searchService->search($request->all());

        if ($request->ajax()) {
            return response()->json($hotels);
        }

        return redirect()->route('hotels.index')->with('hotels', $hotels);
    }

    /**
     * Autocomplete search
     */
    public function autocomplete(Request $request)
    {
        $keyword = $request->get('q');
        $results = $this->searchService->autocomplete($keyword);

        return response()->json($results);
    }

    /**
     * Get featured hotels
     */
    public function featured()
    {
        $hotels = $this->searchService->getFeaturedHotels();

        return view('hotels.featured', compact('hotels'));
    }

    /**
     * Get hotels by city
     */
    public function byCity($city)
    {
        $hotels = $this->searchService->search(['city' => $city]);
        $facilities = HotelFacility::active()->ordered()->get();
        $popularDestinations = $this->searchService->getPopularDestinations();

        return view('hotels.index', compact('hotels', 'facilities', 'popularDestinations', 'city'));
    }

    /**
     * Check room availability (AJAX)
     */
    public function checkAvailability(Request $request)
    {
        $request->validate([
            'room_type_id' => 'required|exists:room_types,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'rooms' => 'nullable|integer|min:1',
        ]);

        $roomType = \App\Models\RoomType::findOrFail($request->room_type_id);

        $availability = $this->availabilityService->checkAvailability(
            $roomType,
            $request->check_in,
            $request->check_out,
            $request->rooms ?? 1
        );

        $isAvailable = collect($availability)->every(fn($day) => $day['is_available']);

        return response()->json([
            'available' => $isAvailable,
            'availability' => $availability,
        ]);
    }
}
