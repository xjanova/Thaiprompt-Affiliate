<?php

namespace App\Http\Controllers\HotelAdmin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\RoomAvailability;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoomManagementController extends Controller
{
    /**
     * List all room types for the managed hotel
     */
    public function index()
    {
        $user = Auth::user();

        $roomTypes = RoomType::where('hotel_id', $user->managed_hotel_id)
            ->withCount(['bookings'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'room_types' => $roomTypes,
        ]);
    }

    /**
     * Get room type details
     */
    public function show($id)
    {
        $user = Auth::user();

        $roomType = RoomType::where('hotel_id', $user->managed_hotel_id)
            ->with(['hotel'])
            ->withCount(['bookings'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'room_type' => $roomType,
        ]);
    }

    /**
     * Create new room type
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'size_sqm' => 'nullable|numeric|min:0',
            'max_adults' => 'required|integer|min:1',
            'max_children' => 'required|integer|min:0',
            'max_occupancy' => 'required|integer|min:1',
            'total_rooms' => 'required|integer|min:1',
            'bed_types' => 'nullable|array',
            'base_price' => 'required|numeric|min:0',
            'weekend_price' => 'nullable|numeric|min:0',
            'extra_adult_price' => 'nullable|numeric|min:0',
            'extra_child_price' => 'nullable|numeric|min:0',
            'amenities' => 'nullable|array',
            'special_features' => 'nullable|array',
            'view_type' => ['nullable', Rule::in(['city', 'sea', 'mountain', 'garden', 'pool', 'none'])],
            'smoking_policy' => ['required', Rule::in(['non_smoking', 'smoking', 'both'])],
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'main_image' => 'nullable|image|max:5120',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|max:5120',
        ]);

        // Handle main image upload
        if ($request->hasFile('main_image')) {
            $validated['main_image'] = $request->file('main_image')
                ->store('rooms/images', 'public');
        }

        // Handle gallery images upload
        if ($request->hasFile('gallery_images')) {
            $galleryImages = [];

            foreach ($request->file('gallery_images') as $image) {
                $galleryImages[] = $image->store('rooms/gallery', 'public');
            }

            $validated['gallery_images'] = $galleryImages;
        }

        // Generate slug
        $validated['slug'] = Str::slug($validated['name']);
        $validated['hotel_id'] = $user->managed_hotel_id;

        // Create room type
        $roomType = RoomType::create($validated);

        // Initialize availability for the next 365 days
        $this->initializeAvailability($roomType);

        return response()->json([
            'success' => true,
            'message' => 'Room type created successfully',
            'room_type' => $roomType,
        ], 201);
    }

    /**
     * Update room type
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        $roomType = RoomType::where('hotel_id', $user->managed_hotel_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'size_sqm' => 'nullable|numeric|min:0',
            'max_adults' => 'required|integer|min:1',
            'max_children' => 'required|integer|min:0',
            'max_occupancy' => 'required|integer|min:1',
            'total_rooms' => 'required|integer|min:1',
            'bed_types' => 'nullable|array',
            'base_price' => 'required|numeric|min:0',
            'weekend_price' => 'nullable|numeric|min:0',
            'extra_adult_price' => 'nullable|numeric|min:0',
            'extra_child_price' => 'nullable|numeric|min:0',
            'amenities' => 'nullable|array',
            'special_features' => 'nullable|array',
            'view_type' => ['nullable', Rule::in(['city', 'sea', 'mountain', 'garden', 'pool', 'none'])],
            'smoking_policy' => ['required', Rule::in(['non_smoking', 'smoking', 'both'])],
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'main_image' => 'nullable|image|max:5120',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|max:5120',
        ]);

        // Handle main image upload
        if ($request->hasFile('main_image')) {
            // Delete old image
            if ($roomType->main_image) {
                Storage::disk('public')->delete($roomType->main_image);
            }

            $validated['main_image'] = $request->file('main_image')
                ->store('rooms/images', 'public');
        }

        // Handle gallery images upload
        if ($request->hasFile('gallery_images')) {
            $galleryImages = [];

            foreach ($request->file('gallery_images') as $image) {
                $galleryImages[] = $image->store('rooms/gallery', 'public');
            }

            // Merge with existing gallery images
            $existingGallery = $roomType->gallery_images ?? [];
            $validated['gallery_images'] = array_merge($existingGallery, $galleryImages);
        }

        // Update slug if name changed
        if ($validated['name'] !== $roomType->name) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Update room type
        $roomType->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Room type updated successfully',
            'room_type' => $roomType,
        ]);
    }

    /**
     * Delete room type
     */
    public function destroy($id)
    {
        $user = Auth::user();

        $roomType = RoomType::where('hotel_id', $user->managed_hotel_id)
            ->findOrFail($id);

        // Check if there are active bookings
        $activeBookings = $roomType->bookings()
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->count();

        if ($activeBookings > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete room type with active bookings. Please complete or cancel all bookings first.',
            ], 422);
        }

        // Delete images
        if ($roomType->main_image) {
            Storage::disk('public')->delete($roomType->main_image);
        }

        if ($roomType->gallery_images) {
            foreach ($roomType->gallery_images as $image) {
                Storage::disk('public')->delete($image);
            }
        }

        // Delete availability records
        RoomAvailability::where('room_type_id', $roomType->id)->delete();

        // Delete room type
        $roomType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Room type deleted successfully',
        ]);
    }

    /**
     * Remove gallery image
     */
    public function removeGalleryImage(Request $request, $id)
    {
        $user = Auth::user();

        $roomType = RoomType::where('hotel_id', $user->managed_hotel_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'image_path' => 'required|string',
        ]);

        $galleryImages = $roomType->gallery_images ?? [];

        // Remove image from array
        $galleryImages = array_filter($galleryImages, function($image) use ($validated) {
            return $image !== $validated['image_path'];
        });

        // Delete from storage
        Storage::disk('public')->delete($validated['image_path']);

        // Update room type
        $roomType->update(['gallery_images' => array_values($galleryImages)]);

        return response()->json([
            'success' => true,
            'message' => 'Image removed successfully',
        ]);
    }

    /**
     * Initialize availability for new room type
     */
    private function initializeAvailability(RoomType $roomType)
    {
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays(365);

        $availabilityData = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $availabilityData[] = [
                'room_type_id' => $roomType->id,
                'date' => $date->format('Y-m-d'),
                'available_rooms' => $roomType->total_rooms,
                'price' => $roomType->base_price,
                'is_available' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Batch insert for performance
        RoomAvailability::insert($availabilityData);
    }

    /**
     * Get room availability
     */
    public function availability($id, Request $request)
    {
        $user = Auth::user();

        $roomType = RoomType::where('hotel_id', $user->managed_hotel_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
        ]);

        $availability = RoomAvailability::where('room_type_id', $roomType->id)
            ->whereBetween('date', [$validated['start_date'], $validated['end_date']])
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'availability' => $availability,
        ]);
    }

    /**
     * Update room availability
     */
    public function updateAvailability(Request $request, $id)
    {
        $user = Auth::user();

        $roomType = RoomType::where('hotel_id', $user->managed_hotel_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'available_rooms' => 'required|integer|min:0|max:' . $roomType->total_rooms,
            'price' => 'nullable|numeric|min:0',
            'is_available' => 'boolean',
            'min_stay' => 'nullable|integer|min:1',
            'max_stay' => 'nullable|integer|min:1',
        ]);

        $availability = RoomAvailability::updateOrCreate(
            [
                'room_type_id' => $roomType->id,
                'date' => $validated['date'],
            ],
            [
                'available_rooms' => $validated['available_rooms'],
                'price' => $validated['price'] ?? $roomType->base_price,
                'is_available' => $validated['is_available'] ?? true,
                'min_stay' => $validated['min_stay'] ?? null,
                'max_stay' => $validated['max_stay'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Availability updated successfully',
            'availability' => $availability,
        ]);
    }

    /**
     * Bulk update availability
     */
    public function bulkUpdateAvailability(Request $request, $id)
    {
        $user = Auth::user();

        $roomType = RoomType::where('hotel_id', $user->managed_hotel_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'available_rooms' => 'nullable|integer|min:0|max:' . $roomType->total_rooms,
            'price' => 'nullable|numeric|min:0',
            'is_available' => 'nullable|boolean',
            'min_stay' => 'nullable|integer|min:1',
            'max_stay' => 'nullable|integer|min:1',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        $updates = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $updateData = [
                'room_type_id' => $roomType->id,
                'date' => $date->format('Y-m-d'),
            ];

            if (isset($validated['available_rooms'])) {
                $updateData['available_rooms'] = $validated['available_rooms'];
            }

            if (isset($validated['price'])) {
                $updateData['price'] = $validated['price'];
            }

            if (isset($validated['is_available'])) {
                $updateData['is_available'] = $validated['is_available'];
            }

            if (isset($validated['min_stay'])) {
                $updateData['min_stay'] = $validated['min_stay'];
            }

            if (isset($validated['max_stay'])) {
                $updateData['max_stay'] = $validated['max_stay'];
            }

            $updateData['updated_at'] = now();

            RoomAvailability::updateOrCreate(
                [
                    'room_type_id' => $roomType->id,
                    'date' => $date->format('Y-m-d'),
                ],
                $updateData
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Availability updated successfully for ' . $startDate->diffInDays($endDate) . ' days',
        ]);
    }
}
