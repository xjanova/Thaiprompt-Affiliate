<?php

namespace App\Http\Controllers\Api\Chatbot;

use App\Http\Controllers\Controller;
use App\Models\AiBotProfile;
use App\Models\AiBotRental;
use App\Models\OwnerEarning;
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

        // ตรวจสอบข้อมูลการเช่า (ใช้ rental_type แทน rental_plan)
        $validator = Validator::make($request->all(), [
            'rental_type' => 'required|in:monthly,per_message',
            'months' => 'required_if:rental_type,monthly|integer|min:1|max:12',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $rentalType = $request->rental_type;
        $months = $request->months ?? 1;

        // กำหนดราคาและวันที่ตาม rental type
        if ($rentalType === 'monthly') {
            $price = $bot->rental_price_per_month;
            $totalAmount = $price * $months;
            $startDate = now();
            $endDate = now()->addMonths($months);
        } else {
            $price = $bot->rental_price_per_message;
            $totalAmount = 0; // จะคำนวณตามการใช้งานจริง
            $startDate = now();
            $endDate = null; // ไม่มีวันหมดอายุสำหรับ per-message
        }

        // สร้างการเช่าใหม่ (ใช้ field names ที่ถูกต้องตาม schema)
        $rental = AiBotRental::create([
            'bot_profile_id' => $bot->id,
            'renter_id' => $user->id,
            'rental_type' => $rentalType,
            'price' => $price,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'active',
            'total_amount' => $totalAmount,
            'commission_rate' => $bot->commission_rate ?? 20,
            'auto_renew' => $request->auto_renew ?? false,
        ]);

        // TODO: Process payment (implement payment logic here)

        return response()->json([
            'success' => true,
            'message' => 'เช่าบอทสำเร็จ',
            'data' => $rental->load(['botProfile.owner']),
        ], 201);
    }

    /**
     * Get my rentals (as renter)
     *
     * ดึงรายการเช่าของผู้ใช้ (ในฐานะผู้เช่า)
     */
    public function myRentals()
    {
        $user = Auth::user();

        // ดึงการเช่าพร้อม botProfile และ owner ผ่าน botProfile
        $rentals = AiBotRental::where('renter_id', $user->id)
            ->with(['botProfile.owner'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $rentals,
        ]);
    }

    /**
     * Get my earnings (as owner)
     *
     * ดึงรายได้จากการให้เช่าบอท (ในฐานะเจ้าของบอท)
     * ค้นหาผ่าน botProfile ที่ผู้ใช้เป็นเจ้าของ
     */
    public function myEarnings()
    {
        $user = Auth::user();

        // ดึง bot_profile_ids ที่ผู้ใช้เป็นเจ้าของ
        $ownedBotIds = AiBotProfile::where('owner_id', $user->id)->pluck('id');

        // ดึงการเช่าที่เป็นบอทของผู้ใช้
        $rentals = AiBotRental::whereIn('bot_profile_id', $ownedBotIds)
            ->with(['botProfile', 'renter'])
            ->latest()
            ->paginate(20);

        // คำนวณสถิติรายได้จาก OwnerEarning model
        $stats = [
            'total_rentals' => AiBotRental::whereIn('bot_profile_id', $ownedBotIds)->count(),
            'active_rentals' => AiBotRental::whereIn('bot_profile_id', $ownedBotIds)->active()->count(),
            'total_earnings' => OwnerEarning::where('owner_id', $user->id)->sum('net_amount'),
            'this_month_earnings' => OwnerEarning::where('owner_id', $user->id)
                ->where('earning_month', now()->format('Y-m'))
                ->sum('net_amount'),
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
     *
     * ต่ออายุการเช่าบอท (เฉพาะแบบรายเดือนเท่านั้น)
     */
    public function renewRental(Request $request, $rentalId)
    {
        $user = Auth::user();

        $rental = AiBotRental::where('renter_id', $user->id)
            ->findOrFail($rentalId);

        // ตรวจสอบว่าเป็นแผนรายเดือนหรือไม่ (ใช้ rental_type แทน rental_plan)
        if ($rental->rental_type !== 'monthly') {
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
