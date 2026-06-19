<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneReading;
use App\Models\FortuneTakeoverLog;
use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use App\Services\FortuneBanService;
use App\Services\FortuneTakeoverService;
use App\Services\LineFortuneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Admin Fortune Takeover Controller
 *
 * จัดการระบบเทคโอเวอร์ (Takeover Control) — แอดมิน/แม่หมอคุยแทน AI
 *
 * Features:
 * - แสดงรายการ conversation ทั้งหมด พร้อมสถานะ takeover
 * - กดเทคโอเวอร์ manual (หยุด AI)
 * - สั่งให้ AI กลับมา (resume)
 * - ต่อเวลา
 * - ส่งข้อความผ่านแอดมินพาเนล (LINE/Facebook)
 * - ดู conversation history
 */
class FortuneTakeoverController extends Controller
{
    protected FortuneTakeoverService $takeoverService;

    public function __construct(FortuneTakeoverService $takeoverService)
    {
        $this->takeoverService = $takeoverService;
    }

    /**
     * แสดงรายการ conversations พร้อมสถานะ takeover
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'active'); // active|taken_over|all
        $platform = $request->query('platform'); // line|facebook|null
        $search = trim($request->query('search', ''));

        $query = FortuneReading::with(['takeoverAdmin', 'user'])
            ->latest('updated_at');

        // Filter: active conversations (ไม่ completed)
        if ($filter === 'active') {
            $query->whereNotIn('conversation_status', [
                FortuneReading::STATUS_COMPLETED,
            ])
                ->where('updated_at', '>=', now()->subDays(7));
        } elseif ($filter === 'taken_over') {
            $query->takenOver();
        }

        // Filter by platform
        if ($platform && in_array($platform, ['line', 'facebook'])) {
            $query->where('platform', $platform);
        }

        // Search by user name or id
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('facebook_user_name', 'like', "%{$search}%")
                    ->orWhere('facebook_user_id', 'like', "%{$search}%")
                    ->orWhere('platform_user_id', 'like', "%{$search}%")
                    ->orWhere('bill_reference', 'like', "%{$search}%");
            });
        }

        $readings = $query->paginate(20)->withQueryString();

        $stats = [
            'total_taken_over' => FortuneReading::takenOver()->count(),
            'total_active' => FortuneReading::whereNotIn('conversation_status', [
                FortuneReading::STATUS_COMPLETED,
            ])->where('updated_at', '>=', now()->subDays(7))->count(),
            'takeovers_today' => FortuneTakeoverLog::where('action', FortuneTakeoverLog::ACTION_TAKEOVER)
                ->whereDate('created_at', today())
                ->count(),
        ];

        $settings = FortuneTellingSetting::getSettings();

        return view('admin.fortune.takeover.index', [
            'readings' => $readings,
            'stats' => $stats,
            'settings' => $settings,
            'filter' => $filter,
            'platform' => $platform,
            'search' => $search,
        ]);
    }

    /**
     * แสดงรายละเอียด conversation (chat view + controls)
     *
     * @return \Illuminate\View\View
     */
    public function show(FortuneReading $reading)
    {
        $reading->load(['takeoverAdmin', 'user', 'takeoverLogs.user']);

        $settings = FortuneTellingSetting::getSettings();

        return view('admin.fortune.takeover.show', [
            'reading' => $reading,
            'settings' => $settings,
        ]);
    }

    /**
     * กดเทคโอเวอร์เอง (manual)
     */
    public function takeover(Request $request, FortuneReading $reading): JsonResponse
    {
        $validated = $request->validate([
            'minutes' => 'nullable|integer|min:1|max:1440',
        ]);

        $minutes = $this->takeoverService->takeover(
            $reading,
            FortuneReading::TAKEOVER_REASON_MANUAL,
            Auth::id(),
            $validated['minutes'] ?? null,
            null,
            true, // forceIgnoreDisabled — admin สั่งเอง ต้องทำงานเสมอ
        );

        $reading->refresh();

        return response()->json([
            'success' => true,
            'message' => "เทคโอเวอร์สำเร็จ — AI จะหยุดทำงาน {$minutes} นาที",
            'data' => [
                'minutes' => $minutes,
                'until' => $reading->admin_takeover_until?->toIso8601String(),
                'remaining_seconds' => $reading->takeoverRemainingSeconds(),
            ],
        ]);
    }

    /**
     * สั่งให้ AI กลับมาทำงาน
     */
    public function resume(FortuneReading $reading): JsonResponse
    {
        $this->takeoverService->resume($reading, Auth::id(), false);

        return response()->json([
            'success' => true,
            'message' => '✨ AI กลับมาทำงานแล้ว',
        ]);
    }

    /**
     * ต่อเวลาเทคโอเวอร์
     */
    public function extend(Request $request, FortuneReading $reading): JsonResponse
    {
        $validated = $request->validate([
            'minutes' => 'required|integer|min:1|max:1440',
        ]);

        $added = $this->takeoverService->extend($reading, $validated['minutes'], Auth::id());

        $reading->refresh();

        return response()->json([
            'success' => true,
            'message' => "⏱ ต่อเวลาอีก {$added} นาที",
            'data' => [
                'minutes_added' => $added,
                'until' => $reading->admin_takeover_until?->toIso8601String(),
                'remaining_seconds' => $reading->takeoverRemainingSeconds(),
            ],
        ]);
    }

    /**
     * ส่งข้อความจากแอดมินพาเนลไปยัง LINE/Facebook user
     *
     * Flow:
     * 1. ตรวจว่า reading กำลังถูกเทคโอเวอร์อยู่ (ถ้าไม่ → takeover ก่อน)
     * 2. ส่งข้อความผ่าน platform ที่เหมาะสม
     * 3. บันทึก log
     */
    public function sendMessage(Request $request, FortuneReading $reading): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|min:1|max:2000',
        ]);

        $message = trim($validated['message']);

        // ถ้ายังไม่ได้ takeover → takeover ก่อนเพื่อความปลอดภัย
        // ใช้ forceIgnoreDisabled เพื่อให้ทำงานได้แม้ settings ปิดอยู่ (admin สั่งเอง)
        if (! $reading->isAdminTakenOver()) {
            $this->takeoverService->takeover(
                $reading,
                FortuneReading::TAKEOVER_REASON_MANUAL,
                Auth::id(),
                null,
                null,
                true, // forceIgnoreDisabled — admin กดเอง ต้องทำงานเสมอ
            );
            $reading->refresh();
        }

        // ส่งข้อความผ่าน platform
        $sent = $this->sendMessageToPlatform($reading, $message);

        if (! $sent) {
            // 🆕 (2026-05-17) Actionable hint — ครอบคลุม 2 เคสที่พบบ่อย
            return response()->json([
                'success' => false,
                'message' => "❌ ส่งข้อความไม่สำเร็จ — อาจเป็นเพราะ:\n"
                    . "• ลูกค้าหายไปนานเกิน 7 วัน (Meta จำกัด — รอให้ลูกค้าทักกลับมาก่อน)\n"
                    . "• หรือยังไม่ได้เปิด HUMAN_AGENT permission ใน Facebook App → Messenger → Advanced Messaging\n"
                    . "• ตรวจสอบรายละเอียดใน storage/logs/laravel.log (grep error_subcode)",
            ], 500);
        }

        // บันทึก log
        $this->takeoverService->logMessage($reading, Auth::id(), $message);

        // 💬 (2026-06-19) Mirror into the realtime chat log so this (legacy admin
        //    panel) reply also shows in the warroom transcript. This path bypasses
        //    FortuneChannelManager::sendResponse, so log explicitly. Fail-safe.
        try {
            $uid = $reading->platform_user_id ?: $reading->facebook_user_id;
            if ($uid) {
                app(\App\Services\Fortune\FortuneChatLogService::class)->record(
                    $reading->platform ?: 'facebook',
                    (string) $uid,
                    'admin',
                    $message,
                    ['by' => 'admin#' . (Auth::id() ?? '?')]
                );
            }
        } catch (\Throwable $logErr) {
            // ignore — chat log is best-effort
        }

        return response()->json([
            'success' => true,
            'message' => '💬 ส่งข้อความสำเร็จ',
            'data' => [
                'sent_at' => now()->toIso8601String(),
                'message_preview' => mb_substr($message, 0, 100),
            ],
        ]);
    }

    /**
     * 🚫 (2026-05-23) แบน user จากหน้า takeover (list/detail) ในคลิกเดียว
     *
     * เป้าหมาย: admin กดปุ่ม "🚫 แบน" ที่ตาราง/หน้า detail → เลือก preset duration → POST ที่ route นี้
     * ดึง platform + user_id + display_name จาก $reading อัตโนมัติ (ไม่ต้อง copy PSID เอง)
     * รองรับทั้งเคสที่ลูกค้ายังไม่สร้างบิล (status=new) เพราะ FortuneReading ถูกสร้างตั้งแต่ first webhook
     *
     * Duration preset:
     *   - 10m  = 10 นาที (เตือนระยะสั้น)
     *   - 1h   = 1 ชั่วโมง
     *   - 24h  = 1 วัน
     *   - 7d   = 7 วัน
     *   - permanent = ถาวร (null minutes)
     *
     * @param  Request  $request  duration (required) + from (index|show)
     * @param  FortuneReading  $reading  reading ที่กำลังแชทอยู่
     * @param  FortuneBanService  $banService  injected service
     */
    public function ban(Request $request, FortuneReading $reading, FortuneBanService $banService): RedirectResponse
    {
        $validated = $request->validate([
            'duration' => 'required|in:10m,1h,24h,7d,permanent',
            'from' => 'nullable|in:index,show',
        ]);

        // แปลง preset → นาที (null = ถาวร)
        $minutes = match ($validated['duration']) {
            '10m' => 10,
            '1h' => 60,
            '24h' => 1440,
            '7d' => 10080,
            'permanent' => null,
        };

        // ดึง platform + user_id จาก reading
        // FB: facebook_user_id (PSID), LINE: platform_user_id
        $platform = $reading->platform ?? 'facebook';
        $userId = $platform === 'facebook'
            ? ($reading->facebook_user_id ?: $reading->platform_user_id)
            : ($reading->platform_user_id ?: $reading->facebook_user_id);

        if (empty($userId)) {
            return redirect()
                ->back()
                ->with('error', '❌ ไม่พบ user ID ของบทสนทนานี้ — แบนไม่ได้');
        }

        // เรียก service แบน (ใช้ updateOrCreate — กรณีถูกแบนอยู่แล้วจะทับด้วย duration ใหม่)
        $ban = $banService->ban(
            platform: $platform,
            platformUserId: $userId,
            minutes: $minutes,
            reason: 'แบนจากหน้า Takeover (Reading #' . $reading->id . ')',
            adminId: Auth::id(),
            displayName: $reading->facebook_user_name,
        );

        Log::info('🚫 Admin: แบน user จากหน้า takeover', [
            'ban_id' => $ban->id,
            'reading_id' => $reading->id,
            'platform' => $platform,
            'user_id' => $userId,
            'duration' => $validated['duration'],
            'admin_id' => Auth::id(),
        ]);

        // 🎯 ถ้า reading กำลังถูก takeover อยู่ → resume AI ก่อน
        //   (ทำเพื่อไม่ให้ takeover timer ค้างหลังจาก user ถูกแบน — บอทไม่คุยอยู่แล้ว ไม่ต้อง takeover)
        if ($reading->isAdminTakenOver()) {
            $this->takeoverService->resume($reading, Auth::id(), false);
        }

        // ข้อความ flash
        $name = $reading->facebook_user_name ?: $userId;
        $durationText = $ban->isPermanent() ? 'ถาวร' : $ban->remainingHumanReadable();
        $msg = "🚫 แบน {$name} เรียบร้อย ({$durationText})";

        // redirect กลับตาม from — default = index
        $from = $validated['from'] ?? 'index';
        $route = $from === 'show'
            ? route('admin.fortune.takeover.show', $reading)
            : route('admin.fortune.takeover.index');

        return redirect()->to($route)->with('success', $msg);
    }

    /**
     * ดึงสถานะ takeover แบบ live (สำหรับ Alpine.js polling)
     */
    public function status(FortuneReading $reading): JsonResponse
    {
        $reading->refresh();

        return response()->json([
            'is_active' => $reading->isAdminTakenOver(),
            'remaining_seconds' => $reading->takeoverRemainingSeconds(),
            'remaining_minutes' => $reading->takeoverRemainingMinutes(),
            'until' => $reading->admin_takeover_until?->toIso8601String(),
            'admin_name' => $reading->takeoverAdmin?->name,
            'reason' => $reading->admin_takeover_reason,
        ]);
    }

    /**
     * ส่งข้อความไปยัง platform ที่เหมาะสม
     *
     * 🆕 (2026-05-17) FB Admin send fix:
     *   ปัญหา: ลูกค้าทักล่าสุดเกิน 24 ชม. → RESPONSE fails → fallback POST_PURCHASE_UPDATE
     *          → ใช้ไม่ได้ถ้าลูกค้ายังไม่จ่าย → admin send fail เงียบ
     *   แก้: ส่ง message_tag = HUMAN_AGENT (Meta ให้แอดมิน human reply ได้ 7 วัน
     *        หลัง user DM ล่าสุด — ไม่ผูกกับการชำระเงิน)
     *        ต้องเปิดสิทธิ์ "Human Agent" ใน Facebook App → Messenger → Advanced Messaging
     *        Reference brain: 12f9a3d8e8d5 (Admin Takeover & FB Handover Limitations)
     */
    protected function sendMessageToPlatform(FortuneReading $reading, string $message): bool
    {
        $settings = FortuneTellingSetting::getSettings();
        $platform = $reading->platform ?? 'facebook';
        $userId = $reading->platform_user_id ?: $reading->facebook_user_id;

        if (empty($userId)) {
            Log::error('FortuneTakeover: ไม่มี user_id ไม่สามารถส่งข้อความได้', [
                'reading_id' => $reading->id,
            ]);

            return false;
        }

        try {
            if ($platform === 'line') {
                $service = new LineFortuneService($settings);

                return $service->sendMessageWithReplyFallback($userId, $message, null);
            }

            // Facebook (default) — admin manual send ใช้ HUMAN_AGENT tag เป็น fallback
            // ครอบคลุมทั้งเคสจ่ายแล้ว (POST_PURCHASE_UPDATE ก็ทำงานได้) และยังไม่จ่าย
            $service = new FacebookWebhookService($settings);

            return $service->sendMessage($userId, $message, [
                'from_admin' => true,
                'message_tag' => 'HUMAN_AGENT',
            ]);
        } catch (\Exception $e) {
            Log::error('FortuneTakeover: ส่งข้อความล้มเหลว', [
                'reading_id' => $reading->id,
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
