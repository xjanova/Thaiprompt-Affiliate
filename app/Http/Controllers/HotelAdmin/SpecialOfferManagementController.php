<?php

namespace App\Http\Controllers\HotelAdmin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\HotelSpecialOffer;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SpecialOfferManagementController extends Controller
{
    /**
     * List all special offers for the managed hotel
     */
    public function index()
    {
        $user = Auth::user();

        $offers = HotelSpecialOffer::where('hotel_id', $user->managed_hotel_id)
            ->with(['hotel'])
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'offers' => $offers,
        ]);
    }

    /**
     * Get special offer details
     */
    public function show($id)
    {
        $user = Auth::user();

        $offer = HotelSpecialOffer::where('hotel_id', $user->managed_hotel_id)
            ->with(['hotel'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'offer' => $offer,
        ]);
    }

    /**
     * Create new special offer
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'code' => [
                'nullable',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('hotel_special_offers')->where('hotel_id', $user->managed_hotel_id),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => ['required', Rule::in(['percentage', 'fixed', 'free_night'])],
            'discount_value' => 'required_if:type,percentage,fixed|nullable|numeric|min:0',
            'free_nights' => 'required_if:type,free_night|nullable|integer|min:1',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after:valid_from',
            'applicable_days' => 'nullable|array',
            'applicable_days.*' => Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']),
            'min_nights' => 'nullable|integer|min:1',
            'min_amount' => 'nullable|numeric|min:0',
            'max_usage' => 'nullable|integer|min:1',
            'room_type_ids' => 'nullable|array',
            'room_type_ids.*' => 'exists:room_types,id',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'banner_image' => 'nullable|image|max:5120',
        ]);

        // Generate code if not provided
        if (empty($validated['code'])) {
            $validated['code'] = strtoupper(Str::random(8));
        } else {
            $validated['code'] = strtoupper($validated['code']);
        }

        // Validate percentage
        if ($validated['type'] === 'percentage' && $validated['discount_value'] > 100) {
            return response()->json([
                'success' => false,
                'message' => 'Percentage discount cannot exceed 100%',
            ], 422);
        }

        // Validate room types belong to hotel
        if (isset($validated['room_type_ids'])) {
            $roomTypes = RoomType::where('hotel_id', $user->managed_hotel_id)
                ->whereIn('id', $validated['room_type_ids'])
                ->pluck('id')
                ->toArray();

            if (count($roomTypes) !== count($validated['room_type_ids'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some room types do not belong to your hotel',
                ], 422);
            }
        }

        // Handle banner image upload
        if ($request->hasFile('banner_image')) {
            $validated['banner_image'] = $request->file('banner_image')
                ->store('offers/banners', 'public');
        }

        $validated['hotel_id'] = $user->managed_hotel_id;
        $validated['used_count'] = 0;

        // Create offer
        $offer = HotelSpecialOffer::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Special offer created successfully',
            'offer' => $offer,
        ], 201);
    }

    /**
     * Update special offer
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        $offer = HotelSpecialOffer::where('hotel_id', $user->managed_hotel_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'code' => [
                'nullable',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('hotel_special_offers')->where('hotel_id', $user->managed_hotel_id)->ignore($offer->id),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => ['required', Rule::in(['percentage', 'fixed', 'free_night'])],
            'discount_value' => 'required_if:type,percentage,fixed|nullable|numeric|min:0',
            'free_nights' => 'required_if:type,free_night|nullable|integer|min:1',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after:valid_from',
            'applicable_days' => 'nullable|array',
            'applicable_days.*' => Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']),
            'min_nights' => 'nullable|integer|min:1',
            'min_amount' => 'nullable|numeric|min:0',
            'max_usage' => 'nullable|integer|min:1',
            'room_type_ids' => 'nullable|array',
            'room_type_ids.*' => 'exists:room_types,id',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'banner_image' => 'nullable|image|max:5120',
        ]);

        // Convert code to uppercase
        if (isset($validated['code'])) {
            $validated['code'] = strtoupper($validated['code']);
        }

        // Validate percentage
        if ($validated['type'] === 'percentage' && $validated['discount_value'] > 100) {
            return response()->json([
                'success' => false,
                'message' => 'Percentage discount cannot exceed 100%',
            ], 422);
        }

        // Validate room types belong to hotel
        if (isset($validated['room_type_ids'])) {
            $roomTypes = RoomType::where('hotel_id', $user->managed_hotel_id)
                ->whereIn('id', $validated['room_type_ids'])
                ->pluck('id')
                ->toArray();

            if (count($roomTypes) !== count($validated['room_type_ids'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some room types do not belong to your hotel',
                ], 422);
            }
        }

        // Handle banner image upload
        if ($request->hasFile('banner_image')) {
            // Delete old image
            if ($offer->banner_image) {
                Storage::disk('public')->delete($offer->banner_image);
            }

            $validated['banner_image'] = $request->file('banner_image')
                ->store('offers/banners', 'public');
        }

        // Update offer
        $offer->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Special offer updated successfully',
            'offer' => $offer,
        ]);
    }

    /**
     * Delete special offer
     */
    public function destroy($id)
    {
        $user = Auth::user();

        $offer = HotelSpecialOffer::where('hotel_id', $user->managed_hotel_id)
            ->findOrFail($id);

        // Check if offer has been used
        if ($offer->used_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete offer that has been used. You can deactivate it instead.',
            ], 422);
        }

        // Delete banner image
        if ($offer->banner_image) {
            Storage::disk('public')->delete($offer->banner_image);
        }

        // Delete offer
        $offer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Special offer deleted successfully',
        ]);
    }

    /**
     * Toggle offer active status
     */
    public function toggleActive($id)
    {
        $user = Auth::user();

        $offer = HotelSpecialOffer::where('hotel_id', $user->managed_hotel_id)
            ->findOrFail($id);

        $offer->update(['is_active' => !$offer->is_active]);

        return response()->json([
            'success' => true,
            'message' => $offer->is_active ? 'Offer activated successfully' : 'Offer deactivated successfully',
            'offer' => $offer,
        ]);
    }

    /**
     * Get offer statistics
     */
    public function statistics($id)
    {
        $user = Auth::user();

        $offer = HotelSpecialOffer::where('hotel_id', $user->managed_hotel_id)
            ->findOrFail($id);

        $usagePercentage = $offer->max_usage
            ? ($offer->used_count / $offer->max_usage) * 100
            : 0;

        $isValid = $offer->isValid();
        $daysRemaining = now()->diffInDays($offer->valid_until, false);

        return response()->json([
            'success' => true,
            'statistics' => [
                'used_count' => $offer->used_count,
                'max_usage' => $offer->max_usage,
                'usage_percentage' => round($usagePercentage, 2),
                'remaining_usage' => $offer->max_usage ? ($offer->max_usage - $offer->used_count) : null,
                'is_valid' => $isValid,
                'days_remaining' => max(0, $daysRemaining),
                'is_active' => $offer->is_active,
            ],
        ]);
    }

    /**
     * Get all room types for offer assignment
     */
    public function getRoomTypes()
    {
        $user = Auth::user();

        $roomTypes = RoomType::where('hotel_id', $user->managed_hotel_id)
            ->where('is_active', true)
            ->select('id', 'name', 'base_price', 'max_occupancy')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'room_types' => $roomTypes,
        ]);
    }
}
