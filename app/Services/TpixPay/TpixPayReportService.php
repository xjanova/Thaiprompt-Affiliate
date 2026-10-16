<?php

namespace App\Services\TpixPay;

use App\Models\TpixPayment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 📊 รายงานรับชำระด้วยเหรียญ TPIX (หน้า admin.tpix-pay.reports)
 *
 * ทุกตัวเลขรวมใน SQL (SUM / COUNT / GROUP BY DATE(...)) — ไม่ดึงทีละแถว
 * ผลลัพธ์เป็น array ล้วน (ไม่มีโมเดล) → แคชได้ CACHE_SECONDS วินาที
 *
 * หมายเหตุ: เวลาในตารางเก็บเป็นเวลาไทย (app.timezone) → DATE(paid_at) = วันที่ไทยตรงตัว
 */
class TpixPayReportService
{
    public const CACHE_KEY = 'tpix_pay:report:v1';

    public const CACHE_SECONDS = 60;

    /** จำนวนวันของกราฟยอดรับรายวัน / ตารางผู้จ่ายสูงสุด */
    public const DAILY_DAYS = 30;

    /** จำนวนวันของกราฟราคา/สภาพคล่อง (snapshot ถูกลบหลัง 7 วัน) */
    public const PRICE_DAYS = 7;

    /** จุดสูงสุดบนกราฟราคา */
    public const MAX_PRICE_POINTS = 300;

    /** ขนาดถังรวม snapshot ใน SQL (วินาที) — คิดค่าเฉลี่ยเคลื่อนที่จากถังเหล่านี้ */
    public const SNAPSHOT_BUCKET_SECONDS = 300;

    /** ช่วงค่าเฉลี่ยเคลื่อนที่ (TWAP) บนกราฟ (นาที) */
    public const TWAP_MINUTES = 30;

    /**
     * รายงานทั้งหมด (แคช 60 วินาที)
     *
     * @return array<string, mixed>
     */
    public function report(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, fn () => $this->build());
    }

    /**
     * สร้างรายงานใหม่ (ไม่ผ่านแคช)
     *
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $now = now();

        return [
            'generated_at' => $now->toIso8601String(),
            'kpi' => $this->kpis($now),
            'daily' => $this->daily($now),
            'price' => $this->priceSeries($now),
            'statuses' => $this->statusBreakdown($now),
            'top_payers' => $this->topPayers($now),
            'action_items' => $this->actionItems($now),
        ];
    }

    /**
     * ตัวเลขสรุป: รับวันนี้ / 7 / 30 วัน, จำนวน, ยอดเฉลี่ย, อัตราแปลง, ใบเปิด, รอตรวจ, เติมเข้ากระเป๋า
     *
     * @return array<string, mixed>
     */
    private function kpis(Carbon $now): array
    {
        $today = $now->copy()->startOfDay();
        $d7 = $now->copy()->subDays(7);
        $d30 = $now->copy()->subDays(self::DAILY_DAYS);

        // ยอดที่ได้รับแล้ว — query เดียว ใช้ดัชนี (status, paid_at)
        $settled = DB::table('tpix_payments')
            ->whereIn('status', TpixPayment::SETTLED_STATUSES)
            ->where('paid_at', '>=', $d30)
            ->selectRaw('COUNT(*) AS c30')
            ->selectRaw('COALESCE(SUM(amount_thb), 0) AS thb30')
            ->selectRaw('COALESCE(SUM(amount_tpix), 0) AS tpix30')
            ->selectRaw('SUM(CASE WHEN paid_at >= ? THEN 1 ELSE 0 END) AS c7', [$d7])
            ->selectRaw('COALESCE(SUM(CASE WHEN paid_at >= ? THEN amount_thb END), 0) AS thb7', [$d7])
            ->selectRaw('COALESCE(SUM(CASE WHEN paid_at >= ? THEN amount_tpix END), 0) AS tpix7', [$d7])
            ->selectRaw('SUM(CASE WHEN paid_at >= ? THEN 1 ELSE 0 END) AS c1', [$today])
            ->selectRaw('COALESCE(SUM(CASE WHEN paid_at >= ? THEN amount_thb END), 0) AS thb1', [$today])
            ->selectRaw('COALESCE(SUM(CASE WHEN paid_at >= ? THEN amount_tpix END), 0) AS tpix1', [$today])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS cw_count', [TpixPayment::STATUS_CREDITED_WALLET])
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? THEN amount_thb END), 0) AS cw_thb', [TpixPayment::STATUS_CREDITED_WALLET])
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? THEN amount_tpix END), 0) AS cw_tpix', [TpixPayment::STATUS_CREDITED_WALLET])
            ->first();

        // อัตราแปลง = ใบที่ได้รับเงิน ÷ ใบที่ออกใน 30 วัน (ดัชนี created_at)
        $quotes = DB::table('tpix_payments')
            ->where('created_at', '>=', $d30)
            ->selectRaw('COUNT(*) AS issued')
            ->selectRaw('SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) AS settled', TpixPayment::SETTLED_STATUSES)
            ->first();

        $issued = (int) ($quotes->issued ?? 0);
        $settledQuotes = (int) ($quotes->settled ?? 0);
        $c30 = (int) ($settled->c30 ?? 0);
        $thb30 = (float) ($settled->thb30 ?? 0);

        return [
            'today' => ['count' => (int) $settled->c1, 'thb' => (float) $settled->thb1, 'tpix' => self::dec($settled->tpix1)],
            'd7' => ['count' => (int) $settled->c7, 'thb' => (float) $settled->thb7, 'tpix' => self::dec($settled->tpix7)],
            'd30' => ['count' => $c30, 'thb' => $thb30, 'tpix' => self::dec($settled->tpix30)],
            'avg_ticket_thb' => $c30 > 0 ? round($thb30 / $c30, 2) : 0.0,
            'quotes_issued' => $issued,
            'quotes_settled' => $settledQuotes,
            'conversion_pct' => $issued > 0 ? round($settledQuotes * 100 / $issued, 1) : null,
            'open_count' => TpixPayment::query()
                ->whereIn('status', [TpixPayment::STATUS_PENDING, TpixPayment::STATUS_CONFIRMING])
                ->where('expires_at', '>', $now)
                ->count(),
            'review_count' => self::needsReviewQuery()->count(),
            'credited_wallet' => ['count' => (int) $settled->cw_count, 'thb' => (float) $settled->cw_thb, 'tpix' => self::dec($settled->cw_tpix)],
        ];
    }

    /**
     * ยอดรับรายวัน 30 วัน แยกตามวัตถุประสงค์ (เติมวันที่ไม่มียอดเป็น 0)
     *
     * @return array{labels: array<int, string>, dates: array<int, string>, thb: array<string, array<int, float>>, tpix: array<string, array<int, float>>, count: array<int, int>}
     */
    private function daily(Carbon $now): array
    {
        $start = $now->copy()->subDays(self::DAILY_DAYS - 1)->startOfDay();

        $rows = DB::table('tpix_payments')
            ->whereIn('status', TpixPayment::SETTLED_STATUSES)
            ->where('paid_at', '>=', $start)
            ->selectRaw('DATE(paid_at) AS d, purpose, COUNT(*) AS c, SUM(amount_thb) AS thb, SUM(amount_tpix) AS tpix')
            ->groupByRaw('DATE(paid_at), purpose')
            ->get();

        $purposes = [TpixPayment::PURPOSE_ORDER, TpixPayment::PURPOSE_WALLET_TOPUP];
        $dates = [];
        for ($i = 0; $i < self::DAILY_DAYS; $i++) {
            $dates[] = $start->copy()->addDays($i)->toDateString();
        }
        $index = array_flip($dates);

        $thb = $tpix = [];
        foreach ($purposes as $p) {
            $thb[$p] = array_fill(0, count($dates), 0.0);
            $tpix[$p] = array_fill(0, count($dates), 0.0);
        }
        $count = array_fill(0, count($dates), 0);

        foreach ($rows as $r) {
            $i = $index[(string) $r->d] ?? null;
            if ($i === null) {
                continue;
            }
            $p = in_array($r->purpose, $purposes, true) ? $r->purpose : TpixPayment::PURPOSE_ORDER;
            $thb[$p][$i] = round($thb[$p][$i] + (float) $r->thb, 2);
            $tpix[$p][$i] = round($tpix[$p][$i] + (float) $r->tpix, 4);
            $count[$i] += (int) $r->c;
        }

        return [
            'dates' => $dates,
            'labels' => array_map(fn ($d) => Carbon::parse($d)->format('d/m'), $dates),
            'thb' => $thb,
            'tpix' => $tpix,
            'count' => $count,
        ];
    }

    /**
     * ราคา TPIX (บาท/USD) + สภาพคล่องพูล 7 วัน — รวมเป็นถัง 5 นาทีใน SQL,
     * คิด TWAP 30 นาทีจากถัง (ถ่วงด้วยจำนวน snapshot) แล้วย่อเหลือ ≤ 300 จุด
     *
     * @return array<string, array<int, float|string|null>>
     */
    private function priceSeries(Carbon $now): array
    {
        $bucket = self::SNAPSHOT_BUCKET_SECONDS;
        $since = $now->copy()->subDays(self::PRICE_DAYS)->startOfMinute();
        $sinceStr = $since->format('Y-m-d H:i:s');

        $rows = DB::table('tpix_price_snapshots')
            ->where('created_at', '>=', $since)
            // นับวินาทีจากจุดเริ่ม (เทียบสตริงเวลาตรง ๆ — ไม่ขึ้นกับ timezone ของ session DB)
            ->selectRaw('FLOOR(TIMESTAMPDIFF(SECOND, ?, created_at) / ?) AS b', [$sinceStr, $bucket])
            ->selectRaw('COUNT(*) AS n, AVG(price_thb) AS thb, AVG(price_usd) AS usd, AVG(reserve_tpix) AS rt, AVG(reserve_usdt) AS ru')
            ->groupBy('b') // ใช้ alias (MySQL/MariaDB รองรับ) — ผูกค่าซ้ำใน GROUP BY จะโดน only_full_group_by
            ->orderBy('b')
            ->get();

        // TWAP: ค่าเฉลี่ยถ่วงจำนวน snapshot ในหน้าต่าง 30 นาทีย้อนหลังของแต่ละถัง (หน้าต่างเลื่อน O(n))
        $window = intdiv(self::TWAP_MINUTES * 60, $bucket);
        $points = [];
        $queue = [];
        $sumN = 0;
        $sumThb = 0.0;
        foreach ($rows as $r) {
            $b = (int) $r->b;
            $n = (int) $r->n;
            $queue[] = [$b, $n, (float) $r->thb * $n];
            $sumN += $n;
            $sumThb += (float) $r->thb * $n;
            while ($queue !== [] && $queue[0][0] <= $b - $window) {
                [, $on, $ot] = array_shift($queue);
                $sumN -= $on;
                $sumThb -= $ot;
            }
            $points[] = [
                'ts' => $since->getTimestamp() + ($b + 1) * $bucket,
                'thb' => (float) $r->thb,
                'usd' => (float) $r->usd,
                'twap' => $sumN > 0 ? $sumThb / $sumN : null,
                'rt' => $r->rt !== null ? (float) $r->rt : null,
                'ru' => $r->ru !== null ? (float) $r->ru : null,
            ];
        }

        // ย่อเหลือ ≤ 300 จุด: เฉลี่ยเป็นกลุ่มเท่า ๆ กัน (จุดเวลา = จุดสุดท้ายของกลุ่ม)
        $step = max(1, (int) ceil(count($points) / self::MAX_PRICE_POINTS));
        $out = ['labels' => [], 'ts' => [], 'thb' => [], 'usd' => [], 'twap' => [], 'reserve_tpix' => [], 'reserve_usdt' => []];
        foreach (array_chunk($points, $step) as $group) {
            $last = end($group);
            $out['ts'][] = $last['ts'];
            $out['labels'][] = Carbon::createFromTimestamp(min($last['ts'], $now->getTimestamp()), config('app.timezone'))->format('d/m H:i');
            $out['thb'][] = self::avg(array_column($group, 'thb'), 6);
            $out['usd'][] = self::avg(array_column($group, 'usd'), 8);
            $out['twap'][] = self::avg(array_column($group, 'twap'), 6);
            $out['reserve_tpix'][] = self::avg(array_column($group, 'rt'), 2);
            $out['reserve_usdt'][] = self::avg(array_column($group, 'ru'), 2);
        }

        return $out;
    }

    /**
     * จำนวนใบแยกตามสถานะ (ใบที่ออกใน 30 วัน)
     *
     * @return array<string, int>
     */
    private function statusBreakdown(Carbon $now): array
    {
        return DB::table('tpix_payments')
            ->where('created_at', '>=', $now->copy()->subDays(self::DAILY_DAYS))
            ->selectRaw('status, COUNT(*) AS c')
            ->groupBy('status')
            ->orderByDesc('c')
            ->pluck('c', 'status')
            ->map(fn ($c) => (int) $c)
            ->all();
    }

    /**
     * ผู้จ่ายสูงสุด 10 อันดับ (มูลค่าบาท 30 วัน) — รวมใน SQL แล้วดึงชื่อผู้ใช้ทีเดียว (ไม่มี N+1)
     *
     * @return array<int, array<string, mixed>>
     */
    private function topPayers(Carbon $now): array
    {
        $rows = DB::table('tpix_payments')
            ->whereIn('status', TpixPayment::SETTLED_STATUSES)
            ->where('paid_at', '>=', $now->copy()->subDays(self::DAILY_DAYS))
            ->whereNotNull('user_id')
            ->selectRaw('user_id, COUNT(*) AS c, SUM(amount_thb) AS thb, SUM(amount_tpix) AS tpix, MAX(paid_at) AS last_paid')
            ->groupBy('user_id')
            ->orderByDesc('thb')
            ->limit(10)
            ->get();

        $users = User::query()
            ->whereIn('id', $rows->pluck('user_id')->all())
            ->get(['id', 'name', 'email'])
            ->keyBy('id');

        return $rows->map(fn ($r) => [
            'user_id' => (int) $r->user_id,
            'name' => $users[$r->user_id]->name ?? '—',
            'email' => $users[$r->user_id]->email ?? null,
            'count' => (int) $r->c,
            'thb' => (float) $r->thb,
            'tpix' => self::dec($r->tpix),
            'last_paid' => $r->last_paid,
        ])->all();
    }

    /**
     * รายการที่ต้องให้แอดมินจัดการ: โอนหลังหมดเวลา (late) + ค้างยืนยันบนเชนเกิน 10 นาที — ล่าสุด 15 รายการ
     *
     * @return array<int, array<string, mixed>>
     */
    private function actionItems(Carbon $now): array
    {
        return TpixPayment::query()
            ->with('user:id,name')
            ->where(function ($q) use ($now) {
                $q->where('status', TpixPayment::STATUS_LATE)
                    ->orWhere(fn ($w) => $w->where('status', TpixPayment::STATUS_CONFIRMING)
                        ->where('updated_at', '<', $now->copy()->subMinutes(10)));
            })
            ->latest('id')
            ->limit(15)
            ->get()
            ->map(fn (TpixPayment $p) => [
                'id' => $p->id,
                'reference' => $p->reference,
                'status' => $p->status,
                'status_label' => $p->statusLabel(),
                'needs_review' => ! empty($p->meta['needs_review']),
                'user_name' => $p->user?->name,
                'user_id' => $p->user_id,
                'amount_thb' => (float) $p->amount_thb,
                'amount_tpix' => (string) $p->amount_tpix,
                'updated_at' => $p->updated_at?->format('d/m H:i'),
            ])
            ->all();
    }

    /**
     * ใบที่โอนหลังหมดเวลาและรอแอดมินอนุมัติ
     * ไม่ใช้ meta->needs_review = true: MariaDB เทียบ JSON boolean ไม่ตรง → ใช้ LIKE
     * (MariaDB เก็บข้อความตามที่เขียน "needs_review":true · MySQL 8 จัดรูปใหม่เป็น "needs_review": true → ครอบทั้งสองแบบ)
     *
     * @return \Illuminate\Database\Eloquent\Builder<TpixPayment>
     */
    public static function needsReviewQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return TpixPayment::query()
            ->where('status', TpixPayment::STATUS_LATE)
            ->where(fn ($q) => $q->where('meta', 'like', '%"needs_review":true%')
                ->orWhere('meta', 'like', '%"needs_review": true%'));
    }

    /** ทศนิยมจาก SUM() → string (คงความแม่นยำของเหรียญ) */
    private static function dec(mixed $v): string
    {
        return $v === null ? '0' : (string) $v;
    }

    /**
     * @param  array<int, float|null>  $values
     */
    private static function avg(array $values, int $precision): ?float
    {
        $values = array_values(array_filter($values, fn ($v) => $v !== null));

        return $values === [] ? null : round(array_sum($values) / count($values), $precision);
    }
}
