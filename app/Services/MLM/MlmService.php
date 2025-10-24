<?php

namespace App\Services\MLM;

use App\Models\User;
use App\Models\MlmNetwork;
use App\Models\MlmGenealogy;
use App\Models\Commission;
use App\Models\Order;
use App\Models\CommissionSetting;
use Illuminate\Support\Facades\DB;

class MlmService
{
    /**
     * Register user in MLM network
     */
    public function registerUser(User $user, ?User $sponsor = null): void
    {
        DB::transaction(function () use ($user, $sponsor) {
            // Create MLM network entry
            $level = $sponsor ? $sponsor->mlm_level + 1 : 0;

            MlmNetwork::create([
                'user_id' => $user->id,
                'sponsor_id' => $sponsor?->id,
                'parent_id' => $sponsor?->id,
                'level' => $level,
                'path' => $this->generatePath($sponsor),
            ]);

            // Build genealogy tree
            $this->buildGenealogy($user, $sponsor);

            // Update user
            $user->update([
                'sponsor_id' => $sponsor?->id,
                'mlm_level' => $level,
                'referral_code' => $user->generateReferralCode(),
            ]);
        });
    }

    /**
     * Build genealogy tree for tracking all uplines
     */
    protected function buildGenealogy(User $user, ?User $sponsor): void
    {
        if (!$sponsor) {
            return;
        }

        // Add direct sponsor
        MlmGenealogy::create([
            'ancestor_id' => $sponsor->id,
            'descendant_id' => $user->id,
            'depth' => 1,
        ]);

        // Add all uplines
        $uplines = MlmGenealogy::where('descendant_id', $sponsor->id)->get();

        foreach ($uplines as $upline) {
            MlmGenealogy::create([
                'ancestor_id' => $upline->ancestor_id,
                'descendant_id' => $user->id,
                'depth' => $upline->depth + 1,
            ]);
        }
    }

    /**
     * Generate path string for network position
     */
    protected function generatePath(?User $sponsor): string
    {
        if (!$sponsor) {
            return '0';
        }

        $sponsorNetwork = MlmNetwork::where('user_id', $sponsor->id)->first();
        return $sponsorNetwork ? $sponsorNetwork->path . '-' . $sponsor->id : '0-' . $sponsor->id;
    }

    /**
     * Calculate and distribute commissions for an order
     */
    public function distributeCommissions(Order $order): void
    {
        if (!$order->isPaid()) {
            return;
        }

        $user = $order->user;
        $commissionSettings = CommissionSetting::where('is_active', true)
            ->orderBy('level')
            ->get();

        $this->distributeUplineCommissions($user, $order, $commissionSettings);
    }

    /**
     * Distribute commissions to upline members
     */
    protected function distributeUplineCommissions(User $user, Order $order, $settings): void
    {
        $uplines = MlmGenealogy::where('descendant_id', $user->id)
            ->with('ancestor')
            ->orderBy('depth')
            ->get();

        foreach ($uplines as $genealogy) {
            $setting = $settings->firstWhere('level', $genealogy->depth);

            if (!$setting) {
                continue;
            }

            $amount = $order->total * ($setting->percentage / 100);

            Commission::create([
                'user_id' => $genealogy->ancestor_id,
                'order_id' => $order->id,
                'referrer_id' => $user->id,
                'type' => 'level_commission',
                'level' => $genealogy->depth,
                'amount' => $amount,
                'percentage' => $setting->percentage,
                'order_total' => $order->total,
                'status' => 'approved',
                'calculation_details' => [
                    'setting_name' => $setting->name,
                    'order_number' => $order->order_number,
                ],
            ]);

            // Add to wallet
            $ancestor = $genealogy->ancestor;
            if ($ancestor->wallet) {
                $ancestor->wallet->credit(
                    $amount,
                    'commission',
                    "MLM Level {$genealogy->depth} commission from order {$order->order_number}",
                    $order
                );
            }
        }
    }

    /**
     * Get downline members
     */
    public function getDownline(User $user, int $maxDepth = null): array
    {
        $query = MlmGenealogy::where('ancestor_id', $user->id)
            ->with('descendant');

        if ($maxDepth) {
            $query->where('depth', '<=', $maxDepth);
        }

        return $query->get()->groupBy('depth')->toArray();
    }

    /**
     * Calculate team sales
     */
    public function calculateTeamSales(User $user): float
    {
        $downlineIds = MlmGenealogy::where('ancestor_id', $user->id)
            ->pluck('descendant_id')
            ->toArray();

        return Order::whereIn('user_id', $downlineIds)
            ->where('payment_status', 'completed')
            ->sum('total');
    }

    /**
     * Get user statistics
     */
    public function getUserStats(User $user): array
    {
        return [
            'direct_referrals' => $user->getDirectReferralsCount(),
            'total_downline' => $user->getTotalDownlineCount(),
            'team_sales' => $this->calculateTeamSales($user),
            'personal_sales' => $user->orders()->where('payment_status', 'completed')->sum('total'),
            'total_commissions' => $user->commissions()->where('status', 'paid')->sum('amount'),
            'pending_commissions' => $user->commissions()->where('status', 'approved')->sum('amount'),
        ];
    }
}
