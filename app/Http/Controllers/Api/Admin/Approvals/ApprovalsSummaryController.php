<?php

namespace App\Http\Controllers\Api\Admin\Approvals;

use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use App\Models\MlmCommission;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\Ticket;
use App\Models\VendorStore;
use App\Support\SafeLog;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🔔 แอปแอดมิน: ตัวเลขป้าย (badge) ของทุกคิวอนุมัติในคำขอเดียว — GET /api/admin/approvals/summary
 *
 * - นิยามแต่ละคิว = ชุดเดียวกับรายการ (index) ของ endpoint นั้น ๆ (ค่าเริ่มต้นของตัวกรอง)
 * - ผลทั้งก้อน cache 20 วินาที ใช้ร่วมกันทุกแอดมิน · computed_at = เวลาที่คำนวณจริง · generated_at = เวลาของคำตอบนี้
 * - คิวที่คำนวณไม่สำเร็จ = null + ชื่ออยู่ใน degraded (ห้ามคืน 0 — แอปแยก "ไม่มีงาน" กับ "อ่านไม่ได้" ไม่ออก)
 */
class ApprovalsSummaryController extends Controller
{
    public const CACHE_KEY = 'admin_app:approvals_summary';

    private const CACHE_TTL = 20;

    /** @var array<int, string> */
    private array $degraded = [];

    /**
     * GET /api/admin/approvals/summary
     */
    public function index(): JsonResponse
    {
        $payload = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->build());
        $payload['generated_at'] = now()->toIso8601String();

        return response()->json(['success' => true, 'data' => $payload], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    /**
     * @return array<string, mixed>
     */
    private function build(): array
    {
        $now = now();
        $this->degraded = [];

        $queues = [
            'ekyc' => $this->section('ekyc', fn () => $this->box(
                KycVerification::query()->ekyc()->where('status', 'pending'),
                'COALESCE(processed_at, submitted_at, created_at)',
                $now
            )),
            'seller_applications' => $this->section('seller_applications', fn () => $this->box(
                VendorStore::query()->where('status', 'pending'),
                'updated_at',
                $now
            )),
            'rider_applications' => $this->section('rider_applications', fn () => $this->box(
                Rider::query()->where('status', 'pending'),
                'created_at',
                $now
            )),
            'rider_documents' => $this->section('rider_documents', fn () => $this->box(
                Rider::query()->whereNotNull('documents_changed_at'),
                'documents_changed_at',
                $now
            )),
            'rider_jobs' => $this->section('rider_jobs', fn () => $this->riderJobs($now)),
            'tickets' => $this->section('tickets', fn () => $this->box(
                TicketsController::applyBucket(Ticket::query(), 'open'),
                'COALESCE(last_reply_at, created_at)',
                $now
            )),
            'mlm_commissions' => $this->section('mlm_commissions', fn () => $this->mlm($now)),
        ];

        $total = 0;
        foreach ($queues as $box) {
            $total += (int) ($box['count'] ?? 0);
        }

        return [
            'queues' => $queues,
            'total' => $total,
            'computed_at' => $now->toIso8601String(),
            'generated_at' => $now->toIso8601String(),
            'degraded' => $this->degraded,
        ];
    }

    /**
     * จำนวน + นาทีที่รายการเก่าสุดรอ (คิวรีเดียว)
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return array{count: int, oldest_minutes: ?int}
     */
    private function box($query, string $sinceExpr, CarbonInterface $now): array
    {
        $row = $query->toBase()
            ->selectRaw('COUNT(*) AS c, MIN('.$sinceExpr.') AS oldest')
            ->first();

        return [
            'count' => (int) ($row->c ?? 0),
            'oldest_minutes' => $this->minutesSince($row->oldest ?? null, $now),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function riderJobs(CarbonInterface $now): array
    {
        $base = $this->box(RiderJobsController::applyFilter(RiderJob::query(), 'needs_decision'), 'rider_jobs.created_at', $now);

        return $base + [
            'disputed' => RiderJobsController::applyFilter(RiderJob::query(), 'disputed')->count(),
            'awaiting_release' => RiderJobsController::applyFilter(RiderJob::query(), 'awaiting_release')->count(),
            'manual_needed' => RiderJobsController::applyFilter(RiderJob::query(), 'manual_needed')->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mlm(CarbonInterface $now): array
    {
        $row = MlmCommission::query()->where('status', 'pending')->toBase()
            ->selectRaw('COUNT(*) AS c, MIN(created_at) AS oldest, COALESCE(SUM(commission_amount), 0) AS amount')
            ->first();

        return [
            'count' => (int) ($row->c ?? 0),
            'oldest_minutes' => $this->minutesSince($row->oldest ?? null, $now),
            'amount_thb' => round((float) ($row->amount ?? 0), 2),
            // อนุมัติแล้วแต่ยังไม่จ่าย (ไม่นับใน count — เป็นงานขั้นถัดไป)
            'approved_unpaid' => MlmCommission::query()->where('status', 'approved')->count(),
        ];
    }

    private function section(string $name, callable $fn): mixed
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            $this->degraded[] = $name;
            Log::warning('AdminApp approvals/summary: คิวหนึ่งอ่านไม่ได้', [
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

        try {
            $ts = $at instanceof CarbonInterface ? $at->getTimestamp() : Carbon::parse((string) $at)->getTimestamp();
        } catch (\Throwable) {
            return null;
        }

        return max(0, (int) floor(($now->getTimestamp() - $ts) / 60));
    }
}
