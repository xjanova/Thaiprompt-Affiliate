<?php

namespace App\Http\Controllers\Api\Admin\Fortune;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\Fortune\FortuneReadingResource;
use App\Models\FortuneReading;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        return response()->json([
            'success' => true,
            'data' => new FortuneReadingResource($reading->fresh()),
            'message' => 'มาร์คจ่ายเรียบร้อย',
        ]);
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
}
