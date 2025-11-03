<?php

namespace App\Services;

use App\Models\MlmMember;
use App\Models\MlmCommission;
use App\Models\MlmPlan;
use App\Models\Order;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MlmCalculationService
{
    protected $unilevelService;
    protected $binaryService;
    protected $pvService;

    public function __construct()
    {
        $this->unilevelService = new MlmUnilevelService();
        $this->binaryService = new MlmBinaryService();
        $this->pvService = new MlmPvService();
    }

    /**
     * Process commissions for an order
     */
    public function processOrderCommissions(Order $order)
    {
        DB::beginTransaction();

        try {
            $user = $order->user;
            if (!$user) {
                throw new \Exception('Order has no user');
            }

            // Get all MLM memberships for this user
            $mlmMembers = MlmMember::where('user_id', $user->id)
                ->where('status', 'active')
                ->get();

            foreach ($mlmMembers as $member) {
                $plan = $member->plan;

                if (!$plan || !$plan->is_active) {
                    continue;
                }

                // Calculate PV for this order
                $pvData = $this->pvService->calculateOrderPv($order, $plan);

                if ($pvData['total_pv'] > 0) {
                    // Record PV transaction
                    $this->pvService->recordPvTransaction($member, $order, $pvData);

                    // Update member PV
                    $member->increment('total_pv', $pvData['total_pv']);
                    $member->update(['last_purchase_at' => now()]);

                    // Process commissions based on plan type
                    if ($plan->type === 'unilevel' || $plan->type === 'hybrid') {
                        $this->unilevelService->calculateUnilevelCommissions(
                            $member,
                            $order,
                            $pvData
                        );
                    }

                    if ($plan->type === 'binary' || $plan->type === 'hybrid') {
                        $this->binaryService->calculateBinaryCommissions(
                            $member,
                            $order,
                            $pvData
                        );
                    }
                }
            }

            DB::commit();
            Log::info('MLM commissions processed for order', ['order_id' => $order->id]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing MLM commissions', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Approve pending commissions
     */
    public function approvePendingCommissions($commissionIds = null)
    {
        $query = MlmCommission::where('status', 'pending');

        if ($commissionIds) {
            $query->whereIn('id', $commissionIds);
        }

        $commissions = $query->get();

        foreach ($commissions as $commission) {
            $commission->approve();
        }

        return $commissions->count();
    }

    /**
     * Pay approved commissions
     */
    public function payApprovedCommissions($commissionIds = null)
    {
        DB::beginTransaction();

        try {
            $query = MlmCommission::where('status', 'approved')
                ->with(['user', 'user.wallet']);

            if ($commissionIds) {
                $query->whereIn('id', $commissionIds);
            }

            $commissions = $query->get();
            $paidCount = 0;

            foreach ($commissions as $commission) {
                $user = $commission->user;

                if (!$user || !$user->wallet) {
                    continue;
                }

                // Create wallet transaction
                $walletTransaction = WalletTransaction::create([
                    'wallet_id' => $user->wallet->id,
                    'user_id' => $user->id,
                    'type' => 'commission',
                    'amount' => $commission->commission_amount,
                    'balance_after' => $user->wallet->balance + $commission->commission_amount,
                    'description' => 'MLM Commission: ' . $commission->type,
                    'status' => 'completed',
                    'metadata' => json_encode([
                        'mlm_commission_id' => $commission->id,
                        'commission_type' => $commission->type,
                    ]),
                ]);

                // Update wallet balance
                $user->wallet->increment('balance', $commission->commission_amount);

                // Mark commission as paid
                $commission->markAsPaid($walletTransaction->id);

                // Update member earnings
                $commission->member->increment('total_earnings', $commission->commission_amount);

                $paidCount++;
            }

            DB::commit();
            Log::info('MLM commissions paid', ['count' => $paidCount]);

            return $paidCount;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error paying MLM commissions', ['error' => $e->getMessage()]);

            throw $e;
        }
    }

    /**
     * Calculate commission preview for a member
     */
    public function calculateCommissionPreview(MlmMember $member, $orderAmount, $orderPv = null)
    {
        $plan = $member->plan;

        if (!$orderPv) {
            $orderPv = $orderAmount * $plan->global_pv_rate;
        }

        $preview = [
            'personal_pv' => $orderPv,
            'unilevel_commission' => 0,
            'binary_commission' => 0,
            'total_commission' => 0,
        ];

        // Unilevel preview (direct level only)
        if ($plan->type === 'unilevel' || $plan->type === 'hybrid') {
            $levels = $plan->unilevel_levels ?? [];
            if (!empty($levels) && isset($levels[0])) {
                $preview['unilevel_commission'] = $orderPv * ($levels[0]['percentage'] / 100);
            }
        }

        // Binary preview (estimated)
        if ($plan->type === 'binary' || $plan->type === 'hybrid') {
            $preview['binary_commission'] = $orderPv * ($plan->binary_match_percentage / 100);
        }

        $preview['total_commission'] = $preview['unilevel_commission'] + $preview['binary_commission'];

        return $preview;
    }

    /**
     * Get member statistics
     */
    public function getMemberStatistics(MlmMember $member)
    {
        return [
            'total_earnings' => $member->total_earnings,
            'total_pv' => $member->total_pv,
            'total_team_pv' => $member->total_team_pv,
            'direct_referrals' => $member->total_direct_referrals,
            'team_members' => $member->total_team_members,
            'left_leg_pv' => $member->left_leg_pv,
            'right_leg_pv' => $member->right_leg_pv,
            'pending_commissions' => $member->commissions()->pending()->sum('commission_amount'),
            'paid_commissions' => $member->commissions()->paid()->sum('commission_amount'),
            'this_month_earnings' => $member->commissions()
                ->paid()
                ->whereMonth('paid_at', now()->month)
                ->sum('commission_amount'),
        ];
    }
}
