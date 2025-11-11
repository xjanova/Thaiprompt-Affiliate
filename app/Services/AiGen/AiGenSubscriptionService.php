<?php

namespace App\Services\AiGen;

use App\Models\User;
use App\Models\AiGenPackage;
use App\Models\AiGenSubscription;
use Carbon\Carbon;

class AiGenSubscriptionService
{
    /**
     * Create a new subscription for a user.
     */
    public function createSubscription(
        User $user,
        AiGenPackage $package,
        array $paymentData = []
    ): AiGenSubscription {
        $expiresAt = null;
        if ($package->duration_days) {
            $expiresAt = Carbon::now()->addDays($package->duration_days);
        }

        return AiGenSubscription::create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'image_credits_total' => $package->image_credits,
            'image_credits_used' => 0,
            'video_credits_total' => $package->video_credits,
            'video_credits_used' => 0,
            'started_at' => Carbon::now(),
            'expires_at' => $expiresAt,
            'status' => 'active',
            'amount_paid' => $paymentData['amount'] ?? $package->price,
            'payment_method' => $paymentData['method'] ?? null,
            'transaction_id' => $paymentData['transaction_id'] ?? null,
        ]);
    }

    /**
     * Get active subscription for a user.
     */
    public function getActiveSubscription(User $user): ?AiGenSubscription
    {
        return AiGenSubscription::where('user_id', $user->id)
            ->active()
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Check if user has active subscription.
     */
    public function hasActiveSubscription(User $user): bool
    {
        return $this->getActiveSubscription($user) !== null;
    }

    /**
     * Check if user can generate with subscription.
     */
    public function canGenerate(User $user, string $type): bool
    {
        // Admin always can
        if ($user->is_admin || $user->is_super_admin) {
            return true;
        }

        $subscription = $this->getActiveSubscription($user);

        if (!$subscription) {
            return false;
        }

        return $subscription->hasCredits($type);
    }

    /**
     * Use credits from subscription.
     */
    public function useCredits(User $user, string $type, int $amount = 1): bool
    {
        // Admin doesn't use credits
        if ($user->is_admin || $user->is_super_admin) {
            return true;
        }

        $subscription = $this->getActiveSubscription($user);

        if (!$subscription) {
            return false;
        }

        return $subscription->useCredits($type, $amount);
    }

    /**
     * Get subscription summary for a user.
     */
    public function getSubscriptionSummary(User $user): array
    {
        if ($user->is_admin || $user->is_super_admin) {
            return [
                'has_subscription' => true,
                'is_admin' => true,
                'unlimited' => true,
            ];
        }

        $subscription = $this->getActiveSubscription($user);

        if (!$subscription) {
            return [
                'has_subscription' => false,
                'is_admin' => false,
            ];
        }

        return [
            'has_subscription' => true,
            'is_admin' => false,
            'package_name' => $subscription->package->name,
            'image_credits' => [
                'total' => $subscription->image_credits_total,
                'used' => $subscription->image_credits_used,
                'remaining' => $subscription->remaining_image_credits,
            ],
            'video_credits' => [
                'total' => $subscription->video_credits_total,
                'used' => $subscription->video_credits_used,
                'remaining' => $subscription->remaining_video_credits,
            ],
            'started_at' => $subscription->started_at->toDateTimeString(),
            'expires_at' => $subscription->expires_at?->toDateTimeString(),
            'status' => $subscription->status,
        ];
    }

    /**
     * Cancel subscription.
     */
    public function cancelSubscription(AiGenSubscription $subscription): bool
    {
        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => Carbon::now(),
        ]);

        return true;
    }

    /**
     * Renew subscription.
     */
    public function renewSubscription(
        AiGenSubscription $subscription,
        array $paymentData = []
    ): AiGenSubscription {
        // Create new subscription based on the same package
        return $this->createSubscription(
            $subscription->user,
            $subscription->package,
            $paymentData
        );
    }
}
