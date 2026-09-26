<?php

namespace App\Services;

use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Pricing\PricingEngine;
use App\Support\TaladsodWebUi;
use Illuminate\Support\Facades\Log;

/**
 * รายได้ร้านตลาดสด (ฝั่งผู้ขาย) — ที่เดียวที่คำนวณตัวเลขรายได้
 *
 * ใช้ร่วมกันระหว่างหน้าเว็บ /taladsod/seller/earnings และ API แอป GET /fresh-market/seller/earnings
 * → ตัวเลขสองฝั่งตรงกันเสมอ (อ่านอย่างเดียว ไม่แตะเงิน)
 *
 * ความหมายของเงิน (เหมือนเดิมทุกตัว):
 *   - gross = ยอดขายรวม (total_amount ไม่รวมค่าส่ง) · gp = ค่าธรรมเนียมแพลตฟอร์ม (platform_fee)
 *   - net = รายรับสุทธิของร้าน (seller_earning) — นับเฉพาะออเดอร์ที่ "สำเร็จ" ตามวันที่ completed_at
 *   - held_net = ออเดอร์ที่ยังไม่จบแต่ลูกค้าจ่ายแล้ว (ระบบถือเงินไว้) · cod_to_collect = เงินสดที่ต้องเก็บปลายทาง
 */
class FreshMarketSellerEarningsService
{
    /** จำนวนวันของกราฟรายได้ */
    public const DAILY_DAYS = 14;

    /** จำนวนรายการเงินเข้ากระเป๋าล่าสุด */
    public const PAYOUT_LIMIT = 10;

    /** จำนวนออเดอร์สำเร็จล่าสุด */
    public const RECENT_LIMIT = 15;

    /** สถานะที่ยังไม่จบ (เงินที่กำลังจะได้) */
    public const ACTIVE_STATUSES = [
        FreshMarketOrder::STATUS_PENDING,
        FreshMarketOrder::STATUS_ACCEPTED,
        FreshMarketOrder::STATUS_PREPARING,
        FreshMarketOrder::STATUS_READY,
        FreshMarketOrder::STATUS_DELIVERING,
        FreshMarketOrder::STATUS_DELIVERED,
    ];

    protected FreshMarketService $marketService;

    public function __construct(?FreshMarketService $marketService = null)
    {
        $this->marketService = $marketService ?? new FreshMarketService;
    }

    /**
     * สรุปรายได้ทั้งหมดของร้าน
     *
     * @return array{
     *     periods: array<string, array{label: string, data: array{orders: int, gross: float, gp: float, net: float}}>,
     *     daily: array<int, array{date: string, label: string, orders: int, net: float}>,
     *     pending: array{orders: int, held_net: float, cod_to_collect: float},
     *     gp_debt: float, gp_rate: float, gp_free: bool, gp_free_until: ?\Carbon\CarbonInterface,
     *     wallet_balance: float,
     *     payouts: \Illuminate\Support\Collection<int, WalletTransaction>,
     *     recent_completed: \Illuminate\Support\Collection<int, FreshMarketOrder>
     * }
     */
    public function summary(FreshMarketSeller $seller): array
    {
        $completed = fn () => FreshMarketOrder::where('seller_id', $seller->id)
            ->where('order_status', FreshMarketOrder::STATUS_COMPLETED);

        $sum = function ($from) use ($completed) {
            $row = $completed()
                ->when($from, fn ($q) => $q->where('completed_at', '>=', $from))
                ->selectRaw('COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as gross, COALESCE(SUM(platform_fee), 0) as gp, COALESCE(SUM(seller_earning), 0) as net')
                ->first();

            return [
                'orders' => (int) ($row->orders ?? 0),
                'gross' => round((float) ($row->gross ?? 0), 2),
                'gp' => round((float) ($row->gp ?? 0), 2),
                'net' => round((float) ($row->net ?? 0), 2),
            ];
        };

        $periods = [
            'today' => ['label' => 'วันนี้', 'data' => $sum(now()->startOfDay())],
            'week' => ['label' => '7 วันล่าสุด', 'data' => $sum(now()->subDays(6)->startOfDay())],
            'month' => ['label' => 'เดือนนี้', 'data' => $sum(now()->startOfMonth())],
            'all' => ['label' => 'ทั้งหมด', 'data' => $sum(null)],
        ];

        // กราฟรายได้สุทธิ 14 วันล่าสุด (วันที่ไม่มีขาย = 0)
        $fromDay = now()->subDays(self::DAILY_DAYS - 1)->startOfDay();
        $byDay = $completed()
            ->where('completed_at', '>=', $fromDay)
            ->selectRaw('DATE(completed_at) as d, COUNT(*) as orders, COALESCE(SUM(seller_earning), 0) as net')
            ->groupBy('d')
            ->get()
            ->keyBy(fn ($r) => (string) $r->d);

        $daily = [];
        for ($i = 0; $i < self::DAILY_DAYS; $i++) {
            $day = $fromDay->copy()->addDays($i);
            $key = $day->toDateString();
            $daily[] = [
                'date' => $key,
                'label' => TaladsodWebUi::shortDay($day),
                'orders' => (int) ($byDay[$key]->orders ?? 0),
                'net' => round((float) ($byDay[$key]->net ?? 0), 2),
            ];
        }

        // เงินที่กำลังจะได้ (ออเดอร์ที่ยังไม่จบ): จ่ายผ่าน wallet ระบบถือไว้ / เก็บเงินปลายทาง
        $pendingRow = FreshMarketOrder::where('seller_id', $seller->id)
            ->whereIn('order_status', self::ACTIVE_STATUSES)
            ->selectRaw("COUNT(*) as orders,
                COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN seller_earning ELSE 0 END), 0) as held_net,
                COALESCE(SUM(CASE WHEN payment_method = 'cod' AND payment_status <> 'paid' THEN total_amount ELSE 0 END), 0) as cod_to_collect")
            ->first();

        $pending = [
            'orders' => (int) ($pendingRow->orders ?? 0),
            'held_net' => round((float) ($pendingRow->held_net ?? 0), 2),
            'cod_to_collect' => round((float) ($pendingRow->cod_to_collect ?? 0), 2),
        ];

        $gpFree = $this->gpPromoActive();
        $gpFreeUntil = null;
        try {
            $gpFreeUntil = $gpFree ? app(PricingEngine::class)->gpPromoEndsAt() : null;
        } catch (\Throwable $e) {
            $gpFreeUntil = null;
        }

        // เงินเข้ากระเป๋า = กระเป๋าของเจ้าของร้าน (ไม่ใช่ผู้ใช้ที่ล็อกอิน — แต่ทุกทางเข้าเป็นเจ้าของร้านเองอยู่แล้ว)
        $ownerId = (int) $seller->user_id;

        return [
            'periods' => $periods,
            'daily' => $daily,
            'pending' => $pending,
            'gp_debt' => $seller->outstandingGpDebt(),
            'gp_rate' => $this->currentGpRate($seller),
            'gp_free' => $gpFree,
            'gp_free_until' => $gpFreeUntil,
            'wallet_balance' => round((float) (Wallet::where('user_id', $ownerId)->value('balance') ?? 0), 2),
            'payouts' => WalletTransaction::where('user_id', $ownerId)
                ->where('reference_type', FreshMarketService::REF_PAYOUT)
                ->latest('id')
                ->limit(self::PAYOUT_LIMIT)
                ->get(['id', 'amount', 'description', 'reference_id', 'created_at']),
            'recent_completed' => $completed()
                ->with('items')
                ->latest('completed_at')
                ->limit(self::RECENT_LIMIT)
                ->get(),
        ];
    }

    /**
     * โปรฯ "GP ฟรีช่วงเปิดตัว" ยังมีผลอยู่หรือไม่ (อ่านไม่ได้ = ถือว่าไม่มีโปรฯ)
     */
    public function gpPromoActive(): bool
    {
        try {
            return app(PricingEngine::class)->gpPromoActive();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * อัตรา GP ตลาดสดที่ร้านนี้โดนหักตอนนี้ (%) — อ่านไม่ได้ = 0
     */
    public function currentGpRate(FreshMarketSeller $seller): float
    {
        try {
            $probe = new FreshMarketListing(['seller_id' => $seller->id]);
            $probe->setRelation('seller', $seller);

            return round((float) $this->marketService->gpRateFor($probe), 2);
        } catch (\Throwable $e) {
            Log::warning('FreshMarket: อ่านอัตรา GP ล้มเหลว', ['seller_id' => $seller->id, 'error' => $e->getMessage()]);

            return 0.0;
        }
    }
}
