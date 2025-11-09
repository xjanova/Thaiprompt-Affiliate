<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HotelReview;
use App\Models\Hotel;
use Illuminate\Http\Request;

class HotelReviewManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    /**
     * Display a listing of the reviews
     */
    public function index(Request $request)
    {
        $query = HotelReview::with(['hotel', 'user']);

        // Filter by hotel
        if ($request->has('hotel_id')) {
            $query->where('hotel_id', $request->hotel_id);
        }

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'approved') {
                $query->where('is_approved', true);
            } elseif ($request->status === 'pending') {
                $query->where('is_approved', false);
            }
        }

        // Filter by rating
        if ($request->has('rating')) {
            $query->where('overall_rating', $request->rating);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('review_text', 'LIKE', "%{$search}%")
                    ->orWhere('title', 'LIKE', "%{$search}%");
            });
        }

        $reviews = $query->orderBy('created_at', 'DESC')->paginate(20);
        $hotels = Hotel::active()->orderBy('name')->get();

        $stats = [
            'total' => HotelReview::count(),
            'approved' => HotelReview::where('is_approved', true)->count(),
            'pending' => HotelReview::where('is_approved', false)->count(),
            'featured' => HotelReview::where('is_featured', true)->count(),
            'average_rating' => round(HotelReview::where('is_approved', true)->avg('overall_rating'), 2),
        ];

        return view('admin.hotels.reviews.index', compact('reviews', 'hotels', 'stats'));
    }

    /**
     * Display the specified review
     */
    public function show($id)
    {
        $review = HotelReview::with(['hotel', 'user'])->findOrFail($id);

        return view('admin.hotels.reviews.show', compact('review'));
    }

    /**
     * Approve a review
     */
    public function approve($id)
    {
        $review = HotelReview::findOrFail($id);
        $review->update(['is_approved' => true]);

        // Update hotel rating
        $review->hotel->updateRating();

        return back()->with('success', 'Review approved successfully!');
    }

    /**
     * Reject a review
     */
    public function reject($id)
    {
        $review = HotelReview::findOrFail($id);
        $review->update(['is_approved' => false]);

        // Update hotel rating
        $review->hotel->updateRating();

        return back()->with('success', 'Review rejected successfully!');
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured($id)
    {
        $review = HotelReview::findOrFail($id);
        $review->update(['is_featured' => !$review->is_featured]);

        return back()->with('success', 'Featured status updated successfully!');
    }

    /**
     * Respond to a review
     */
    public function respond(Request $request, $id)
    {
        $review = HotelReview::findOrFail($id);

        $validated = $request->validate([
            'response' => 'required|string',
        ]);

        $review->update([
            'management_response' => $validated['response'],
            'responded_at' => now(),
        ]);

        return back()->with('success', 'Response added successfully!');
    }

    /**
     * Remove the specified review
     */
    public function destroy($id)
    {
        $review = HotelReview::findOrFail($id);
        $hotel = $review->hotel;

        try {
            $review->delete();

            // Update hotel rating
            $hotel->updateRating();

            return back()->with('success', 'Review deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete review: ' . $e->getMessage());
        }
    }
}
