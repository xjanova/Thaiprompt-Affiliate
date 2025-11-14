<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QualityCheckpoint;
use App\Services\QualityControlService;
use App\Http\Requests\FoodPassport\CreateQualityCheckpointRequest;
use App\Http\Resources\QualityCheckpointResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QualityController extends Controller
{
    public function __construct(
        protected QualityControlService $qualityService
    ) {}

    /**
     * Create quality checkpoint
     * POST /api/v1/food-passport/quality/checkpoints
     */
    public function store(CreateQualityCheckpointRequest $request): JsonResponse
    {
        try {
            $checkpoint = $this->qualityService->createCheckpoint($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Quality checkpoint created successfully',
                'data' => new QualityCheckpointResource($checkpoint),
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Failed to create quality checkpoint', [
                'error' => $e->getMessage(),
                'data' => $request->validated(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create quality checkpoint',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get quality checkpoint details
     * GET /api/v1/food-passport/quality/checkpoints/{id}
     */
    public function show(int $id): JsonResponse
    {
        $checkpoint = QualityCheckpoint::with([
            'foodProduct',
            'journeyStage',
            'inspector',
            'retestCheckpoint',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new QualityCheckpointResource($checkpoint),
        ]);
    }

    /**
     * Update quality checkpoint
     * PUT /api/v1/food-passport/quality/checkpoints/{id}
     */
    public function update(CreateQualityCheckpointRequest $request, int $id): JsonResponse
    {
        $checkpoint = QualityCheckpoint::findOrFail($id);

        // Authorization check
        $this->authorize('update', $checkpoint);

        $checkpoint->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Quality checkpoint updated successfully',
            'data' => new QualityCheckpointResource($checkpoint->fresh()),
        ]);
    }

    /**
     * Approve quality checkpoint
     * POST /api/v1/food-passport/quality/checkpoints/{id}/approve
     */
    public function approve(int $id): JsonResponse
    {
        // Authorization check
        $checkpoint = QualityCheckpoint::findOrFail($id);
        $this->authorize('approve', $checkpoint);

        $this->qualityService->approveCheckpoint($id);

        return response()->json([
            'success' => true,
            'message' => 'Quality checkpoint approved',
        ]);
    }

    /**
     * Reject quality checkpoint
     * POST /api/v1/food-passport/quality/checkpoints/{id}/reject
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $checkpoint = QualityCheckpoint::findOrFail($id);
        $this->authorize('reject', $checkpoint);

        $this->qualityService->rejectCheckpoint($id, $request->input('reason'));

        return response()->json([
            'success' => true,
            'message' => 'Quality checkpoint rejected',
        ]);
    }

    /**
     * Request retest
     * POST /api/v1/food-passport/quality/checkpoints/{id}/retest
     */
    public function requestRetest(int $id): JsonResponse
    {
        $checkpoint = QualityCheckpoint::findOrFail($id);
        $this->authorize('retest', $checkpoint);

        $retestCheckpoint = $this->qualityService->requestRetest($id);

        return response()->json([
            'success' => true,
            'message' => 'Retest requested successfully',
            'data' => new QualityCheckpointResource($retestCheckpoint),
        ]);
    }

    /**
     * Get quality report for a product
     * GET /api/v1/food-passport/quality/products/{productId}/report
     */
    public function getProductReport(int $productId): JsonResponse
    {
        $report = $this->qualityService->generateQualityReport($productId);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get quality trends
     * GET /api/v1/food-passport/quality/products/{productId}/trends
     */
    public function getQualityTrends(Request $request, int $productId): JsonResponse
    {
        $period = $request->input('period', '30days');
        $trends = $this->qualityService->getQualityTrends($productId, $period);

        return response()->json([
            'success' => true,
            'data' => $trends,
        ]);
    }
}
