<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HotelFacility;
use Illuminate\Http\Request;

class HotelFacilityController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    /**
     * Display a listing of the facilities
     */
    public function index()
    {
        $facilities = HotelFacility::ordered()->paginate(50);

        return view('admin.hotels.facilities.index', compact('facilities'));
    }

    /**
     * Store a newly created facility
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? HotelFacility::max('sort_order') + 1;

        try {
            HotelFacility::create($validated);

            return back()->with('success', 'Facility created successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create facility: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified facility
     */
    public function update(Request $request, $id)
    {
        $facility = HotelFacility::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['is_active'] = $request->has('is_active');

        try {
            $facility->update($validated);

            return back()->with('success', 'Facility updated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update facility: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified facility
     */
    public function destroy($id)
    {
        $facility = HotelFacility::findOrFail($id);

        // Check if facility is in use
        if ($facility->hotels()->count() > 0) {
            return back()->with('error', 'Cannot delete facility that is in use by hotels.');
        }

        try {
            $facility->delete();

            return back()->with('success', 'Facility deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete facility: ' . $e->getMessage());
        }
    }

    /**
     * Reorder facilities
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|integer|exists:hotel_facilities,id',
        ]);

        try {
            foreach ($validated['order'] as $index => $facilityId) {
                HotelFacility::where('id', $facilityId)->update(['sort_order' => $index + 1]);
            }

            return response()->json(['success' => true, 'message' => 'Facilities reordered successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
