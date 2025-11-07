<?php

namespace App\Http\Controllers;

use App\Models\HotelReview;
use App\Models\HotelBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HotelReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show']);
    }

    /**
     * Display reviews for a hotel
     */
    public function index($hotelSlug)
    {
        $hotel = \App\Models\Hotel::where('slug', $hotelSlug)->firstOrFail();

        $reviews = $hotel->reviews()
            ->approved()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $stats = [
            'average_rating' => round($hotel->rating, 1),
            'total_reviews' => $hotel->review_count,
            'rating_distribution' => $this->getRatingDistribution($hotel),
            'category_ratings' => $this->getCategoryRatings($hotel),
        ];

        return view('hotels.reviews.index', compact('hotel', 'reviews', 'stats'));
    }

    /**
     * Show create review form
     */
    public function create($bookingNumber)
    {
        $booking = HotelBooking::where('booking_number', $bookingNumber)
            ->where('user_id', Auth::id())
            ->whereIn('status', ['checked_out', 'completed'])
            ->with(['hotel', 'roomType'])
            ->firstOrFail();

        // Check if already reviewed
        if ($booking->review) {
            return redirect()
                ->route('hotels.reviews.show', $booking->review->id)
                ->with('info', 'You have already reviewed this booking.');
        }

        return view('hotels.reviews.create', compact('booking'));
    }

    /**
     * Store review
     */
    public function store(Request $request, $bookingNumber)
    {
        $booking = HotelBooking::where('booking_number', $bookingNumber)
            ->where('user_id', Auth::id())
            ->whereIn('status', ['checked_out', 'completed'])
            ->firstOrFail();

        // Check if already reviewed
        if ($booking->review) {
            return back()->with('error', 'You have already reviewed this booking.');
        }

        $request->validate([
            'overall_rating' => 'required|integer|min:1|max:5',
            'cleanliness_rating' => 'nullable|integer|min:1|max:5',
            'staff_rating' => 'nullable|integer|min:1|max:5',
            'facilities_rating' => 'nullable|integer|min:1|max:5',
            'location_rating' => 'nullable|integer|min:1|max:5',
            'value_rating' => 'nullable|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'required|string',
            'pros' => 'nullable|string',
            'cons' => 'nullable|string',
            'guest_type' => 'nullable|string',
            'images.*' => 'nullable|image|max:5120', // 5MB max
        ]);

        try {
            // Handle image uploads
            $images = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('reviews', 'public');
                    $images[] = $path;
                }
            }

            // Create review
            $review = HotelReview::create([
                'hotel_id' => $booking->hotel_id,
                'booking_id' => $booking->id,
                'user_id' => Auth::id(),
                'overall_rating' => $request->overall_rating,
                'cleanliness_rating' => $request->cleanliness_rating,
                'staff_rating' => $request->staff_rating,
                'facilities_rating' => $request->facilities_rating,
                'location_rating' => $request->location_rating,
                'value_rating' => $request->value_rating,
                'title' => $request->title,
                'comment' => $request->comment,
                'pros' => $request->pros,
                'cons' => $request->cons,
                'guest_name' => Auth::user()->name,
                'guest_type' => $request->guest_type,
                'stayed_date' => $booking->check_in_date,
                'images' => $images,
                'is_verified' => true, // Verified because it's from actual booking
                'is_approved' => true, // Auto-approve (can be changed to require admin approval)
            ]);

            return redirect()
                ->route('hotels.show', $booking->hotel->slug)
                ->with('success', 'Thank you for your review!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to submit review: ' . $e->getMessage());
        }
    }

    /**
     * Show review
     */
    public function show($id)
    {
        $review = HotelReview::with(['hotel', 'user', 'booking'])
            ->findOrFail($id);

        return view('hotels.reviews.show', compact('review'));
    }

    /**
     * Edit review
     */
    public function edit($id)
    {
        $review = HotelReview::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return view('hotels.reviews.edit', compact('review'));
    }

    /**
     * Update review
     */
    public function update(Request $request, $id)
    {
        $review = HotelReview::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $request->validate([
            'overall_rating' => 'required|integer|min:1|max:5',
            'cleanliness_rating' => 'nullable|integer|min:1|max:5',
            'staff_rating' => 'nullable|integer|min:1|max:5',
            'facilities_rating' => 'nullable|integer|min:1|max:5',
            'location_rating' => 'nullable|integer|min:1|max:5',
            'value_rating' => 'nullable|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'required|string',
            'pros' => 'nullable|string',
            'cons' => 'nullable|string',
        ]);

        $review->update($request->all());

        return redirect()
            ->route('hotels.reviews.show', $review->id)
            ->with('success', 'Review updated successfully.');
    }

    /**
     * Delete review
     */
    public function destroy($id)
    {
        $review = HotelReview::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $hotelSlug = $review->hotel->slug;
        $review->delete();

        return redirect()
            ->route('hotels.show', $hotelSlug)
            ->with('success', 'Review deleted successfully.');
    }

    /**
     * Mark review as helpful
     */
    public function markHelpful($id)
    {
        $review = HotelReview::findOrFail($id);
        $review->markAsHelpful(Auth::id());

        return back()->with('success', 'Thank you for your feedback!');
    }

    /**
     * Mark review as not helpful
     */
    public function markNotHelpful($id)
    {
        $review = HotelReview::findOrFail($id);
        $review->markAsNotHelpful(Auth::id());

        return back()->with('success', 'Thank you for your feedback!');
    }

    /**
     * Get rating distribution for hotel
     */
    protected function getRatingDistribution($hotel)
    {
        $distribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $count = $hotel->reviews()
                ->approved()
                ->where('overall_rating', $i)
                ->count();

            $percentage = $hotel->review_count > 0
                ? round(($count / $hotel->review_count) * 100, 1)
                : 0;

            $distribution[$i] = [
                'count' => $count,
                'percentage' => $percentage,
            ];
        }

        return $distribution;
    }

    /**
     * Get category ratings for hotel
     */
    protected function getCategoryRatings($hotel)
    {
        $reviews = $hotel->reviews()->approved()->get();

        return [
            'cleanliness' => round($reviews->avg('cleanliness_rating'), 1),
            'staff' => round($reviews->avg('staff_rating'), 1),
            'facilities' => round($reviews->avg('facilities_rating'), 1),
            'location' => round($reviews->avg('location_rating'), 1),
            'value' => round($reviews->avg('value_rating'), 1),
        ];
    }
}
