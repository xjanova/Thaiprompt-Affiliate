<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\EarningsLedger;
use App\Models\PayoutSetting;
use App\Models\WalletDebt;
use App\Models\MlmGlobalSetting;
use App\Models\MlmProductPv;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * OrderDistributionService
 *
 * จัดการการแบ่งเงินจาก Order - หักค่า Fee, VAT แล้วโอนให้ Seller
 *
 * ⚠️ IMPORTANT: MLM Commission ถูกหักจาก Platform Fee ไม่ใช่จาก Seller
 * Seller ต้องได้รับเงินตามที่ตั้งราคาไว้ (หลังหัก Fee + VAT เท่านั้น)
 *
 * Flow การคำนวณ:
 * 1. หัก Platform Fee จาก Seller → platform_fee wallet
 * 2. หัก VAT จาก Seller → vat wallet
 * 3. Seller ได้รับ: grossAmount - platformFee - VAT (ไม่หัก MLM)
 * 4. แยก MLM Commission จาก Platform Fee → mlm_pool wallet
 *    (platformFeeNet = platformFee - mlmCommission)
 * 5. คำนวณและสร้าง Commission ให้ uplines:
 *    - Direct Referral Bonus → original_sponsor (ผู้แนะนำตรงจริงๆ)
 *    - Unilevel Commission → unilevel_sponsor (สายงาน)
 *    - Binary Commission → binary_sponsor (ตำแหน่ง binary)
 */
class OrderDistributionService
{
    protected PlatformRevenueService $revenueService;
    protected MlmCommissionService $mlmCommissionService;

    public function __construct()
    {
        $this->revenueService = new PlatformRevenueService();
        $this->mlmCommissionService = new MlmCommissionService();
    }

    /**
     * ประมวลผลการแบ่งเงินจาก Order ที่ชำระเงินแล้ว
     *
     * @param Order $order
     * @return array ผลลัพธ์การแบ่งเงิน
     */
    public function processOrderDistribution(Order $order): array
    {
        // ตรวจสอบว่าชำระเงินแล้ว
        if ($order->payment_status !== 'paid') {
            throw new \Exception('Order ยังไม่ได้ชำระเงิน');
        }

        return DB::transaction(function () use ($order) {
            $results = [
                'order_id' => $order->id,
                'total_amount' => $order->total_amount,
                'distributions' => [],
                'platform_collections' => [],
                'seller_earnings' => [],
                'mlm_commissions' => [],
            ];

            // แยกรายการตาม Seller
            $itemsBySeller = $order->items->groupBy('seller_id');

            foreach ($itemsBySeller as $sellerId => $items) {
                // ถ้า seller_id = Official Shop Seller ID → สินค้าของ Admin (Official Shop)
                if ($this->isOfficialShopSeller($sellerId)) {
                    $adminResult = $this->processAdminShopItems($order, $items);
                    $results['distributions'][] = $adminResult;
                    continue;
                }

                $sellerResult = $this->processSellerItems($order, $sellerId, $items);
                $results['distributions'][] = $sellerResult;
            }

            // ตรวจสอบบริการของ Admin (ค่าสมัคร, ค่าบริการ, etc.)
            $adminServices = $order->items->filter(fn($item) => $item->item_type === 'admin_service');
            if ($adminServices->isNotEmpty()) {
                $serviceResult = $this->processAdminServices($order, $adminServices);
                $results['distributions'][] = $serviceResult;
            }

            // คำนวณ MLM Commission (ถ้าเปิดใช้งาน)
            if (MlmGlobalSetting::get('mlm_enabled', false)) {
                $mlmResult = $this->processMlmCommissions($order);
                $results['mlm_commissions'] = $mlmResult;
            }

            Log::info('Order distribution processed', [
                'order_id' => $order->id,
                'results' => $results,
            ]);

            return $results;
        });
    }

    /**
     * ประมวลผลรายการของ Seller
     *
     * @param Order $order
     * @param int $sellerId
     * @param \Illuminate\Support\Collection $items
     * @return array
     */
    protected function processSellerItems(Order $order, int $sellerId, $items): array
    {
        $seller = User::find($sellerId);
        if (!$seller) {
            return ['error' => "Seller #{$sellerId} not found"];
        }

        // คำนวณยอดรวม
        $grossAmount = $items->sum('total');
        $platformFee = $items->sum('commission_amount');
        $vatAmount = $this->calculateVat($items);
        $mlmCommission = $this->calculateMlmCommissionFromItems($items);

        // ⚠️ CRITICAL: MLM Commission ต้องหักจาก Platform Fee เท่านั้น ไม่ใช่จาก Seller
        // Seller ต้องได้เงินตามที่ตั้งราคาไว้ (หลังหัก Fee + VAT)
        // ถ้า MLM > Platform Fee → cap ไว้ที่ Platform Fee (ป้องกัน overpay)
        if ($mlmCommission > $platformFee) {
            Log::warning('MLM Commission เกิน Platform Fee - cap ไว้ที่ Platform Fee', [
                'order_id' => $order->id,
                'seller_id' => $sellerId,
                'platform_fee' => $platformFee,
                'mlm_commission_original' => $mlmCommission,
                'mlm_commission_capped' => $platformFee,
            ]);
            $mlmCommission = $platformFee;
        }

        // Platform Fee สุทธิ = Platform Fee - MLM Commission (ที่แยกไป MLM Pool)
        $platformFeeNet = $platformFee - $mlmCommission;

        // ยอดสุทธิที่ Seller จะได้รับ (ไม่หัก MLM Commission)
        $netAmount = $grossAmount - $platformFee - $vatAmount;

        // ตรวจสอบหนี้
        $debtDeduction = 0;
        $totalDebt = WalletDebt::getTotalDebtForUser($sellerId);
        if ($totalDebt > 0 && $netAmount > 0) {
            $debtResult = WalletDebt::deductAllDebts($sellerId, $netAmount);
            $debtDeduction = $debtResult['total_deducted'];
            $netAmount = $debtResult['remaining'];
        }

        // เก็บค่า Fee สุทธิเข้า Platform Wallet (หลังหัก MLM แล้ว)
        if ($platformFeeNet > 0) {
            $this->revenueService->collectPlatformFee(
                $platformFeeNet,
                'Order',
                $order->id,
                $sellerId,
                [
                    'order_number' => $order->order_number,
                    'items_count' => $items->count(),
                    'gross_amount' => $grossAmount,
                    'original_platform_fee' => $platformFee,
                    'mlm_commission_deducted' => $mlmCommission,
                ]
            );
        }

        // เก็บ VAT เข้า Platform Wallet
        if ($vatAmount > 0) {
            $this->revenueService->collectVat(
                $vatAmount,
                'Order',
                $order->id,
                [
                    'order_number' => $order->order_number,
                    'seller_id' => $sellerId,
                ]
            );
        }

        // เก็บ MLM Commission เข้า Pool (มาจาก Platform Fee ไม่ใช่จาก Seller)
        if ($mlmCommission > 0) {
            $this->revenueService->collectMlmPool(
                $mlmCommission,
                'Order',
                $order->id,
                [
                    'order_number' => $order->order_number,
                    'seller_id' => $sellerId,
                    'funded_from' => 'platform_fee',
                    'original_platform_fee' => $platformFee,
                    'platform_fee_net' => $platformFeeNet,
                ]
            );
        }

        // สร้างบันทึกรายได้ใน Earnings Ledger
        $payoutSetting = PayoutSetting::getSellerSetting();
        $availableAt = now()->addDays($payoutSetting->holding_days ?? 0);

        $earningEntry = EarningsLedger::create([
            'user_id' => $sellerId,
            'earning_type' => EarningsLedger::TYPE_SELLER_SALE,
            'source_type' => 'Order',
            'source_id' => $order->id,
            'gross_amount' => $grossAmount,
            'platform_fee' => $platformFee,
            'vat_amount' => $vatAmount,
            'mlm_commission' => $mlmCommission,
            'debt_deduction' => $debtDeduction,
            'net_amount' => $netAmount,
            'status' => EarningsLedger::STATUS_PENDING,
            'available_at' => $availableAt,
            'description' => "รายได้จากการขาย Order #{$order->order_number}",
            'breakdown' => [
                'items' => $items->map(fn($item) => [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total' => $item->total,
                    'commission_amount' => $item->commission_amount,
                    'pv_value' => $item->pv_value ?? 0,
                ])->toArray(),
                'calculations' => [
                    'gross_amount' => $grossAmount,
                    'platform_fee_rate' => $items->first()->commission_rate ?? 0,
                    'platform_fee_original' => $platformFee,
                    'platform_fee_net' => $platformFeeNet,
                    'vat_rate' => 7,
                    'vat_amount' => $vatAmount,
                    'mlm_commission' => $mlmCommission,
                    'mlm_funded_from' => 'platform_fee',
                    'debt_deduction' => $debtDeduction,
                    'net_amount' => $netAmount,
                ],
            ],
        ]);

        return [
            'seller_id' => $sellerId,
            'seller_name' => $seller->name,
            'gross_amount' => $grossAmount,
            'platform_fee_original' => $platformFee,
            'platform_fee_net' => $platformFeeNet,
            'vat_amount' => $vatAmount,
            'mlm_commission' => $mlmCommission,
            'mlm_funded_from' => 'platform_fee',
            'debt_deduction' => $debtDeduction,
            'net_amount' => $netAmount,
            'earning_entry_id' => $earningEntry->id,
            'available_at' => $availableAt->toDateTimeString(),
        ];
    }

    /**
     * คำนวณ VAT
     *
     * @param \Illuminate\Support\Collection $items
     * @return float
     */
    protected function calculateVat($items): float
    {
        $vatRate = MlmGlobalSetting::get('vat_percentage', 7) / 100;
        $vatableAmount = $items->sum('total');

        // VAT = ยอดรวม * 7 / 107 (สำหรับราคารวม VAT แล้ว)
        return round($vatableAmount * $vatRate / (1 + $vatRate), 4);
    }

    /**
     * คำนวณ MLM Commission (ค่าการตลาด/PV) จากรายการสินค้า - หักจาก Platform Fee (ไม่ใช่จาก Seller)
     *
     * แก้ Bug: ใช้ค่าจากแอดมิน (MlmProductPv / MlmGlobalSetting) เป็นหลัก
     * แทนการใช้ item.pv_value เป็น percentage ตรงๆ
     *
     * สูตรคำนวณ: PV points × commission_per_pv = จำนวนเงิน THB ที่หัก
     * - PV จาก MlmProductPv (absolute) หรือ price × global_pv_rate
     * - commission_per_pv จาก MlmGlobalSetting (อัตราแปลง PV→บาท)
     *
     * @param \Illuminate\Support\Collection $items
     * @return float จำนวนเงิน (THB) ที่หักเป็นค่าการตลาด PV
     */
    protected function calculateMlmCommissionFromItems($items): float
    {
        // ดึงค่าจาก Global Settings (แอดมินตั้งค่า)
        $globalPvRate = (float) MlmGlobalSetting::get('global_pv_rate', 1);
        $commissionPerPv = (float) MlmGlobalSetting::get('commission_per_pv', 1);
        $defaultPlanId = (int) MlmGlobalSetting::get('default_mlm_plan_id', 1);

        $totalPvAmount = 0;

        foreach ($items as $item) {
            // ลำดับ 1: ดึงค่า PV จาก MlmProductPv (แอดมินกำหนดต่อสินค้า)
            $productPv = MlmProductPv::where('product_id', $item->product_id)
                ->where('mlm_plan_id', $defaultPlanId)
                ->first();

            $itemPv = 0;

            if ($productPv && $productPv->pv_value > 0) {
                // ใช้ค่าจากแอดมิน: PV absolute × จำนวน
                $itemPv = $productPv->pv_value * ($item->quantity ?? 1);
            } elseif ($globalPvRate > 0) {
                // ลำดับ 2: ใช้ global_pv_rate × ราคาสินค้า
                $itemPv = $item->total * $globalPvRate;
            }

            // คำนวณจำนวนเงิน THB ที่หัก: PV × commission_per_pv
            if ($itemPv > 0) {
                $totalPvAmount += $itemPv * $commissionPerPv;
            }
        }

        if ($totalPvAmount <= 0) {
            return 0;
        }

        return round($totalPvAmount, 4);
    }

    /**
     * ประมวลผล MLM Commissions
     *
     * คำนวณและสร้าง commission ให้ uplines ตามประเภท:
     * - Direct Referral Bonus: จ่ายให้ original_sponsor (ผู้แนะนำตรงจริงๆ)
     * - Unilevel Commission: จ่ายให้ unilevel_sponsor หลายชั้น (พร้อม rollup ถ้า inactive)
     * - Binary Commission: จ่ายให้ binary_sponsor (ตาม matching/pairing)
     *
     * @param Order $order
     * @return array ผลลัพธ์การคำนวณ commission
     */
    protected function processMlmCommissions(Order $order): array
    {
        try {
            // คำนวณ PV รวมจาก Order items
            $pvData = $this->calculateTotalPvFromOrder($order);

            // ตรวจสอบว่ามีการเปิดใช้ระบบ Genealogy หรือไม่
            $genealogyEnabled = MlmGlobalSetting::get('genealogy_enabled', true);

            if (!$genealogyEnabled && $pvData['total_pv'] <= 0) {
                Log::debug('MLM Commission skipped: genealogy disabled and no PV', [
                    'order_id' => $order->id,
                ]);
                return [
                    'success' => true,
                    'direct_referral' => null,
                    'unilevel' => [],
                    'binary' => [],
                    'total_pv' => 0,
                    'message' => 'ไม่มี PV และระบบ Genealogy ปิดอยู่',
                ];
            }

            // แก้ Bug PV-2: บันทึก PV Transaction + อัพเดท member PV
            // (ย้ายมาจาก MlmCalculationService ที่เคยเรียกซ้ำ)
            if ($pvData['total_pv'] > 0) {
                $buyerMember = \App\Models\MlmMember::where('user_id', $order->user_id)
                    ->where('status', 'active')
                    ->first();

                if ($buyerMember) {
                    $pvService = new MlmPvService();
                    $pvService->recordPvTransaction($buyerMember, $order, $pvData);
                    $buyerMember->increment('total_pv', $pvData['total_pv']);
                    $buyerMember->update(['last_purchase_at' => now()]);
                }
            }

            // เรียกใช้ MlmCommissionService เพื่อคำนวณ commission ทั้งหมด
            $result = $this->mlmCommissionService->processOrderCommissions($order, $pvData);

            // Log ผลลัพธ์
            Log::info('MLM Commissions processed for order', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total_pv' => $pvData['total_pv'],
                'pv_amount_thb' => $pvData['pv_amount_thb'],
                'has_direct_referral' => $result['direct_referral'] !== null,
                'unilevel_count' => count($result['unilevel']),
                'binary_count' => count($result['binary']),
            ]);

            return [
                'success' => true,
                'direct_referral' => $result['direct_referral'],
                'unilevel' => $result['unilevel'],
                'binary' => $result['binary'],
                'total_pv' => $pvData['total_pv'],
                'pv_amount_thb' => $pvData['pv_amount_thb'],
            ];

        } catch (\Exception $e) {
            Log::error('MLM Commission processing failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'direct_referral' => null,
                'unilevel' => [],
                'binary' => [],
            ];
        }
    }

    /**
     * คำนวณ PV รวมจาก Order items
     *
     * แก้ Bug: ใช้ค่าจากแอดมิน (MlmProductPv / MlmGlobalSetting) เป็นหลัก
     * แทนการใช้ item.pv_value เป็น percentage ตรงๆ
     *
     * ลำดับการดึงค่า PV:
     * 1. MlmProductPv (แอดมินกำหนดต่อสินค้า) → ค่า absolute PV (เช่น 50 PV)
     * 2. MlmGlobalSetting.global_pv_rate (อัตราส่วนกลาง) → เช่น 0.1 = 10% ของราคา
     *
     * สูตรคำนวณ:
     * - PV points: จาก MlmProductPv.pv_value × quantity, หรือ item.total × global_pv_rate
     * - PV amount THB: PV points × commission_per_pv (อัตราแปลง PV→บาท)
     *
     * @param Order $order
     * @return array ['total_pv' => float, 'pv_amount_thb' => float, 'items' => array]
     */
    protected function calculateTotalPvFromOrder(Order $order): array
    {
        // ดึงค่าจาก Global Settings (แอดมินตั้งค่า)
        $globalPvRate = (float) MlmGlobalSetting::get('global_pv_rate', 1);
        $commissionPerPv = (float) MlmGlobalSetting::get('commission_per_pv', 1);
        $defaultPlanId = (int) MlmGlobalSetting::get('default_mlm_plan_id', 1);

        $totalPv = 0;
        $pvAmountThb = 0;
        $itemsDetail = [];

        foreach ($order->items as $item) {
            // ลำดับ 1: ดึงค่า PV จาก MlmProductPv (แอดมินกำหนดต่อสินค้า)
            $productPv = MlmProductPv::where('product_id', $item->product_id)
                ->where('mlm_plan_id', $defaultPlanId)
                ->first();

            $pvSource = 'none';
            $itemPv = 0;

            if ($productPv && $productPv->pv_value > 0) {
                // ใช้ค่าจากแอดมิน: PV absolute × จำนวน
                $itemPv = $productPv->pv_value * ($item->quantity ?? 1);
                $pvSource = 'admin_product_pv';
            } elseif ($globalPvRate > 0) {
                // ลำดับ 2: ใช้ global_pv_rate × ราคาสินค้า
                $itemPv = $item->total * $globalPvRate;
                $pvSource = 'global_pv_rate';
            }

            if ($itemPv > 0) {
                $totalPv += $itemPv;

                // คำนวณค่า PV เป็นจำนวนเงิน THB (ใช้ commission_per_pv จาก Global Settings)
                $itemPvThb = $itemPv * $commissionPerPv;
                $pvAmountThb += $itemPvThb;

                $itemsDetail[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'total' => $item->total,
                    'pv_source' => $pvSource,
                    'pv_points' => round($itemPv, 2),
                    'pv_amount_thb' => round($itemPvThb, 2),
                ];
            }
        }

        return [
            'total_pv' => round($totalPv, 2),
            'pv_amount_thb' => round($pvAmountThb, 2),
            'items' => $itemsDetail,
        ];
    }

    /**
     * ประมวลผลรายการสินค้าของ Admin Shop (Official Shop)
     * สินค้าที่ seller_id = Official Seller ID คือสินค้าของ Admin
     *
     * @param Order $order
     * @param \Illuminate\Support\Collection $items
     * @return array
     */
    protected function processAdminShopItems(Order $order, $items): array
    {
        // คำนวณยอดรวม
        $grossAmount = $items->sum('total');
        $vatAmount = $this->calculateVat($items);
        $mlmCommission = $this->calculateMlmCommissionFromItems($items);

        // Admin Shop ไม่มี Platform Fee (ขายเอง)
        // ยอดสุทธิ = ยอดรวม - VAT - MLM Commission
        $netAmount = $grossAmount - $vatAmount - $mlmCommission;

        // เก็บ VAT เข้า Platform Wallet
        if ($vatAmount > 0) {
            $this->revenueService->collectVat(
                $vatAmount,
                'Order',
                $order->id,
                [
                    'order_number' => $order->order_number,
                    'source' => 'admin_shop',
                ]
            );
        }

        // เก็บ MLM Commission เข้า Pool
        if ($mlmCommission > 0) {
            $this->revenueService->collectMlmPool(
                $mlmCommission,
                'Order',
                $order->id,
                [
                    'order_number' => $order->order_number,
                    'source' => 'admin_shop',
                ]
            );
        }

        // เก็บรายได้สุทธิเข้า Admin Shop Wallet
        if ($netAmount > 0) {
            $this->revenueService->recordIncome(
                'admin_shop',
                $netAmount,
                'admin_shop_sale',
                "รายได้ขายสินค้า Order #{$order->order_number}",
                'Order',
                $order->id,
                null,
                [
                    'items_count' => $items->count(),
                    'gross_amount' => $grossAmount,
                    'vat_amount' => $vatAmount,
                    'mlm_commission' => $mlmCommission,
                    'items' => $items->map(fn($item) => [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'total' => $item->total,
                    ])->toArray(),
                ]
            );
        }

        Log::info('Admin shop items processed', [
            'order_id' => $order->id,
            'gross_amount' => $grossAmount,
            'net_amount' => $netAmount,
        ]);

        return [
            'type' => 'admin_shop',
            'gross_amount' => $grossAmount,
            'vat_amount' => $vatAmount,
            'mlm_commission' => $mlmCommission,
            'net_amount' => $netAmount,
            'items_count' => $items->count(),
        ];
    }

    /**
     * ประมวลผลบริการของ Admin (ค่าสมัคร, ค่าบริการ, ค่าอัพเกรด, etc.)
     *
     * @param Order $order
     * @param \Illuminate\Support\Collection $items
     * @return array
     */
    protected function processAdminServices(Order $order, $items): array
    {
        // คำนวณยอดรวม
        $grossAmount = $items->sum('total');
        $vatAmount = $this->calculateVat($items);

        // บริการของ Admin ไม่มี MLM Commission (ยกเว้นกำหนดเป็นพิเศษ)
        $mlmCommission = 0;

        // ตรวจสอบว่ามี PV หรือไม่ (บางบริการอาจให้ PV)
        foreach ($items as $item) {
            if (isset($item->pv_value) && $item->pv_value > 0) {
                $mlmCommission += $this->calculateMlmCommissionFromItems(collect([$item]));
            }
        }

        // ยอดสุทธิ = ยอดรวม - VAT - MLM Commission
        $netAmount = $grossAmount - $vatAmount - $mlmCommission;

        // เก็บ VAT เข้า Platform Wallet
        if ($vatAmount > 0) {
            $this->revenueService->collectVat(
                $vatAmount,
                'Order',
                $order->id,
                [
                    'order_number' => $order->order_number,
                    'source' => 'admin_services',
                ]
            );
        }

        // เก็บ MLM Commission เข้า Pool (ถ้ามี)
        if ($mlmCommission > 0) {
            $this->revenueService->collectMlmPool(
                $mlmCommission,
                'Order',
                $order->id,
                [
                    'order_number' => $order->order_number,
                    'source' => 'admin_services',
                ]
            );
        }

        // เก็บรายได้สุทธิเข้า Admin Services Wallet
        if ($netAmount > 0) {
            $this->revenueService->recordIncome(
                'admin_services',
                $netAmount,
                'admin_service_fee',
                "รายได้บริการ Order #{$order->order_number}",
                'Order',
                $order->id,
                null,
                [
                    'items_count' => $items->count(),
                    'gross_amount' => $grossAmount,
                    'vat_amount' => $vatAmount,
                    'mlm_commission' => $mlmCommission,
                    'service_types' => $items->pluck('service_type')->unique()->toArray(),
                    'items' => $items->map(fn($item) => [
                        'service_type' => $item->service_type ?? 'general',
                        'service_name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'total' => $item->total,
                    ])->toArray(),
                ]
            );
        }

        Log::info('Admin services processed', [
            'order_id' => $order->id,
            'gross_amount' => $grossAmount,
            'net_amount' => $netAmount,
        ]);

        return [
            'type' => 'admin_services',
            'gross_amount' => $grossAmount,
            'vat_amount' => $vatAmount,
            'mlm_commission' => $mlmCommission,
            'net_amount' => $netAmount,
            'items_count' => $items->count(),
            'service_types' => $items->pluck('service_type')->unique()->toArray(),
        ];
    }

    /**
     * ตรวจสอบว่า Order ถูก distribute แล้วหรือยัง
     *
     * @param Order $order
     * @return bool
     */
    public function isOrderDistributed(Order $order): bool
    {
        return EarningsLedger::where('source_type', 'Order')
            ->where('source_id', $order->id)
            ->exists();
    }

    /**
     * ดึงรายการ Order ที่รอ distribute
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getPendingOrders(int $limit = 100)
    {
        return Order::where('payment_status', 'paid')
            ->whereDoesntHave('earningsLedger')
            ->latest('paid_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Batch process pending orders
     *
     * @param int $limit
     * @return array
     */
    public function processPendingOrders(int $limit = 100): array
    {
        $orders = $this->getPendingOrders($limit);
        $results = [
            'processed' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($orders as $order) {
            try {
                $this->processOrderDistribution($order);
                $results['processed']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Order distribution failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * ตรวจสอบว่า seller_id เป็นของ Official Shop หรือไม่
     *
     * @param int|null $sellerId
     * @return bool
     */
    protected function isOfficialShopSeller($sellerId): bool
    {
        if (!$sellerId) {
            return false;
        }

        return $sellerId === Product::getOfficialSellerId();
    }
}
