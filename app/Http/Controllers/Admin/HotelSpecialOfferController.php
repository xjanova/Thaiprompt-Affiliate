<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HotelSpecialOffer;
use App\Models\Hotel;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HotelSpecialOfferController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    /**
     * Display a listing of the special offers
     */
    public function index(Request $request)
    {
        $query = HotelSpecialOffer::with('hotel');

        // Filter by hotel
        if ($request->has('hotel_id')) {
            $query->where('hotel_id', $request->hotel_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $offers = $query->orderBy('created_at', 'DESC')->paginate(20);
        $hotels = Hotel::active()->orderBy('name')->get();

        $stats = [
            'total' => HotelSpecialOffer::count(),
            'active' => HotelSpecialOffer::where('is_active', true)->count(),
            'expired' => HotelSpecialOffer::where('valid_to', '<', now())->count(),
        ];

        return view('admin.hotels.special-offers.index', compact('offers', 'hotels', 'stats'));
    }

    /**
     * Show the form for creating a new special offer
     */
    public function create()
    {
        $hotels = Hotel::active()->orderBy('name')->get();

        return view('admin.hotels.special-offers.create', compact('hotels'));
    }

    /**
     * Store a newly created special offer
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'hotel_id' => 'required|exists:hotels,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:hotel_special_offers,code',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percentage,fixed,free_nights',
            'discount_value' => 'required_if:discount_type,percentage,fixed|numeric|min:0',
            'free_nights' => 'required_if:discount_type,free_nights|integer|min:1',
            'valid_from' => 'required|date',
            'valid_to' => 'required|date|after:valid_from',
            'applicable_days' => 'nullable|array',
            'min_nights' => 'nullable|integer|min:1',
            'min_amount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'max_uses_per_user' => 'nullable|integer|min:1',
            'room_type_ids' => 'nullable|array',
            'room_type_ids.*' => 'exists:room_types,id',
            'banner_image' => 'nullable|image|max:5120',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        try {
            // Handle banner image upload
            if ($request->hasFile('banner_image')) {
                $validated['banner_image'] = $request->file('banner_image')->store('offers', 'public');
            }

            // Convert arrays to JSON
            $validated['applicable_days'] = $validated['applicable_days'] ?? [];
            $validated['room_type_ids'] = $validated['room_type_ids'] ?? [];

            $validated['is_active'] = $request->has('is_active');
            $validated['is_featured'] = $request->has('is_featured');

            HotelSpecialOffer::create($validated);

            return redirect()
                ->route('admin.hotels.special-offers.index')
                ->with('success', 'Special offer created successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create special offer: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified special offer
     */
    public function show($id)
    {
        $offer = HotelSpecialOffer::with('hotel')->findOrFail($id);

        $stats = [
            'total_uses' => $offer->current_uses ?? 0,
            'total_savings' => 0, // You can calculate this based on bookings
        ];

        return view('admin.hotels.special-offers.show', compact('offer', 'stats'));
    }

    /**
     * Show the form for editing the specified special offer
     */
    public function edit($id)
    {
        $offer = HotelSpecialOffer::findOrFail($id);
        $hotels = Hotel::active()->orderBy('name')->get();
        $roomTypes = RoomType::where('hotel_id', $offer->hotel_id)->get();

        return view('admin.hotels.special-offers.edit', compact('offer', 'hotels', 'roomTypes'));
    }

    /**
     * Update the specified special offer
     */
    public function update(Request $request, $id)
    {
        $offer = HotelSpecialOffer::findOrFail($id);

        $validated = $request->validate([
            'hotel_id' => 'required|exists:hotels,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:hotel_special_offers,code,' . $id,
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percentage,fixed,free_nights',
            'discount_value' => 'required_if:discount_type,percentage,fixed|numeric|min:0',
            'free_nights' => 'required_if:discount_type,free_nights|integer|min:1',
            'valid_from' => 'required|date',
            'valid_to' => 'required|date|after:valid_from',
            'applicable_days' => 'nullable|array',
            'min_nights' => 'nullable|integer|min:1',
            'min_amount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'max_uses_per_user' => 'nullable|integer|min:1',
            'room_type_ids' => 'nullable|array',
            'room_type_ids.*' => 'exists:room_types,id',
            'banner_image' => 'nullable|image|max:5120',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        try {
            // Handle banner image upload
            if ($request->hasFile('banner_image')) {
                // Delete old image
                if ($offer->banner_image) {
                    Storage::disk('public')->delete($offer->banner_image);
                }
                $validated['banner_image'] = $request->file('banner_image')->store('offers', 'public');
            }

            // Convert arrays to JSON
            $validated['applicable_days'] = $validated['applicable_days'] ?? [];
            $validated['room_type_ids'] = $validated['room_type_ids'] ?? [];

            $validated['is_active'] = $request->has('is_active');
            $validated['is_featured'] = $request->has('is_featured');

            $offer->update($validated);

            return redirect()
                ->route('admin.hotels.special-offers.show', $offer->id)
                ->with('success', 'Special offer updated successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update special offer: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified special offer
     */
    public function destroy($id)
    {
        $offer = HotelSpecialOffer::findOrFail($id);

        try {
            // Delete banner image
            if ($offer->banner_image) {
                Storage::disk('public')->delete($offer->banner_image);
            }

            $offer->delete();

            return redirect()
                ->route('admin.hotels.special-offers.index')
                ->with('success', 'Special offer deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete special offer: ' . $e->getMessage());
        }
    }

    /**
     * Toggle active status
     */
    public function toggleStatus($id)
    {
        $offer = HotelSpecialOffer::findOrFail($id);
        $offer->update(['is_active' => !$offer->is_active]);

        return back()->with('success', 'Offer status updated.');
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured($id)
    {
        $offer = HotelSpecialOffer::findOrFail($id);
        $offer->update(['is_featured' => !$offer->is_featured]);

        return back()->with('success', 'Featured status updated.');
    }
}
