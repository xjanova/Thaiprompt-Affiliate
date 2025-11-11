<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiGenPackage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AiGenPackageController extends Controller
{
    /**
     * Get all active packages.
     */
    public function index(): JsonResponse
    {
        try {
            $packages = AiGenPackage::active()
                ->orderBy('sort_order')
                ->orderBy('price')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $packages,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single package.
     */
    public function show(int $packageId): JsonResponse
    {
        try {
            $package = AiGenPackage::active()
                ->where('id', $packageId)
                ->first();

            if (!$package) {
                return response()->json([
                    'success' => false,
                    'error' => 'Package not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $package,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Purchase package (placeholder - integrate with payment gateway).
     */
    public function purchase(Request $request, int $packageId): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => 'required|string',
            // Add other payment-related fields as needed
        ]);

        try {
            $package = AiGenPackage::active()
                ->where('id', $packageId)
                ->first();

            if (!$package) {
                return response()->json([
                    'success' => false,
                    'error' => 'Package not found',
                ], 404);
            }

            // TODO: Integrate with payment gateway
            // For now, create a pending transaction

            return response()->json([
                'success' => true,
                'message' => 'Payment initiated. Please complete the payment process.',
                'package' => $package,
                'amount' => $package->price,
                'currency' => $package->currency,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
