<?php

namespace App\Services\VendorFeature;

use App\Models\Vendor;
use App\Models\VendorFeature;
use App\Models\VendorFeaturePurchase;
use App\Models\VendorFeatureUsageLog;
use App\Models\WalletTransaction;
use App\Services\Payment\PaymentService;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;
use Exception;

class VendorFeatureService
{
    protected PaymentService $paymentService;
    protected WalletService $walletService;

    public function __construct(
        PaymentService $paymentService,
        WalletService $walletService
    ) {
        $this->paymentService = $paymentService;
        $this->walletService = $walletService;
    }

    /**
     * Get all available features for vendors
     */
    public function getAvailableFeatures(?string $category = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = VendorFeature::active()->ordered();

        if ($category) {
            $query->category($category);
        }

        return $query->get();
    }

    /**
     * Get featured features
     */
    public function getFeaturedFeatures(): \Illuminate\Database\Eloquent\Collection
    {
        return VendorFeature::active()->featured()->ordered()->get();
    }

    /**
     * Get feature categories
     */
    public function getCategories(): array
    {
        return VendorFeature::active()
            ->distinct('category')
            ->pluck('category')
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Get vendor's purchased features
     */
    public function getVendorFeatures(Vendor $vendor, bool $activeOnly = true): \Illuminate\Database\Eloquent\Collection
    {
        $query = $vendor->featurePurchases()->with('feature');

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Check if vendor has active feature
     */
    public function hasActiveFeature(Vendor $vendor, int $featureId): bool
    {
        return $vendor->featurePurchases()
            ->where('vendor_feature_id', $featureId)
            ->active()
            ->exists();
    }

    /**
     * Purchase a feature
     */
    public function purchaseFeature(
        Vendor $vendor,
        VendorFeature $feature,
        string $paymentMethod,
        ?string $transactionId = null
    ): VendorFeaturePurchase {
        DB::beginTransaction();

        try {
            // Check if feature is available
            if (!$feature->isAvailableForPurchase()) {
                throw new Exception('This feature is not available for purchase.');
            }

            // Check if vendor already has active subscription
            if ($this->hasActiveFeature($vendor, $feature->id)) {
                throw new Exception('You already have an active subscription to this feature.');
            }

            // Create purchase record
            $purchase = new VendorFeaturePurchase([
                'vendor_id' => $vendor->id,
                'vendor_feature_id' => $feature->id,
                'price_paid' => $feature->price,
                'payment_method' => $paymentMethod,
                'payment_status' => 'pending',
                'purchased_version' => $feature->current_version,
                'status' => 'active',
            ]);

            // Process payment
            $paymentResult = $this->processPayment(
                $vendor,
                $feature->price,
                $paymentMethod,
                $transactionId
            );

            if (!$paymentResult['success']) {
                throw new Exception($paymentResult['message'] ?? 'Payment failed.');
            }

            $purchase->payment_status = 'completed';
            $purchase->transaction_id = $paymentResult['transaction_id'] ?? null;

            if (isset($paymentResult['wallet_transaction_id'])) {
                $purchase->wallet_transaction_id = $paymentResult['wallet_transaction_id'];
            }

            $purchase->save();

            // Activate the purchase
            $purchase->activate();

            // Log the purchase
            VendorFeatureUsageLog::logAction(
                $vendor->id,
                $feature->id,
                'purchased',
                $purchase->id,
                [
                    'payment_method' => $paymentMethod,
                    'price' => $feature->price,
                    'version' => $feature->current_version,
                ]
            );

            DB::commit();

            return $purchase;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Process payment for feature purchase
     */
    protected function processPayment(
        Vendor $vendor,
        float $amount,
        string $method,
        ?string $transactionId = null
    ): array {
        try {
            switch ($method) {
                case 'wallet':
                    // Pay using wallet
                    $wallet = $vendor->user->wallet;
                    if (!$wallet || $wallet->balance < $amount) {
                        return [
                            'success' => false,
                            'message' => 'Insufficient wallet balance.',
                        ];
                    }

                    $transaction = $this->walletService->deductBalance(
                        $wallet,
                        $amount,
                        'purchase',
                        'Feature purchase'
                    );

                    return [
                        'success' => true,
                        'wallet_transaction_id' => $transaction->id,
                    ];

                case 'stripe':
                case 'promptpay':
                    // Use existing payment service
                    return $this->paymentService->processPayment($amount, $method, [
                        'description' => 'Feature purchase',
                        'transaction_id' => $transactionId,
                    ]);

                default:
                    return [
                        'success' => false,
                        'message' => 'Invalid payment method.',
                    ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Renew a feature subscription
     */
    public function renewFeature(
        VendorFeaturePurchase $purchase,
        string $paymentMethod,
        ?string $transactionId = null
    ): bool {
        DB::beginTransaction();

        try {
            $feature = $purchase->feature;

            // Check if feature is recurring
            if (!in_array($feature->price_type, ['monthly', 'yearly'])) {
                throw new Exception('This feature is not a recurring subscription.');
            }

            // Process payment
            $paymentResult = $this->processPayment(
                $purchase->vendor,
                $feature->price,
                $paymentMethod,
                $transactionId
            );

            if (!$paymentResult['success']) {
                throw new Exception($paymentResult['message'] ?? 'Payment failed.');
            }

            // Renew the purchase
            $purchase->renew();

            // Log the renewal
            VendorFeatureUsageLog::logAction(
                $purchase->vendor_id,
                $feature->id,
                'renewed',
                $purchase->id,
                [
                    'payment_method' => $paymentMethod,
                    'price' => $feature->price,
                    'expires_at' => $purchase->expires_at,
                ]
            );

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cancel a feature subscription
     */
    public function cancelFeature(VendorFeaturePurchase $purchase, ?string $reason = null): bool
    {
        DB::beginTransaction();

        try {
            $purchase->cancel($reason);

            // Log the cancellation
            VendorFeatureUsageLog::logAction(
                $purchase->vendor_id,
                $purchase->vendor_feature_id,
                'cancelled',
                $purchase->id,
                [
                    'reason' => $reason,
                ]
            );

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get feature statistics
     */
    public function getFeatureStatistics(VendorFeature $feature): array
    {
        return [
            'total_purchases' => $feature->purchases()->count(),
            'active_purchases' => $feature->activePurchases()->count(),
            'total_revenue' => $feature->getTotalRevenue(),
            'average_price' => $feature->purchases()
                ->where('payment_status', 'completed')
                ->avg('price_paid'),
            'latest_version' => $feature->current_version,
            'total_versions' => $feature->versions()->count(),
            'total_changelogs' => $feature->publishedChangelogs()->count(),
        ];
    }

    /**
     * Get vendor feature usage statistics
     */
    public function getVendorStatistics(Vendor $vendor): array
    {
        return [
            'total_features' => $vendor->featurePurchases()->count(),
            'active_features' => $vendor->featurePurchases()->active()->count(),
            'total_spent' => $vendor->featurePurchases()
                ->where('payment_status', 'completed')
                ->sum('price_paid'),
            'expiring_soon' => $vendor->featurePurchases()
                ->expiringSoon(7)
                ->count(),
        ];
    }

    /**
     * Get expiring features for a vendor
     */
    public function getExpiringFeatures(Vendor $vendor, int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return $vendor->featurePurchases()
            ->with('feature')
            ->expiringSoon($days)
            ->get();
    }

    /**
     * Auto-renew expiring features
     */
    public function autoRenewExpiringFeatures(): array
    {
        $renewed = 0;
        $failed = 0;
        $errors = [];

        $expiringPurchases = VendorFeaturePurchase::expiringSoon(3)
            ->with(['vendor', 'feature'])
            ->get();

        foreach ($expiringPurchases as $purchase) {
            try {
                // Try to renew using wallet
                $this->renewFeature($purchase, 'wallet');
                $renewed++;
            } catch (Exception $e) {
                $failed++;
                $errors[] = [
                    'purchase_id' => $purchase->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'renewed' => $renewed,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }
}
