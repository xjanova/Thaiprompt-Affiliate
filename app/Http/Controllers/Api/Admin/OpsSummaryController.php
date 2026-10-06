<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Admin\Fortune\FortuneBillsController;
use App\Http\Controllers\Controller;
use App\Models\AiApiKey;
use App\Models\FortuneReading;
use App\Models\FortuneTakeoverLog;
use App\Models\Order;
use App\Models\SmsPaymentNotification;
use App\Models\WithdrawalRequest;
use App\Services\AdminApp\ActiveReadingPresenter;
use App\Services\AdminApp\FortuneBillBuckets;
use App\Services\AdminApp\FortuneBillPresenter;
use App\Services\AdminApp\StuckReadingFinder;
use App\Services\LineFortuneService;
use App\Services\LineGatekeeperService;
use App\Support\SafeLog;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

/**
 * Admin Mobile API: หน้าแรกของแอปแอดมิน — สรุปงานที่ต้องทำตอนนี้ + สุขภาพระบบ + รายได้วันนี้ ในคำขอเดียว
 *
 * - ทุกส่วนห่อ try/catch แยกกัน — ส่วนที่พังได้ค่า null + ชื่อส่วนอยู่ใน degraded[]
 *   (ห้ามคืนกล่องว่าง count 0 — แอปจะแยกไม่ออกระหว่าง "ไม่มีงาน" กับ "อ่านไม่ได้")
 * - ผลทั้งก้อน cache 20 วินาที ใช้ร่วมกันทุกแอดมิน (แอปหลายเครื่อง poll พร้อมกัน) — server_time/generated_at
 *   เขียนทับเป็นเวลาปัจจุบันตอนตอบ · computed_at = เวลาที่คำนวณจริง
 * - ไม่เช็ค Schema::hasTable/hasColumn ทุกคำขอ (ตาราง/คอลัมน์มีบน prod แล้ว) — ถ้าหายจริง ส่วนนั้นเข้า degraded เอง
 * - อ่านอย่างเดียว ไม่มีการเขียนข้อมูลธุรกิจ (มีแค่ cache สรุปหน้าแรก + สถิติโควตา LINE)
 */
class OpsSummaryController extends Controller
{
    /** cache สรุปหน้าแรก (ทุกแอดมินเห็นชุดเดียวกัน) */
    public const CACHE_KEY = 'admin_app:ops_summary';

    private const CACHE_TTL = 20;

    /** จำนวนตัวอย่างต่อกล่อง */
    private const PREVIEW = 5;

    /** หน้าต่างเวลาของ SMS ที่ยังไม่ผูกบิล (ชั่วโมง) — เก่ากว่านี้ไม่ใช่งานค้างแล้ว */
    private const SMS_WINDOW_HOURS = 24;

    /** cache สถิติโควตา push ของ LINE (วินาที) */
    private const LINE_QUOTA_TTL = 600;

    private const LINE_QUOTA_KEY = 'admin_app:line_push_quota';

    /** @var array<int, string> ชื่อส่วนที่คำนวณไม่สำเร็จในรอบนี้ */
    private array $degraded = [];

    /**
     * GET /api/admin/ops/summary
     */
    public function index(FortuneBillPresenter $presenter): JsonResponse
    {
        $payload = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->build($presenter));

        // เวลาของ "คำตอบนี้" — ข้อมูลข้างในอาจเก่าได้ถึง 20 วินาที (ดู computed_at)
        $now = now()->toIso8601String();
        $payload['health']['server_time'] = $now;
        $payload['generated_at'] = $now;

        return response()->json(['success' => true, 'data' => $payload]);
    }

    /**
     * คำนวณสรุปหน้าแรกทั้งก้อน (เรียกผ่าน cache)
     *
     * @return array<string, mixed>
     */
    private function build(FortuneBillPresenter $presenter): array
    {
        $now = now();
        $this->degraded = [];

        $revenueToday = $this->revenue($now->copy()->startOfDay(), $now, withHourly: true);
        $yesterdayNow = $now->copy()->subDay();
        $revenueYesterday = $this->revenue($yesterdayNow->copy()->startOfDay(), $yesterdayNow, withHourly: false);

        $today = $revenueToday['total'];
        $yesterday = $revenueYesterday['complete'] ? $revenueYesterday['total'] : null;
        // เทียบได้ก็ต่อเมื่ออ่านครบทั้งสองวัน — ตัวเลขครึ่งเดียวทำให้ % หลอกตา
        $comparable = $revenueToday['complete'] && $yesterday !== null && $yesterday > 0;

        $payload = [
            'queue' => [
                'customer_requests' => $this->section('customer_requests', fn () => $this->customerRequests($now)),
                'bills_awaiting' => $this->section('bills_awaiting', fn () => $this->billsAwaiting($now, $presenter)),
                'withdrawals_pending' => $this->section('withdrawals_pending', fn () => $this->withdrawalsPending($now)),
                'sms_unmatched' => $this->section('sms_unmatched', fn () => $this->smsUnmatched($now)),
                'stuck_readings' => $this->section('stuck_readings', fn () => $this->stuckReadings($now)),
            ],
            'health' => [
                'ai_pool' => $this->section('ai_pool', fn () => $this->aiPool()),
                'line_push' => $this->section('line_push', fn () => $this->linePush()),
                'queue_backlog' => $this->section('queue_backlog', fn () => $this->queueBacklog($now)),
                'server_time' => $now->toIso8601String(),
            ],
            'revenue_today' => [
                'total' => $today,
                'fortune' => $revenueToday['fortune'],
                'marketplace' => $revenueToday['marketplace'],
                'other' => 0.0,
                'currency' => 'THB',
                'hourly' => $revenueToday['hourly'],
                'as_of' => $now->toIso8601String(),
            ],
            'revenue_yesterday_same_time' => $yesterday,
            'revenue_change_pct' => $comparable ? round((($today - $yesterday) / $yesterday) * 100, 1) : null,
            'computed_at' => $now->toIso8601String(),
            'generated_at' => $now->toIso8601String(),
        ];

        $payload['degraded'] = array_values(array_unique($this->degraded));

        return $payload;
    }

    // ────────────────────────────────────────────────────────────
    // คิวงาน
    // ────────────────────────────────────────────────────────────

    /**
     * ลูกค้าพิมพ์ขอคุยกับคน → ระบบเทคโอเวอร์ให้ (customer_request) และยังไม่หมดเวลา
     */
    private function customerRequests(CarbonInterface $now): array
    {
        $base = fn () => FortuneReading::query()
            ->where('admin_takeover_reason', FortuneReading::TAKEOVER_REASON_CUSTOMER_REQUEST)
            ->whereNotNull('admin_takeover_until')
            ->where('admin_takeover_until', '>', $now);

        $count = $base()->count();
        $oldest = $base()->min('admin_takeover_started_at');

        $rows = $base()
            ->with('user:id,name')
            ->orderBy('admin_takeover_started_at')
            ->limit(self::PREVIEW)
            ->get(['id', 'user_id', 'platform', 'platform_user_id', 'facebook_user_id', 'facebook_user_name',
                'user_profile', 'admin_takeover_started_at', 'admin_takeover_until']);
        $keywords = self::customerRequestKeywords($rows->pluck('id')->all());

        return [
            'count' => $count,
            'oldest_minutes' => $this->minutesSince($oldest, $now),
            'preview' => $rows->map(fn (FortuneReading $r) => [
                'reading_id' => (int) $r->id,
                'customer_name' => FortuneBillPresenter::customerName($r),
                'platform' => FortuneBillPresenter::platform($r),
                'keyword' => $keywords[(int) $r->id] ?? null,
                'requested_at' => $r->admin_takeover_started_at?->toIso8601String(),
                'remaining_minutes' => $r->takeoverRemainingMinutes(),
            ])->values()->all(),
        ];
    }

    /**
     * ข้อความที่ลูกค้าพิมพ์ตอนขอคุยกับคน (log เทคโอเวอร์ล่าสุดของแต่ละบิล)
     *
     * @param  array<int, int>  $readingIds
     * @return array<int, string>
     */
    public static function customerRequestKeywords(array $readingIds): array
    {
        if ($readingIds === []) {
            return [];
        }

        $out = [];
        FortuneTakeoverLog::query()
            ->whereIn('fortune_reading_id', $readingIds)
            ->where('action', FortuneTakeoverLog::ACTION_TAKEOVER)
            ->where('reason', FortuneReading::TAKEOVER_REASON_CUSTOMER_REQUEST)
            ->whereNotNull('message')
            ->orderByDesc('id')
            ->get(['fortune_reading_id', 'message'])
            ->each(function ($log) use (&$out) {
                $rid = (int) $log->fortune_reading_id;
                if (! isset($out[$rid])) {
                    $out[$rid] = mb_substr((string) $log->message, 0, 120);
                }
            });

        return $out;
    }

    /**
     * บิลรอแอดมินตรวจ (นิยามกอง awaiting ใน FortuneBillBuckets)
     */
    private function billsAwaiting(CarbonInterface $now, FortuneBillPresenter $presenter): array
    {
        $base = fn () => FortuneBillBuckets::applyStatus(
            FortuneBillBuckets::billedScope(FortuneReading::query()),
            'awaiting',
            $now
        );

        $agg = $base()
            // ยอดต่อใบเดียวกับที่รายการบิลโชว์ (amount_paid > 0 ไม่งั้นยอดทศนิยมจาก UPA)
            ->selectRaw('COUNT(*) AS c, COALESCE(SUM('.FortuneBillBuckets::billAmountSql().'), 0) AS amt,'
                .' MIN(COALESCE(slip_received_at, transfer_reported_at, paid_at, updated_at)) AS oldest')
            ->toBase()
            ->first();

        $rows = FortuneBillPresenter::selectListColumns($base())
            ->orderByRaw('COALESCE(slip_received_at, transfer_reported_at, paid_at, updated_at) ASC')
            ->orderBy('id')
            ->limit(self::PREVIEW)
            ->get();

        return [
            'count' => (int) ($agg->c ?? 0),
            'amount_thb' => round((float) ($agg->amt ?? 0), 2),
            'oldest_minutes' => $this->minutesSince($agg->oldest ?? null, $now),
            'preview' => $presenter->presentManyMini($rows),
        ];
    }

    /**
     * คำขอถอนเงินรออนุมัติ (ชุดเดียวกับ finance/withdrawals/pending)
     */
    private function withdrawalsPending(CarbonInterface $now): array
    {
        $agg = WithdrawalRequest::query()->pending()
            ->selectRaw('COUNT(*) AS c, COALESCE(SUM(amount), 0) AS amt, MIN(created_at) AS oldest')
            ->toBase()
            ->first();

        $rows = WithdrawalRequest::query()->pending()
            ->with('user:id,name')
            ->orderBy('created_at')
            ->limit(self::PREVIEW)
            ->get(['id', 'user_id', 'amount', 'net_amount', 'created_at']);

        return [
            'count' => (int) ($agg->c ?? 0),
            'amount_thb' => round((float) ($agg->amt ?? 0), 2),
            'oldest_minutes' => $this->minutesSince($agg->oldest ?? null, $now),
            'preview' => $rows->map(fn ($w) => [
                'id' => (int) $w->id,
                'user_name' => $w->user?->name,
                'amount_thb' => round((float) $w->amount, 2),
                'net_amount_thb' => round((float) $w->net_amount, 2),
                'created_at' => $w->created_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    /**
     * SMS เงินเข้าที่ยังไม่ผูกกับบิล ภายใน 24 ชม.
     */
    private function smsUnmatched(CarbonInterface $now): array
    {
        $base = fn () => SmsPaymentNotification::query()
            ->where('type', 'credit')
            ->whereIn('status', ['pending', 'requires_admin_review'])
            ->whereNull('matched_transaction_id')
            ->where('created_at', '>=', $now->copy()->subHours(self::SMS_WINDOW_HOURS));

        $agg = $base()
            ->selectRaw('COUNT(*) AS c, COALESCE(SUM(amount), 0) AS amt, MIN(created_at) AS oldest')
            ->toBase()
            ->first();

        $rows = $base()
            ->orderBy('created_at')
            ->limit(self::PREVIEW)
            ->get(['id', 'bank', 'amount', 'sender_or_receiver', 'status', 'sms_timestamp', 'created_at']);

        return [
            'count' => (int) ($agg->c ?? 0),
            'amount_thb' => round((float) ($agg->amt ?? 0), 2),
            'oldest_minutes' => $this->minutesSince($agg->oldest ?? null, $now),
            'preview' => $rows->map(fn (SmsPaymentNotification $s) => [
                'id' => (int) $s->id,
                'bank' => $s->bank,
                'amount_thb' => round((float) $s->amount, 2),
                'sender' => $s->sender_or_receiver,
                'status' => $s->status,
                'at' => ($s->sms_timestamp ?? $s->created_at)?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    /**
     * บิลจ่ายแล้วที่ระบบไม่ขยับ (นิยามใน StuckReadingFinder)
     */
    private function stuckReadings(CarbonInterface $now): array
    {
        $stuck = FortuneBillPresenter::selectListColumns(StuckReadingFinder::candidateScope(FortuneReading::query(), $now))
            ->with('user:id,name')
            ->limit(200)
            ->get()
            ->filter(fn (FortuneReading $r) => StuckReadingFinder::stuckReason($r, $now) !== null)
            ->sortBy(fn (FortuneReading $r) => StuckReadingFinder::stuckSince($r)?->getTimestamp() ?? PHP_INT_MAX)
            ->values();

        $oldest = $stuck->first() ? StuckReadingFinder::stuckSince($stuck->first()) : null;

        return [
            'count' => $stuck->count(),
            'oldest_minutes' => $this->minutesSince($oldest, $now),
            'preview' => $stuck->take(self::PREVIEW)
                ->map(fn (FortuneReading $r) => ActiveReadingPresenter::present($r, $now))
                ->values()->all(),
        ];
    }

    // ────────────────────────────────────────────────────────────
    // สุขภาพระบบ
    // ────────────────────────────────────────────────────────────

    /**
     * คีย์ AI ที่ระบบหยิบไปใช้ได้จริง (scopeAvailable) / คีย์ทั้งหมด
     */
    private function aiPool(): array
    {
        return [
            'healthy' => AiApiKey::query()->available()->count(),
            'total' => AiApiKey::query()->count(),
        ];
    }

    /**
     * โควตา push ของ LINE — อ่านจาก cache เท่านั้น ถ้ายังไม่มีให้ดึงหลังส่งคำตอบแล้ว (ไม่ให้หน้าแรกรอ LINE API)
     */
    private function linePush(): ?array
    {
        $exhausted = LineGatekeeperService::isQuotaExhausted();
        $cached = Cache::get(self::LINE_QUOTA_KEY);

        if (! is_array($cached)) {
            $this->refreshLineQuotaAfterResponse();

            return $exhausted
                ? ['used_this_month' => null, 'limit' => 300, 'remaining' => 0, 'exhausted' => true, 'checked_at' => null]
                : null;
        }

        return [
            'used_this_month' => $cached['used'],
            'limit' => $cached['limit'],
            'remaining' => $cached['remaining'],
            'exhausted' => $exhausted,
            'checked_at' => $cached['checked_at'],
        ];
    }

    /**
     * ดึงโควตา LINE หลังตอบแล้ว (ครั้งเดียวต่อ 5 นาที) — ข้ามถ้ายังไม่ได้ตั้ง token ของ LINE
     */
    private function refreshLineQuotaAfterResponse(): void
    {
        if (! Cache::add(self::LINE_QUOTA_KEY.':lock', 1, 300)) {
            return;
        }

        app()->terminating(function () {
            try {
                $line = new LineFortuneService;
                $settings = \App\Models\FortuneTellingSetting::getSettings();
                if (trim((string) ($settings->line_channel_access_token ?? config('services.line.channel_token') ?? '')) === '') {
                    return;
                }

                $q = $line->getMessageQuota();
                if (! empty($q['error']) || (int) $q['quota'] <= 0) {
                    return;
                }

                Cache::put(self::LINE_QUOTA_KEY, [
                    'used' => (int) $q['used'],
                    'limit' => (int) $q['quota'],
                    'remaining' => (int) $q['remaining'],
                    'checked_at' => now()->toIso8601String(),
                ], self::LINE_QUOTA_TTL);
            } catch (\Throwable $e) {
                Log::debug('AdminApp ops/summary: ดึงโควตา LINE ไม่สำเร็จ', ['error' => SafeLog::exceptionMessage($e)]);
            }
        });
    }

    /**
     * งานค้างในคิว + งานล้มใน 24 ชม.
     */
    private function queueBacklog(CarbonInterface $now): array
    {
        $driver = (string) config('queue.connections.'.config('queue.default').'.driver', config('queue.default'));
        $pending = 0;

        if ($driver === 'database') {
            $pending = (int) DB::table('jobs')->count();
        } elseif ($driver === 'redis') {
            foreach (['default', 'fortune-deep', 'notifications', 'line-retry', 'fortune-voice'] as $queue) {
                $pending += (int) Queue::size($queue);
            }
        }

        $failed = (int) DB::table('failed_jobs')->where('failed_at', '>=', $now->copy()->subDay())->count();

        return ['driver' => $driver, 'pending' => $pending, 'failed_24h' => $failed];
    }

    // ────────────────────────────────────────────────────────────
    // รายได้ (เงินเข้าจริง)
    // ────────────────────────────────────────────────────────────

    /**
     * เงินเข้าจริงในช่วงเวลา — ดูดวง (บิลที่กลายเป็นจ่ายแล้ว) + ออเดอร์ร้านค้าที่จ่ายแล้ว
     *
     * ส่วนที่อ่านไม่ได้ = null + ชื่ออยู่ใน degraded (revenue_fortune / revenue_marketplace) · complete = false
     *
     * @return array{total: float, fortune: float|null, marketplace: float|null, hourly: array<int, array{hour: int, amount: float}>, complete: bool}
     */
    private function revenue(CarbonInterface $from, CarbonInterface $to, bool $withHourly): array
    {
        $hours = array_fill(0, 24, 0.0);

        $fortune = $this->section('revenue_fortune', fn () => FortuneBillsController::paidTodayQuery($from, $to)
            ->selectRaw('HOUR(paid_at) AS h, COALESCE(SUM(COALESCE(amount_received, amount_paid)), 0) AS s')
            ->groupByRaw('HOUR(paid_at)')
            ->toBase()
            ->get()
            ->all());

        $marketplace = $this->section('revenue_marketplace', fn () => Order::query()
            ->where('payment_status', 'paid')
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', $from)
            ->where('paid_at', '<=', $to)
            ->selectRaw('HOUR(paid_at) AS h, COALESCE(SUM(total_amount), 0) AS s')
            ->groupByRaw('HOUR(paid_at)')
            ->toBase()
            ->get()
            ->all());

        $sum = function (?array $rows) use (&$hours): ?float {
            if ($rows === null) {
                return null;
            }

            $total = 0.0;
            foreach ($rows as $row) {
                $total += (float) $row->s;
                $hours[(int) $row->h] += (float) $row->s;
            }

            return round($total, 2);
        };

        $fortuneTotal = $sum($fortune);
        $marketTotal = $sum($marketplace);

        $hourly = [];
        if ($withHourly) {
            foreach ($hours as $h => $amount) {
                $hourly[] = ['hour' => $h, 'amount' => round($amount, 2)];
            }
        }

        return [
            'total' => round((float) $fortuneTotal + (float) $marketTotal, 2),
            'fortune' => $fortuneTotal,
            'marketplace' => $marketTotal,
            'hourly' => $hourly,
            'complete' => $fortuneTotal !== null && $marketTotal !== null,
        ];
    }

    // ────────────────────────────────────────────────────────────
    // ตัวช่วย
    // ────────────────────────────────────────────────────────────

    /**
     * รันส่วนหนึ่งของหน้า — พังแล้วได้ null + จดชื่อส่วนลง degraded (log ไว้ ไม่ทำให้ทั้งหน้า 500)
     */
    private function section(string $name, callable $fn): mixed
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            $this->degraded[] = $name;
            Log::warning('AdminApp ops/summary: ส่วนหนึ่งของหน้าแรกพัง', [
                'section' => $name,
                'error' => SafeLog::exceptionMessage($e),
            ]);

            return null;
        }
    }

    private function minutesSince(mixed $at, CarbonInterface $now): ?int
    {
        if ($at === null || $at === '') {
            return null;
        }

        $ts = $at instanceof CarbonInterface ? $at->getTimestamp() : strtotime((string) $at);
        if ($ts === false) {
            return null;
        }

        return max(0, (int) floor(($now->getTimestamp() - $ts) / 60));
    }
}
