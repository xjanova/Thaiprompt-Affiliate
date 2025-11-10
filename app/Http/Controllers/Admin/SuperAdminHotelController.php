<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\HotelFacility;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SuperAdminHotelController extends Controller
{
    /**
     * List all hotels
     */
    public function index(Request $request)
    {
        $query = Hotel::with(['owner', 'roomTypes']);

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } else if ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Filter by city
        if ($request->has('city')) {
            $query->where('city', $request->city);
        }

        // Filter by rating
        if ($request->has('min_rating')) {
            $query->where('rating', '>=', $request->min_rating);
        }

        // Filter by owner
        if ($request->has('owner_id')) {
            $query->where('owner_id', $request->owner_id);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $query->orderBy($sortBy, $sortOrder);

        $hotels = $query->paginate($request->get('per_page', 20));

        // Add statistics for each hotel
        $hotels->getCollection()->transform(function($hotel) {
            $hotel->statistics = [
                'total_bookings' => $hotel->bookings()->count(),
                'total_revenue' => $hotel->bookings()
                    ->whereIn('status', ['confirmed', 'checked_in', 'completed'])
                    ->sum('total_amount'),
                'total_reviews' => $hotel->reviews()->count(),
                'average_rating' => round($hotel->rating, 2),
                'total_rooms' => $hotel->roomTypes()->sum('total_rooms'),
            ];

            return $hotel;
        });

        return response()->json([
            'success' => true,
            'hotels' => $hotels,
        ]);
    }

    /**
     * Get hotel details
     */
    public function show($id)
    {
        $hotel = Hotel::with([
            'owner',
            'facilities',
            'roomTypes',
            'bookings' => function($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            },
            'reviews' => function($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            },
        ])->findOrFail($id);

        $statistics = [
            'total_bookings' => $hotel->bookings()->count(),
            'confirmed_bookings' => $hotel->bookings()->where('status', 'confirmed')->count(),
            'completed_bookings' => $hotel->bookings()->where('status', 'completed')->count(),
            'cancelled_bookings' => $hotel->bookings()->where('status', 'cancelled')->count(),
            'total_revenue' => $hotel->bookings()
                ->whereIn('status', ['confirmed', 'checked_in', 'completed'])
                ->sum('total_amount'),
            'total_reviews' => $hotel->reviews()->count(),
            'approved_reviews' => $hotel->reviews()->where('is_approved', true)->count(),
            'average_rating' => round($hotel->rating, 2),
            'total_rooms' => $hotel->roomTypes()->sum('total_rooms'),
            'active_room_types' => $hotel->roomTypes()->where('is_active', true)->count(),
        ];

        return response()->json([
            'success' => true,
            'hotel' => $hotel,
            'statistics' => $statistics,
        ]);
    }

    /**
     * Create new hotel
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:hotels',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'star_rating' => 'nullable|integer|between:1,5',
            'type' => ['required', Rule::in(['hotel', 'resort', 'hostel', 'apartment', 'villa', 'guesthouse'])],
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i',
            'cancellation_policy' => 'nullable|string',
            'payment_policy' => 'nullable|string',
            'house_rules' => 'nullable|string',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'facilities' => 'nullable|array',
            'facilities.*' => 'exists:hotel_facilities,id',
            'owner_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'main_image' => 'nullable|image|max:5120',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|max:5120',
            'video_url' => 'nullable|url|max:255',
        ]);

        // Handle main image upload
        if ($request->hasFile('main_image')) {
            $validated['main_image'] = $request->file('main_image')
                ->store('hotels/images', 'public');
        }

        // Handle gallery images upload
        if ($request->hasFile('gallery_images')) {
            $galleryImages = [];

            foreach ($request->file('gallery_images') as $image) {
                $galleryImages[] = $image->store('hotels/gallery', 'public');
            }

            $validated['gallery_images'] = $galleryImages;
        }

        // Generate slug
        $validated['slug'] = Str::slug($validated['name']);

        // Create hotel
        $hotel = Hotel::create($validated);

        // Sync facilities
        if (isset($validated['facilities'])) {
            $hotel->facilities()->sync($validated['facilities']);
        }

        // Update owner's managed_hotel_id if owner is assigned
        if (isset($validated['owner_id'])) {
            User::where('id', $validated['owner_id'])
                ->update(['managed_hotel_id' => $hotel->id]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Hotel created successfully',
            'hotel' => $hotel->load(['owner', 'facilities']),
        ], 201);
    }

    /**
     * Update hotel
     */
    public function update(Request $request, $id)
    {
        $hotel = Hotel::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('hotels')->ignore($hotel->id)],
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'star_rating' => 'nullable|integer|between:1,5',
            'type' => ['required', Rule::in(['hotel', 'resort', 'hostel', 'apartment', 'villa', 'guesthouse'])],
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i',
            'cancellation_policy' => 'nullable|string',
            'payment_policy' => 'nullable|string',
            'house_rules' => 'nullable|string',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'facilities' => 'nullable|array',
            'facilities.*' => 'exists:hotel_facilities,id',
            'owner_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'main_image' => 'nullable|image|max:5120',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|max:5120',
            'video_url' => 'nullable|url|max:255',
        ]);

        // Handle main image upload
        if ($request->hasFile('main_image')) {
            // Delete old image
            if ($hotel->main_image) {
                Storage::disk('public')->delete($hotel->main_image);
            }

            $validated['main_image'] = $request->file('main_image')
                ->store('hotels/images', 'public');
        }

        // Handle gallery images upload
        if ($request->hasFile('gallery_images')) {
            $galleryImages = [];

            foreach ($request->file('gallery_images') as $image) {
                $galleryImages[] = $image->store('hotels/gallery', 'public');
            }

            // Merge with existing gallery images
            $existingGallery = $hotel->gallery_images ?? [];
            $validated['gallery_images'] = array_merge($existingGallery, $galleryImages);
        }

        // Update slug if name changed
        if ($validated['name'] !== $hotel->name) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Update hotel
        $hotel->update($validated);

        // Sync facilities
        if (isset($validated['facilities'])) {
            $hotel->facilities()->sync($validated['facilities']);
        }

        // Update owner's managed_hotel_id if owner changed
        if (isset($validated['owner_id']) && $validated['owner_id'] !== $hotel->owner_id) {
            // Remove old owner
            if ($hotel->owner_id) {
                User::where('id', $hotel->owner_id)
                    ->update(['managed_hotel_id' => null]);
            }

            // Add new owner
            User::where('id', $validated['owner_id'])
                ->update(['managed_hotel_id' => $hotel->id]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Hotel updated successfully',
            'hotel' => $hotel->load(['owner', 'facilities']),
        ]);
    }

    /**
     * Delete hotel
     */
    public function destroy($id)
    {
        $hotel = Hotel::findOrFail($id);

        // Check if there are active bookings
        $activeBookings = $hotel->bookings()
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->count();

        if ($activeBookings > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete hotel with active bookings. Please complete or cancel all bookings first.',
            ], 422);
        }

        // Delete images
        if ($hotel->main_image) {
            Storage::disk('public')->delete($hotel->main_image);
        }

        if ($hotel->gallery_images) {
            foreach ($hotel->gallery_images as $image) {
                Storage::disk('public')->delete($image);
            }
        }

        // Remove from owner
        if ($hotel->owner_id) {
            User::where('id', $hotel->owner_id)
                ->update(['managed_hotel_id' => null]);
        }

        // Delete related data
        $hotel->facilities()->detach();
        $hotel->roomTypes()->delete();
        $hotel->specialOffers()->delete();

        // Delete hotel
        $hotel->delete();

        return response()->json([
            'success' => true,
            'message' => 'Hotel deleted successfully',
        ]);
    }

    /**
     * Toggle hotel active status
     */
    public function toggleActive($id)
    {
        $hotel = Hotel::findOrFail($id);

        $hotel->update(['is_active' => !$hotel->is_active]);

        return response()->json([
            'success' => true,
            'message' => $hotel->is_active ? 'Hotel activated successfully' : 'Hotel deactivated successfully',
            'hotel' => $hotel,
        ]);
    }

    /**
     * Toggle hotel featured status
     */
    public function toggleFeatured($id)
    {
        $hotel = Hotel::findOrFail($id);

        $hotel->update(['is_featured' => !$hotel->is_featured]);

        return response()->json([
            'success' => true,
            'message' => $hotel->is_featured ? 'Hotel marked as featured' : 'Hotel unmarked as featured',
            'hotel' => $hotel,
        ]);
    }

    /**
     * Remove gallery image
     */
    public function removeGalleryImage(Request $request, $id)
    {
        $hotel = Hotel::findOrFail($id);

        $validated = $request->validate([
            'image_path' => 'required|string',
        ]);

        $galleryImages = $hotel->gallery_images ?? [];

        // Remove image from array
        $galleryImages = array_filter($galleryImages, function($image) use ($validated) {
            return $image !== $validated['image_path'];
        });

        // Delete from storage
        Storage::disk('public')->delete($validated['image_path']);

        // Update hotel
        $hotel->update(['gallery_images' => array_values($galleryImages)]);

        return response()->json([
            'success' => true,
            'message' => 'Image removed successfully',
        ]);
    }

    /**
     * Get all available owners for assignment
     */
    public function getAvailableOwners()
    {
        $owners = User::where('is_hotel_admin', true)
            ->select('id', 'name', 'email', 'managed_hotel_id')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'owners' => $owners,
        ]);
    }

    /**
     * Get all cities
     */
    public function getCities()
    {
        $cities = Hotel::select('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        return response()->json([
            'success' => true,
            'cities' => $cities,
        ]);
    }

    /**
     * Get hotel statistics
     */
    public function statistics()
    {
        $totalHotels = Hotel::count();
        $activeHotels = Hotel::where('is_active', true)->count();
        $inactiveHotels = Hotel::where('is_active', false)->count();
        $featuredHotels = Hotel::where('is_featured', true)->count();

        $hotelsByType = Hotel::select('type')
            ->selectRaw('count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type');

        $totalRooms = Hotel::join('room_types', 'hotels.id', '=', 'room_types.hotel_id')
            ->sum('room_types.total_rooms');

        $totalBookings = Hotel::join('hotel_bookings', 'hotels.id', '=', 'hotel_bookings.hotel_id')
            ->count();

        $totalRevenue = Hotel::join('hotel_bookings', 'hotels.id', '=', 'hotel_bookings.hotel_id')
            ->whereIn('hotel_bookings.status', ['confirmed', 'checked_in', 'completed'])
            ->sum('hotel_bookings.total_amount');

        $averageRating = Hotel::where('review_count', '>', 0)
            ->avg('rating');

        return response()->json([
            'success' => true,
            'statistics' => [
                'total_hotels' => $totalHotels,
                'active_hotels' => $activeHotels,
                'inactive_hotels' => $inactiveHotels,
                'featured_hotels' => $featuredHotels,
                'hotels_by_type' => $hotelsByType,
                'total_rooms' => $totalRooms,
                'total_bookings' => $totalBookings,
                'total_revenue' => $totalRevenue,
                'average_rating' => round($averageRating, 2),
            ],
        ]);
    }
}
