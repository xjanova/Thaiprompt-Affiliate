<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FoodCertification;
use App\Services\CertificationService;
use App\Http\Resources\FoodCertificationResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CertificationController extends Controller
{
    public function __construct(
        protected CertificationService $certificationService
    ) {}

    /**
     * Issue new certification
     * POST /api/v1/food-passport/certifications
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'food_product_id' => 'required|exists:food_products,id',
            'certification_type' => 'required|string|max:100',
            'certification_name' => 'required|string|max:255',
            'certifying_body' => 'required|string|max:255',
            'issued_date' => 'required|date',
            'expiry_date' => 'required|date|after:issued_date',
            'scope' => 'nullable|string',
            'standards_version' => 'nullable|string|max:50',
        ]);

        // Authorization check
        $this->authorize('issue', FoodCertification::class);

        try {
            $certification = $this->certificationService->issueCertification($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Certification issued successfully',
                'data' => new FoodCertificationResource($certification),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to issue certification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get certification details
     * GET /api/v1/food-passport/certifications/{id}
     */
    public function show(int $id): JsonResponse
    {
        $certification = FoodCertification::with('foodProduct')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new FoodCertificationResource($certification),
        ]);
    }

    /**
     * Verify certification by certificate number
     * GET /api/v1/food-passport/certifications/verify/{certificateNumber}
     */
    public function verify(string $certificateNumber): JsonResponse
    {
        $certification = $this->certificationService->verifyCertificate($certificateNumber);

        if (!$certification) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'valid' => $certification->isActive(),
                'certification' => new FoodCertificationResource($certification),
            ],
        ]);
    }

    /**
     * Renew certification
     * POST /api/v1/food-passport/certifications/{id}/renew
     */
    public function renew(int $id): JsonResponse
    {
        $certification = FoodCertification::findOrFail($id);
        $this->authorize('renew', $certification);

        try {
            $newCertification = $this->certificationService->renewCertificate($id);

            return response()->json([
                'success' => true,
                'message' => 'Certification renewed successfully',
                'data' => new FoodCertificationResource($newCertification),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to renew certification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Revoke certification
     * POST /api/v1/food-passport/certifications/{id}/revoke
     */
    public function revoke(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $certification = FoodCertification::findOrFail($id);
        $this->authorize('revoke', $certification);

        $this->certificationService->revokeCertificate($id, $request->input('reason'));

        return response()->json([
            'success' => true,
            'message' => 'Certification revoked successfully',
        ]);
    }

    /**
     * Get product certifications
     * GET /api/v1/food-passport/certifications/products/{productId}
     */
    public function getProductCertifications(int $productId): JsonResponse
    {
        $certifications = $this->certificationService->getProductCertifications($productId);

        return response()->json([
            'success' => true,
            'data' => FoodCertificationResource::collection($certifications),
        ]);
    }

    /**
     * Get expiring certifications
     * GET /api/v1/food-passport/certifications/expiring
     */
    public function getExpiring(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);
        $certifications = $this->certificationService->checkExpiringSoon($days);

        return response()->json([
            'success' => true,
            'data' => FoodCertificationResource::collection($certifications),
        ]);
    }
}
