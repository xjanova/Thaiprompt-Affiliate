<?php

namespace App\Http\Controllers\HotelAdmin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\HotelFacility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class HotelManagementController extends Controller
{
    /**
     * Display hotel details for editing
     */
    public function edit()
    {
        $user = Auth::user();

        // Get the managed hotel
        $hotel = Hotel::with(['facilities', 'owner'])
            ->findOrFail($user->managed_hotel_id);

        // Get all available facilities
        $allFacilities = HotelFacility::where('is_active', true)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category');

        return response()->json([
            'success' => true,
            'hotel' => $hotel,
            'facilities' => $allFacilities,
        ]);
    }

    /**
     * Update hotel details
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $hotel = Hotel::findOrFail($user->managed_hotel_id);

        // Validate request
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
            'facilities' => 'nullable|array',
            'facilities.*' => 'exists:hotel_facilities,id',
            'is_active' => 'boolean',
            'main_image' => 'nullable|image|max:5120', // 5MB max
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

        // Generate slug if name changed
        if ($validated['name'] !== $hotel->name) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Update hotel
        $hotel->update($validated);

        // Sync facilities
        if (isset($validated['facilities'])) {
            $hotel->facilities()->sync($validated['facilities']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Hotel details updated successfully',
            'hotel' => $hotel->load('facilities'),
        ]);
    }

    /**
     * Remove image from gallery
     */
    public function removeGalleryImage(Request $request)
    {
        $user = Auth::user();
        $hotel = Hotel::findOrFail($user->managed_hotel_id);

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
     * Get hotel statistics
     */
    public function statistics()
    {
        $user = Auth::user();
        $hotel = Hotel::with(['bookings', 'reviews', 'roomTypes'])
            ->findOrFail($user->managed_hotel_id);

        $totalBookings = $hotel->bookings()->count();
        $confirmedBookings = $hotel->bookings()->where('status', 'confirmed')->count();
        $completedBookings = $hotel->bookings()->where('status', 'completed')->count();
        $totalRevenue = $hotel->bookings()
            ->whereIn('status', ['confirmed', 'checked_in', 'completed'])
            ->sum('total_amount');

        $totalReviews = $hotel->reviews()->where('is_approved', true)->count();
        $averageRating = $hotel->reviews()->where('is_approved', true)->avg('overall_rating') ?? 0;

        $totalRooms = $hotel->roomTypes()->sum('total_rooms');
        $activeRoomTypes = $hotel->roomTypes()->where('is_active', true)->count();

        return response()->json([
            'success' => true,
            'statistics' => [
                'total_bookings' => $totalBookings,
                'confirmed_bookings' => $confirmedBookings,
                'completed_bookings' => $completedBookings,
                'total_revenue' => $totalRevenue,
                'total_reviews' => $totalReviews,
                'average_rating' => round($averageRating, 2),
                'total_rooms' => $totalRooms,
                'active_room_types' => $activeRoomTypes,
                'view_count' => $hotel->view_count,
                'booking_count' => $hotel->booking_count,
            ],
        ]);
    }
}
