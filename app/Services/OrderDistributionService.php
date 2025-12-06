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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * OrderDistributionService
 *
 * จัดการการแบ่งเงินจาก Order - หักค่า Fee, VAT, MLM Commission แล้วโอนให้ Seller
 */
class OrderDistributionService
{
    protected PlatformRevenueService $revenueService;

    public function __construct()
    {
        $this->revenueService = new PlatformRevenueService();
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

        // ยอดสุทธิที่ Seller จะได้รับ
        $netAmount = $grossAmount - $platformFee - $vatAmount - $mlmCommission;

        // ตรวจสอบหนี้
        $debtDeduction = 0;
        $totalDebt = WalletDebt::getTotalDebtForUser($sellerId);
        if ($totalDebt > 0 && $netAmount > 0) {
            $debtResult = WalletDebt::deductAllDebts($sellerId, $netAmount);
            $debtDeduction = $debtResult['total_deducted'];
            $netAmount = $debtResult['remaining'];
        }

        // เก็บค่า Fee เข้า Platform Wallet
        if ($platformFee > 0) {
            $this->revenueService->collectPlatformFee(
                $platformFee,
                'Order',
                $order->id,
                $sellerId,
                [
                    'order_number' => $order->order_number,
                    'items_count' => $items->count(),
                    'gross_amount' => $grossAmount,
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

        // เก็บ MLM Commission เข้า Pool
        if ($mlmCommission > 0) {
            $this->revenueService->collectMlmPool(
                $mlmCommission,
                'Order',
                $order->id,
                [
                    'order_number' => $order->order_number,
                    'seller_id' => $sellerId,
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
                    'platform_fee' => $platformFee,
                    'vat_rate' => 7,
                    'vat_amount' => $vatAmount,
                    'mlm_commission' => $mlmCommission,
                    'debt_deduction' => $debtDeduction,
                    'net_amount' => $netAmount,
                ],
            ],
        ]);

        return [
            'seller_id' => $sellerId,
            'seller_name' => $seller->name,
            'gross_amount' => $grossAmount,
            'platform_fee' => $platformFee,
            'vat_amount' => $vatAmount,
            'mlm_commission' => $mlmCommission,
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
     * คำนวณ MLM Commission จากรายการสินค้า
     *
     * @param \Illuminate\Support\Collection $items
     * @return float
     */
    protected function calculateMlmCommissionFromItems($items): float
    {
        $totalPv = 0;
        $commissionPerPv = MlmGlobalSetting::get('commission_per_pv', 1);

        foreach ($items as $item) {
            // ดึง PV จาก product หรือ item
            $pvValue = $item->pv_value ?? $item->product?->pv_value ?? 0;
            $totalPv += $pvValue * $item->quantity;
        }

        // ไม่มี PV = ไม่มี MLM Commission
        if ($totalPv <= 0) {
            return 0;
        }

        // คำนวณ commission = PV × commission_per_pv × max_commission_percentage
        $maxCommissionPercentage = MlmGlobalSetting::get('max_commission_percentage', 50) / 100;
        $baseCommission = $totalPv * $commissionPerPv;

        return round($baseCommission * $maxCommissionPercentage, 4);
    }

    /**
     * ประมวลผล MLM Commissions
     *
     * @param Order $order
     * @return array
     */
    protected function processMlmCommissions(Order $order): array
    {
        // TODO: เรียกใช้ MlmCommissionService หรือ MlmCalculationService
        // เพื่อคำนวณและสร้าง commission ให้ uplines
        // ตอนนี้ return empty array ก่อน
        return [];
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
