<?php

namespace App\Services;

use App\Models\MlmCommission;
use App\Models\MlmGlobalSetting;
use App\Models\MlmMember;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * MlmReferralBonusService - จัดการค่าแนะนำตรง (Direct Referral Bonus)
 *
 * ค่าแนะนำตรงเป็นคนละส่วนกับ PV Commission ของ Unilevel/Binary:
 * - จ่ายให้ผู้แนะนำตรงจริงๆ (original_sponsor) ไม่ใช่ unilevel_sponsor
 * - จ่ายครั้งเดียวต่อออเดอร์ (หรือครั้งเดียวต่อสมาชิกถ้าตั้งค่า first_order_only)
 * - คำนวณจาก fixed amount หรือ percentage ของยอดสั่งซื้อ
 *
 * Settings ที่เกี่ยวข้อง (MlmGlobalSetting):
 * - direct_referral_bonus_enabled: เปิด/ปิดระบบ
 * - direct_referral_bonus_type: 'fixed' หรือ 'percentage'
 * - direct_referral_bonus_amount: จำนวนเงินคงที่ (ถ้า type = fixed)
 * - direct_referral_bonus_percentage: เปอร์เซ็นต์ (ถ้า type = percentage)
 * - direct_referral_bonus_min_order: ยอดสั่งซื้อขั้นต่ำ
 * - direct_referral_bonus_max_per_order: ค่าแนะนำสูงสุดต่อออเดอร์
 * - direct_referral_bonus_first_order_only: จ่ายเฉพาะออเดอร์แรก
 */
class MlmReferralBonusService
{
    /**
     * คำนวณและสร้าง Direct Referral Bonus สำหรับออเดอร์
     *
     * เรียกใช้หลังจากออเดอร์ได้รับชำระเงินแล้ว
     *
     * @param  Order  $order  ออเดอร์ที่ชำระแล้ว
     * @return MlmCommission|null รายการคอมมิชชันที่สร้าง หรือ null ถ้าไม่ผ่านเงื่อนไข
     */
    public function calculateReferralBonus(Order $order): ?MlmCommission
    {
        // ตรวจสอบว่าเปิดใช้ระบบผังสายเลือด (Genealogy) หรือไม่
        if (! MlmGlobalSetting::get('genealogy_enabled', true)) {
            Log::debug('Genealogy commission system disabled', ['order_id' => $order->id]);

            return null;
        }

        // ตรวจสอบว่าเปิดใช้ค่าแนะนำตรงหรือไม่
        if (! MlmGlobalSetting::get('direct_referral_bonus_enabled', true)) {
            Log::debug('Direct referral bonus disabled', ['order_id' => $order->id]);

            return null;
        }

        // หา MlmMember ของผู้ซื้อ
        $buyerMember = MlmMember::where('user_id', $order->user_id)->first();

        if (! $buyerMember) {
            Log::debug('Buyer is not MLM member', [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
            ]);

            return null;
        }

        // หา original sponsor (ผู้แนะนำตรงจริงๆ)
        $originalSponsor = $buyerMember->originalSponsor;

        if (! $originalSponsor) {
            Log::debug('No original sponsor found', [
                'order_id' => $order->id,
                'buyer_member_id' => $buyerMember->id,
            ]);

            return null;
        }

        // ตรวจสอบยอดสั่งซื้อขั้นต่ำ
        // ⚠️ แก้ไข: ใช้ total_amount แทน total (Order model มี field total_amount)
        $minOrder = MlmGlobalSetting::get('direct_referral_bonus_min_order', 0);
        if ($order->total_amount < $minOrder) {
            Log::debug('Order total below minimum for referral bonus', [
                'order_id' => $order->id,
                'order_total' => $order->total_amount,
                'min_order' => $minOrder,
            ]);

            return null;
        }

        // ตรวจสอบว่าเป็น first_order_only หรือไม่
        $firstOrderOnly = MlmGlobalSetting::get('direct_referral_bonus_first_order_only', false);
        if ($firstOrderOnly) {
            // ตรวจสอบว่ามี referral bonus สำหรับสมาชิกนี้แล้วหรือยัง
            $existingBonus = MlmCommission::where('from_member_id', $buyerMember->id)
                ->where('type', 'direct_referral')
                ->exists();

            if ($existingBonus) {
                Log::debug('Referral bonus already paid for this member (first_order_only)', [
                    'order_id' => $order->id,
                    'buyer_member_id' => $buyerMember->id,
                ]);

                return null;
            }
        }

        // ตรวจสอบว่ายังไม่เคยจ่ายค่าแนะนำสำหรับออเดอร์นี้
        $existingOrderBonus = MlmCommission::where('source_type', Order::class)
            ->where('source_id', $order->id)
            ->where('type', 'direct_referral')
            ->exists();

        if ($existingOrderBonus) {
            Log::debug('Referral bonus already paid for this order', [
                'order_id' => $order->id,
            ]);

            return null;
        }

        // คำนวณจำนวนเงินค่าแนะนำ
        // ⚠️ แก้ไข: ใช้ total_amount แทน total
        $bonusAmount = $this->calculateBonusAmount($order->total_amount);

        if ($bonusAmount <= 0) {
            Log::debug('Calculated bonus amount is zero', [
                'order_id' => $order->id,
                'order_total' => $order->total_amount,
            ]);

            return null;
        }

        // สร้างรายการ commission
        return $this->createReferralCommission($originalSponsor, $buyerMember, $order, $bonusAmount);
    }

    /**
     * คำนวณจำนวนเงินค่าแนะนำ
     *
     * @param  float  $orderTotal  ยอดสั่งซื้อ
     * @return float จำนวนเงินค่าแนะนำ
     */
    protected function calculateBonusAmount(float $orderTotal): float
    {
        $bonusType = MlmGlobalSetting::get('direct_referral_bonus_type', 'fixed');

        if ($bonusType === 'fixed') {
            $amount = (float) MlmGlobalSetting::get('direct_referral_bonus_amount', 100);
        } else {
            // percentage
            $percentage = (float) MlmGlobalSetting::get('direct_referral_bonus_percentage', 5);
            $amount = ($orderTotal * $percentage) / 100;
        }

        // จำกัดค่าสูงสุดต่อออเดอร์
        $maxPerOrder = MlmGlobalSetting::get('direct_referral_bonus_max_per_order');
        if ($maxPerOrder !== null && $amount > $maxPerOrder) {
            $amount = (float) $maxPerOrder;
        }

        return round($amount, 2);
    }

    /**
     * สร้างรายการ commission สำหรับค่าแนะนำตรง
     *
     * @param  MlmMember  $sponsor  ผู้แนะนำที่จะได้รับค่าแนะนำ
     * @param  MlmMember  $buyer  ผู้ซื้อ (ลูกทีมตรง)
     * @param  Order  $order  ออเดอร์
     * @param  float  $amount  จำนวนเงิน
     */
    protected function createReferralCommission(
        MlmMember $sponsor,
        MlmMember $buyer,
        Order $order,
        float $amount
    ): MlmCommission {
        return DB::transaction(function () use ($sponsor, $buyer, $order, $amount) {
            // ⚠️ แก้ไข: ใช้ total_amount แทน total
            $commission = MlmCommission::create([
                'mlm_member_id' => $sponsor->id,
                'mlm_plan_id' => $sponsor->mlm_plan_id,
                'user_id' => $sponsor->user_id,
                'from_member_id' => $buyer->id,
                'source_type' => Order::class,
                'source_id' => $order->id,
                'type' => 'direct_referral',
                'level' => 1, // ค่าแนะนำตรงจ่ายให้ชั้นเดียวเท่านั้น
                'pv_amount' => 0, // ไม่เกี่ยวกับ PV
                'sales_amount' => $order->total_amount,
                'commission_amount' => $amount,
                'percentage' => $this->getPercentageForRecord($order->total_amount, $amount),
                'status' => 'pending',
                'notes' => 'ค่าแนะนำตรงจากออเดอร์ #'.$order->order_number,
            ]);

            Log::info('Direct referral bonus created', [
                'commission_id' => $commission->id,
                'sponsor_id' => $sponsor->id,
                'buyer_id' => $buyer->id,
                'order_id' => $order->id,
                'amount' => $amount,
            ]);

            return $commission;
        });
    }

    /**
     * คำนวณ percentage สำหรับบันทึก (เพื่อแสดงใน report)
     *
     * @param  float  $orderTotal  ยอดสั่งซื้อ
     * @param  float  $bonusAmount  จำนวนเงินค่าแนะนำ
     */
    protected function getPercentageForRecord(float $orderTotal, float $bonusAmount): float
    {
        if ($orderTotal <= 0) {
            return 0;
        }

        return round(($bonusAmount / $orderTotal) * 100, 2);
    }

    /**
     * ดึงสถิติค่าแนะนำตรงของสมาชิก
     */
    public function getReferralBonusStats(MlmMember $member): array
    {
        $query = MlmCommission::where('mlm_member_id', $member->id)
            ->where('type', 'direct_referral');

        return [
            'total_earned' => (clone $query)->sum('commission_amount'),
            'total_pending' => (clone $query)->where('status', 'pending')->sum('commission_amount'),
            'total_approved' => (clone $query)->where('status', 'approved')->sum('commission_amount'),
            'total_paid' => (clone $query)->where('status', 'paid')->sum('commission_amount'),
            'count' => (clone $query)->count(),
            'direct_referrals_count' => $member->directReferrals()->count(),
        ];
    }

    /**
     * ดึงรายการลูกทีมตรง (สำหรับแสดงผังสายเลือด)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDirectReferralsList(MlmMember $member, int $limit = 50)
    {
        return $member->directReferrals()
            ->with(['user:id,name,email,profile_picture'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * สร้าง Direct Referral Tree สำหรับการแสดงผล
     *
     * ต่างจาก Unilevel Tree ตรงที่ใช้ original_sponsor_id แทน unilevel_sponsor_id
     * แสดงว่า "ใครแนะนำใครจริงๆ" ไม่ใช่ตำแหน่งใน Unilevel Tree
     */
    public function buildGenealogyTree(MlmMember $member, int $maxDepth = 5): array
    {
        return $this->buildTreeRecursive($member, 0, $maxDepth);
    }

    /**
     * สร้าง tree แบบ recursive
     */
    protected function buildTreeRecursive(MlmMember $member, int $currentDepth, int $maxDepth): array
    {
        $node = [
            'id' => $member->id,
            'user_id' => $member->user_id,
            'member_code' => $member->member_code,
            'name' => $member->user->name ?? 'Unknown',
            'profile_picture' => $member->user->profile_picture ?? null,
            'joined_at' => $member->joined_at,
            'status' => $member->status,
            'total_direct_referrals' => $member->total_direct_referrals,
            'depth' => $currentDepth,
            'children' => [],
        ];

        // ถ้าถึง maxDepth แล้วไม่ต้องโหลด children
        if ($currentDepth >= $maxDepth) {
            return $node;
        }

        // โหลด direct referrals (ลูกทีมตรงจริงๆ)
        $directReferrals = $member->directReferrals()
            ->with('user:id,name,profile_picture')
            ->orderBy('created_at')
            ->get();

        foreach ($directReferrals as $referral) {
            $node['children'][] = $this->buildTreeRecursive($referral, $currentDepth + 1, $maxDepth);
        }

        return $node;
    }

    /**
     * นับจำนวนลูกทีมตรงทั้งหมด (recursive) สำหรับ member
     */
    public function countTotalGenealogyDescendants(MlmMember $member, int $maxDepth = 10): int
    {
        return $this->countDescendantsRecursive($member, 0, $maxDepth);
    }

    /**
     * นับ descendants แบบ recursive
     */
    protected function countDescendantsRecursive(MlmMember $member, int $currentDepth, int $maxDepth): int
    {
        if ($currentDepth >= $maxDepth) {
            return 0;
        }

        $directCount = $member->directReferrals()->count();
        $totalCount = $directCount;

        // นับต่อลงไปในแต่ละ direct referral
        foreach ($member->directReferrals as $referral) {
            $totalCount += $this->countDescendantsRecursive($referral, $currentDepth + 1, $maxDepth);
        }

        return $totalCount;
    }
}
