<?php

namespace App\Services\BotAutomation;

use App\Models\BotAutomation\BotRentalSubscription;
use App\Models\BotAutomation\BotMarketplaceListing;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BotRentalService
{
    /**
     * Subscribe to bot
     */
    public function subscribe(User $user, BotMarketplaceListing $listing, array $data = []): BotRentalSubscription
    {
        DB::beginTransaction();

        try {
            $billingCycle = $data['billing_cycle'] ?? $listing->pricing_model;
            $price = $this->calculatePrice($listing, $billingCycle);
            $commission = $price * ($listing->commission_rate / 100);
            $ownerEarning = $price - $commission;

            $subscription = BotRentalSubscription::create([
                'user_id' => $user->id,
                'listing_id' => $listing->id,
                'automation_id' => $listing->automation_id,
                'subscription_id' => BotRentalSubscription::generateSubscriptionId(),
                'status' => 'trial',
                'billing_cycle' => $billingCycle,
                'price_paid' => $price,
                'platform_commission' => $commission,
                'owner_earning' => $ownerEarning,
                'trial_ends_at' => now()->addDays(7), // 7-day trial
                'current_period_start' => now(),
                'current_period_end' => $this->calculatePeriodEnd($billingCycle),
                'next_billing_date' => $this->calculateNextBillingDate($billingCycle),
                'settings' => $data['settings'] ?? [],
                'auto_renew' => $data['auto_renew'] ?? true,
            ]);

            // Update listing stats
            $listing->recordRental($price);

            DB::commit();

            Log::info("Bot subscription created", [
                'user_id' => $user->id,
                'listing_id' => $listing->id,
                'subscription_id' => $subscription->subscription_id,
            ]);

            return $subscription;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Calculate price based on billing cycle
     */
    protected function calculatePrice(BotMarketplaceListing $listing, string $billingCycle): float
    {
        return match($billingCycle) {
            'monthly' => $listing->monthly_price ?? $listing->price,
            'yearly' => $listing->yearly_price ?? ($listing->monthly_price * 10), // 2 months free
            'one_time' => $listing->price,
            default => $listing->monthly_price ?? $listing->price,
        };
    }

    /**
     * Calculate period end
     */
    protected function calculatePeriodEnd(string $billingCycle): \Carbon\Carbon
    {
        return match($billingCycle) {
            'monthly' => now()->addMonth(),
            'yearly' => now()->addYear(),
            'one_time' => now()->addYear(10), // Long period for one-time purchase
            default => now()->addMonth(),
        };
    }

    /**
     * Calculate next billing date
     */
    protected function calculateNextBillingDate(string $billingCycle): \Carbon\Carbon
    {
        return $this->calculatePeriodEnd($billingCycle);
    }

    /**
     * Renew subscription
     */
    public function renew(BotRentalSubscription $subscription): bool
    {
        if (!$subscription->auto_renew) {
            return false;
        }

        DB::beginTransaction();

        try {
            $listing = $subscription->listing;
            $price = $this->calculatePrice($listing, $subscription->billing_cycle);
            $commission = $price * ($listing->commission_rate / 100);
            $ownerEarning = $price - $commission;

            $subscription->update([
                'status' => 'active',
                'current_period_start' => now(),
                'current_period_end' => $this->calculatePeriodEnd($subscription->billing_cycle),
                'next_billing_date' => $this->calculateNextBillingDate($subscription->billing_cycle),
                'usage_count' => 0, // Reset usage
            ]);

            $subscription->increment('total_spent', $price);

            // Record payment (integrate with payment gateway)
            // $this->recordPayment($subscription, $price, $commission, $ownerEarning);

            DB::commit();

            Log::info("Subscription renewed", [
                'subscription_id' => $subscription->id,
                'amount' => $price,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Subscription renewal failed", [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel(BotRentalSubscription $subscription, string $reason = null): void
    {
        $subscription->cancel($reason);

        Log::info("Subscription cancelled", [
            'subscription_id' => $subscription->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Process due renewals
     */
    public function processDueRenewals(): array
    {
        $subscriptions = BotRentalSubscription::dueForRenewal()->get();
        $results = [];

        foreach ($subscriptions as $subscription) {
            $success = $this->renew($subscription);
            $results[] = [
                'subscription_id' => $subscription->id,
                'success' => $success,
            ];
        }

        return $results;
    }

    /**
     * Get subscription statistics
     */
    public function getStatistics(int $listingId = null): array
    {
        $query = BotRentalSubscription::query();

        if ($listingId) {
            $query->where('listing_id', $listingId);
        }

        $total = $query->count();
        $active = $query->where('status', 'active')->count();
        $trial = $query->where('status', 'trial')->count();
        $cancelled = $query->where('status', 'cancelled')->count();
        $totalRevenue = $query->sum('total_spent');
        $avgSubscriptionValue = $query->avg('total_spent');
        $churnRate = $total > 0 ? ($cancelled / $total) * 100 : 0;

        return [
            'total_subscriptions' => $total,
            'active' => $active,
            'trial' => $trial,
            'cancelled' => $cancelled,
            'total_revenue' => $totalRevenue,
            'avg_subscription_value' => round($avgSubscriptionValue ?? 0, 2),
            'churn_rate' => round($churnRate, 2),
            'retention_rate' => round(100 - $churnRate, 2),
        ];
    }

    /**
     * Track usage
     */
    public function trackUsage(BotRentalSubscription $subscription): void
    {
        $subscription->incrementUsage();
    }
}
