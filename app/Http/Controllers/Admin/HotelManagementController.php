<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\HotelFacility;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HotelManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    /**
     * Display hotels list
     */
    public function index(Request $request)
    {
        $query = Hotel::with(['owner', 'roomTypes']);

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('city', 'LIKE', "%{$search}%")
                    ->orWhere('address', 'LIKE', "%{$search}%");
            });
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $hotels = $query->orderBy('created_at', 'DESC')->paginate(20);

        $stats = [
            'total' => Hotel::count(),
            'active' => Hotel::where('is_active', true)->count(),
            'featured' => Hotel::where('is_featured', true)->count(),
            'total_rooms' => \App\Models\RoomType::sum('total_rooms'),
        ];

        return view('admin.hotels.index', compact('hotels', 'stats'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $facilities = HotelFacility::active()->ordered()->get();
        $owners = User::role('hotel_owner')->get(); // Assuming role-based users

        return view('admin.hotels.create', compact('facilities', 'owners'));
    }

    /**
     * Store new hotel
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:hotels,slug',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'nullable|string',
            'country' => 'required|string',
            'postal_code' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'website' => 'nullable|url',
            'main_image' => 'nullable|image|max:5120',
            'gallery_images.*' => 'nullable|image|max:5120',
            'type' => 'required|in:hotel,resort,hostel,apartment,villa,guesthouse',
            'star_rating' => 'nullable|integer|min:1|max:5',
            'check_in_time' => 'nullable',
            'check_out_time' => 'nullable',
            'cancellation_policy' => 'nullable|string',
            'house_rules' => 'nullable|string',
            'payment_policy' => 'nullable|string',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'owner_id' => 'nullable|exists:users,id',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'facilities' => 'nullable|array',
            'facilities.*' => 'exists:hotel_facilities,id',
        ]);

        try {
            // Handle main image upload
            if ($request->hasFile('main_image')) {
                $validated['main_image'] = $request->file('main_image')->store('hotels', 'public');
            }

            // Handle gallery images
            $galleryImages = [];
            if ($request->hasFile('gallery_images')) {
                foreach ($request->file('gallery_images') as $image) {
                    $galleryImages[] = $image->store('hotels/gallery', 'public');
                }
            }
            $validated['gallery_images'] = $galleryImages;

            // Generate slug if not provided
            if (empty($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['name']);
            }

            // Create hotel
            $hotel = Hotel::create($validated);

            // Attach facilities
            if ($request->has('facilities')) {
                $hotel->facilities()->attach($request->facilities);
            }

            return redirect()
                ->route('admin.hotels.show', $hotel->id)
                ->with('success', 'Hotel created successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create hotel: ' . $e->getMessage());
        }
    }

    /**
     * Show hotel details
     */
    public function show($id)
    {
        $hotel = Hotel::with(['roomTypes', 'facilities', 'bookings', 'reviews', 'owner'])
            ->findOrFail($id);

        $stats = [
            'total_bookings' => $hotel->bookings()->count(),
            'confirmed_bookings' => $hotel->bookings()->confirmed()->count(),
            'total_revenue' => $hotel->bookings()->where('payment_status', 'paid')->sum('total_amount'),
            'total_rooms' => $hotel->roomTypes()->sum('total_rooms'),
            'average_rating' => round($hotel->rating, 2),
            'total_reviews' => $hotel->review_count,
        ];

        return view('admin.hotels.show', compact('hotel', 'stats'));
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $hotel = Hotel::with('facilities')->findOrFail($id);
        $facilities = HotelFacility::active()->ordered()->get();
        $owners = User::role('hotel_owner')->get();

        return view('admin.hotels.edit', compact('hotel', 'facilities', 'owners'));
    }

    /**
     * Update hotel
     */
    public function update(Request $request, $id)
    {
        $hotel = Hotel::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:hotels,slug,' . $id,
            'description' => 'nullable|string',
            'short_description' => 'nullable|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'nullable|string',
            'country' => 'required|string',
            'postal_code' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'website' => 'nullable|url',
            'main_image' => 'nullable|image|max:5120',
            'type' => 'required|in:hotel,resort,hostel,apartment,villa,guesthouse',
            'star_rating' => 'nullable|integer|min:1|max:5',
            'check_in_time' => 'nullable',
            'check_out_time' => 'nullable',
            'cancellation_policy' => 'nullable|string',
            'house_rules' => 'nullable|string',
            'payment_policy' => 'nullable|string',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'owner_id' => 'nullable|exists:users,id',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'facilities' => 'nullable|array',
        ]);

        try {
            // Handle main image upload
            if ($request->hasFile('main_image')) {
                // Delete old image
                if ($hotel->main_image) {
                    Storage::disk('public')->delete($hotel->main_image);
                }
                $validated['main_image'] = $request->file('main_image')->store('hotels', 'public');
            }

            // Update hotel
            $hotel->update($validated);

            // Sync facilities
            if ($request->has('facilities')) {
                $hotel->facilities()->sync($request->facilities);
            }

            return redirect()
                ->route('admin.hotels.show', $hotel->id)
                ->with('success', 'Hotel updated successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update hotel: ' . $e->getMessage());
        }
    }

    /**
     * Delete hotel
     */
    public function destroy($id)
    {
        $hotel = Hotel::findOrFail($id);

        // Check if hotel has bookings
        if ($hotel->bookings()->whereIn('status', ['pending', 'confirmed', 'checked_in'])->exists()) {
            return back()->with('error', 'Cannot delete hotel with active bookings.');
        }

        try {
            // Delete images
            if ($hotel->main_image) {
                Storage::disk('public')->delete($hotel->main_image);
            }

            if ($hotel->gallery_images) {
                foreach ($hotel->gallery_images as $image) {
                    Storage::disk('public')->delete($image);
                }
            }

            $hotel->delete();

            return redirect()
                ->route('admin.hotels.index')
                ->with('success', 'Hotel deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete hotel: ' . $e->getMessage());
        }
    }

    /**
     * Toggle hotel status
     */
    public function toggleStatus($id)
    {
        $hotel = Hotel::findOrFail($id);
        $hotel->update(['is_active' => !$hotel->is_active]);

        return back()->with('success', 'Hotel status updated.');
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured($id)
    {
        $hotel = Hotel::findOrFail($id);
        $hotel->update(['is_featured' => !$hotel->is_featured]);

        return back()->with('success', 'Featured status updated.');
    }
}
