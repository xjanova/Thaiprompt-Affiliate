<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CarbonCredit;
use App\Models\CarbonFootprintRecord;
use App\Services\CarbonCreditService;
use App\Http\Resources\CarbonCreditResource;
use App\Http\Resources\CarbonFootprintRecordResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CarbonCreditController extends Controller
{
    public function __construct(
        protected CarbonCreditService $carbonService
    ) {}

    /**
     * Get carbon footprint for a product
     * GET /api/v1/food-passport/carbon/products/{productId}/footprint
     */
    public function getProductFootprint(int $productId): JsonResponse
    {
        $footprint = $this->carbonService->calculateFootprint($productId);
        $breakdown = $this->carbonService->getCarbonBreakdown($productId);

        return response()->json([
            'success' => true,
            'data' => [
                'total_carbon_footprint' => $footprint,
                'breakdown' => $breakdown,
            ],
        ]);
    }

    /**
     * Get carbon footprint breakdown
     * GET /api/v1/food-passport/carbon/products/{productId}/breakdown
     */
    public function getBreakdown(int $productId): JsonResponse
    {
        $breakdown = $this->carbonService->getCarbonBreakdown($productId);

        return response()->json([
            'success' => true,
            'data' => $breakdown,
        ]);
    }

    /**
     * Calculate carbon footprint
     * POST /api/v1/food-passport/carbon/calculate
     */
    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:food_products,id',
        ]);

        $footprint = $this->carbonService->calculateFootprint($request->input('product_id'));

        return response()->json([
            'success' => true,
            'data' => [
                'total_carbon_footprint' => $footprint,
            ],
        ]);
    }

    /**
     * Verify carbon emission data
     * POST /api/v1/food-passport/carbon/verify/{recordId}
     */
    public function verify(int $recordId): JsonResponse
    {
        $this->authorize('verify', CarbonFootprintRecord::class);

        $this->carbonService->verifyEmissionData($recordId, auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Carbon data verified successfully',
        ]);
    }

    /**
     * Get all carbon credits
     * GET /api/v1/food-passport/carbon/credits
     */
    public function index(Request $request): JsonResponse
    {
        $query = CarbonCredit::query()->with(['user', 'foodProduct']);

        // Filters
        if ($request->filled('user_id')) {
            $query->byUser($request->input('user_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->boolean('tradeable_only')) {
            $query->tradeable();
        }

        if ($request->boolean('expiring_soon')) {
            $query->expiringSoon($request->input('days', 30));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'issued_date');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->input('per_page', 15), 100);
        $credits = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => CarbonCreditResource::collection($credits),
            'meta' => [
                'pagination' => [
                    'total' => $credits->total(),
                    'per_page' => $credits->perPage(),
                    'current_page' => $credits->currentPage(),
                    'last_page' => $credits->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Claim carbon credit for a product
     * POST /api/v1/food-passport/carbon/credits/claim
     */
    public function claim(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:food_products,id',
        ]);

        $credit = $this->carbonService->issueCarbonCredit(
            auth()->id(),
            $request->input('product_id')
        );

        if (!$credit) {
            return response()->json([
                'success' => false,
                'message' => 'Carbon emission is not below baseline. No credit issued.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Carbon credit issued successfully',
            'data' => new CarbonCreditResource($credit),
        ], 201);
    }

    /**
     * Trade carbon credit
     * POST /api/v1/food-passport/carbon/credits/{id}/trade
     */
    public function trade(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'buyer_id' => 'required|exists:users,id',
            'price' => 'required|numeric|min:0',
        ]);

        $credit = CarbonCredit::findOrFail($id);

        // Authorization check
        $this->authorize('trade', $credit);

        try {
            $this->carbonService->tradeCarbonCredit(
                $id,
                $request->input('buyer_id'),
                $request->input('price')
            );

            return response()->json([
                'success' => true,
                'message' => 'Carbon credit traded successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to trade carbon credit',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get carbon credit leaderboard
     * GET /api/v1/food-passport/carbon/credits/leaderboard
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $period = $request->input('period', 'month');
        $leaderboard = $this->carbonService->getLeaderboard($period);

        return response()->json([
            'success' => true,
            'data' => $leaderboard->map(fn($item) => [
                'user' => [
                    'id' => $item->user_id,
                    'name' => $item->user->name,
                ],
                'total_credits' => $item->total_credits,
                'total_value' => $item->total_value,
                'products_count' => $item->products_count,
            ]),
        ]);
    }

    /**
     * Get user rewards summary
     * GET /api/v1/food-passport/carbon/rewards
     */
    public function getRewards(Request $request): JsonResponse
    {
        $userId = $request->input('user_id', auth()->id());

        // Authorization check
        if ($userId != auth()->id()) {
            $this->authorize('viewRewards', CarbonCredit::class);
        }

        $rewards = $this->carbonService->calculateRewards($userId);

        return response()->json([
            'success' => true,
            'data' => $rewards,
        ]);
    }

    /**
     * Get marketplace listings
     * GET /api/v1/food-passport/carbon/marketplace
     */
    public function marketplace(Request $request): JsonResponse
    {
        $credits = CarbonCredit::tradeable()
            ->with(['user', 'foodProduct'])
            ->orderBy('credit_value_per_ton', 'asc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => CarbonCreditResource::collection($credits),
            'meta' => [
                'pagination' => [
                    'total' => $credits->total(),
                    'per_page' => $credits->perPage(),
                    'current_page' => $credits->currentPage(),
                    'last_page' => $credits->lastPage(),
                ],
            ],
        ]);
    }
}
