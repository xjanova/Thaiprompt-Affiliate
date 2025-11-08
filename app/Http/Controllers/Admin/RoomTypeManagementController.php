<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Services\RoomAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class RoomTypeManagementController extends Controller
{
    protected $availabilityService;

    public function __construct(RoomAvailabilityService $availabilityService)
    {
        $this->middleware(['auth', 'role:admin']);
        $this->availabilityService = $availabilityService;
    }

    /**
     * Display room types for a hotel
     */
    public function index($hotelId)
    {
        $hotel = Hotel::findOrFail($hotelId);
        $roomTypes = $hotel->roomTypes()->paginate(20);

        return view('admin.hotels.rooms.index', compact('hotel', 'roomTypes'));
    }

    /**
     * Show create form
     */
    public function create($hotelId)
    {
        $hotel = Hotel::findOrFail($hotelId);

        return view('admin.hotels.rooms.create', compact('hotel'));
    }

    /**
     * Store new room type
     */
    public function store(Request $request, $hotelId)
    {
        $hotel = Hotel::findOrFail($hotelId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'size_sqm' => 'nullable|numeric|min:0',
            'max_adults' => 'required|integer|min:1',
            'max_children' => 'nullable|integer|min:0',
            'max_occupancy' => 'required|integer|min:1',
            'total_rooms' => 'required|integer|min:1',
            'bed_types' => 'nullable|array',
            'base_price' => 'required|numeric|min:0',
            'weekend_price' => 'nullable|numeric|min:0',
            'extra_adult_price' => 'nullable|numeric|min:0',
            'extra_child_price' => 'nullable|numeric|min:0',
            'main_image' => 'nullable|image|max:5120',
            'gallery_images.*' => 'nullable|image|max:5120',
            'amenities' => 'nullable|array',
            'special_features' => 'nullable|string',
            'view_type' => 'nullable|in:city,sea,mountain,garden,pool,none',
            'smoking_policy' => 'required|in:non_smoking,smoking,both',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
        ]);

        try {
            $validated['hotel_id'] = $hotel->id;

            // Handle main image
            if ($request->hasFile('main_image')) {
                $validated['main_image'] = $request->file('main_image')->store('rooms', 'public');
            }

            // Handle gallery images
            $galleryImages = [];
            if ($request->hasFile('gallery_images')) {
                foreach ($request->file('gallery_images') as $image) {
                    $galleryImages[] = $image->store('rooms/gallery', 'public');
                }
            }
            $validated['gallery_images'] = $galleryImages;

            // Create room type
            $roomType = RoomType::create($validated);

            // Initialize availability for next 365 days
            $startDate = Carbon::today();
            $endDate = Carbon::today()->addDays(365);
            $this->availabilityService->initializeAvailability($roomType, $startDate, $endDate);

            return redirect()
                ->route('admin.hotels.rooms.show', [$hotel->id, $roomType->id])
                ->with('success', 'Room type created successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create room type: ' . $e->getMessage());
        }
    }

    /**
     * Show room type details
     */
    public function show($hotelId, $roomTypeId)
    {
        $hotel = Hotel::findOrFail($hotelId);
        $roomType = RoomType::where('hotel_id', $hotelId)
            ->findOrFail($roomTypeId);

        $stats = [
            'total_bookings' => $roomType->bookings()->count(),
            'confirmed_bookings' => $roomType->bookings()->confirmed()->count(),
            'total_revenue' => $roomType->bookings()->where('payment_status', 'paid')->sum('total_amount'),
        ];

        return view('admin.hotels.rooms.show', compact('hotel', 'roomType', 'stats'));
    }

    /**
     * Show edit form
     */
    public function edit($hotelId, $roomTypeId)
    {
        $hotel = Hotel::findOrFail($hotelId);
        $roomType = RoomType::where('hotel_id', $hotelId)
            ->findOrFail($roomTypeId);

        return view('admin.hotels.rooms.edit', compact('hotel', 'roomType'));
    }

    /**
     * Update room type
     */
    public function update(Request $request, $hotelId, $roomTypeId)
    {
        $hotel = Hotel::findOrFail($hotelId);
        $roomType = RoomType::where('hotel_id', $hotelId)
            ->findOrFail($roomTypeId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'size_sqm' => 'nullable|numeric|min:0',
            'max_adults' => 'required|integer|min:1',
            'max_children' => 'nullable|integer|min:0',
            'max_occupancy' => 'required|integer|min:1',
            'total_rooms' => 'required|integer|min:1',
            'bed_types' => 'nullable|array',
            'base_price' => 'required|numeric|min:0',
            'weekend_price' => 'nullable|numeric|min:0',
            'extra_adult_price' => 'nullable|numeric|min:0',
            'extra_child_price' => 'nullable|numeric|min:0',
            'main_image' => 'nullable|image|max:5120',
            'amenities' => 'nullable|array',
            'special_features' => 'nullable|string',
            'view_type' => 'nullable|in:city,sea,mountain,garden,pool,none',
            'smoking_policy' => 'required|in:non_smoking,smoking,both',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
        ]);

        try {
            // Handle main image
            if ($request->hasFile('main_image')) {
                if ($roomType->main_image) {
                    Storage::disk('public')->delete($roomType->main_image);
                }
                $validated['main_image'] = $request->file('main_image')->store('rooms', 'public');
            }

            $roomType->update($validated);

            return redirect()
                ->route('admin.hotels.rooms.show', [$hotel->id, $roomType->id])
                ->with('success', 'Room type updated successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update room type: ' . $e->getMessage());
        }
    }

    /**
     * Delete room type
     */
    public function destroy($hotelId, $roomTypeId)
    {
        $roomType = RoomType::where('hotel_id', $hotelId)
            ->findOrFail($roomTypeId);

        // Check if has active bookings
        if ($roomType->bookings()->whereIn('status', ['pending', 'confirmed', 'checked_in'])->exists()) {
            return back()->with('error', 'Cannot delete room type with active bookings.');
        }

        $roomType->delete();

        return redirect()
            ->route('admin.hotels.rooms.index', $hotelId)
            ->with('success', 'Room type deleted successfully.');
    }

    /**
     * Manage availability calendar
     */
    public function availability($hotelId, $roomTypeId, Request $request)
    {
        $hotel = Hotel::findOrFail($hotelId);
        $roomType = RoomType::where('hotel_id', $hotelId)
            ->findOrFail($roomTypeId);

        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);

        $calendar = $this->availabilityService->getCalendarData($roomType, $month, $year);

        return view('admin.hotels.rooms.availability', compact('hotel', 'roomType', 'calendar', 'month', 'year'));
    }

    /**
     * Update availability
     */
    public function updateAvailability(Request $request, $hotelId, $roomTypeId)
    {
        $roomType = RoomType::where('hotel_id', $hotelId)
            ->findOrFail($roomTypeId);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'action' => 'required|in:update_price,block,unblock',
            'price' => 'required_if:action,update_price|nullable|numeric|min:0',
        ]);

        try {
            switch ($request->action) {
                case 'update_price':
                    $this->availabilityService->updatePrices(
                        $roomType,
                        $request->start_date,
                        $request->end_date,
                        $request->price
                    );
                    $message = 'Prices updated successfully.';
                    break;

                case 'block':
                    $this->availabilityService->blockDates(
                        $roomType,
                        $request->start_date,
                        $request->end_date
                    );
                    $message = 'Dates blocked successfully.';
                    break;

                case 'unblock':
                    $this->availabilityService->unblockDates(
                        $roomType,
                        $request->start_date,
                        $request->end_date
                    );
                    $message = 'Dates unblocked successfully.';
                    break;
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
