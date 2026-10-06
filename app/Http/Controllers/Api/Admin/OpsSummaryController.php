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
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

/**
 * Admin Mobile API: หน้าแรกของแอปแอดมิน — สรุปงานที่ต้องทำตอนนี้ + สุขภาพระบบ + รายได้วันนี้ ในคำขอเดียว
 *
 * ทุกส่วนห่อ try/catch แยกกัน — ส่วนใดพัง (ตารางหาย/บริการภายนอกล่ม) ได้ค่าว่างของส่วนนั้น ไม่ทำให้ทั้งหน้าพัง
 * อ่านอย่างเดียว ไม่มีการเขียนข้อมูลธุรกิจ (มีแค่ cache สถิติโควตา LINE)
 */
class OpsSummaryController extends Controller
{
    /** จำนวนตัวอย่างต่อกล่อง */
    private const PREVIEW = 5;

    /** หน้าต่างเวลาของ SMS ที่ยังไม่ผูกบิล (ชั่วโมง) — เก่ากว่านี้ไม่ใช่งานค้างแล้ว */
    private const SMS_WINDOW_HOURS = 24;

    /** cache สถิติโควตา push ของ LINE (วินาที) */
    private const LINE_QUOTA_TTL = 600;

    private const LINE_QUOTA_KEY = 'admin_app:line_push_quota';

    /**
     * GET /api/admin/ops/summary
     */
    public function index(FortuneBillPresenter $presenter): JsonResponse
    {
        $now = now();

        $revenueToday = $this->revenue($now->copy()->startOfDay(), $now, withHourly: true);
        $yesterdayNow = $now->copy()->subDay();
        $revenueYesterday = $this->revenue($yesterdayNow->copy()->startOfDay(), $yesterdayNow, withHourly: false);

        $today = $revenueToday['total'];
        $yesterday = $revenueYesterday['total'];

        return response()->json([
            'success' => true,
            'data' => [
                'queue' => [
                    'customer_requests' => $this->safe(fn () => $this->customerRequests($now), $this->emptyBox(false)),
                    'bills_awaiting' => $this->safe(fn () => $this->billsAwaiting($now, $presenter), $this->emptyBox(true)),
                    'withdrawals_pending' => $this->safe(fn () => $this->withdrawalsPending($now), $this->emptyBox(true)),
                    'sms_unmatched' => $this->safe(fn () => $this->smsUnmatched($now), $this->emptyBox(true)),
                    'stuck_readings' => $this->safe(fn () => $this->stuckReadings($now), $this->emptyBox(false)),
                ],
                'health' => [
                    'ai_pool' => $this->safe(fn () => $this->aiPool(), ['healthy' => 0, 'total' => 0]),
                    'line_push' => $this->safe(fn () => $this->linePush(), null),
                    'queue_backlog' => $this->safe(fn () => $this->queueBacklog($now), null),
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
                'revenue_change_pct' => $yesterday > 0 ? round((($today - $yesterday) / $yesterday) * 100, 1) : null,
                'generated_at' => $now->toIso8601String(),
            ],
        ]);
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

        $rows = $base()->with('user:id,name')->orderBy('admin_takeover_started_at')->limit(self::PREVIEW)->get();
        $keywords = $this->customerRequestKeywords($rows->pluck('id')->all());

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
            ->selectRaw('COUNT(*) AS c, COALESCE(SUM(COALESCE(amount_paid, 0)), 0) AS amt,'
                .' MIN(COALESCE(slip_received_at, transfer_reported_at, paid_at, updated_at)) AS oldest')
            ->toBase()
            ->first();

        $rows = $base()
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

        $rows = $base()->orderBy('created_at')->limit(self::PREVIEW)->get();

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
        $stuck = StuckReadingFinder::candidateScope(FortuneReading::query(), $now)
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
                Log::debug('AdminApp ops/summary: ดึงโควตา LINE ไม่สำเร็จ', ['error' => \App\Support\SafeLog::exceptionMessage($e)]);
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

        if ($driver === 'database' && Schema::hasTable('jobs')) {
            $pending = (int) DB::table('jobs')->count();
        } elseif ($driver === 'redis') {
            foreach (['default', 'fortune-deep', 'notifications', 'line-retry', 'fortune-voice'] as $queue) {
                $pending += (int) Queue::size($queue);
            }
        }

        $failed = Schema::hasTable('failed_jobs')
            ? (int) DB::table('failed_jobs')->where('failed_at', '>=', $now->copy()->subDay())->count()
            : 0;

        return ['driver' => $driver, 'pending' => $pending, 'failed_24h' => $failed];
    }

    // ────────────────────────────────────────────────────────────
    // รายได้ (เงินเข้าจริง)
    // ────────────────────────────────────────────────────────────

    /**
     * เงินเข้าจริงในช่วงเวลา — ดูดวง (บิลที่กลายเป็นจ่ายแล้ว) + ออเดอร์ร้านค้าที่จ่ายแล้ว
     *
     * @return array{total: float, fortune: float, marketplace: float, hourly: array<int, array{hour: int, amount: float}>}
     */
    private function revenue(CarbonInterface $from, CarbonInterface $to, bool $withHourly): array
    {
        $hours = array_fill(0, 24, 0.0);

        $fortune = $this->safe(function () use ($from, $to) {
            return FortuneBillsController::paidTodayQuery($from, $to)
                ->selectRaw('HOUR(paid_at) AS h, COALESCE(SUM(COALESCE(amount_received, amount_paid)), 0) AS s')
                ->groupByRaw('HOUR(paid_at)')
                ->toBase()
                ->get();
        }, collect());

        $marketplace = $this->safe(function () use ($from, $to) {
            if (! Schema::hasColumn('orders', 'paid_at')) {
                return collect();
            }

            return Order::query()
                ->where('payment_status', 'paid')
                ->whereNotNull('paid_at')
                ->where('paid_at', '>=', $from)
                ->where('paid_at', '<=', $to)
                ->selectRaw('HOUR(paid_at) AS h, COALESCE(SUM(total_amount), 0) AS s')
                ->groupByRaw('HOUR(paid_at)')
                ->toBase()
                ->get();
        }, collect());

        $fortuneTotal = 0.0;
        foreach ($fortune as $row) {
            $fortuneTotal += (float) $row->s;
            $hours[(int) $row->h] += (float) $row->s;
        }

        $marketTotal = 0.0;
        foreach ($marketplace as $row) {
            $marketTotal += (float) $row->s;
            $hours[(int) $row->h] += (float) $row->s;
        }

        $hourly = [];
        if ($withHourly) {
            foreach ($hours as $h => $amount) {
                $hourly[] = ['hour' => $h, 'amount' => round($amount, 2)];
            }
        }

        return [
            'total' => round($fortuneTotal + $marketTotal, 2),
            'fortune' => round($fortuneTotal, 2),
            'marketplace' => round($marketTotal, 2),
            'hourly' => $hourly,
        ];
    }

    // ────────────────────────────────────────────────────────────
    // ตัวช่วย
    // ────────────────────────────────────────────────────────────

    /**
     * รันส่วนหนึ่งของหน้า — พังแล้วได้ค่าสำรอง (log ไว้ ไม่ทำให้ทั้งหน้า 500)
     */
    private function safe(callable $fn, mixed $fallback): mixed
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            Log::warning('AdminApp ops/summary: ส่วนหนึ่งของหน้าแรกพัง', [
                'error' => \App\Support\SafeLog::exceptionMessage($e),
            ]);

            return $fallback;
        }
    }

    private function emptyBox(bool $withAmount): array
    {
        return array_merge(
            ['count' => 0],
            $withAmount ? ['amount_thb' => 0.0] : [],
            ['oldest_minutes' => null, 'preview' => []]
        );
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
