<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceCommission;
use App\Models\MarketplaceOrder;
use App\Models\User;
use App\Models\MlmPlan;
use App\Models\MlmMember;
use App\Services\MlmUnilevelService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarketplaceCommissionService
{
    /**
     * Calculate and create commissions for an order
     *
     * @param MarketplaceOrder $order
     * @return array
     */
    public function calculateCommissions(MarketplaceOrder $order): array
    {
        if ($order->commission_status !== 'pending') {
            Log::warning("Order {$order->id} is not pending commission calculation", [
                'current_status' => $order->commission_status,
            ]);
            return [];
        }

        if (!$order->affiliateUser) {
            Log::warning("Order {$order->id} has no affiliate user");
            return [];
        }

        DB::beginTransaction();

        try {
            $commissions = [];

            // 1. Calculate Direct Commission (for the user who shared the link)
            $directCommission = $this->calculateDirectCommission($order);
            if ($directCommission) {
                $commissions[] = $directCommission;
            }

            // 2. Calculate MLM Commissions (for upline sponsors)
            $mlmCommissions = $this->calculateMlmCommissions($order);
            $commissions = array_merge($commissions, $mlmCommissions);

            // Update order status
            $order->update([
                'commission_status' => 'calculated',
                'commission_calculated_at' => now(),
            ]);

            DB::commit();

            Log::info("Commissions calculated for order {$order->id}", [
                'total_commissions' => count($commissions),
                'total_amount' => array_sum(array_column($commissions, 'commission_amount')),
            ]);

            return $commissions;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Failed to calculate commissions for order {$order->id}: {$e->getMessage()}");

            return [];
        }
    }

    /**
     * Calculate direct commission for the affiliate user
     *
     * @param MarketplaceOrder $order
     * @return MarketplaceCommission|null
     */
    private function calculateDirectCommission(MarketplaceOrder $order): ?MarketplaceCommission
    {
        $commissionRate = $order->commission_rate ?? $order->account->commission_rate ?? $order->platform->default_commission_rate;

        if ($commissionRate <= 0) {
            return null;
        }

        $commissionAmount = ($order->total_amount * $commissionRate) / 100;
        $platformFee = $order->platform_fee ?? 0;
        $netCommission = $commissionAmount - $platformFee;

        return MarketplaceCommission::create([
            'user_id' => $order->affiliate_user_id,
            'order_id' => $order->id,
            'affiliate_link_id' => $order->affiliate_link_id,
            'platform_id' => $order->platform_id,
            'commission_type' => 'direct',
            'order_amount' => $order->total_amount,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'platform_fee' => $platformFee,
            'net_commission' => $netCommission,
            'currency' => $order->currency,
            'status' => 'pending',
        ]);
    }

    /**
     * Calculate MLM commissions for upline members
     *
     * @param MarketplaceOrder $order
     * @return array
     */
    private function calculateMlmCommissions(MarketplaceOrder $order): array
    {
        $commissions = [];

        // Get the affiliate user's MLM membership
        $mlmMember = MlmMember::where('user_id', $order->affiliate_user_id)
            ->where('status', 'active')
            ->first();

        if (!$mlmMember) {
            return [];
        }

        $mlmPlan = $mlmMember->mlmPlan;

        if (!$mlmPlan) {
            return [];
        }

        // Calculate Unilevel Commissions
        if ($mlmPlan->type === 'unilevel' || $mlmPlan->type === 'hybrid') {
            $unilevelCommissions = $this->calculateUnilevelCommissions($order, $mlmMember, $mlmPlan);
            $commissions = array_merge($commissions, $unilevelCommissions);
        }

        // Calculate Binary Commissions (if applicable)
        if ($mlmPlan->type === 'binary' || $mlmPlan->type === 'hybrid') {
            // Binary commissions are typically calculated based on PV
            // For marketplace orders, we can treat the order amount as PV
            // This would be handled by the existing MLM system
        }

        return $commissions;
    }

    /**
     * Calculate unilevel commissions
     *
     * @param MarketplaceOrder $order
     * @param MlmMember $member
     * @param MlmPlan $plan
     * @return array
     */
    private function calculateUnilevelCommissions(MarketplaceOrder $order, MlmMember $member, MlmPlan $plan): array
    {
        $commissions = [];
        $levels = $plan->unilevel_levels ?? [];
        $maxDepth = $plan->unilevel_max_depth ?? 10;

        // Get upline path
        $uplinePath = $this->getUnilevelUplinePath($member, $maxDepth);

        foreach ($uplinePath as $level => $uplineMember) {
            $levelNumber = $level + 1; // Level starts from 1

            if ($levelNumber > $maxDepth) {
                break;
            }

            // Get commission percentage for this level
            $commissionPercentage = $levels["level_{$levelNumber}"] ?? 0;

            if ($commissionPercentage <= 0) {
                continue;
            }

            $commissionAmount = ($order->total_amount * $commissionPercentage) / 100;

            $commissions[] = MarketplaceCommission::create([
                'user_id' => $uplineMember->user_id,
                'order_id' => $order->id,
                'affiliate_link_id' => $order->affiliate_link_id,
                'platform_id' => $order->platform_id,
                'commission_type' => 'mlm_unilevel',
                'mlm_level' => $levelNumber,
                'mlm_sponsor_id' => $level === 0 ? $order->affiliate_user_id : $uplinePath[$level - 1]->user_id,
                'order_amount' => $order->total_amount,
                'commission_rate' => $commissionPercentage,
                'commission_amount' => $commissionAmount,
                'platform_fee' => 0,
                'net_commission' => $commissionAmount,
                'currency' => $order->currency,
                'status' => 'pending',
            ]);
        }

        return $commissions;
    }

    /**
     * Get unilevel upline path
     *
     * @param MlmMember $member
     * @param int $maxDepth
     * @return array
     */
    private function getUnilevelUplinePath(MlmMember $member, int $maxDepth): array
    {
        $upline = [];
        $currentMember = $member;
        $depth = 0;

        while ($currentMember->unilevel_sponsor_id && $depth < $maxDepth) {
            $sponsor = MlmMember::where('user_id', $currentMember->unilevel_sponsor_id)
                ->where('mlm_plan_id', $currentMember->mlm_plan_id)
                ->where('status', 'active')
                ->first();

            if (!$sponsor) {
                break;
            }

            $upline[] = $sponsor;
            $currentMember = $sponsor;
            $depth++;
        }

        return $upline;
    }

    /**
     * Approve commission and add to wallet
     *
     * @param MarketplaceCommission $commission
     * @param int|null $approvedBy
     * @return bool
     */
    public function approveCommission(MarketplaceCommission $commission, ?int $approvedBy = null): bool
    {
        if ($commission->status !== 'pending') {
            return false;
        }

        DB::beginTransaction();

        try {
            $commission->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $approvedBy ?? auth()->id(),
            ]);

            DB::commit();

            Log::info("Commission {$commission->id} approved", [
                'user_id' => $commission->user_id,
                'amount' => $commission->net_commission,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Failed to approve commission {$commission->id}: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Pay commission to user wallet
     *
     * @param MarketplaceCommission $commission
     * @return bool
     */
    public function payCommission(MarketplaceCommission $commission): bool
    {
        if ($commission->status !== 'approved') {
            return false;
        }

        DB::beginTransaction();

        try {
            $user = $commission->user;
            $wallet = $user->wallet;

            if (!$wallet) {
                throw new \Exception("User {$user->id} has no wallet");
            }

            // Create wallet transaction
            $transaction = $wallet->transactions()->create([
                'user_id' => $user->id,
                'type' => 'credit',
                'amount' => $commission->net_commission,
                'balance_before' => $wallet->balance,
                'balance_after' => $wallet->balance + $commission->net_commission,
                'currency' => $commission->currency,
                'reference_type' => MarketplaceCommission::class,
                'reference_id' => $commission->id,
                'status' => 'completed',
                'metadata' => [
                    'source' => 'marketplace_commission',
                    'platform' => $commission->platform->slug,
                    'order_id' => $commission->order_id,
                ],
                'completed_at' => now(),
            ]);

            // Update wallet balance
            $wallet->increment('balance', $commission->net_commission);
            $wallet->increment('total_income', $commission->net_commission);
            $wallet->update(['last_transaction_at' => now()]);

            // Update commission status
            $commission->update([
                'status' => 'paid',
                'paid_at' => now(),
                'wallet_transaction_id' => $transaction->id,
            ]);

            DB::commit();

            Log::info("Commission {$commission->id} paid to wallet", [
                'user_id' => $user->id,
                'amount' => $commission->net_commission,
                'wallet_balance' => $wallet->balance,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Failed to pay commission {$commission->id}: {$e->getMessage()}");

            return false;
        }
    }
}
