<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiGenPackage;
use App\Services\AiGen\AiGenSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AiGenPackageController extends Controller
{
    protected AiGenSubscriptionService $subscriptionService;

    public function __construct(AiGenSubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }
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
     * Purchase package.
     *
     * For production, integrate with payment gateway (Stripe, PayPal, etc.)
     * Current implementation creates subscription immediately for testing/demo.
     */
    public function purchase(Request $request, int $packageId): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => 'required|string|in:credit_card,paypal,bank_transfer,promptpay',
            'transaction_id' => 'nullable|string',
        ]);

        try {
            $user = $request->user();

            $package = AiGenPackage::active()
                ->where('id', $packageId)
                ->first();

            if (!$package) {
                return response()->json([
                    'success' => false,
                    'error' => 'Package not found',
                ], 404);
            }

            // Check if user already has an active subscription
            $activeSubscription = $this->subscriptionService->getActiveSubscription($user);
            if ($activeSubscription) {
                return response()->json([
                    'success' => false,
                    'error' => 'You already have an active subscription. Please wait for it to expire or cancel it first.',
                ], 400);
            }

            // Create payment data
            $paymentData = [
                'amount' => $package->price,
                'method' => $validated['payment_method'],
                'transaction_id' => $validated['transaction_id'] ?? 'DEMO-' . uniqid(),
            ];

            // TODO: Integrate with payment gateway here
            // For now, we create subscription immediately
            // In production:
            // 1. Process payment through gateway
            // 2. Wait for payment confirmation
            // 3. Then create subscription

            // Create subscription
            $subscription = $this->subscriptionService->createSubscription(
                $user,
                $package,
                $paymentData
            );

            return response()->json([
                'success' => true,
                'message' => 'Package purchased successfully!',
                'subscription' => [
                    'id' => $subscription->id,
                    'package_name' => $subscription->package->name,
                    'image_credits' => $subscription->image_credits_total,
                    'video_credits' => $subscription->video_credits_total,
                    'started_at' => $subscription->started_at->toDateTimeString(),
                    'expires_at' => $subscription->expires_at?->toDateTimeString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
