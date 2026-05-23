<?php

namespace App\Http\Controllers\Api\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\SmsPaymentNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Admin Mobile API: Finance/PaymentReconController
 *
 * Powers the Warroom Juntra `/payment` page: shows the SMS inbox + pending
 * bills + manual match/reject controls. All read+state-change ops are scoped
 * to `SmsPaymentNotification` and the matched `PaymentTransaction`.
 *
 * The actual matching logic already lives in
 * SmsPaymentNotification::attemptMatch() — this controller is a thin
 * read+command surface around it.
 */
class PaymentReconController extends Controller
{
    /**
     * GET /api/admin/payment/sms/inbox
     *
     * Query:
     *   ?status=pending|matched|confirmed|rejected|expired  (default: all)
     *   ?bank=KBANK|SCB|...
     *   ?date_from=YYYY-MM-DD
     *   ?date_to=YYYY-MM-DD
     *   ?search= (sender_or_receiver/reference_number)
     *   ?per_page=20 (1-100)
     */
    public function inbox(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $query = SmsPaymentNotification::query()
            ->with(['matchedTransaction:id,user_id,amount,status,payment_method,created_at'])
            ->orderByDesc('sms_timestamp')
            ->orderByDesc('id');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($bank = $request->input('bank')) {
            $query->where('bank', $bank);
        }
        if ($from = $request->input('date_from')) {
            $query->whereDate('sms_timestamp', '>=', $from);
        }
        if ($to = $request->input('date_to')) {
            $query->whereDate('sms_timestamp', '<=', $to);
        }
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('sender_or_receiver', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%");
            });
        }

        $page = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $page->getCollection()->map(fn ($n) => $this->present($n))->all(),
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    /**
     * GET /api/admin/payment/recon/stats
     *
     * Returns aggregated counters for the page header tiles.
     * Light-weight COUNT queries — safe to poll at the warroom refresh tick.
     */
    public function stats(): JsonResponse
    {
        $today = now()->startOfDay();
        $weekAgo = now()->subDays(7);

        $byStatus = SmsPaymentNotification::query()
            ->where('created_at', '>=', $weekAgo)
            ->selectRaw('status, COUNT(*) as total, SUM(amount) as amount_sum')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $todayCount = SmsPaymentNotification::query()
            ->where('created_at', '>=', $today)
            ->count();
        $todayMatched = SmsPaymentNotification::query()
            ->where('created_at', '>=', $today)
            ->where('status', 'matched')
            ->count();
        $todayPending = SmsPaymentNotification::query()
            ->where('created_at', '>=', $today)
            ->where('status', 'pending')
            ->count();

        $matchRate = $todayCount > 0
            ? round(($todayMatched / $todayCount) * 100, 1)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'today' => [
                    'total' => $todayCount,
                    'matched' => $todayMatched,
                    'pending' => $todayPending,
                    'match_rate_pct' => $matchRate,
                ],
                'week_by_status' => $byStatus->map(fn ($r) => [
                    'count' => (int) $r->total,
                    'amount_sum_thb' => (float) ($r->amount_sum ?? 0),
                ]),
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * POST /api/admin/payment/sms/{sms}/match
     *
     * Manually re-attempt matching for one SMS. Useful when a pending SMS
     * sat without a bill at the time it arrived but a bill was created later
     * (e.g. operator pre-paid before bill creation, or a bill was reissued
     * with the same unique amount).
     */
    public function match(SmsPaymentNotification $sms): JsonResponse
    {
        if ($sms->matched_transaction_id !== null) {
            return response()->json([
                'success' => false,
                'message' => 'SMS นี้ถูกจับคู่กับบิลไปแล้ว ไม่สามารถ re-match ได้',
            ], 422);
        }

        $ok = $sms->attemptMatch(autoConfirm: true);

        if ($ok) {
            Log::info('PaymentRecon: manual re-match success', [
                'sms_id' => $sms->id,
                'matched_transaction_id' => $sms->fresh()->matched_transaction_id,
            ]);

            return response()->json([
                'success' => true,
                'data' => ['sms' => $this->present($sms->fresh())],
                'message' => 'จับคู่สำเร็จ',
            ]);
        }

        return response()->json([
            'success' => false,
            'data' => ['sms' => $this->present($sms->fresh())],
            'message' => 'ยังไม่พบบิลที่ตรงกับ SMS นี้',
        ]);
    }

    /**
     * POST /api/admin/payment/sms/{sms}/reject
     *
     * Body: { reason?: string }
     *
     * Mark an SMS as rejected by an operator (e.g. duplicate, test, irrelevant).
     * This does NOT delete the row — the audit trail stays intact.
     */
    public function reject(SmsPaymentNotification $sms, Request $request): JsonResponse
    {
        if ($sms->status === 'matched' || $sms->status === 'confirmed') {
            return response()->json([
                'success' => false,
                'message' => 'SMS ที่จับคู่/ยืนยันแล้วไม่สามารถ reject ได้',
            ], 422);
        }

        $reason = (string) $request->input('reason', 'admin_rejected');

        $sms->status = 'rejected';
        // Stash the reason in raw_payload so audit can recover it later.
        $payload = $sms->raw_payload ? json_decode($sms->raw_payload, true) : [];
        if (! is_array($payload)) {
            $payload = ['original' => $sms->raw_payload];
        }
        $payload['_rejected_by_admin'] = [
            'admin_id' => $request->user()?->id,
            'reason' => $reason,
            'at' => now()->toIso8601String(),
        ];
        $sms->raw_payload = json_encode($payload);
        $sms->save();

        Log::info('PaymentRecon: SMS rejected by admin', [
            'sms_id' => $sms->id,
            'admin_id' => $request->user()?->id,
            'reason' => $reason,
        ]);

        return response()->json([
            'success' => true,
            'data' => ['sms' => $this->present($sms->fresh())],
            'message' => 'ปฏิเสธ SMS สำเร็จ',
        ]);
    }

    /**
     * Shared shape between inbox/match/reject responses. Keeps the warroom
     * adapter on one schema regardless of which endpoint hit it.
     */
    private function present(SmsPaymentNotification $sms): array
    {
        $matched = $sms->matchedTransaction;

        return [
            'id' => $sms->id,
            'bank' => $sms->bank,
            'type' => $sms->type,
            'amount' => (float) $sms->amount,
            'account_number' => $sms->account_number,
            'sender_or_receiver' => $sms->sender_or_receiver,
            'reference_number' => $sms->reference_number,
            'sms_timestamp' => $sms->sms_timestamp?->toIso8601String(),
            'device_id' => $sms->device_id,
            'status' => $sms->status,
            'matched_transaction_id' => $sms->matched_transaction_id,
            'created_at' => $sms->created_at?->toIso8601String(),
            'matched_bill' => $matched ? [
                'id' => $matched->id,
                'user_id' => $matched->user_id,
                'amount' => (float) $matched->amount,
                'status' => $matched->status,
                'payment_method' => $matched->payment_method,
                'created_at' => $matched->created_at?->toIso8601String(),
            ] : null,
        ];
    }
}
