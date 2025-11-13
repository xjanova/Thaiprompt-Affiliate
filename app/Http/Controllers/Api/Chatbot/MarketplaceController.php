<?php

namespace App\Http\Controllers\Api\Chatbot;

use App\Http\Controllers\Controller;
use App\Models\AiBotProfile;
use App\Models\AiBotRental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Marketplace Controller
 *
 * ตลาดซื้อขายและเช่าบอท
 */
class MarketplaceController extends Controller
{
    /**
     * Get all bots available in marketplace
     */
    public function index(Request $request)
    {
        $query = AiBotProfile::public()
            ->rentable()
            ->with(['owner', 'provider', 'model']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by category (if needed)
        if ($request->filled('category')) {
            // Add category filtering logic
        }

        // Filter by price range
        if ($request->filled('min_price')) {
            $query->where('rental_price_per_month', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('rental_price_per_month', '<=', $request->max_price);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        if ($sortBy === 'popular') {
            // Sort by active rentals count
            $query->withCount('activeRentals')
                ->orderBy('active_rentals_count', 'desc');
        } elseif ($sortBy === 'price') {
            $query->orderBy('rental_price_per_month', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $bots = $query->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $bots,
        ]);
    }

    /**
     * Get bot details for marketplace
     */
    public function show($id)
    {
        $bot = AiBotProfile::public()
            ->rentable()
            ->with(['owner', 'provider', 'model'])
            ->withCount('activeRentals')
            ->findOrFail($id);

        // Get sample conversations or reviews (if implemented)

        return response()->json([
            'success' => true,
            'data' => [
                'bot' => $bot,
                'stats' => [
                    'active_rentals' => $bot->active_rentals_count,
                    'total_conversations' => $bot->conversations()->count(),
                ],
            ],
        ]);
    }

    /**
     * Rent a bot
     */
    public function rent(Request $request, $id)
    {
        $user = Auth::user();

        $bot = AiBotProfile::public()
            ->rentable()
            ->findOrFail($id);

        // Check if user is the owner
        if ($bot->owner_id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่สามารถเช่าบอทของตัวเองได้',
            ], 422);
        }

        // Check if already rented
        $existingRental = AiBotRental::where('bot_profile_id', $bot->id)
            ->where('renter_id', $user->id)
            ->active()
            ->first();

        if ($existingRental) {
            return response()->json([
                'success' => false,
                'message' => 'คุณกำลังเช่าบอทนี้อยู่แล้ว',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'rental_plan' => 'required|in:monthly,per_message',
            'months' => 'required_if:rental_plan,monthly|integer|min:1|max:12',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $rentalPlan = $request->rental_plan;
        $months = $request->months ?? 1;

        if ($rentalPlan === 'monthly') {
            $price = $bot->rental_price_per_month;
            $totalPrice = $price * $months;
            $startDate = now();
            $endDate = now()->addMonths($months);
        } else {
            $price = $bot->rental_price_per_message;
            $totalPrice = 0; // Will be calculated based on usage
            $startDate = now();
            $endDate = null; // No end date for per-message plan
        }

        // Create rental
        $rental = AiBotRental::create([
            'bot_profile_id' => $bot->id,
            'renter_id' => $user->id,
            'owner_id' => $bot->owner_id,
            'rental_plan' => $rentalPlan,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => true,
            'monthly_price' => $rentalPlan === 'monthly' ? $price : null,
            'price_per_message' => $rentalPlan === 'per_message' ? $price : null,
            'commission_rate' => $bot->commission_rate ?? 20,
        ]);

        // Process payment (implement payment logic here)

        return response()->json([
            'success' => true,
            'message' => 'เช่าบอทสำเร็จ',
            'data' => $rental->load(['botProfile', 'owner']),
        ], 201);
    }

    /**
     * Get my rentals (as renter)
     */
    public function myRentals()
    {
        $user = Auth::user();

        $rentals = AiBotRental::where('renter_id', $user->id)
            ->with(['botProfile', 'owner'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $rentals,
        ]);
    }

    /**
     * Get my earnings (as owner)
     */
    public function myEarnings()
    {
        $user = Auth::user();

        $rentals = AiBotRental::where('owner_id', $user->id)
            ->with(['botProfile', 'renter'])
            ->latest()
            ->paginate(20);

        $stats = [
            'total_rentals' => AiBotRental::where('owner_id', $user->id)->count(),
            'active_rentals' => AiBotRental::where('owner_id', $user->id)->active()->count(),
            'total_earnings' => AiBotRental::where('owner_id', $user->id)->sum('owner_earning'),
            'this_month_earnings' => AiBotRental::where('owner_id', $user->id)
                ->whereMonth('created_at', now()->month)
                ->sum('owner_earning'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'rentals' => $rentals,
                'stats' => $stats,
            ],
        ]);
    }

    /**
     * Cancel rental
     */
    public function cancelRental($rentalId)
    {
        $user = Auth::user();

        $rental = AiBotRental::where('renter_id', $user->id)
            ->findOrFail($rentalId);

        $rental->cancel();

        return response()->json([
            'success' => true,
            'message' => 'ยกเลิกการเช่าสำเร็จ',
        ]);
    }

    /**
     * Renew rental
     */
    public function renewRental(Request $request, $rentalId)
    {
        $user = Auth::user();

        $rental = AiBotRental::where('renter_id', $user->id)
            ->findOrFail($rentalId);

        if ($rental->rental_plan !== 'monthly') {
            return response()->json([
                'success' => false,
                'message' => 'สามารถต่ออายุได้เฉพาะแผนรายเดือนเท่านั้น',
            ], 422);
        }

        $months = $request->months ?? 1;

        $rental->renew($months);

        return response()->json([
            'success' => true,
            'message' => 'ต่ออายุสำเร็จ',
            'data' => $rental,
        ]);
    }
}
