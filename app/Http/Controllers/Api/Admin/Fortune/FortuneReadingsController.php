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

        if ($reading->isJuntraBill()) {
            return response()->json([
                'success' => false,
                'message' => FortuneReading::JUNTRA_PAID_ELSEWHERE,
            ], 422);
        }

        // 🩹 (v3) ยอดที่บันทึก = ยอดจริงของบิลใบนี้ — เดิมบิลที่ amount_paid = 0 ถูกบันทึกเป็น 49 ตายตัว
        //   (ไม่มีแพคเกจไหนราคา 49 ⇒ รายได้/ค่าแนะนำเพี้ยนทุกใบ) ลำดับเดียวกับ amount_thb ในรายการบิล:
        //   amount ที่แอดมินส่งมา → amount_paid > 0 → ยอดทศนิยมจาก UPA → ราคาแพคเกจในตั้งค่า · ไม่มีเลย = 422
        $amount = array_key_exists('amount', $data) && $data['amount'] !== null
            ? (float) $data['amount']
            : $this->billAmountFor($reading);
        if ($amount === null) {
            return response()->json([
                'success' => false,
                'error_code' => 'AMOUNT_REQUIRED',
                'message' => 'บิลนี้ไม่มียอดให้ดึงอัตโนมัติ — กรุณาระบุยอดที่ได้รับ (amount)',
            ], 422);
        }

        $reading->is_paid = true;
        $reading->amount_paid = $amount;
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
     * ยอดของบิลใบนี้สำหรับ mark-paid เมื่อแอดมินไม่ได้ระบุ amount — null = หายอดไม่ได้ (ต้องให้แอดมินระบุ)
     *
     * ลำดับเดียวกับ FortuneBillPresenter::billAmount / FortuneBillBuckets::billAmountSql (amount_thb ในรายการบิล):
     *   1. amount_paid > 0
     *   2. ยอดทศนิยมจาก UPA (unique_payment_amounts.unique_amount — ยอดที่ลูกค้าเห็นใน QR)
     *   3. ราคาแพคเกจในตั้งค่าแม่หมอ (deep_reading_price / celtic_cross_price / reading_price) — ห้ามเดาเลขตายตัว
     */
    private function billAmountFor(FortuneReading $reading): ?float
    {
        if ((float) $reading->amount_paid > 0) {
            return round((float) $reading->amount_paid, 2);
        }

        if ($reading->unique_payment_amount_id) {
            $upaAmount = (float) DB::table('unique_payment_amounts')
                ->where('id', $reading->unique_payment_amount_id)
                ->value('unique_amount');
            if ($upaAmount > 0) {
                return round($upaAmount, 2);
            }
        }

        $settings = \App\Models\FortuneTellingSetting::getSettings();
        $price = match ($reading->reading_type) {
            FortuneReading::READING_TYPE_DEEP => (float) ($settings->deep_reading_price ?? 0),
            FortuneReading::READING_TYPE_CELTIC_CROSS => (float) ($settings->celtic_cross_price ?? 0),
            FortuneReading::READING_TYPE_BASIC, null => (float) ($settings->reading_price ?? 0),
            default => 0.0,
        };

        return $price > 0 ? round($price, 2) : null;
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
     * คืนเงิน/ยกเลิกการอนุมัติบิลที่จ่ายแล้ว — ไม่ได้โอนเงินคืนจริง (แอดมินโอนคืนเองนอกระบบ)
     *
     * 🩹 (v3) เดิมพลิก is_paid = false อย่างเดียว ⇒ UPA ค้าง used (ยอดทศนิยมไม่ถูกปล่อย) · SMS ยังผูกกับบิลนี้
     *   (เงินจริงของคนอื่นที่ match ผิดไปไม่กลับไปหาบิลที่ถูก) · ค่าแนะนำไม่ถูกดึงคืน
     *   ตอนนี้ใช้ FortuneReading::voidApproval() ตัวเดียวกับปุ่ม void บนหน้าเว็บ (Admin\FortuneBillingController::void)
     *   — คืน UPA → cancelled · ปลด SMS · ดึงค่าแนะนำคืน · ปิดบิล (completed + approval_voided) + audit
     *   รูปคำตอบเดิมคงไว้ (FortuneReadingResource + message) · บิลจะอยู่กอง refunded ด้วย status_reason = approval_voided
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

        // 🌙 บิลจันทรา: คืนเงินที่จันทรา (คืนเครดิตลูกค้า + ดึงค่าแนะนำคืน) — ที่นี่แค่พลิกธง
        if ($reading->isJuntraBill()) {
            return response()->json([
                'success' => false,
                'message' => FortuneReading::JUNTRA_VOID_ELSEWHERE,
            ], 422);
        }

        $reason = trim((string) ($data['reason'] ?? ''));
        $amountWas = $reading->amount_paid;

        try {
            $result = $reading->voidApproval(
                'คืนเงินจากแอปแอดมิน'.($reason !== '' ? ': '.$reason : ''),
                $request->user()?->id
            );
        } catch (\Throwable $e) {
            // deadlock/รอล็อกนาน — voidApproval โยนออกมาให้ลองใหม่ทั้งก้อน (ยังไม่มีอะไรถูกเปลี่ยน)
            Log::error('FortuneReading: refund (voidApproval) failed', [
                'reading_id' => $reading->id,
                'error' => \App\Support\SafeLog::exceptionMessage($e),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'คืนเงินไม่สำเร็จ ระบบกำลังยุ่ง — ลองใหม่อีกครั้ง',
            ], 503);
        }

        if (! ($result['ok'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'คืนเงินไม่สำเร็จ',
            ], 422);
        }

        Log::info('FortuneReading: refund (voidApproval) by admin app', [
            'reading_id' => $reading->id,
            'admin_id' => $request->user()?->id,
            'reason' => $reason !== '' ? $reason : null,
            'amount_was' => $amountWas,
            'reverted' => $result['reverted'] ?? [],
            'warnings' => $result['warnings'] ?? [],
        ]);

        $message = 'ส่งเข้าคิวคืนเงินแล้ว';
        if (! empty($result['warnings'])) {
            $message .= ' ⚠️ '.implode('; ', $result['warnings']);
        }

        return response()->json([
            'success' => true,
            'data' => new FortuneReadingResource($reading->fresh()),
            'message' => $message,
        ]);
    }

    /**
     * POST /api/admin/fortune/readings/{reading}/retry
     *
     * 🛟 (v3) "ทำนายซ้ำ" บิลจ่ายแล้วที่ค้าง — เซิร์ฟเวอร์เลือกวิธีกู้เอง (ดู App\Services\AdminApp\StuckReadingRetrier)
     * ไม่รับ body · คำตอบ {success, action, message, data: {reading_id, action, delivered, stuck_reason, conversation_status}}
     */
    public function retry(FortuneReading $reading, Request $request, \App\Services\AdminApp\StuckReadingRetrier $retrier): JsonResponse
    {
        [$status, $body] = $retrier->retry($reading, $request->user()?->id);

        return response()->json($body, $status);
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
     * Reconstructs the closest-to-Messenger conversation we can from everything
     * persisted for a reading — so warroom /chat shows the real exchange, not an
     * empty shell. Sources, woven together in conversation-flow order:
     *   1. initial customer question(s) + any sent image (slip/photo)
     *   2. payment confirmation
     *   3. Celtic Cross: the 10 cards as they were opened (conversation_state),
     *      the birthdate the customer gave, and every Q&A turn
     *      (fortune_celtic_questions — the bulk of a 99฿ conversation)
     *   4. the row-level AI response (Deep/Basic — skipped for Celtic, whose Q1
     *      is already stored as a celtic question, to avoid a duplicate)
     *   5. admin replies typed in Messenger after the bot (fortune_admin_qa)
     *
     * NOTE: the Fortune bot does NOT persist a full raw Messenger log — free-form
     * chatter (small talk, greetings) outside these structured slots isn't stored
     * anywhere, so it can't be shown. This is the most faithful reconstruction the
     * data allows.
     */
    public function transcript(FortuneReading $reading): JsonResponse
    {
        // 💬 (2026-06-19) Realtime path — if a live chat log exists for this
        //    customer TODAY (Redis, captured verbatim from Messenger), that's the
        //    most faithful transcript. Only for recently-active readings; older
        //    ones fall through to the structured reconstruction below (their day's
        //    log has expired / cleared at midnight).
        $tz = \App\Services\Fortune\FortuneChatLogService::TZ;
        $recentlyActive = optional($reading->updated_at)->gte(\Carbon\Carbon::now($tz)->startOfDay());
        $pid = $reading->platform_user_id ?: $reading->facebook_user_id;
        if ($recentlyActive && $pid) {
            $live = app(\App\Services\Fortune\FortuneChatLogService::class)
                ->getForCustomer($reading->platform ?: 'facebook', (string) $pid);
            if (! empty($live)) {
                $messages = [['id' => 1, 'role' => 'system', 'text' => '💬 บทสนทนาเรียลไทม์วันนี้', 'ts' => null]];
                $mid = 2;
                foreach ($live as $m) {
                    $messages[] = [
                        'id' => $mid++,
                        'role' => $m['role'] ?? 'user',
                        'text' => (string) ($m['text'] ?? ''),
                        'ts' => $m['ts'] ?? null,
                        'by' => $m['by'] ?? null,
                        'ai' => $m['ai'] ?? null,
                        'image_url' => $m['image_url'] ?? null,
                    ];
                }

                return response()->json([
                    'success' => true,
                    'data' => [
                        'reading_id' => $reading->id,
                        'messages' => $messages,
                        'source' => 'redis',
                        'generated_at' => now()->toIso8601String(),
                    ],
                ]);
            }
        }

        // Each item carries a coarse conversation PHASE + a timestamp; we sort by
        // (phase, ts) so the flow reads correctly even where exact timestamps are
        // missing or identical (e.g. the initial question and the row share
        // created_at). Phases mirror the real journey: greet → ask → pay → cards
        // → birthdate → Q&A → final reading → admin follow-ups.
        $items = [];
        $created = optional($reading->created_at)->toIso8601String();
        $add = function (int $phase, string $role, ?string $text, ?string $ts, array $extra = []) use (&$items) {
            $items[] = array_merge([
                'role' => $role,
                'text' => (string) $text,
                'ts' => $ts,
                '_phase' => $phase,
                '_sort' => $ts ? strtotime($ts) : 0,
            ], $extra);
        };

        $add(0, 'system', 'เริ่มสนทนา · '.$created, $created);

        foreach ($this->normalizeQuestions($reading->questions) as $q) {
            $add(1, 'user', (string) $q, $created);
        }

        // 📸 Customer-sent image (payment slip or fortune-question picture).
        if (! empty($reading->user_image_url)) {
            $isSlip = $reading->is_paid || ! empty($reading->bill_reference);
            $add(2, 'user', $isSlip ? '📎 ส่งสลิปการโอน' : '📷 ส่งรูปภาพ', $created, [
                'image_url' => (string) $reading->user_image_url,
            ]);
        }

        // Payment confirmation.
        if ($reading->is_paid && $reading->amount_paid > 0 && $reading->paid_at) {
            $paidTs = optional($reading->paid_at)->toIso8601String();
            $add(3, 'system', '✓ รับชำระ ฿'.number_format((float) $reading->amount_paid, 2), $paidTs);
        }

        // 🃏 Celtic Cross — the cards as they were opened (1..10), the birthdate,
        //    then every Q&A turn. This is the heart of a 99฿ conversation.
        $celticCards = $reading->getCelticCards();
        if (is_array($celticCards) && count($celticCards) > 0) {
            ksort($celticCards);
            foreach ($celticCards as $pos => $card) {
                if (! is_array($card)) {
                    continue;
                }
                $name = $card['card_name_th'] ?? $card['card_name_en'] ?? '?';
                $rev = ! empty($card['is_reversed']) ? ' (ไพ่กลับหัว)' : '';
                $posName = $card['position_name'] ?? ('ใบที่ '.$pos);
                $add(4, 'system', "🃏 เปิดไพ่ใบที่ {$pos} · {$posName} → {$name}{$rev}", $card['picked_at'] ?? null);
            }
        }

        // 🎂 Birthdate the customer provided for the base chart (พื้นดวง).
        $birthdate = $reading->getConversationState('celtic_birthdate_text');
        if (! empty($birthdate) && ! $reading->getConversationState('celtic_birthdate_from_prior')) {
            $add(5, 'user', '🎂 '.(string) $birthdate, null);
        }

        // 🔮 Celtic Q&A turns (question + AI answer per row). The reading row's
        //    ai_response is just a copy of Q1, so when Q&A exists we render the
        //    Q&A list and skip the row response below (no duplicate).
        $hasCelticQa = false;
        if (Schema::hasTable('fortune_celtic_questions')) {
            foreach ($reading->celticQuestions()->get() as $cq) {
                $hasCelticQa = true;
                if (! empty($cq->question)) {
                    $add(6, 'user', (string) $cq->question, optional($cq->created_at)->toIso8601String());
                }
                if (! empty($cq->response)) {
                    $add(6, 'bot', (string) $cq->response, optional($cq->answered_at ?? $cq->created_at)->toIso8601String(), [
                        'ai' => $cq->ai_provider ?? null,
                    ]);
                }
            }
        }

        // Row-level AI response (Deep/Basic). Skipped for Celtic — already shown
        // as a Q&A turn above.
        if ($reading->ai_response && ! $hasCelticQa) {
            $isAdmin = ($reading->response_type ?? '') === 'admin';
            $add(7, $isAdmin ? 'admin' : 'bot', (string) $reading->ai_response, optional($reading->responded_at ?? $reading->created_at)->toIso8601String(), [
                'by' => $isAdmin ? 'admin' : null,
                'ai' => $isAdmin ? null : ($reading->ai_provider ?? null),
            ]);
        } elseif (! $reading->ai_response && ! $hasCelticQa && ! $reading->responded_at) {
            // Nothing delivered yet. Only a PAID reading is genuinely "awaiting a
            // prediction"; an unpaid row is just an ongoing chat.
            $add(7, 'system', $reading->is_paid ? '⌛ รอคำทำนาย' : '💬 กำลังสนทนา (ยังไม่ชำระเงิน)', null);
        }

        // Admin replies typed in Messenger after the bot (fortune_admin_qa).
        if (Schema::hasTable('fortune_admin_qa')) {
            $follows = DB::table('fortune_admin_qa')
                ->where('reading_id', $reading->id)
                ->orderBy('created_at')
                ->limit(200)
                ->get(['q_text', 'a_text', 'admin_user_id', 'created_at']);

            foreach ($follows as $row) {
                $ts = $row->created_at ? (string) $row->created_at : null;
                if (! empty($row->q_text)) {
                    $add(8, 'user', (string) $row->q_text, $ts);
                }
                if (! empty($row->a_text)) {
                    $add(8, 'admin', (string) $row->a_text, $ts, [
                        'by' => $row->admin_user_id ? ('admin#'.$row->admin_user_id) : 'admin',
                    ]);
                }
            }
        }

        // Sort by (phase, timestamp) then strip the sort keys + assign ids.
        usort($items, function ($a, $b) {
            return [$a['_phase'], $a['_sort']] <=> [$b['_phase'], $b['_sort']];
        });
        $messages = [];
        $mid = 1;
        foreach ($items as $it) {
            unset($it['_phase'], $it['_sort']);
            $messages[] = array_merge(['id' => $mid++], $it);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'reading_id' => $reading->id,
                'messages' => $messages,
                'source' => 'structured',
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    private function normalizeQuestions($raw): array
    {
        if (is_array($raw)) {
            return array_values(array_filter($raw, fn ($x) => $x !== '' && $x !== null));
        }
        if (is_string($raw) && $raw !== '') {
            $trimmed = trim($raw);
            if (str_starts_with($trimmed, '[')) {
                try {
                    $decoded = json_decode($trimmed, true);
                    if (is_array($decoded)) {
                        return array_values(array_filter($decoded, fn ($x) => $x !== '' && $x !== null));
                    }
                } catch (\Throwable $e) {
                }
            }

            return [$trimmed];
        }

        return [];
    }
}
