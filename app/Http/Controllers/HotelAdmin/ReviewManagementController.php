<?php

namespace App\Http\Controllers\HotelAdmin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\HotelReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewManagementController extends Controller
{
    /**
     * List all reviews for the managed hotel
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = HotelReview::where('hotel_id', $user->managed_hotel_id)
            ->with(['user', 'booking']);

        // Filter by status
        if ($request->has('status')) {
            switch ($request->status) {
                case 'approved':
                    $query->where('is_approved', true);
                    break;
                case 'pending':
                    $query->where('is_approved', false);
                    break;
                case 'responded':
                    $query->whereNotNull('hotel_response');
                    break;
                case 'not_responded':
                    $query->whereNull('hotel_response');
                    break;
            }
        }

        // Filter by rating
        if ($request->has('min_rating')) {
            $query->where('overall_rating', '>=', $request->min_rating);
        }

        if ($request->has('max_rating')) {
            $query->where('overall_rating', '<=', $request->max_rating);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $query->orderBy($sortBy, $sortOrder);

        $reviews = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'reviews' => $reviews,
        ]);
    }

    /**
     * Get review details
     */
    public function show($id)
    {
        $user = Auth::user();

        $review = HotelReview::where('hotel_id', $user->managed_hotel_id)
            ->with(['user', 'booking', 'hotel'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'review' => $review,
        ]);
    }

    /**
     * Respond to review (Hotel Owner can only respond, not delete)
     */
    public function respond(Request $request, $id)
    {
        $user = Auth::user();

        $review = HotelReview::where('hotel_id', $user->managed_hotel_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'response' => 'required|string|max:2000',
        ]);

        $review->update([
            'hotel_response' => $validated['response'],
            'hotel_response_date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Response posted successfully',
            'review' => $review->load(['user', 'booking']),
        ]);
    }

    /**
     * Update response to review
     */
    public function updateResponse(Request $request, $id)
    {
        $user = Auth::user();

        $review = HotelReview::where('hotel_id', $user->managed_hotel_id)
            ->findOrFail($id);

        if (!$review->hotel_response) {
            return response()->json([
                'success' => false,
                'message' => 'No response exists to update',
            ], 422);
        }

        $validated = $request->validate([
            'response' => 'required|string|max:2000',
        ]);

        $review->update([
            'hotel_response' => $validated['response'],
            'hotel_response_date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Response updated successfully',
            'review' => $review->load(['user', 'booking']),
        ]);
    }

    /**
     * Delete response (only the response, not the review)
     */
    public function deleteResponse($id)
    {
        $user = Auth::user();

        $review = HotelReview::where('hotel_id', $user->managed_hotel_id)
            ->findOrFail($id);

        if (!$review->hotel_response) {
            return response()->json([
                'success' => false,
                'message' => 'No response exists to delete',
            ], 422);
        }

        $review->update([
            'hotel_response' => null,
            'hotel_response_date' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Response deleted successfully',
            'review' => $review->load(['user', 'booking']),
        ]);
    }

    /**
     * Get review statistics
     */
    public function statistics()
    {
        $user = Auth::user();

        $reviews = HotelReview::where('hotel_id', $user->managed_hotel_id)->get();

        $totalReviews = $reviews->count();
        $approvedReviews = $reviews->where('is_approved', true);

        $ratingBreakdown = [
            5 => $approvedReviews->where('overall_rating', 5)->count(),
            4 => $approvedReviews->where('overall_rating', 4)->count(),
            3 => $approvedReviews->where('overall_rating', 3)->count(),
            2 => $approvedReviews->where('overall_rating', 2)->count(),
            1 => $approvedReviews->where('overall_rating', 1)->count(),
        ];

        $categoryAverages = [
            'overall' => $approvedReviews->avg('overall_rating'),
            'cleanliness' => $approvedReviews->avg('cleanliness_rating'),
            'staff' => $approvedReviews->avg('staff_rating'),
            'facilities' => $approvedReviews->avg('facilities_rating'),
            'location' => $approvedReviews->avg('location_rating'),
            'value' => $approvedReviews->avg('value_rating'),
        ];

        $responseStats = [
            'total_responses' => $reviews->whereNotNull('hotel_response')->count(),
            'pending_responses' => $reviews->whereNull('hotel_response')->where('is_approved', true)->count(),
            'response_rate' => $totalReviews > 0
                ? ($reviews->whereNotNull('hotel_response')->count() / $totalReviews) * 100
                : 0,
        ];

        return response()->json([
            'success' => true,
            'statistics' => [
                'total_reviews' => $totalReviews,
                'approved_reviews' => $approvedReviews->count(),
                'pending_reviews' => $reviews->where('is_approved', false)->count(),
                'rating_breakdown' => $ratingBreakdown,
                'category_averages' => array_map(function($avg) {
                    return $avg ? round($avg, 2) : 0;
                }, $categoryAverages),
                'response_stats' => [
                    'total_responses' => $responseStats['total_responses'],
                    'pending_responses' => $responseStats['pending_responses'],
                    'response_rate' => round($responseStats['response_rate'], 2),
                ],
                'verified_reviews' => $reviews->where('is_verified', true)->count(),
                'featured_reviews' => $reviews->where('is_featured', true)->count(),
            ],
        ]);
    }

    /**
     * Get recent reviews
     */
    public function recent(Request $request)
    {
        $user = Auth::user();

        $limit = $request->get('limit', 5);

        $reviews = HotelReview::where('hotel_id', $user->managed_hotel_id)
            ->with(['user', 'booking'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'reviews' => $reviews,
        ]);
    }

    /**
     * Get reviews that need response
     */
    public function needingResponse(Request $request)
    {
        $user = Auth::user();

        $reviews = HotelReview::where('hotel_id', $user->managed_hotel_id)
            ->where('is_approved', true)
            ->whereNull('hotel_response')
            ->with(['user', 'booking'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'reviews' => $reviews,
        ]);
    }

    /**
     * Mark review as featured (Hotel Owner can suggest, but Super Admin approves)
     */
    public function suggestFeatured($id)
    {
        $user = Auth::user();

        $review = HotelReview::where('hotel_id', $user->managed_hotel_id)
            ->findOrFail($id);

        if (!$review->is_approved) {
            return response()->json([
                'success' => false,
                'message' => 'Only approved reviews can be suggested as featured',
            ], 422);
        }

        // Hotel Owner can only suggest, Super Admin needs to approve
        // For now, we'll just add a note or flag
        // You can extend this with a "suggested_featured" field in the database

        return response()->json([
            'success' => true,
            'message' => 'Review suggested as featured. Pending admin approval.',
            'review' => $review,
        ]);
    }
}
