<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FoodProduct;
use App\Models\ConsumerScan;
use App\Services\FoodTraceabilityService;
use App\Http\Requests\FoodPassport\CreateFoodProductRequest;
use App\Http\Resources\FoodProductResource;
use App\Http\Resources\FoodProductDetailResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FoodPassportController extends Controller
{
    public function __construct(
        protected FoodTraceabilityService $traceabilityService
    ) {}

    /**
     * Get all food products (with filters)
     * GET /api/v1/food-passport/products
     */
    public function index(Request $request): JsonResponse
    {
        $query = FoodProduct::query()
            ->with(['farmer', 'journeyStages', 'qualityCheckpoints', 'certifications']);

        // Filters
        if ($request->filled('farmer_id')) {
            $query->byFarmer($request->farmer_id);
        }

        if ($request->filled('product_type')) {
            $query->ofType($request->product_type);
        }

        if ($request->filled('current_stage')) {
            $query->inStage($request->current_stage);
        }

        if ($request->filled('carbon_score')) {
            $query->withCarbonScore($request->carbon_score);
        }

        if ($request->boolean('high_quality')) {
            $query->highQuality();
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->input('per_page', 15), 100);
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => FoodProductResource::collection($products),
            'meta' => [
                'pagination' => [
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Create new food product
     * POST /api/v1/food-passport/products
     */
    public function store(CreateFoodProductRequest $request): JsonResponse
    {
        try {
            $product = $this->traceabilityService->createFoodProduct($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Food product created successfully',
                'data' => new FoodProductDetailResource($product),
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Failed to create food product', [
                'error' => $e->getMessage(),
                'data' => $request->validated(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create food product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single food product details
     * GET /api/v1/food-passport/products/{id}
     */
    public function show(int $id): JsonResponse
    {
        $product = FoodProduct::with([
            'farmer',
            'product',
            'journeyStages.handler',
            'journeyStages.qualityCheckpoints',
            'qualityCheckpoints.inspector',
            'carbonRecords',
            'carbonCredits',
            'certifications',
            'consumerScans',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new FoodProductDetailResource($product),
        ]);
    }

    /**
     * Update food product
     * PUT /api/v1/food-passport/products/{id}
     */
    public function update(CreateFoodProductRequest $request, int $id): JsonResponse
    {
        $product = FoodProduct::findOrFail($id);

        // Authorization check
        $this->authorize('update', $product);

        $product->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Food product updated successfully',
            'data' => new FoodProductDetailResource($product->fresh()),
        ]);
    }

    /**
     * Delete food product
     * DELETE /api/v1/food-passport/products/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $product = FoodProduct::findOrFail($id);

        // Authorization check
        $this->authorize('delete', $product);

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Food product deleted successfully',
        ]);
    }

    /**
     * Get full journey timeline
     * GET /api/v1/food-passport/products/{id}/journey
     */
    public function getJourney(int $id): JsonResponse
    {
        $timeline = $this->traceabilityService->generateTimeline($id);

        return response()->json([
            'success' => true,
            'data' => $timeline,
        ]);
    }

    /**
     * Get current location
     * GET /api/v1/food-passport/products/{id}/current-location
     */
    public function getCurrentLocation(int $id): JsonResponse
    {
        $location = $this->traceabilityService->getCurrentLocation($id);

        return response()->json([
            'success' => true,
            'data' => $location,
        ]);
    }

    /**
     * Get product statistics
     * GET /api/v1/food-passport/products/{id}/statistics
     */
    public function getStatistics(int $id): JsonResponse
    {
        $stats = $this->traceabilityService->getProductStatistics($id);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Public scan endpoint (no auth required)
     * GET /api/v1/food-passport/scan/{passportId}
     */
    public function scan(Request $request, string $passportId): JsonResponse
    {
        $product = FoodProduct::where('food_passport_id', $passportId)
            ->with([
                'farmer:id,name',
                'journeyStages' => fn($q) => $q->latest('sequence_order')->take(5),
                'qualityCheckpoints' => fn($q) => $q->latest('checked_at')->take(3),
                'certifications' => fn($q) => $q->active(),
            ])
            ->firstOrFail();

        // Track scan
        ConsumerScan::create([
            'food_product_id' => $product->id,
            'user_id' => auth()->id(),
            'session_id' => $request->session()->getId(),
            'scanned_at' => now(),
            'scan_location' => [
                'lat' => $request->input('lat'),
                'lng' => $request->input('lng'),
            ],
            'device_info' => [
                'type' => $request->input('device_type'),
                'os' => $request->input('os'),
                'browser' => $request->input('browser'),
            ],
        ]);

        return response()->json([
            'success' => true,
            'data' => new FoodProductDetailResource($product),
        ]);
    }

    /**
     * Track scan engagement
     * POST /api/v1/food-passport/scan/{passportId}/track
     */
    public function trackScanEngagement(Request $request, string $passportId): JsonResponse
    {
        $product = FoodProduct::where('food_passport_id', $passportId)->firstOrFail();

        $scan = ConsumerScan::where('food_product_id', $product->id)
            ->where('session_id', $request->session()->getId())
            ->latest('scanned_at')
            ->first();

        if ($scan) {
            $scan->update([
                'viewed_journey' => $request->boolean('viewed_journey', $scan->viewed_journey),
                'viewed_quality' => $request->boolean('viewed_quality', $scan->viewed_quality),
                'viewed_carbon' => $request->boolean('viewed_carbon', $scan->viewed_carbon),
                'time_spent_seconds' => $request->input('time_spent_seconds'),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Engagement tracked',
        ]);
    }

    /**
     * Submit consumer feedback
     * POST /api/v1/food-passport/scan/{passportId}/feedback
     */
    public function submitFeedback(Request $request, string $passportId): JsonResponse
    {
        $request->validate([
            'consumer_rating' => 'nullable|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:1000',
            'purchase_receipt_url' => 'nullable|url',
        ]);

        $product = FoodProduct::where('food_passport_id', $passportId)->firstOrFail();

        $scan = ConsumerScan::where('food_product_id', $product->id)
            ->where('session_id', $request->session()->getId())
            ->latest('scanned_at')
            ->first();

        if ($scan) {
            $scan->update([
                'consumer_rating' => $request->input('consumer_rating'),
                'feedback' => $request->input('feedback'),
                'verified_purchase' => $request->filled('purchase_receipt_url'),
                'purchase_receipt_url' => $request->input('purchase_receipt_url'),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Feedback submitted successfully',
        ]);
    }

    /**
     * Bulk import products
     * POST /api/v1/food-passport/products/bulk-import
     */
    public function bulkImport(Request $request): JsonResponse
    {
        $request->validate([
            'products' => 'required|array|min:1|max:100',
            'products.*.product_type' => 'required|string',
            'products.*.variety' => 'required|string',
            'products.*.quantity' => 'required|numeric|min:0',
            'products.*.unit' => 'required|string',
            'products.*.harvest_date' => 'required|date',
        ]);

        $results = [
            'success' => [],
            'failed' => [],
        ];

        foreach ($request->input('products') as $index => $productData) {
            try {
                $product = $this->traceabilityService->createFoodProduct($productData);
                $results['success'][] = [
                    'index' => $index,
                    'passport_id' => $product->food_passport_id,
                ];
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => sprintf(
                'Imported %d products successfully, %d failed',
                count($results['success']),
                count($results['failed'])
            ),
            'data' => $results,
        ]);
    }
}
