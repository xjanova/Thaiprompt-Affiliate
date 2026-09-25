<?php

namespace App\Services;

use App\Models\EarningsLedger;
use App\Models\MlmMember;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PlatformTransaction;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\VendorStore;
use App\Services\Pricing\PriceBreakdown;
use App\Services\Pricing\PricingEngine;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * OrderDistributionService — แบ่งเงินออเดอร์ e-commerce ที่ชำระแล้ว
 *
 * ใช้สูตรเดียวของระบบคือ PricingEngine (ตัวเดียวกับตัวจำลองราคาที่ผู้ขายเห็น):
 *  - GP: อัตราที่แพลตฟอร์มกำหนด (admin_gp_rate → แพ็กเกจร้าน → ค่ากลาง ไม่ต่ำกว่าขั้นต่ำ)
 *    products.commission_rate ที่ผู้ขายส่งมาเองไม่ถูกใช้คิดเงิน
 *  - VAT 7/107 เฉพาะร้านที่จด VAT (vendor_stores.vat_registered)
 *  - ค่าแนะนำ (MLM pool) เฉพาะเมื่อเปิดระบบ MLM และสินค้าตั้ง PV ไว้ — ไม่มี PV = 0 บาท
 *    (เดิม fallback ราคา × global_pv_rate ทำให้หัก 100% ของยอดขาย — audit G2/SELLER-04)
 *
 * ผลการแบ่งเงินต่อผู้ขาย 1 ราย:
 *  - GP → กระเป๋า fee, VAT → กระเป๋า vat, ค่าแนะนำ → กระเป๋า mlm_pool
 *  - เงินสุทธิผู้ขาย + ค่าส่งส่วนของร้าน → พักใน seller_escrow + EarningsLedger (pending)
 *    แล้วจ่ายเข้า wallet ผู้ขายหลังส่งของถึง + ครบ holding days (SellerPayoutService)
 * ร้านทางการ (Official Shop) ไม่มี GP — รายได้สุทธิเข้ากระเป๋า admin_shop
 *
 * ความปลอดภัย: lock แถวออเดอร์ก่อนตรวจว่าแบ่งแล้วหรือยัง → เรียกซ้ำ/พร้อมกันก็แบ่งครั้งเดียว
 * unique key ของ earnings_ledger รวม user_id แล้ว → ออเดอร์หลายผู้ขายแบ่งได้ครบ (audit G3)
 */
class OrderDistributionService
{
    /**
     * sub_type ของรายการเงินเข้า (income) ที่เกิดจากการแบ่งเงิน — ใช้ตัดสินว่าออเดอร์ถูกแบ่งแล้ว
     * (ไม่นับรายจ่ายโปรโมชัน/คืนเงิน ที่ใช้ source Order เหมือนกัน)
     */
    public const DISTRIBUTION_SUB_TYPES = [
        'order_fee',
        'vat_collection',
        'mlm_commission_pool',
        'admin_shop_sale',
        SellerPayoutService::ESCROW_HOLD_SUB_TYPE,
    ];

    /** Setting: cron แบ่งเงินย้อนหลังหยิบเฉพาะออเดอร์ที่ชำระตั้งแต่เวลานี้ */
    public const SETTING_BACKFILL_FROM = 'money.distribution_backfill_from';

    protected PlatformRevenueService $revenueService;

    protected MlmCommissionService $mlmCommissionService;

    protected SellerPayoutService $payoutService;

    protected PlatformExpenseService $expenseService;

    public function __construct(
        ?PlatformRevenueService $revenueService = null,
        ?MlmCommissionService $mlmCommissionService = null,
        ?SellerPayoutService $payoutService = null,
        ?PlatformExpenseService $expenseService = null
    ) {
        $this->revenueService = $revenueService ?? new PlatformRevenueService;
        $this->mlmCommissionService = $mlmCommissionService ?? new MlmCommissionService;
        $this->payoutService = $payoutService ?? new SellerPayoutService;
        $this->expenseService = $expenseService ?? new PlatformExpenseService;
    }

    /**
     * แบ่งเงินออเดอร์ที่ชำระแล้ว (idempotent)
     *
     * @return array{order_id: int, total_amount: float, distributions: array, platform_collections: array, seller_earnings: array, mlm_commissions: array, skipped?: bool, reason?: string, summary?: array}
     *
     * @throws \DomainException เมื่อออเดอร์ยังไม่ชำระเงิน
     */
    public function processOrderDistribution(Order $order): array
    {
        if ($order->payment_status !== 'paid') {
            throw new \DomainException('Order ยังไม่ได้ชำระเงิน');
        }

        // สร้าง engine ใหม่ทุกออเดอร์ → ค่าตั้งล่าสุดมีผลทันทีแม้ใน process ที่รันนาน (queue/cron)
        $engine = app(PricingEngine::class);

        return DB::transaction(function () use ($order, $engine) {
            /** @var Order|null $locked */
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();

            if (! $locked || $locked->payment_status !== 'paid') {
                throw new \DomainException('Order ยังไม่ได้ชำระเงิน');
            }

            if (in_array($locked->status, ['cancelled', 'refunded'], true)) {
                return $this->skippedResult($locked, 'order_cancelled_or_refunded');
            }

            if ($this->isOrderDistributed($locked)) {
                Log::info('Order already distributed, skipping', ['order_id' => $locked->id]);

                return $this->skippedResult($locked, 'already_distributed');
            }

            $items = OrderItem::where('order_id', $locked->id)
                ->with(['product' => fn ($q) => $q->withTrashed()])
                ->orderBy('id')
                ->get();

            if ($items->isEmpty()) {
                // OrderItems อาจยังไม่ถูกสร้าง (ถูกเรียกกลาง transaction ของ checkout) → ไม่บันทึกอะไร ให้ cron ลองใหม่
                Log::warning('Order distribution: order has no items yet', ['order_id' => $locked->id]);

                return $this->skippedResult($locked, 'no_items');
            }

            $officialSellerId = $this->officialSellerId();

            // คำนวณทีละรายการด้วย PricingEngine
            $computed = $items->map(fn (OrderItem $item) => $this->computeItem($engine, $item, $officialSellerId));
            $groups = $computed->groupBy(fn (array $row) => (int) $row['item']->seller_id);

            // ส่วนลดของออเดอร์: ใครออกเงิน (คูปองร้าน = ร้านรับภาระ / คูปองแพลตฟอร์ม = แพลตฟอร์มรับภาระ)
            $discount = self::splitDiscount(
                (float) $locked->discount_amount,
                (float) $locked->shipping_fee,
                (float) $items->sum(fn (OrderItem $item) => (float) $item->discount_amount),
                $locked->product_discount !== null ? (float) $locked->product_discount : null,
                $locked->shipping_discount !== null ? (float) $locked->shipping_discount : null,
                (float) $locked->discount_amount > 0 ? $this->discountFundedBy($locked) : 'platform'
            );

            // แบ่งค่าส่งให้ร้านที่ส่งของ — คูปองส่งฟรีของร้าน: ร้านได้เฉพาะค่าส่งที่ผู้ซื้อจ่ายจริง
            $shippingShares = $this->allocateShippingShares(
                $discount['seller_shipping_base'],
                $this->shippingWeights($groups)
            );

            // ส่วนลดของร้านที่ยังไม่ถูกหักใน order_items.total (ข้อมูลเก่า/ไม่ครบ) → หักจากรายได้ร้านตามสัดส่วนยอดขาย
            $discountDeductions = $this->allocateShippingShares(
                $discount['seller_deduction'],
                $groups->map(fn ($rows) => round($rows->sum(fn ($r) => $r['breakdown']->gross), 2))->all()
            );

            $results = [
                'order_id' => (int) $locked->id,
                'total_amount' => (float) $locked->total_amount,
                'distributions' => [],
                'platform_collections' => [],
                'seller_earnings' => [],
                'mlm_commissions' => [],
            ];

            $summary = ['gp' => 0.0, 'vat' => 0.0, 'pool' => 0.0, 'pv' => 0.0, 'seller_net' => 0.0];

            foreach ($groups as $sellerId => $rows) {
                $share = (float) ($shippingShares[(int) $sellerId] ?? 0.0);
                $deduction = (float) ($discountDeductions[(int) $sellerId] ?? 0.0);

                $groupResult = ($officialSellerId !== null && (int) $sellerId === $officialSellerId)
                    ? $this->processAdminShopItems($locked, $rows, $share, $deduction)
                    : $this->processSellerItems($locked, (int) $sellerId, $rows, $share, $engine, $deduction);

                $results['distributions'][] = $groupResult;
                if (($groupResult['type'] ?? '') === 'seller') {
                    $results['seller_earnings'][] = $groupResult;
                }

                $summary['gp'] += (float) ($groupResult['platform_fee'] ?? 0);
                $summary['vat'] += (float) ($groupResult['vat_amount'] ?? 0);
                $summary['pool'] += (float) ($groupResult['mlm_commission'] ?? 0);
                $summary['pv'] += (float) ($groupResult['pv_total'] ?? 0);
                $summary['seller_net'] += (float) ($groupResult['net_amount'] ?? 0);
            }

            // ส่วนลดที่แพลตฟอร์มออกเงินจริง (คูปองแพลตฟอร์มเท่านั้น) → รายจ่ายโปรฯ กระเป๋า fee
            // คูปองของร้าน: ร้านรับภาระแล้ว (หักใน order_items.total / ค่าส่งส่วนของร้าน) → ไม่ลงรายจ่ายแพลตฟอร์ม
            $platformDiscount = $discount['platform_expense'];
            if ($platformDiscount > 0) {
                $this->expenseService->recordPromoExpense(
                    'fee',
                    $platformDiscount,
                    'order_discount',
                    'Order',
                    (int) $locked->id,
                    "ส่วนลดคูปองที่แพลตฟอร์มออกให้ ออเดอร์ #{$locked->order_number}",
                    [
                        'order_number' => $locked->order_number,
                        'product_discount' => $discount['product_discount'],
                        'shipping_discount' => $discount['shipping_discount'],
                    ],
                    (int) $locked->user_id
                );
                $results['platform_collections'][] = ['type' => 'order_discount_expense', 'amount' => $platformDiscount];
            }
            $results['discount'] = $discount;

            // ค่าคอมแนะนำ (เฉพาะเมื่อเปิดระบบ MLM)
            if ($engine->mlmEnabled()) {
                $results['mlm_commissions'] = $this->processMlmCommissions($locked, $this->pvData($computed));
            }

            // เขียนค่าสรุปกลับลงออเดอร์ (ใช้ query builder → ไม่ยิง OrderObserver ซ้ำ)
            $summary = array_map(fn ($v) => round($v, 2), $summary);
            Order::whereKey($locked->id)->update([
                'vat_amount' => $summary['vat'],
                'mlm_pv_total' => $summary['pv'],
                'mlm_commission_total' => $summary['pool'],
                'seller_net_earnings' => $summary['seller_net'],
            ]);
            $results['summary'] = $summary;

            Log::info('Order distribution processed', [
                'order_id' => $locked->id,
                'sellers' => $groups->count(),
                'summary' => $summary,
            ]);

            return $results;
        });
    }

    /**
     * คำนวณการแบ่งเงินของ 1 รายการสินค้าด้วย PricingEngine
     *
     * @return array{item: OrderItem, breakdown: PriceBreakdown, gp_source: string, snapshot_gp_rate: float, pv_per_unit: float, official: bool}
     */
    protected function computeItem(PricingEngine $engine, OrderItem $item, ?int $officialSellerId): array
    {
        $gross = max(0.0, round((float) $item->total, 2));
        $qty = max(1, (int) $item->quantity);
        $official = $officialSellerId !== null && (int) $item->seller_id === $officialSellerId;
        $product = $item->product;

        if ($product instanceof Product) {
            $opts = $engine->optionsForProduct($product);
            $gpInfo = $engine->gpRateInfoForProduct($product);
            $gpSource = $gpInfo['source'];
        } else {
            // สินค้าถูกลบถาวร → ใช้อัตรา GP ตอนซื้อ (ไม่ต่ำกว่าขั้นต่ำ) และสถานะ VAT ของร้านผู้ขาย
            $store = VendorStore::where('user_id', $item->seller_id)->orderBy('id')->first();
            $opts = [
                'gp_rate' => $official ? 0.0 : max($engine->minGpRate(), (float) $item->commission_rate),
                'pv' => 0,
                'vat_registered' => $official ? $engine->officialShopVatRegistered() : $engine->storeVatRegistered($store),
            ];
            $gpSource = 'order_item_snapshot';
        }

        // คิดทั้งบรรทัดเป็นก้อนเดียว (ยอดหลังส่วนลดรายการ) → PV ต้องเป็นยอดรวมของบรรทัด
        unset($opts['cost_per_unit']);
        $pvPerUnit = max(0.0, (float) ($opts['pv'] ?? 0));
        $opts['pv'] = $pvPerUnit * $qty;

        return [
            'item' => $item,
            'breakdown' => $engine->breakdown($gross, 1, $opts),
            'gp_source' => $gpSource,
            'snapshot_gp_rate' => (float) $item->commission_rate,
            'pv_per_unit' => $pvPerUnit,
            'official' => $official,
        ];
    }

    /**
     * แบ่งเงินส่วนของผู้ขาย 1 ราย
     *
     * @param  Collection<int, array>  $rows  ผลจาก computeItem ของผู้ขายรายนี้
     */
    protected function processSellerItems(Order $order, int $sellerId, Collection $rows, float $shippingShare, PricingEngine $engine, float $discountDeduction = 0.0): array
    {
        $seller = User::withTrashed()->find($sellerId);
        if (! $seller) {
            // order_items.seller_id มี FK cascade → ไม่ควรเกิด ถ้าเกิดให้ rollback ทั้งออเดอร์แล้วให้แอดมินตรวจ
            throw new \RuntimeException("Seller #{$sellerId} not found for order #{$order->id}");
        }

        $gross = round($rows->sum(fn ($r) => $r['breakdown']->gross), 2);
        $gp = round($rows->sum(fn ($r) => $r['breakdown']->gp_amount), 2);
        $vat = round($rows->sum(fn ($r) => $r['breakdown']->vat_amount), 2);
        $pool = round($rows->sum(fn ($r) => $r['breakdown']->referral_pool_amount), 2);
        $pv = round($rows->sum(fn ($r) => $r['breakdown']->pv_total), 2);
        $net = round($gross - $gp - $vat - $pool, 2);

        if ($net < 0) {
            // GP+VAT เกินยอดขาย (อัตราผิดปกติ) → ลด GP ลงให้ผู้ขายไม่ติดลบ และบัญชีรวมยังเท่ายอดขาย
            Log::warning('Order distribution: seller net negative, GP reduced', [
                'order_id' => $order->id,
                'seller_id' => $sellerId,
                'gross' => $gross,
                'gp' => $gp,
                'vat' => $vat,
                'pool' => $pool,
            ]);
            $gp = round(max(0.0, $gp + $net), 2);
            $net = 0.0;
        }

        $shippingShare = round(max(0.0, $shippingShare), 2);
        $ledgerGross = round($gross + $shippingShare, 2);
        // ส่วนลดของร้านที่ยังไม่ถูกหักในยอดสินค้า → หักจากรายได้ร้าน (ไม่ให้ติดลบ)
        $discountDeduction = round(min(max(0.0, $discountDeduction), $net + $shippingShare), 2);
        $ledgerNet = round($net + $shippingShare - $discountDeduction, 2);

        $meta = [
            'order_number' => $order->order_number,
            'seller_id' => $sellerId,
            'items_count' => $rows->count(),
            'gross_amount' => $gross,
        ];

        if ($gp > 0) {
            $this->revenueService->collectPlatformFee($gp, 'Order', (int) $order->id, $sellerId, $meta);
        }
        if ($vat > 0) {
            $this->revenueService->collectVat($vat, 'Order', (int) $order->id, $meta);
        }
        if ($pool > 0) {
            $this->revenueService->collectMlmPool($pool, 'Order', (int) $order->id, $meta);
        }
        if ($ledgerNet > 0) {
            $this->payoutService->holdInEscrow($ledgerNet, $order, $sellerId, ['shipping_share' => $shippingShare]);
        }

        $availableAt = $this->payoutService->availableAtFor($order);
        $vatRegistered = $rows->contains(fn ($r) => $r['breakdown']->vat_rate > 0);

        $ledger = EarningsLedger::create([
            'user_id' => $sellerId,
            'earning_type' => EarningsLedger::TYPE_SELLER_SALE,
            'source_type' => 'Order',
            'source_id' => $order->id,
            'gross_amount' => $ledgerGross,
            'platform_fee' => $gp,
            'vat_amount' => $vat,
            'mlm_commission' => $pool,
            'other_deductions' => $discountDeduction,
            'debt_deduction' => 0,
            'net_amount' => $ledgerNet,
            'status' => EarningsLedger::STATUS_PENDING,
            'available_at' => $availableAt,
            'description' => mb_substr("รายได้จากการขาย ออเดอร์ #{$order->order_number}", 0, 255),
            'breakdown' => [
                'version' => 2,
                'formula' => 'pricing_engine',
                'items' => $rows->map(fn ($r) => $this->itemBreakdownRow($r))->values()->all(),
                'calculations' => [
                    'items_gross' => $gross,
                    'shipping_share' => $shippingShare,
                    'store_discount_deduction' => $discountDeduction,
                    'gross_amount' => $ledgerGross,
                    'platform_fee' => $gp,
                    'vat_amount' => $vat,
                    'mlm_commission' => $pool,
                    'pv_total' => $pv,
                    'net_amount' => $ledgerNet,
                    'vat_registered' => $vatRegistered,
                    'mlm_enabled' => $engine->mlmEnabled(),
                ],
                'shipping_share' => $shippingShare,
                'release_rule' => 'after_delivery_plus_holding_days',
                'holding_days' => $this->payoutService->holdingDays(),
            ],
        ]);

        return [
            'type' => 'seller',
            'seller_id' => $sellerId,
            'seller_name' => $seller->name,
            'gross_amount' => $ledgerGross,
            'items_gross' => $gross,
            'shipping_share' => $shippingShare,
            'platform_fee' => $gp,
            'vat_amount' => $vat,
            'mlm_commission' => $pool,
            'pv_total' => $pv,
            'net_amount' => $ledgerNet,
            'earning_entry_id' => $ledger->id,
            'available_at' => $availableAt?->toDateTimeString(),
        ];
    }

    /**
     * สินค้าร้านทางการ (แพลตฟอร์มขายเอง) — ไม่มี GP, VAT ตามการจดทะเบียนของร้านทางการ
     *
     * @param  Collection<int, array>  $rows
     */
    protected function processAdminShopItems(Order $order, Collection $rows, float $shippingShare, float $discountDeduction = 0.0): array
    {
        $gross = round($rows->sum(fn ($r) => $r['breakdown']->gross), 2);
        $vat = round($rows->sum(fn ($r) => $r['breakdown']->vat_amount), 2);
        $pool = round($rows->sum(fn ($r) => $r['breakdown']->referral_pool_amount), 2);
        $pv = round($rows->sum(fn ($r) => $r['breakdown']->pv_total), 2);
        $shippingShare = round(max(0.0, $shippingShare), 2);
        $net = round(max(0.0, $gross - $vat - $pool) + $shippingShare, 2);
        $discountDeduction = round(min(max(0.0, $discountDeduction), $net), 2);
        $net = round($net - $discountDeduction, 2);

        $meta = ['order_number' => $order->order_number, 'source' => 'admin_shop'];

        if ($vat > 0) {
            $this->revenueService->collectVat($vat, 'Order', (int) $order->id, $meta);
        }
        if ($pool > 0) {
            $this->revenueService->collectMlmPool($pool, 'Order', (int) $order->id, $meta);
        }

        // บันทึกเสมอแม้เป็น 0 → เป็นหลักฐานว่าแบ่งแล้ว (cron จะไม่หยิบซ้ำ)
        $this->revenueService->recordIncome(
            'admin_shop',
            $net,
            'admin_shop_sale',
            "รายได้ร้านทางการ ออเดอร์ #{$order->order_number}",
            'Order',
            (int) $order->id,
            null,
            [
                'items_count' => $rows->count(),
                'gross_amount' => $gross,
                'shipping_share' => $shippingShare,
                'store_discount_deduction' => $discountDeduction,
                'vat_amount' => $vat,
                'mlm_commission' => $pool,
                'items' => $rows->map(fn ($r) => $this->itemBreakdownRow($r))->values()->all(),
            ]
        );

        return [
            'type' => 'admin_shop',
            'gross_amount' => round($gross + $shippingShare, 2),
            'items_gross' => $gross,
            'shipping_share' => $shippingShare,
            'platform_fee' => 0.0,
            'vat_amount' => $vat,
            'mlm_commission' => $pool,
            'pv_total' => $pv,
            'net_amount' => $net,
            'items_count' => $rows->count(),
        ];
    }

    /**
     * ค่าคอมแนะนำ/MLM ของออเดอร์ (เรียกเมื่อเปิดระบบ MLM เท่านั้น)
     *
     * @param  array{total_pv: float, pv_amount_thb: float, items: array}  $pvData
     */
    protected function processMlmCommissions(Order $order, array $pvData): array
    {
        try {
            if ($pvData['total_pv'] > 0) {
                $buyerMember = MlmMember::where('user_id', $order->user_id)
                    ->where('status', 'active')
                    ->first();

                if ($buyerMember) {
                    (new MlmPvService)->recordPvTransaction($buyerMember, $order, $pvData);
                    $buyerMember->increment('total_pv', $pvData['total_pv']);
                    $buyerMember->update(['last_purchase_at' => now()]);
                }
            }

            $result = $this->mlmCommissionService->processOrderCommissions($order, $pvData);

            return [
                'success' => true,
                'direct_referral' => $result['direct_referral'],
                'unilevel' => $result['unilevel'],
                'binary' => $result['binary'],
                'total_pv' => $pvData['total_pv'],
                'pv_amount_thb' => $pvData['pv_amount_thb'],
            ];
        } catch (\Throwable $e) {
            // คอม MLM ล้มต้องไม่ทำให้เงินผู้ขายไม่ถูกแบ่ง — log ไว้ให้แอดมินตรวจ
            Log::error('MLM Commission processing failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'direct_referral' => null,
                'unilevel' => [],
                'binary' => [],
            ];
        }
    }

    /**
     * ข้อมูล PV ของออเดอร์ (จาก PricingEngine — PV ที่ตั้งไว้จริงเท่านั้น)
     *
     * @param  Collection<int, array>  $computed
     * @return array{total_pv: float, pv_amount_thb: float, items: array}
     */
    protected function pvData(Collection $computed): array
    {
        $items = [];
        $totalPv = 0.0;
        $amount = 0.0;

        foreach ($computed as $row) {
            /** @var PriceBreakdown $b */
            $b = $row['breakdown'];
            if ($b->pv_total <= 0) {
                continue;
            }

            $totalPv += $b->pv_total;
            $amount += $b->referral_pool_amount;
            $items[] = [
                'product_id' => $row['item']->product_id,
                'product_name' => $row['item']->product_name,
                'quantity' => (int) $row['item']->quantity,
                'total' => (float) $row['item']->total,
                'pv_source' => 'pricing_engine',
                'pv_points' => round($b->pv_total, 2),
                'pv_amount_thb' => round($b->referral_pool_amount, 2),
            ];
        }

        return [
            'total_pv' => round($totalPv, 2),
            'pv_amount_thb' => round($amount, 2),
            'items' => $items,
        ];
    }

    /**
     * น้ำหนักสำหรับแบ่งค่าส่งต่อร้าน = ค่าส่งตามตั้งค่าสินค้าของแต่ละร้าน (สินค้าดิจิทัล = 0)
     * ถ้าคำนวณไม่ได้หรือเป็น 0 ทั้งหมด → ใช้ยอดขายของสินค้าที่ต้องส่ง
     *
     * @param  Collection<int, Collection<int, array>>  $groups
     * @return array<int, float>
     */
    protected function shippingWeights(Collection $groups): array
    {
        $byFee = [];
        $byGross = [];
        $shipping = new ShippingService;

        foreach ($groups as $sellerId => $rows) {
            $fee = 0.0;
            $gross = 0.0;

            foreach ($rows as $row) {
                $product = $row['item']->product;
                if ($product instanceof Product && $product->is_virtual) {
                    continue;
                }

                $gross += (float) $row['breakdown']->gross;

                if ($product instanceof Product) {
                    try {
                        $fee += (float) $shipping->calculateForProduct($product, max(1, (int) $row['item']->quantity));
                    } catch (\Throwable $e) {
                        Log::debug('Order distribution: shipping weight fallback', ['product_id' => $product->id]);
                    }
                }
            }

            $byFee[(int) $sellerId] = round($fee, 2);
            $byGross[(int) $sellerId] = round($gross, 2);
        }

        return array_sum($byFee) > 0 ? $byFee : $byGross;
    }

    /**
     * แบ่งค่าส่งของออเดอร์ตามน้ำหนัก (ปัดเป็นสตางค์ ผลรวมเท่าค่าส่งพอดี — เศษไปอยู่ร้านสุดท้ายที่มีน้ำหนัก)
     *
     * ฟังก์ชันบริสุทธิ์ (ไม่แตะฐานข้อมูล) — ทดสอบได้โดยตรง
     *
     * @param  array<int, float>  $weights  seller_id => น้ำหนัก
     * @return array<int, float> seller_id => ค่าส่งที่ได้
     */
    public static function allocateShippingShares(float $shippingFee, array $weights): array
    {
        $shares = array_fill_keys(array_keys($weights), 0.0);
        $shippingFee = round(max(0.0, $shippingFee), 2);

        if ($shippingFee <= 0 || $weights === []) {
            return $shares;
        }

        $positive = array_filter($weights, fn ($w) => is_numeric($w) && (float) $w > 0);
        if ($positive === []) {
            // ไม่มีน้ำหนักเลย (เช่น ทุกร้านเป็นสินค้าดิจิทัล) → แบ่งเท่าๆ กัน
            $positive = array_fill_keys(array_keys($weights), 1.0);
        }

        $totalWeight = array_sum($positive);
        $remaining = $shippingFee;
        $keys = array_keys($positive);
        $lastKey = end($keys);

        foreach ($positive as $sellerId => $weight) {
            if ($sellerId === $lastKey) {
                $shares[$sellerId] = round($remaining, 2);
                break;
            }

            $part = round($shippingFee * (float) $weight / $totalWeight, 2);
            $part = min($part, $remaining);
            $shares[$sellerId] = $part;
            $remaining = round($remaining - $part, 2);
        }

        return $shares;
    }

    /**
     * แยกส่วนลดของออเดอร์ว่าใครออกเงิน และผลต่อการแบ่งเงิน
     *
     * - คูปองของร้าน (store): ร้านรับภาระทั้งหมด
     *     · ส่วนลดสินค้าถูกหักใน order_items.total แล้ว (item_discount_absorbed) — ส่วนที่ยังไม่หัก → seller_deduction
     *     · ส่วนลดค่าส่ง → ร้านได้ค่าส่งเฉพาะที่ผู้ซื้อจ่ายจริง (seller_shipping_base = ค่าส่ง − ส่วนลดค่าส่ง)
     *     · แพลตฟอร์มไม่ลงรายจ่าย (platform_expense = 0)
     * - คูปองของแพลตฟอร์ม (platform): ร้านได้ราคาเต็ม + ค่าส่งเต็ม แพลตฟอร์มลงรายจ่ายเท่าส่วนลดที่ร้านไม่ได้รับภาระ
     *
     * ฟังก์ชันบริสุทธิ์ (ไม่แตะฐานข้อมูล) — ทดสอบได้โดยตรง · ผลรวมเงินทุกฝ่ายเท่ายอดที่ผู้ซื้อจ่ายเสมอ
     *
     * @param  float|null  $productDiscount  orders.product_discount (null = ออเดอร์เก่า → คำนวณย้อน)
     * @param  float|null  $shippingDiscount  orders.shipping_discount (null = ออเดอร์เก่า → คำนวณย้อน)
     * @param  string  $fundedBy  store | platform
     * @return array{funded_by: string, product_discount: float, shipping_discount: float, item_discount_absorbed: float, seller_shipping_base: float, seller_deduction: float, platform_expense: float}
     */
    public static function splitDiscount(
        float $discountAmount,
        float $shippingFee,
        float $itemDiscountAbsorbed,
        ?float $productDiscount,
        ?float $shippingDiscount,
        string $fundedBy
    ): array {
        $discountAmount = round(max(0.0, $discountAmount), 2);
        $shippingFee = round(max(0.0, $shippingFee), 2);
        $absorbed = round(max(0.0, $itemDiscountAbsorbed), 2);

        if ($shippingDiscount !== null) {
            $shipping = round(min(max(0.0, $shippingDiscount), $shippingFee), 2);
        } else {
            // ออเดอร์เก่า: ส่วนที่เกินส่วนลดสินค้า = ส่วนลดค่าส่ง (ไม่เกินค่าส่ง)
            $knownProduct = $productDiscount !== null ? max(0.0, $productDiscount) : $absorbed;
            $shipping = round(min(max(0.0, $discountAmount - $knownProduct), $shippingFee), 2);
        }

        $product = $productDiscount !== null
            ? round(max(0.0, $productDiscount), 2)
            : round(max(0.0, $discountAmount - $shipping), 2);

        // ส่วนลดสินค้าที่ยังไม่ถูกหักใน order_items.total
        $unabsorbed = round(max(0.0, $product - $absorbed), 2);
        $fundedBy = $fundedBy === 'store' ? 'store' : 'platform';

        if ($fundedBy === 'store') {
            return [
                'funded_by' => 'store',
                'product_discount' => $product,
                'shipping_discount' => $shipping,
                'item_discount_absorbed' => $absorbed,
                'seller_shipping_base' => round($shippingFee - $shipping, 2),
                'seller_deduction' => $unabsorbed,
                'platform_expense' => 0.0,
            ];
        }

        return [
            'funded_by' => 'platform',
            'product_discount' => $product,
            'shipping_discount' => $shipping,
            'item_discount_absorbed' => $absorbed,
            'seller_shipping_base' => $shippingFee,
            'seller_deduction' => 0.0,
            'platform_expense' => round($unabsorbed + $shipping, 2),
        ];
    }

    /**
     * ผู้ออกเงินส่วนลดของออเดอร์: store | platform
     *
     * orders.discount_funded_by (ออเดอร์ใหม่) → ไม่มีให้ดูคูปองที่ใช้ (coupons.store_id ไม่ว่าง = คูปองร้าน)
     * → ไม่พบคูปอง = ส่วนลดเก่าของแพลตฟอร์ม (โค้ดส่วนลดระบบเดิม)
     */
    protected function discountFundedBy(Order $order): string
    {
        $stored = $order->getAttribute('discount_funded_by');
        if (in_array($stored, ['store', 'platform'], true)) {
            return $stored;
        }

        try {
            $storeIds = DB::table('coupon_usages')
                ->join('coupons', 'coupons.id', '=', 'coupon_usages.coupon_id')
                ->where('coupon_usages.order_id', $order->id)
                ->pluck('coupons.store_id');

            if ($storeIds->isNotEmpty()) {
                return $storeIds->contains(fn ($id) => $id !== null) ? 'store' : 'platform';
            }
        } catch (\Throwable $e) {
            Log::warning('Order distribution: resolve coupon owner failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }

        return 'platform';
    }

    /**
     * ออเดอร์นี้แบ่งเงินแล้วหรือยัง
     *
     * มี EarningsLedger ของออเดอร์ หรือมีรายการเงินเข้ากระเป๋าแพลตฟอร์มจากการแบ่งเงิน
     * (ออเดอร์ร้านทางการล้วนไม่มี ledger — ดูจาก admin_shop_sale)
     */
    public function isOrderDistributed(Order $order): bool
    {
        if (EarningsLedger::withTrashed()
            ->where('source_type', 'Order')
            ->where('source_id', $order->id)
            ->exists()) {
            return true;
        }

        return PlatformTransaction::where('source_type', 'Order')
            ->where('source_id', $order->id)
            ->where('type', PlatformTransaction::TYPE_INCOME)
            ->whereIn('sub_type', self::DISTRIBUTION_SUB_TYPES)
            ->exists();
    }

    /**
     * ออเดอร์ที่ชำระแล้วแต่ยังไม่ถูกแบ่งเงิน
     *
     * @param  bool  $respectCutoff  true = ข้ามออเดอร์ที่ชำระก่อน money.distribution_backfill_from
     */
    public function pendingOrdersQuery(bool $respectCutoff = true)
    {
        $query = Order::query()
            ->where('payment_status', 'paid')
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('earnings_ledger')
                    ->whereColumn('earnings_ledger.source_id', 'orders.id')
                    ->where('earnings_ledger.source_type', 'Order');
            })
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('platform_transactions')
                    ->whereColumn('platform_transactions.source_id', 'orders.id')
                    ->where('platform_transactions.source_type', 'Order')
                    ->where('platform_transactions.type', PlatformTransaction::TYPE_INCOME)
                    ->whereIn('platform_transactions.sub_type', self::DISTRIBUTION_SUB_TYPES);
            });

        $cutoff = $respectCutoff ? $this->backfillCutoff() : null;
        if ($cutoff !== null) {
            $query->where(function ($q) use ($cutoff) {
                $q->where('paid_at', '>=', $cutoff)
                    ->orWhere(function ($q2) use ($cutoff) {
                        $q2->whereNull('paid_at')->where('created_at', '>=', $cutoff);
                    });
            });
        }

        return $query->orderBy('id');
    }

    /**
     * @return Collection<int, Order>
     */
    public function getPendingOrders(int $limit = 100, bool $respectCutoff = true)
    {
        return $this->pendingOrdersQuery($respectCutoff)->limit(max(1, $limit))->get();
    }

    /**
     * แบ่งเงินออเดอร์ที่ค้าง (cron)
     *
     * @return array{processed: int, skipped: int, failed: int, errors: array<int, array{order_id: int, error: string}>}
     */
    public function processPendingOrders(int $limit = 100, bool $respectCutoff = true): array
    {
        $results = ['processed' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => []];

        foreach ($this->getPendingOrders($limit, $respectCutoff) as $order) {
            try {
                $outcome = $this->processOrderDistribution($order);
                if (! empty($outcome['skipped'])) {
                    $results['skipped']++;
                } else {
                    $results['processed']++;
                }
            } catch (\Throwable $e) {
                $results['failed']++;
                $results['errors'][] = ['order_id' => (int) $order->id, 'error' => $e->getMessage()];

                Log::error('Order distribution failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * เวลาเริ่มต้นของการแบ่งเงินย้อนหลัง (null = ไม่จำกัด)
     */
    public function backfillCutoff(): ?Carbon
    {
        try {
            $value = Setting::get(self::SETTING_BACKFILL_FROM);

            return $value ? Carbon::parse((string) $value) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * แถวสรุปของ 1 รายการสินค้า (เก็บใน breakdown ของ ledger)
     */
    protected function itemBreakdownRow(array $row): array
    {
        /** @var PriceBreakdown $b */
        $b = $row['breakdown'];
        $item = $row['item'];

        return [
            'order_item_id' => $item->id,
            'product_id' => $item->product_id,
            'product_name' => $item->product_name,
            'quantity' => (int) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'total' => $b->gross,
            'gp_rate' => $b->gp_rate,
            'gp_source' => $row['gp_source'],
            'snapshot_gp_rate' => $row['snapshot_gp_rate'],
            'gp_amount' => $b->gp_amount,
            'vat_amount' => $b->vat_amount,
            'pv_per_unit' => $row['pv_per_unit'],
            'pv_total' => $b->pv_total,
            'referral_pool_amount' => $b->referral_pool_amount,
            'seller_net' => $b->seller_net,
        ];
    }

    protected function skippedResult(Order $order, string $reason): array
    {
        return [
            'order_id' => (int) $order->id,
            'total_amount' => (float) $order->total_amount,
            'distributions' => [],
            'platform_collections' => [],
            'seller_earnings' => [],
            'mlm_commissions' => [],
            'skipped' => true,
            'reason' => $reason,
        ];
    }

    protected function officialSellerId(): ?int
    {
        try {
            $id = Product::getOfficialSellerId();

            return $id > 0 ? (int) $id : null;
        } catch (\Throwable $e) {
            Log::warning('Order distribution: resolve official seller failed', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
