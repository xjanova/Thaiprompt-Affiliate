<?php

namespace App\Services;

use App\Models\MlmGlobalSetting;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\MlmProductPv;
use App\Models\MlmPvTransaction;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * MlmPvService - จัดการการคำนวณ PV
 *
 * ⚠️ IMPORTANT: Service นี้ใช้ค่าจาก MlmGlobalSetting เท่านั้น
 * ไม่ใช้ค่าจาก MlmPlan (per-plan settings) เพื่อความเป็นเอกภาพ
 */
class MlmPvService
{
    /**
     * Calculate PV for an order
     * ใช้ค่าจาก Global Settings แทน per-plan settings
     */
    public function calculateOrderPv(Order $order, MlmPlan $plan)
    {
        // ใช้ Global Settings แทน per-plan settings
        $globalPvRate = MlmGlobalSetting::get('global_pv_rate', 1);

        $totalPv = 0;
        $items = [];

        foreach ($order->items as $orderItem) {
            $product = $orderItem->product;
            $quantity = $orderItem->quantity;

            // 🚨 สินค้า affiliate (ของร้านอื่น เช่น Lazada) — PV ต้องเป็น 0 เสมอในขั้นนี้
            //
            // เหตุผล (สำคัญมาก ห้ามเอาออกโดยไม่อ่านให้จบ):
            //   เราไม่ได้ขายของชิ้นนี้เอง เราแค่ส่งลูกค้าไปซื้อที่ Lazada
            //   รายได้จริงของเรา = "ค่าคอม" ไม่กี่เปอร์เซ็นต์ และจะได้ก็ต่อเมื่อ
            //   ลูกค้าจ่ายเงินสำเร็จ + Lazada ยืนยันจ่ายค่าคอมให้เราแล้วเท่านั้น
            //
            //   ถ้าปล่อยให้ตกไป fallback ข้างล่าง (subtotal × global_pv_rate)
            //   ระบบจะคิด PV เท่ากับ "ราคาเต็มของสินค้า" เช่น จี้ 1,859 บาท → 1,859 PV
            //   ทั้งที่เราได้ค่าคอมจริงแค่ ~221 บาท = จ่ายคอม MLM เกินจริงราว 8 เท่า
            //
            //   PV ของสินค้า affiliate จะถูกให้เครดิตผ่านเส้นทางแยก (MarketplaceOrder)
            //   หลังยืนยันยอดกับรายงาน conversion ของ Lazada แล้วเท่านั้น
            if ($product && $product->is_affiliate) {
                $items[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'subtotal' => $orderItem->subtotal,
                    'pv' => 0,
                    'pv_skipped_reason' => 'affiliate_product_awaiting_settlement',
                ];

                continue;
            }

            // Get PV configuration for this product (resolver ทนทานต่อ plan-key ไม่ตรง)
            $productPv = MlmProductPv::resolveForProduct($product->id, $plan->id);

            if ($productPv) {
                $itemPv = $productPv->pv_value * $quantity;
            } else {
                // Use global PV rate from MlmGlobalSetting
                $itemPv = $orderItem->subtotal * $globalPvRate;
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

        // ⚠️ แก้ไข: ใช้ total_amount แทน total (Order model มี field total_amount)
        return MlmPvTransaction::create([
            'mlm_member_id' => $member->id,
            'mlm_plan_id' => $member->mlm_plan_id,
            'transaction_type' => 'purchase',
            'order_id' => $order->id,
            'pv_amount' => $pvData['total_pv'],
            'sales_amount' => $order->total_amount,
            'previous_balance' => $previousBalance,
            'new_balance' => $newBalance,
            'attributed_leg' => $attributedLeg,
            'description' => 'PV from Order #'.$order->id,
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
     * ใช้ค่าจาก Global Settings แทน per-plan settings
     */
    public function getProductPvConfig(Product $product, MlmPlan $plan)
    {
        // ใช้ Global Settings แทน per-plan settings
        $globalPvRate = MlmGlobalSetting::get('global_pv_rate', 1);
        $commissionPerPv = MlmGlobalSetting::get('commission_per_pv', 1);

        // 🔗 สินค้า affiliate — PV มาจาก "ค่าคอมที่เราจะได้จริง" ไม่ใช่ราคาสินค้า
        //    (products.pv_value ถูกคำนวณตอนนำเข้า = ค่าคอม × ปันผล% ÷ commission_per_pv)
        //    ห้ามใช้ fallback ราคาเต็ม × global_pv_rate เพราะเราไม่ได้รับเงินเต็มราคา
        //    และเป็นยอด "รอยืนยัน" เสมอ จนกว่า Lazada จะยืนยันว่าจ่ายค่าคอมให้เราแล้ว
        if ($product->is_affiliate) {
            $pv = (float) ($product->pv_value ?? 0);

            return [
                'pv_value' => $pv,
                'use_global_rate' => false,
                'commission_preview' => $pv * $commissionPerPv,
                'show_pv' => true,
                'show_commission' => true,
                'is_affiliate' => true,
                'pending_settlement' => true, // ได้รับจริงหลัง Lazada ยืนยันยอดเท่านั้น
            ];
        }

        $config = MlmProductPv::where('product_id', $product->id)
            ->where('mlm_plan_id', $plan->id)
            ->first();

        if (! $config) {
            // Return default config using global settings
            return [
                'pv_value' => $product->price * $globalPvRate,
                'use_global_rate' => true,
                'commission_preview' => $product->price * $globalPvRate * $commissionPerPv,
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
     * ใช้ค่าจาก Global Settings แทน per-plan settings
     *
     * แก้ Bug: เพิ่ม commission_per_pv ในสูตรคำนวณ
     * สูตรจริง: PV × level_percentage% × commission_per_pv
     * ตรงกับ MlmCommissionService::calculateUnilevelWithRollup() line 179
     */
    public function calculateProductCommissionPreview(Product $product, MlmPlan $plan, $quantity = 1)
    {
        $pvConfig = $this->getProductPvConfig($product, $plan);
        $totalPv = $pvConfig['pv_value'] * $quantity;

        // ดึงค่าจาก Global Settings
        $unilevelEnabled = MlmGlobalSetting::get('unilevel_enabled', true);
        $binaryEnabled = MlmGlobalSetting::get('binary_enabled', true);
        $levels = MlmGlobalSetting::get('unilevel_levels', []);
        $binaryMatchPercentage = MlmGlobalSetting::get('binary_match_percentage', 50);
        // แก้ Bug: ดึง commission_per_pv สำหรับแปลง PV → บาท
        $commissionPerPv = (float) MlmGlobalSetting::get('commission_per_pv', 1);

        $preview = [
            'pv' => $totalPv,
            'commission_per_pv' => $commissionPerPv,
            'commissions' => [],
        ];

        // Unilevel preview
        if ($unilevelEnabled && ($plan->type === 'unilevel' || $plan->type === 'hybrid')) {
            foreach ($levels as $index => $level) {
                $percentage = $level['percentage'] ?? 0;
                // แก้ Bug: เพิ่ม × commission_per_pv ให้ตรงกับสูตรจริง
                $commission = ($totalPv * $percentage) / 100 * $commissionPerPv;

                $preview['commissions'][] = [
                    'level' => $index + 1,
                    'type' => 'unilevel',
                    'percentage' => $percentage,
                    'amount' => $commission,
                ];
            }
        }

        // Binary preview
        if ($binaryEnabled && ($plan->type === 'binary' || $plan->type === 'hybrid')) {
            // แก้ Bug: เพิ่ม × commission_per_pv ให้ตรงกับสูตรจริง
            $binaryCommission = $totalPv * ($binaryMatchPercentage / 100) * $commissionPerPv;

            $preview['commissions'][] = [
                'type' => 'binary',
                'percentage' => $binaryMatchPercentage,
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
            // แก้ Bug PV-4: เพิ่ม whereYear ป้องกันการรวมข้อมูลข้ามปี
            'this_month_pv' => MlmPvTransaction::where('mlm_member_id', $member->id)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->sum('pv_amount'),
            'last_month_pv' => MlmPvTransaction::where('mlm_member_id', $member->id)
                ->whereYear('created_at', now()->subMonth()->year)
                ->whereMonth('created_at', now()->subMonth()->month)
                ->sum('pv_amount'),
            'left_leg_pv' => $member->left_leg_pv,
            'right_leg_pv' => $member->right_leg_pv,
            'carried_left_pv' => $member->carried_left_pv,
            'carried_right_pv' => $member->carried_right_pv,
        ];
    }
}
