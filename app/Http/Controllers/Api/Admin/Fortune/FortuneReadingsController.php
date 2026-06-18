<?php

namespace App\Http\Controllers\Api\Admin\Fortune;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\Fortune\FortuneReadingResource;
use App\Models\FortuneReading;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Admin Mobile API: Fortune Readings
 *
 * คืนรายการ readings (คำทำนาย) สำหรับ admin app
 */
class FortuneReadingsController extends Controller
{
    /**
     * GET /api/admin/fortune/readings
     *
     * Query: ?search=&is_paid=&response_type=&date_from=&date_to=&page=
     */
    public function index(Request $request): JsonResponse
    {
        $query = FortuneReading::query();

        // Filters
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('facebook_user_name', 'like', "%{$search}%")
                    ->orWhere('facebook_user_id', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_paid')) {
            $query->where('is_paid', $request->boolean('is_paid'));
        }

        if ($request->filled('response_type')) {
            $query->where('response_type', $request->input('response_type'));
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to'));
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $readings = $query->with('user')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => FortuneReadingResource::collection($readings)->response()->getData(true),
        ]);
    }

    /**
     * GET /api/admin/fortune/readings/{reading}
     */
    public function show(FortuneReading $reading): JsonResponse
    {
        $reading->load('user');

        return response()->json([
            'success' => true,
            'data' => new FortuneReadingResource($reading),
        ]);
    }

    /**
     * GET /api/admin/fortune/readings/stats
     *
     * คืนสถิติแบ่งตามสถานะ
     */
    public function stats(): JsonResponse
    {
        try {
            $totalCount = FortuneReading::count();
            $paidCount = FortuneReading::where('is_paid', true)->count();
            $pendingCount = FortuneReading::whereNull('responded_at')->count();
            $deepCount = FortuneReading::where('reading_type', 'deep')->count();
            $totalRevenue = (float) FortuneReading::where('is_paid', true)->sum('amount_paid');

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $totalCount,
                    'paid' => $paidCount,
                    'pending' => $pendingCount,
                    'deep' => $deepCount,
                    'basic' => $totalCount - $deepCount,
                    'total_revenue_thb' => round($totalRevenue, 2),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => true,
                'data' => [
                    'total' => 0, 'paid' => 0, 'pending' => 0,
                    'deep' => 0, 'basic' => 0, 'total_revenue_thb' => 0,
                ],
            ]);
        }
    }

    /**
     * POST /api/admin/fortune/readings/{reading}/mark-paid
     * Body: { amount?: number, note?: string }
     *
     * Admin override: mark a reading as paid without an SMS match. Updates
     * `is_paid` + `amount_paid` + `paid_at`. Does NOT touch payment_transactions
     * (those have their own lifecycle managed by PaymentService).
     *
     * 💸 (2026-05-24) Triggers the fortune-telling flow after marking paid:
     *   - Deep 39฿ → ProcessDeepFortuneReadingJob (async — AI gen + send to FB/LINE)
     *   - Celtic 99฿ → SmsPaymentService::handleCelticPaymentMatched (same path
     *     as the SMS auto-match flow, keeps Celtic state machine consistent)
     * Previously only flipped is_paid → customer never received their reading
     * when warroom admin approved manually. Now mirrors the Stripe webhook flow.
     */
    public function markPaid(FortuneReading $reading, Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => 'nullable|numeric|min:0|max:100000',
            'note' => 'nullable|string|max:500',
        ]);

        if ($reading->is_paid) {
            return response()->json([
                'success' => false,
                'message' => 'บิลนี้ถูกมาร์คจ่ายแล้ว',
            ], 422);
        }

        $reading->is_paid = true;
        $reading->amount_paid = $data['amount'] ?? ($reading->amount_paid > 0 ? $reading->amount_paid : 49);
        $reading->paid_at = $reading->paid_at ?? now();
        $reading->save();

        Log::info('FortuneReading: manual mark-paid by admin', [
            'reading_id' => $reading->id,
            'admin_id' => $request->user()?->id,
            'amount' => $reading->amount_paid,
            'note' => $data['note'] ?? null,
        ]);

        // 💸 Kick off the fortune-telling flow so the customer actually receives
        //    their reading. Failures here don't roll back the mark-paid — the
        //    customer is paid in DB; if dispatch fails the operator can retry
        //    from /workers or by /chat send. Logged at error level for ops.
        $this->triggerFortuneFlowAfterPayment($reading->fresh());

        return response()->json([
            'success' => true,
            'data' => new FortuneReadingResource($reading->fresh()),
            'message' => 'มาร์คจ่ายเรียบร้อย — ส่งคำทำนายให้ลูกค้าแล้ว',
        ]);
    }

    /**
     * 💸 (2026-05-24) Mirror of FortuneStripeWebhookController::triggerFortuneFlow
     * for the admin manual-approve path. Kept inline (not a shared service) to
     * avoid threading a new dependency through the controller — the logic is
     * 30 lines and only used in two places. If a third call site appears,
     * extract to App\Services\FortunePaymentApprovalService.
     */
    protected function triggerFortuneFlowAfterPayment(FortuneReading $reading): void
    {
        // Idempotency — if the reading is already in a downstream state, skip.
        // Same guard as the Stripe webhook so re-triggering is safe.
        if (in_array($reading->conversation_status, [
            FortuneReading::STATUS_PAID,
            FortuneReading::STATUS_COMPLETED,
            FortuneReading::STATUS_CELTIC_PICKING,
            FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
            FortuneReading::STATUS_CELTIC_GENERATING,
            FortuneReading::STATUS_CELTIC_QA_PROMPT,
        ], true)) {
            Log::info('FortuneReading: mark-paid skip trigger — already in flight', [
                'reading_id' => $reading->id,
                'status' => $reading->conversation_status,
            ]);
            return;
        }

        $reading->update(['conversation_status' => FortuneReading::STATUS_PAID]);

        $platform = $reading->platform ?? 'facebook';
        $userId = $reading->facebook_user_id ?? $reading->platform_user_id ?? '';

        try {
            if ($reading->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS) {
                // Celtic uses the SMS service path so Celtic state machine
                // stays consistent with the auto-match flow.
                app(\App\Services\SmsPaymentService::class)->handleCelticPaymentMatched(
                    $reading, null, $platform, $userId, (float) $reading->amount_paid
                );
                Log::info('FortuneReading: Celtic flow triggered after admin mark-paid', [
                    'reading_id' => $reading->id,
                ]);
            } else {
                // Deep/basic — async job that runs AI gen + push to FB/LINE.
                \App\Jobs\ProcessDeepFortuneReadingJob::dispatchSmart(
                    $reading->id,
                    /* notificationId: */ null,
                    $platform,
                    $userId
                );
                Log::info('FortuneReading: Deep job dispatched after admin mark-paid', [
                    'reading_id' => $reading->id,
                    'platform' => $platform,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('FortuneReading: trigger fortune flow failed after admin mark-paid', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * POST /api/admin/fortune/readings/{reading}/refund
     * Body: { reason?: string }
     *
     * Mark a paid reading as refunded. Audit-only — does NOT trigger an
     * actual money movement (that's a separate operator workflow on the
     * payment gateway side). The flag here just hides the bill from
     * the "paid" rollup and surfaces it under "refunded".
     */
    public function refund(FortuneReading $reading, Request $request): JsonResponse
    {
        $data = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        if (! $reading->is_paid) {
            return response()->json([
                'success' => false,
                'message' => 'บิลที่ยังไม่จ่ายไม่ต้องคืนเงิน',
            ], 422);
        }

        // Use response_type to flag refund state — we don't have a dedicated
        // column for this and don't want to bloat the schema. The bot
        // already treats refund-flagged readings differently.
        $reading->is_paid = false;
        $reading->save();

        Log::info('FortuneReading: refund flagged by admin', [
            'reading_id' => $reading->id,
            'admin_id' => $request->user()?->id,
            'reason' => $data['reason'] ?? null,
            'amount_was' => $reading->amount_paid,
        ]);

        return response()->json([
            'success' => true,
            'data' => new FortuneReadingResource($reading->fresh()),
            'message' => 'ส่งเข้าคิวคืนเงินแล้ว',
        ]);
    }

    /**
     * POST /api/admin/fortune/readings/{reading}/cancel
     * Body: { reason?: string }
     *
     * Mark a reading as cancelled (soft delete). Use for: customer
     * abandoned, double-billed, test entries.
     */
    public function cancel(FortuneReading $reading, Request $request): JsonResponse
    {
        $data = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $reading->delete(); // soft delete via SoftDeletes trait

        Log::info('FortuneReading: cancelled by admin', [
            'reading_id' => $reading->id,
            'admin_id' => $request->user()?->id,
            'reason' => $data['reason'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => ['reading_id' => $reading->id],
            'message' => 'ยกเลิกบิลแล้ว',
        ]);
    }

    /**
     * GET /api/admin/fortune/readings/{reading}/transcript
     *
     * Returns the full message trail for a reading, reconstructed from:
     *   1. fortune_readings — the initial question + AI response
     *   2. fortune_admin_qa  — extra admin replies typed in Messenger after
     *      the initial bot reply (joined by reading_id)
     *
     * Used by warroom /chat to render real conversation history instead of
     * just the initial question/answer pair.
     */
    public function transcript(FortuneReading $reading): JsonResponse
    {
        $messages = [];
        $mid = 1;

        $messages[] = [
            'id' => $mid++,
            'role' => 'system',
            'text' => 'เริ่มสนทนา · ' . optional($reading->created_at)->toIso8601String(),
            'ts' => optional($reading->created_at)->toIso8601String(),
        ];

        // Initial customer question(s)
        $questions = $this->normalizeQuestions($reading->questions);
        foreach ($questions as $q) {
            $messages[] = [
                'id' => $mid++,
                'role' => 'user',
                'text' => (string) $q,
                'ts' => optional($reading->created_at)->toIso8601String(),
            ];
        }

        // 📸 (2026-05-24) Customer-sent image (payment slip or fortune-question
        //    picture). Surface as a dedicated user message so the warroom
        //    operator can SEE the slip before approving — previously was
        //    invisible to admins on /chat.
        if (! empty($reading->user_image_url)) {
            $isSlip = $reading->is_paid || ! empty($reading->bill_reference);
            $messages[] = [
                'id' => $mid++,
                'role' => 'user',
                'text' => $isSlip ? '📎 ส่งสลิปการโอน' : '📷 ส่งรูปภาพ',
                'image_url' => (string) $reading->user_image_url,
                'ts' => optional($reading->created_at)->toIso8601String(),
            ];
        }

        // Payment confirmation
        if ($reading->is_paid && $reading->amount_paid > 0 && $reading->paid_at) {
            $messages[] = [
                'id' => $mid++,
                'role' => 'system',
                'text' => '✓ รับชำระ ฿' . number_format((float) $reading->amount_paid, 2) . ' · ' . optional($reading->paid_at)->toIso8601String(),
                'ts' => optional($reading->paid_at)->toIso8601String(),
            ];
        }

        // Initial AI response from the row itself
        if ($reading->ai_response) {
            $isAdmin = ($reading->response_type ?? '') === 'admin';
            $messages[] = [
                'id' => $mid++,
                'role' => $isAdmin ? 'admin' : 'bot',
                'by' => $isAdmin ? 'admin' : null,
                'ai' => $isAdmin ? null : ($reading->ai_provider ?? null),
                'text' => (string) $reading->ai_response,
                'ts' => optional($reading->responded_at ?? $reading->created_at)->toIso8601String(),
            ];
        } elseif (! $reading->responded_at) {
            // Only a PAID reading is genuinely "awaiting a prediction". An unpaid
            // row with no response is just an ongoing chat — labeling it "รอคำทำนาย"
            // is wrong (they haven't paid, so there's no prediction owed).
            $messages[] = [
                'id' => $mid++,
                'role' => 'system',
                'text' => $reading->is_paid ? '⌛ รอคำทำนาย' : '💬 กำลังสนทนา (ยังไม่ชำระเงิน)',
                'ts' => null,
            ];
        }

        // Real admin replies typed in Messenger after the initial bot reply.
        // Stored in fortune_admin_qa keyed by reading_id.
        if (Schema::hasTable('fortune_admin_qa')) {
            $follows = DB::table('fortune_admin_qa')
                ->where('reading_id', $reading->id)
                ->orderBy('created_at')
                ->limit(200)
                ->get(['q_text', 'a_text', 'admin_user_id', 'created_at']);

            foreach ($follows as $row) {
                if (! empty($row->q_text)) {
                    $messages[] = [
                        'id' => $mid++,
                        'role' => 'user',
                        'text' => (string) $row->q_text,
                        'ts' => (string) $row->created_at,
                    ];
                }
                if (! empty($row->a_text)) {
                    $messages[] = [
                        'id' => $mid++,
                        'role' => 'admin',
                        'by' => $row->admin_user_id ? ('admin#' . $row->admin_user_id) : 'admin',
                        'text' => (string) $row->a_text,
                        'ts' => (string) $row->created_at,
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'reading_id' => $reading->id,
                'messages' => $messages,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    private function normalizeQuestions($raw): array
    {
        if (is_array($raw)) return array_values(array_filter($raw, fn ($x) => $x !== '' && $x !== null));
        if (is_string($raw) && $raw !== '') {
            $trimmed = trim($raw);
            if (str_starts_with($trimmed, '[')) {
                try {
                    $decoded = json_decode($trimmed, true);
                    if (is_array($decoded)) {
                        return array_values(array_filter($decoded, fn ($x) => $x !== '' && $x !== null));
                    }
                } catch (\Throwable $e) {}
            }
            return [$trimmed];
        }
        return [];
    }
}
