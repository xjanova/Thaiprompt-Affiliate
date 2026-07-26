<?php

namespace App\Http\Controllers\Admin\LazadaHub;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceLinkClick;
use App\Models\MarketplaceOrder;
use App\Models\MarketplacePlatform;
use App\Services\Marketplace\LazadaConversionSyncService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Lazada Hub — อนาไลติกสายเงิน affiliate (หลังบ้าน)
 *
 * ╔══════════════════════════════════════════════════════════════════════════════════╗
 * ║ หน้านี้ "อ่านอย่างเดียว" ไม่แตะเงิน ไม่อนุมัติอะไรทั้งสิ้น                              ║
 * ║ หน้าที่เดียวคือทำให้เจ้าของระบบ "เห็นครบ" ว่าเงินไหลถึงไหนแล้ว และติดอยู่ตรงไหน        ║
 * ║                                                                                  ║
 * ║ กฎเหล็กที่ต้องสะท้อนบนหน้าจอ (ตาม LazadaConversionSyncService):                     ║
 * ║   ตัวเลขเงิน "ยืนยันแล้ว" กับ "รอยืนยัน" ห้ามรวมเป็นก้อนเดียวเด็ดขาด                   ║
 * ║   เพราะ "ค่าคอมตามรายงาน" ≠ "เงินที่ลาซาด้าจ่ายให้เราจริง"                            ║
 * ╚══════════════════════════════════════════════════════════════════════════════════╝
 *
 * 🚀 ประสิทธิภาพ: ทุกตัวเลขคำนวณด้วย aggregate query (COUNT/SUM/CASE) ฝั่งฐานข้อมูล
 *    ห้ามดึง collection มาบวกใน PHP เพราะตาราง click/order จะโตเร็วมาก
 */
class AnalyticsController extends Controller
{
    /**
     * คำอธิบายภาษาไทยของ "ด่านตรวจ" แต่ละข้อ
     *
     * ⚠️ คีย์ต้องตรงกับ LazadaConversionSyncService::evaluateGate() เป๊ะ ๆ
     *    (เก็บไว้ใน order_data['_sync_meta']['gate'] ของทุกออเดอร์ที่ sync มา)
     *    ถ้าฝั่ง service เพิ่มด่านใหม่ ต้องมาเพิ่มคำแปลที่นี่ด้วย ไม่งั้นสาเหตุนั้นจะหายไปจากหน้าจอ
     */
    protected const GATE_CHECK_LABELS = [
        'mapping_verified' => 'ยังไม่ยืนยันชื่อฟิลด์จากลาซาด้า (mapping_verified)',
        'settled' => 'ลาซาด้ายังไม่เคลียร์ค่าคอมให้เรา',
        'payout_positive' => 'ยอดค่าคอมเป็น 0 หรืออ่านตัวเลขไม่ได้',
        'attributed' => 'ระบุตัวผู้แนะนำไม่ได้ (ไม่พบคลิกที่ตรงกัน หรือกำกวมหลายคน)',
        'real_order_id' => 'ไม่มีเลขออเดอร์จริงจากลาซาด้า (ระบบสังเคราะห์เลขเอง)',
        'order_time_known' => 'อ่านเวลาออเดอร์ไม่ได้',
        'currency_ok' => 'สกุลเงินไม่ใช่บาท (หรือไม่ระบุสกุลเงิน)',
    ];

    /** สถานะที่ถือว่า "ค่าคอมยืนยันแล้ว" ในฝั่งออเดอร์ */
    protected const SETTLED_COMMISSION_STATUSES = ['approved', 'paid'];

    /** สถานะที่ถือว่า "ยังไม่ยืนยัน" (ตัวเลขนี้ห้ามนับเป็นรายได้) */
    protected const UNCONFIRMED_COMMISSION_STATUSES = ['pending', 'calculated'];

    /** เพดานช่วงวันที่ที่ยอมให้เลือก (กันคิวรีหนักโดยไม่ตั้งใจ) */
    protected const MAX_RANGE_DAYS = 730;

    /** เพดานจำนวนแถวที่ยอมให้ไล่อ่าน JSON ใน PHP กรณีฐานข้อมูลไม่มีฟังก์ชัน JSON */
    protected const GATE_FALLBACK_SCAN_LIMIT = 5000;

    /**
     * หน้าภาพรวม — กรวยการแปลง + KPI + สาเหตุที่ยังไม่อนุมัติ + สุขภาพการ sync + ตารางท็อป
     */
    public function index(Request $request)
    {
        $platform = MarketplacePlatform::where('slug', 'lazada')->first();
        // ยังไม่มีแพลตฟอร์ม lazada ในระบบ → ใช้ -1 เพื่อให้ทุกคิวรีคืนค่าว่างอย่างปลอดภัย
        // (ดีกว่าการแยกเงื่อนไขในทุกคิวรี — พลาดจุดเดียวจะกลายเป็นนับข้ามแพลตฟอร์ม)
        $platformId = $platform?->id ?? -1;

        [$from, $to] = $this->resolveRange($request);

        // ── 1) คลิก (นับด้วยคิวรีเดียว) ──
        $clickAgg = $this->clicksBaseQuery($platformId, $from, $to)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN c.is_unique_click = 1 THEN 1 ELSE 0 END) as unique_clicks')
            ->selectRaw('SUM(CASE WHEN c.converted = 1 THEN 1 ELSE 0 END) as converted_clicks')
            ->first();

        $clicksTotal = (int) ($clickAgg->total ?? 0);
        $clicksUnique = (int) ($clickAgg->unique_clicks ?? 0);
        $clicksConverted = (int) ($clickAgg->converted_clicks ?? 0);

        // ── 2) ออเดอร์ + เงิน (คิวรีเดียว แยก "ยืนยันแล้ว" ออกจาก "รอยืนยัน") ──
        $settledIn = "'".implode("','", self::SETTLED_COMMISSION_STATUSES)."'";
        $unconfirmedIn = "'".implode("','", self::UNCONFIRMED_COMMISSION_STATUSES)."'";

        $orderAgg = $this->ordersBaseQuery($platformId, $from, $to)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('COALESCE(SUM(o.total_amount), 0) as sales_sum')
            ->selectRaw('COALESCE(SUM(o.net_commission), 0) as commission_reported')
            ->selectRaw("COALESCE(SUM(CASE WHEN o.commission_status IN ({$settledIn}) THEN o.net_commission ELSE 0 END), 0) as commission_settled")
            ->selectRaw("COALESCE(SUM(CASE WHEN o.commission_status IN ({$unconfirmedIn}) THEN o.net_commission ELSE 0 END), 0) as commission_unconfirmed")
            ->selectRaw("SUM(CASE WHEN o.commission_status IN ({$settledIn}) THEN 1 ELSE 0 END) as settled_orders")
            ->selectRaw("SUM(CASE WHEN o.commission_status = 'pending' THEN 1 ELSE 0 END) as pending_orders")
            ->selectRaw("SUM(CASE WHEN o.commission_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders")
            ->first();

        $ordersTotal = (int) ($orderAgg->total ?? 0);
        $salesSum = (float) ($orderAgg->sales_sum ?? 0);
        $commissionReported = (float) ($orderAgg->commission_reported ?? 0);
        $commissionSettled = (float) ($orderAgg->commission_settled ?? 0);
        $commissionUnconfirmed = (float) ($orderAgg->commission_unconfirmed ?? 0);
        $settledOrders = (int) ($orderAgg->settled_orders ?? 0);
        $pendingOrders = (int) ($orderAgg->pending_orders ?? 0);
        $cancelledOrders = (int) ($orderAgg->cancelled_orders ?? 0);

        // ── 3) จ่ายออกให้สมาชิกจริงแล้วเท่าไหร่ (ยึดจากตารางค่าคอม = สมุดบัญชีจริง) ──
        $paidOrders = (int) $this->ordersBaseQuery($platformId, $from, $to)
            ->whereExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('marketplace_commissions as mc')
                    ->whereColumn('mc.order_id', 'o.id')
                    ->where('mc.status', 'paid');
            })
            ->count();

        $paidCommissionSum = (float) $this->ordersBaseQuery($platformId, $from, $to)
            ->join('marketplace_commissions as mc', 'mc.order_id', '=', 'o.id')
            ->where('mc.status', 'paid')
            ->sum('mc.net_commission');

        // 🚨 ตัวเลขที่เจ้าของระบบต้องเห็น: ลาซาด้าบอกว่าเคลียร์แล้ว แต่ด่านอื่นยังไม่ผ่าน
        $settledButBlocked = (int) $this->ordersBaseQuery($platformId, $from, $to)
            ->whereIn('o.order_status', LazadaConversionSyncService::SETTLED_STATUSES)
            ->where('o.commission_status', 'pending')
            ->count();

        // ── 4) กรวยการแปลง ──
        $funnel = $this->buildFunnel($clicksTotal, $clicksConverted, $settledOrders, $paidOrders);

        // ── 5) KPI ──
        $kpis = $this->buildKpis([
            'clicks' => $clicksTotal,
            'unique_clicks' => $clicksUnique,
            'converted_clicks' => $clicksConverted,
            'orders' => $ordersTotal,
            'sales' => $salesSum,
            'commission_reported' => $commissionReported,
            'commission_settled' => $commissionSettled,
            'commission_paid_out' => $paidCommissionSum,
        ]);

        // ── 6) ทำไมยังไม่อนุมัติ ──
        $gateBreakdown = $this->gateBreakdown($platformId, $from, $to);

        // ── 7) สุขภาพการ sync (ดูทั้งระบบ ไม่ผูกกับช่วงวันที่) ──
        $syncHealth = $this->syncHealth($platformId);

        // ── 8) ตารางท็อป ──
        $topProducts = $this->topProducts($platformId, $from, $to);
        $topAffiliates = $this->topAffiliates($platformId, $from, $to);

        return view('admin.lazada-hub.analytics.index', [
            'pageTitle' => 'อนาไลติก Lazada Affiliate',
            'platform' => $platform,
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                // cast เป็น int เอง — Carbon บางเวอร์ชันคืนค่าเป็น float
                'days' => (int) $from->diffInDays($to) + 1,
            ],
            'funnel' => $funnel,
            'kpis' => $kpis,
            'gateBreakdown' => $gateBreakdown,
            'syncHealth' => $syncHealth,
            'topProducts' => $topProducts,
            'topAffiliates' => $topAffiliates,
            'orderStates' => [
                'total' => $ordersTotal,
                'settled' => $settledOrders,
                'pending' => $pendingOrders,
                'cancelled' => $cancelledOrders,
                'settled_but_blocked' => $settledButBlocked,
                'commission_unconfirmed' => $commissionUnconfirmed,
            ],
        ]);
    }

    /**
     * หน้าบันทึกการคลิก — ตารางดิบพร้อมฟิลเตอร์ (หน้าละ 50)
     */
    public function clicks(Request $request)
    {
        $platform = MarketplacePlatform::where('slug', 'lazada')->first();
        $platformId = $platform?->id ?? -1;

        [$from, $to] = $this->resolveRange($request);

        $userId = (int) $request->query('user_id', 0);
        $convertedOnly = $request->boolean('converted');

        // 🚀 eager load ครบสายในนัดเดียว (คลิก → ลิงก์ → เจ้าของลิงก์ / สินค้า) กัน N+1
        //    ต้องเลือกคอลัมน์ FK มาด้วย ไม่งั้น Eloquent จับคู่ความสัมพันธ์ไม่ได้
        $query = MarketplaceLinkClick::query()
            ->with([
                'affiliateLink:id,user_id,product_id,short_code',
                'affiliateLink.user:id,name',
                'affiliateLink.product:id,name',
            ])
            ->whereBetween('clicked_at', [$from, $to])
            ->whereHas('affiliateLink', function ($q) use ($platformId, $userId) {
                $q->where('platform_id', $platformId);

                if ($userId > 0) {
                    $q->where('user_id', $userId);
                }
            });

        if ($convertedOnly) {
            $query->where('converted', true);
        }

        $clicks = $query->orderByDesc('clicked_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        // รายชื่อสมาชิกสำหรับ dropdown — จำกัดไว้ 200 คนกันลิสต์ยาวจนหน้าอืด
        $members = DB::table('marketplace_affiliate_links as l')
            ->join('users as u', 'u.id', '=', 'l.user_id')
            ->where('l.platform_id', $platformId)
            ->distinct()
            ->orderBy('u.name')
            ->limit(200)
            ->get(['u.id', 'u.name']);

        return view('admin.lazada-hub.analytics.clicks', [
            'pageTitle' => 'บันทึกการคลิก Lazada',
            'platform' => $platform,
            'clicks' => $clicks,
            'members' => $members,
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'user_id' => $userId,
                'converted' => $convertedOnly,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // ช่วงวันที่
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * อ่านช่วงวันที่จาก request แบบปลอดภัย (ค่าเริ่มต้น = 30 วันล่าสุด)
     *
     * - ค่าที่แปลงไม่ได้ → ใช้ค่าเริ่มต้น (ห้าม throw ให้หน้าจอพัง)
     * - สลับวันให้ถูกทิศถ้าผู้ใช้กรอกกลับด้าน
     * - ตัดช่วงที่กว้างเกิน MAX_RANGE_DAYS กันคิวรีหนัก
     *
     * @return array{0:Carbon,1:Carbon}
     */
    protected function resolveRange(Request $request): array
    {
        $to = $this->parseDate($request->query('to')) ?? Carbon::today();
        $from = $this->parseDate($request->query('from')) ?? $to->copy()->subDays(29);

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy(), $from->copy()];
        }

        if ($from->diffInDays($to) > self::MAX_RANGE_DAYS) {
            $from = $to->copy()->subDays(self::MAX_RANGE_DAYS);
        }

        return [$from->copy()->startOfDay(), $to->copy()->endOfDay()];
    }

    /** แปลงสตริงวันที่ — แปลงไม่ได้/ค่าเพี้ยน = null */
    protected function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            $date = Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }

        // ปีเพี้ยน = ถือว่าอ่านไม่ออก (กันคนพิมพ์มั่วแล้วได้ช่วงประหลาด)
        if ($date->year < 2000 || $date->year > 2100) {
            return null;
        }

        return $date;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // คิวรีฐาน
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * คิวรีฐานของคลิก — ผูกแพลตฟอร์มผ่านลิงก์ (ตารางคลิกไม่มีคอลัมน์ platform_id)
     */
    protected function clicksBaseQuery(int $platformId, Carbon $from, Carbon $to)
    {
        return DB::table('marketplace_link_clicks as c')
            ->join('marketplace_affiliate_links as l', 'l.id', '=', 'c.affiliate_link_id')
            ->where('l.platform_id', $platformId)
            ->whereBetween('c.clicked_at', [$from, $to]);
    }

    /**
     * คิวรีฐานของออเดอร์ในช่วงวันที่
     */
    protected function ordersBaseQuery(int $platformId, Carbon $from, Carbon $to)
    {
        $query = DB::table('marketplace_orders as o')->where('o.platform_id', $platformId);

        $this->applyOrderDateRange($query, $from, $to);

        return $query;
    }

    /**
     * กรองช่วงวันที่ของออเดอร์
     *
     * ⚠️ ordered_at เป็น null ได้ (ลาซาด้าไม่ส่งเวลามา / อ่านเวลาไม่ออก)
     *    ถ้ากรองด้วย ordered_at ตรง ๆ ออเดอร์กลุ่มนี้จะ "หายไปจากรายงานทั้งหมด"
     *    ซึ่งอันตรายมาก — ออเดอร์ที่มีปัญหาคือออเดอร์ที่เจ้าของระบบต้องเห็นที่สุด
     *    จึงถอยไปใช้ created_at เฉพาะแถวที่ ordered_at ว่าง
     *
     * 🚀 เขียนเป็น OR สองสาขาแทน COALESCE() เพื่อให้สาขาแรกยังใช้ index บน ordered_at ได้
     *
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $prefix  คำนำหน้าคอลัมน์ (ใส่ '' เมื่อคิวรีไม่มี alias)
     */
    protected function applyOrderDateRange($query, Carbon $from, Carbon $to, string $prefix = 'o.'): void
    {
        $query->where(function ($w) use ($from, $to, $prefix) {
            $w->whereBetween($prefix.'ordered_at', [$from, $to])
                ->orWhere(function ($w2) use ($from, $to, $prefix) {
                    $w2->whereNull($prefix.'ordered_at')
                        ->whereBetween($prefix.'created_at', [$from, $to]);
                });
        });
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // กรวย + KPI
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * สร้างกรวยการแปลง 4 ขั้น
     *
     * ⚠️ ขั้น 1-2 นับเป็น "คลิก" แต่ขั้น 3-4 นับเป็น "ออเดอร์" — คนละหน่วยกัน
     *    จึงต้องเขียนหน่วยกำกับไว้บนหน้าจอเสมอ ไม่ให้เข้าใจผิดว่าเป็นตัวเลขชุดเดียวกัน
     *
     * @return array<int,array<string,mixed>>
     */
    protected function buildFunnel(int $clicks, int $convertedClicks, int $settledOrders, int $paidOrders): array
    {
        $steps = [
            [
                'label' => 'คลิกทั้งหมด',
                'icon' => 'fa-hand-pointer',
                'unit' => 'คลิก',
                'count' => $clicks,
                'color' => 'var(--accent1)',
                'hint' => 'ลูกค้ากดลิงก์ของสมาชิกแล้วถูกส่งไปลาซาด้า',
            ],
            [
                'label' => 'คลิกที่แปลงเป็นออเดอร์',
                'icon' => 'fa-cart-shopping',
                'unit' => 'คลิก',
                'count' => $convertedClicks,
                'color' => '#b79ae8',
                'hint' => 'คลิกที่จับคู่กับออเดอร์จากรายงานลาซาด้าได้แล้ว',
            ],
            [
                'label' => 'ออเดอร์ที่ลาซาด้าเคลียร์แล้ว',
                'icon' => 'fa-circle-check',
                'unit' => 'ออเดอร์',
                'count' => $settledOrders,
                'color' => '#5aa07e',
                'hint' => 'ผ่านด่านตรวจครบทุกข้อ → ค่าคอมถูกยืนยัน (approved/paid)',
            ],
            [
                'label' => 'จ่ายค่าคอมให้สมาชิกแล้ว',
                'icon' => 'fa-hand-holding-dollar',
                'unit' => 'ออเดอร์',
                'count' => $paidOrders,
                'color' => 'var(--accent2)',
                'hint' => 'มีแถวค่าคอมสถานะ paid = เงินออกจากระบบไปหาสมาชิกจริง',
            ],
        ];

        $first = $steps[0]['count'];
        $previous = null;

        foreach ($steps as $i => $step) {
            $count = $step['count'];

            // 🛡️ กันหารศูนย์ทุกจุด — ตารางทั้งหมดยังว่างอยู่ ณ ตอนนี้
            $steps[$i]['pct_prev'] = ($previous !== null && $previous > 0)
                ? round($count * 100 / $previous, 2)
                : null;

            $steps[$i]['pct_first'] = $first > 0
                ? round($count * 100 / $first, 2)
                : null;

            // ความกว้างแท่ง: ถ้ามีค่าแต่สัดส่วนต่ำมาก ให้เหลืออย่างน้อย 2% เพื่อให้ยังมองเห็น
            $width = ($first > 0 && $count > 0) ? max(2.0, round($count * 100 / $first, 2)) : 0.0;
            $steps[$i]['width'] = min(100.0, $width);

            $previous = $count;
        }

        return $steps;
    }

    /**
     * การ์ด KPI — ทุกใบที่เป็น "เงิน" ต้องติดป้ายว่ายืนยันแล้วหรือรอยืนยัน
     *
     * @param  array<string,float|int>  $data
     * @return array<int,array<string,mixed>>
     */
    protected function buildKpis(array $data): array
    {
        $clicks = (int) $data['clicks'];
        $orders = (int) $data['orders'];

        // 🛡️ กันหารศูนย์
        $epc = $clicks > 0 ? $data['commission_settled'] / $clicks : 0.0;

        // ⚠️ ต้องคิดจาก "คลิกที่แปลงเป็นออเดอร์" ไม่ใช่ "จำนวนออเดอร์"
        //    เพราะออเดอร์บางใบจับคู่กับคลิกไม่ได้เลย (หรือแอดมินสร้างเอง)
        //    ถ้าหารด้วยจำนวนออเดอร์ตรง ๆ ตัวเลขจะเกิน 100% หรือขัดกับกรวยที่อยู่หน้าเดียวกัน
        $conversionRate = $clicks > 0 ? (int) $data['converted_clicks'] * 100 / $clicks : 0.0;

        return [
            [
                'label' => 'คลิกทั้งหมด',
                'value' => number_format($clicks),
                'icon' => 'fa-hand-pointer',
                'color' => 'var(--accent1)',
                'badge' => null,
                'hint' => 'นับจากบันทึกการคลิกของเราเอง',
            ],
            [
                'label' => 'คลิกไม่ซ้ำ',
                'value' => number_format((int) $data['unique_clicks']),
                'icon' => 'fa-fingerprint',
                'color' => 'var(--accent1)',
                'badge' => null,
                'hint' => 'คลิกแรกของแต่ละ session',
            ],
            [
                'label' => 'ออเดอร์',
                'value' => number_format($orders),
                'icon' => 'fa-receipt',
                'color' => '#b79ae8',
                'badge' => null,
                'hint' => 'ออเดอร์ที่ sync มาจากรายงานลาซาด้า',
            ],
            [
                'label' => 'ยอดขายรวม',
                'value' => '฿'.number_format((float) $data['sales'], 2),
                'icon' => 'fa-cash-register',
                'color' => '#b79ae8',
                'badge' => 'รอยืนยัน',
                'hint' => 'ตัวเลขตามรายงาน ยังไม่ใช่เงินที่เข้ากระเป๋าเรา',
            ],
            [
                'label' => 'ค่าคอมที่ควรได้',
                'value' => '฿'.number_format((float) $data['commission_reported'], 2),
                'icon' => 'fa-coins',
                'color' => '#e0a52e',
                'badge' => 'รอยืนยัน',
                'hint' => 'รวมทุกออเดอร์ตามรายงาน — ห้ามนับเป็นรายได้',
            ],
            [
                'label' => 'ค่าคอมที่ยืนยันแล้ว',
                'value' => '฿'.number_format((float) $data['commission_settled'], 2),
                'icon' => 'fa-circle-check',
                'color' => '#5aa07e',
                'badge' => 'ยืนยันแล้ว',
                'hint' => 'ลาซาด้าเคลียร์ + ผ่านด่านตรวจครบ',
            ],
            [
                'label' => 'จ่ายให้สมาชิกแล้ว',
                'value' => '฿'.number_format((float) $data['commission_paid_out'], 2),
                'icon' => 'fa-hand-holding-dollar',
                'color' => '#5aa07e',
                'badge' => 'ยืนยันแล้ว',
                'hint' => 'เงินที่ออกจากระบบไปหาสมาชิกจริง',
            ],
            [
                'label' => 'EPC (รายได้ต่อคลิก)',
                'value' => '฿'.number_format($epc, 4),
                'icon' => 'fa-chart-line',
                'color' => '#5aa07e',
                'badge' => 'ยืนยันแล้ว',
                'hint' => 'ค่าคอมที่ยืนยันแล้ว ÷ จำนวนคลิก',
            ],
            [
                'label' => 'อัตราแปลง',
                'value' => number_format($conversionRate, 2).'%',
                'icon' => 'fa-percent',
                'color' => 'var(--accent2)',
                'badge' => null,
                'hint' => 'คลิกที่แปลงเป็นออเดอร์ ÷ คลิกทั้งหมด',
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // ทำไมยังไม่อนุมัติ
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * นับว่าออเดอร์ที่ค้าง pending ตกด่านไหนบ้าง (อ่านจาก order_data['_sync_meta']['gate'])
     *
     * 🚀 ปกติใช้ฟังก์ชัน JSON ของ MySQL นับทีเดียวจบ (คิวรีเดียว ไม่ดึงแถวมา PHP)
     * 🛡️ ถ้าฐานข้อมูลไม่รองรับ JSON_VALID/JSON_EXTRACT → ถอยไปไล่อ่านใน PHP แบบจำกัดจำนวน
     *    หน้ารายงาน "ห้ามพัง" เพราะเวอร์ชันฐานข้อมูล
     *
     * @return array<string,mixed>
     */
    protected function gateBreakdown(int $platformId, Carbon $from, Carbon $to): array
    {
        $keys = array_keys(self::GATE_CHECK_LABELS);

        try {
            $query = $this->ordersBaseQuery($platformId, $from, $to)
                ->where('o.commission_status', 'pending')
                ->selectRaw('COUNT(*) as pending_total');

            foreach ($keys as $key) {
                // คีย์มาจาก whitelist ในคลาสนี้เท่านั้น (ไม่มีอินพุตผู้ใช้) → ต่อสตริงได้ปลอดภัย
                $path = "'\$._sync_meta.gate.".$key."'";

                // CASE ซ้อน CASE: กัน JSON_EXTRACT ถูกประเมินกับค่าที่ไม่ใช่ JSON
                $query->selectRaw(
                    'SUM(CASE WHEN JSON_VALID(o.order_data) THEN '
                    ."(CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(o.order_data, {$path})) = 'false' THEN 1 ELSE 0 END) "
                    ."ELSE 0 END) as fail_{$key}"
                );
            }

            $query->selectRaw(
                'SUM(CASE WHEN o.order_data IS NULL OR JSON_VALID(o.order_data) = 0 THEN 1 '
                ."ELSE (CASE WHEN JSON_EXTRACT(o.order_data, '\$._sync_meta.gate') IS NULL THEN 1 ELSE 0 END) END) as no_meta"
            );

            $row = $query->first();

            $counts = [];
            foreach ($keys as $key) {
                $counts[$key] = (int) ($row->{'fail_'.$key} ?? 0);
            }

            return $this->formatGateBreakdown(
                (int) ($row->pending_total ?? 0),
                $counts,
                (int) ($row->no_meta ?? 0),
                false
            );
        } catch (\Throwable $e) {
            Log::warning('lazada analytics: นับด่านตรวจด้วยฟังก์ชัน JSON ไม่สำเร็จ ถอยไปอ่านใน PHP', [
                'error' => $e->getMessage(),
            ]);

            return $this->gateBreakdownFallback($platformId, $from, $to, $keys);
        }
    }

    /**
     * ทางถอย: ไล่อ่าน JSON ใน PHP แบบ cursor (จำกัดจำนวนแถว กันหน่วยความจำบวม)
     *
     * @param  array<int,string>  $keys
     * @return array<string,mixed>
     */
    protected function gateBreakdownFallback(int $platformId, Carbon $from, Carbon $to, array $keys): array
    {
        $counts = array_fill_keys($keys, 0);
        $noMeta = 0;
        $scanned = 0;

        $query = MarketplaceOrder::query()
            ->where('platform_id', $platformId)
            ->where('commission_status', 'pending');

        $this->applyOrderDateRange($query, $from, $to, '');

        $rows = $query->orderByDesc('id')
            ->limit(self::GATE_FALLBACK_SCAN_LIMIT)
            ->select(['id', 'order_data'])
            ->cursor();

        foreach ($rows as $order) {
            $scanned++;

            $checks = $order->order_data['_sync_meta']['gate'] ?? null;
            if (! is_array($checks)) {
                $noMeta++;

                continue;
            }

            foreach ($keys as $key) {
                if (array_key_exists($key, $checks) && ! $checks[$key]) {
                    $counts[$key]++;
                }
            }
        }

        return $this->formatGateBreakdown($scanned, $counts, $noMeta, true);
    }

    /**
     * จัดรูปผลนับด่านตรวจให้ view ใช้ได้ตรง ๆ (เรียงจากสาเหตุที่พบมากที่สุด)
     *
     * @param  array<string,int>  $counts
     * @return array<string,mixed>
     */
    protected function formatGateBreakdown(int $pendingTotal, array $counts, int $noMeta, bool $isFallback): array
    {
        $reasons = [];

        foreach ($counts as $key => $count) {
            if ($count <= 0) {
                continue;
            }

            $reasons[] = [
                'key' => $key,
                'label' => self::GATE_CHECK_LABELS[$key] ?? $key,
                'count' => $count,
                // 🛡️ กันหารศูนย์
                'pct' => $pendingTotal > 0 ? round($count * 100 / $pendingTotal, 1) : 0.0,
            ];
        }

        if ($noMeta > 0) {
            $reasons[] = [
                'key' => '_no_meta',
                'label' => 'ไม่มีข้อมูลด่านตรวจ (ออเดอร์ไม่ได้มาจากการ sync)',
                'count' => $noMeta,
                'pct' => $pendingTotal > 0 ? round($noMeta * 100 / $pendingTotal, 1) : 0.0,
            ];
        }

        usort($reasons, fn ($a, $b) => $b['count'] <=> $a['count']);

        return [
            'pending_total' => $pendingTotal,
            'reasons' => $reasons,
            'is_fallback' => $isFallback,
            'scan_limit' => self::GATE_FALLBACK_SCAN_LIMIT,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // สุขภาพการ sync
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * สถานะการ sync + ธงยืนยัน mapping + ชื่อฟิลด์จริงที่ลาซาด้าส่งมา
     *
     * 🔑 รายชื่อฟิลด์นี้คือกุญแจปลดล็อกทั้งระบบ — เจ้าของระบบต้องเห็นของจริงก่อน
     *    จึงจะล็อก mapping แล้วเปิดธงยืนยันได้อย่างมั่นใจ
     *
     * @return array<string,mixed>
     */
    protected function syncHealth(int $platformId): array
    {
        $agg = DB::table('marketplace_orders')
            ->where('platform_id', $platformId)
            ->selectRaw('COUNT(*) as orders_total')
            ->selectRaw('SUM(CASE WHEN last_synced_at IS NOT NULL THEN 1 ELSE 0 END) as synced_total')
            ->selectRaw('MAX(last_synced_at) as last_synced_at')
            ->first();

        // อ่านธงด้วยตรรกะเดียวกับตัว sync เอง (แหล่งความจริงเดียว ห้ามเขียนเงื่อนไขซ้ำ)
        $mappingVerified = (new LazadaConversionSyncService)->isMappingVerified();

        // ดึงแถวดิบล่าสุด 1 แถว เพื่อโชว์ชื่อฟิลด์จริงที่ลาซาด้าส่งมา
        $discoveredKeys = [];
        $discoveredFrom = null;

        $latest = MarketplaceOrder::where('platform_id', $platformId)
            ->whereNotNull('last_synced_at')
            ->orderByDesc('last_synced_at')
            ->orderByDesc('id')
            ->first(['id', 'external_order_id', 'last_synced_at', 'order_data']);

        if ($latest && is_array($latest->order_data)) {
            $raw = $latest->order_data['lazada_raw'] ?? null;

            if (is_array($raw) && $raw !== []) {
                $discoveredKeys = array_keys($raw);
                $discoveredFrom = (string) $latest->external_order_id;
            }
        }

        $lastSyncedAt = null;
        if (! empty($agg->last_synced_at)) {
            try {
                $lastSyncedAt = Carbon::parse($agg->last_synced_at);
            } catch (\Throwable $e) {
                $lastSyncedAt = null;
            }
        }

        return [
            'orders_total' => (int) ($agg->orders_total ?? 0),
            'synced_total' => (int) ($agg->synced_total ?? 0),
            'last_synced_at' => $lastSyncedAt,
            'mapping_verified' => $mappingVerified,
            'mapping_key' => LazadaConversionSyncService::MAPPING_VERIFIED_KEY,
            'settled_statuses' => LazadaConversionSyncService::SETTLED_STATUSES,
            'discovered_keys' => $discoveredKeys,
            'discovered_from' => $discoveredFrom,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // ตารางท็อป
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * สินค้าที่ทำเงินได้ดีที่สุด — นับเฉพาะ "ค่าคอมที่ยืนยันแล้ว" เท่านั้น
     *
     * ⚠️ marketplace_orders ไม่มีคอลัมน์สินค้า → ต้องวิ่งผ่านลิงก์ affiliate
     *    และ join เฉพาะ marketplace_products (ตาราง FK จริง) เท่านั้น
     *    ห้าม join products มาเดาชื่อ เพราะเลข id สองตารางชนกันได้ = โชว์ชื่อสินค้าผิด
     */
    protected function topProducts(int $platformId, Carbon $from, Carbon $to)
    {
        return $this->ordersBaseQuery($platformId, $from, $to)
            ->join('marketplace_affiliate_links as l', 'l.id', '=', 'o.affiliate_link_id')
            ->leftJoin('marketplace_products as p', 'p.id', '=', 'l.product_id')
            ->whereIn('o.commission_status', self::SETTLED_COMMISSION_STATUSES)
            ->groupBy('l.product_id', 'p.name', 'p.main_image_url')
            ->selectRaw('l.product_id as product_id')
            ->selectRaw('p.name as product_name')
            ->selectRaw('p.main_image_url as product_image')
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('COALESCE(SUM(o.total_amount), 0) as sales_sum')
            ->selectRaw('COALESCE(SUM(o.net_commission), 0) as commission_sum')
            ->orderByDesc('commission_sum')
            ->limit(10)
            ->get();
    }

    /**
     * สมาชิกที่แนะนำได้ดีที่สุด — นับเฉพาะ "ค่าคอมที่ยืนยันแล้ว" เท่านั้น
     */
    protected function topAffiliates(int $platformId, Carbon $from, Carbon $to)
    {
        return $this->ordersBaseQuery($platformId, $from, $to)
            ->join('users as u', 'u.id', '=', 'o.affiliate_user_id')
            ->whereIn('o.commission_status', self::SETTLED_COMMISSION_STATUSES)
            ->groupBy('u.id', 'u.name')
            ->selectRaw('u.id as user_id')
            ->selectRaw('u.name as user_name')
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('COALESCE(SUM(o.total_amount), 0) as sales_sum')
            ->selectRaw('COALESCE(SUM(o.net_commission), 0) as commission_sum')
            ->orderByDesc('commission_sum')
            ->limit(10)
            ->get();
    }
}
