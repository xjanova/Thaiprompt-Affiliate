<?php

namespace App\Services;

use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\MlmPvTransaction;
use App\Models\MlmProductPv;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class MlmPvService
{
    /**
     * Calculate PV for an order
     */
    public function calculateOrderPv(Order $order, MlmPlan $plan)
    {
        $totalPv = 0;
        $items = [];

        foreach ($order->items as $orderItem) {
            $product = $orderItem->product;
            $quantity = $orderItem->quantity;

            // Get PV configuration for this product
            $productPv = MlmProductPv::where('product_id', $product->id)
                ->where('mlm_plan_id', $plan->id)
                ->first();

            if ($productPv) {
                $itemPv = $productPv->pv_value * $quantity;
            } else {
                // Use global PV rate
                $itemPv = $orderItem->subtotal * $plan->global_pv_rate;
            }

            $totalPv += $itemPv;

            $items[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'subtotal' => $orderItem->subtotal,
                'pv' => $itemPv,
            ];
        }

        return [
            'total_pv' => $totalPv,
            'items' => $items,
        ];
    }

    /**
     * Record PV transaction
     */
    public function recordPvTransaction(MlmMember $member, Order $order, array $pvData)
    {
        $previousBalance = $member->total_pv;
        $newBalance = $previousBalance + $pvData['total_pv'];

        // Determine which leg to attribute (for binary)
        $attributedLeg = $this->determineAttributedLeg($member);

        return MlmPvTransaction::create([
            'mlm_member_id' => $member->id,
            'mlm_plan_id' => $member->mlm_plan_id,
            'transaction_type' => 'purchase',
            'order_id' => $order->id,
            'pv_amount' => $pvData['total_pv'],
            'sales_amount' => $order->total,
            'previous_balance' => $previousBalance,
            'new_balance' => $newBalance,
            'attributed_leg' => $attributedLeg,
            'description' => 'PV from Order #' . $order->id,
        ]);
    }

    /**
     * Determine which binary leg to attribute PV to
     */
    protected function determineAttributedLeg(MlmMember $member)
    {
        // Personal purchase is attributed to 'personal'
        // But can be changed based on business rules
        return 'personal';
    }

    /**
     * Add manual PV adjustment
     */
    public function addPvAdjustment(MlmMember $member, $pvAmount, $description, $userId = null)
    {
        DB::beginTransaction();

        try {
            $previousBalance = $member->total_pv;
            $newBalance = $previousBalance + $pvAmount;

            $transaction = MlmPvTransaction::create([
                'mlm_member_id' => $member->id,
                'mlm_plan_id' => $member->mlm_plan_id,
                'transaction_type' => $pvAmount > 0 ? 'bonus' : 'deduction',
                'pv_amount' => abs($pvAmount),
                'previous_balance' => $previousBalance,
                'new_balance' => $newBalance,
                'description' => $description,
                'created_by' => $userId,
            ]);

            // Update member PV
            $member->increment('total_pv', $pvAmount);

            DB::commit();

            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get PV configuration for a product
     */
    public function getProductPvConfig(Product $product, MlmPlan $plan)
    {
        $config = MlmProductPv::where('product_id', $product->id)
            ->where('mlm_plan_id', $plan->id)
            ->first();

        if (!$config) {
            // Return default config
            return [
                'pv_value' => $product->price * $plan->global_pv_rate,
                'use_global_rate' => true,
                'commission_preview' => $product->price * $plan->global_pv_rate * $plan->global_commission_per_pv,
                'show_pv' => true,
                'show_commission' => true,
            ];
        }

        return [
            'pv_value' => $config->pv_value,
            'use_global_rate' => $config->use_global_rate,
            'commission_preview' => $config->calculateCommissionPreview(),
            'show_pv' => $config->show_pv_on_product_page,
            'show_commission' => $config->show_commission_preview,
            'description' => $config->getDisplayDescriptionAttribute(),
        ];
    }

    /**
     * Calculate commission preview for product
     */
    public function calculateProductCommissionPreview(Product $product, MlmPlan $plan, $quantity = 1)
    {
        $pvConfig = $this->getProductPvConfig($product, $plan);
        $totalPv = $pvConfig['pv_value'] * $quantity;

        $preview = [
            'pv' => $totalPv,
            'commissions' => [],
        ];

        // Unilevel preview
        if ($plan->type === 'unilevel' || $plan->type === 'hybrid') {
            $levels = $plan->unilevel_levels ?? [];

            foreach ($levels as $index => $level) {
                $percentage = $level['percentage'] ?? 0;
                $commission = ($totalPv * $percentage) / 100;

                $preview['commissions'][] = [
                    'level' => $index + 1,
                    'type' => 'unilevel',
                    'percentage' => $percentage,
                    'amount' => $commission,
                ];
            }
        }

        // Binary preview
        if ($plan->type === 'binary' || $plan->type === 'hybrid') {
            $binaryCommission = $totalPv * ($plan->binary_match_percentage / 100);

            $preview['commissions'][] = [
                'type' => 'binary',
                'percentage' => $plan->binary_match_percentage,
                'amount' => $binaryCommission,
                'note' => 'Estimated binary matching bonus',
            ];
        }

        return $preview;
    }

    /**
     * Get PV statistics for a member
     */
    public function getMemberPvStatistics(MlmMember $member)
    {
        return [
            'current_pv' => $member->total_pv,
            'team_pv' => $member->total_team_pv,
            'this_month_pv' => MlmPvTransaction::where('mlm_member_id', $member->id)
                ->whereMonth('created_at', now()->month)
                ->sum('pv_amount'),
            'last_month_pv' => MlmPvTransaction::where('mlm_member_id', $member->id)
                ->whereMonth('created_at', now()->subMonth()->month)
                ->sum('pv_amount'),
            'left_leg_pv' => $member->left_leg_pv,
            'right_leg_pv' => $member->right_leg_pv,
            'carried_left_pv' => $member->carried_left_pv,
            'carried_right_pv' => $member->carried_right_pv,
        ];
    }
}
