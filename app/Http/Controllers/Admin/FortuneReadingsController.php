<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessDeepFortuneReadingJob;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneChannelManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Fortune Readings Controller
 *
 * จัดการประวัติการทำนาย
 */
class FortuneReadingsController extends Controller
{
    /**
     * แสดงรายการการทำนาย
     */
    public function index(Request $request)
    {
        $query = FortuneReading::query()
            ->with('user')
            ->orderBy('created_at', 'desc');

        // ค้นหาตามชื่อ / รหัสบิล / platform user id
        // รองรับ:
        //   - ชื่อลูกค้า (facebook_user_name)
        //   - รหัสบิล เช่น FTU-260425-T4022 (bill_reference)
        //   - Platform user ID (FB PSID / LINE userId)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('facebook_user_name', 'like', "%{$search}%")
                    ->orWhere('facebook_user_id', 'like', "%{$search}%")
                    ->orWhere('platform_user_id', 'like', "%{$search}%")
                    ->orWhere('bill_reference', 'like', "%{$search}%");
            });
        }

        // กรองตามหมวดคำทำนาย
        if ($request->filled('category')) {
            $category = $request->category;
            $query->whereJsonContains('categories', $category);
        }

        // กรองตามสถานะ Conversation
        if ($request->filled('conversation_status')) {
            $query->where('conversation_status', $request->conversation_status);
        }

        // กรองตามสถานะชำระเงิน
        if ($request->filled('is_paid')) {
            $query->where('is_paid', $request->is_paid);
        }

        // กรองตาม AI provider
        if ($request->filled('ai_provider')) {
            $query->where('ai_provider', $request->ai_provider);
        }

        // กรองตามประเภทคำทำนาย (basic/deep)
        if ($request->filled('reading_type')) {
            $query->where('reading_type', $request->reading_type);
        }

        // กรองตามวันที่
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $readings = $query->paginate(20);

        // สถิติ
        $stats = [
            'total' => FortuneReading::count(),
            'today' => FortuneReading::today()->count(),
            'deep' => FortuneReading::deep()->count(),
            // 💎 (2026-07-05) นับ Celtic 99 แยก (เดิมตกสำรวจ — ไม่มีในการ์ดใดเลย)
            'celtic' => FortuneReading::where('reading_type', 'celtic_cross')->count(),
            'basic' => FortuneReading::basic()->count(),
            'paid' => FortuneReading::paid()->count(),
            'free' => FortuneReading::free()->count(),
            // ⚠️ จำนวนบิลที่ชำระแล้วแต่ AI สร้างคำทำนายไม่สำเร็จ
            'stuck_paid' => FortuneReading::where('is_paid', true)
                ->where('reading_type', 'deep')
                ->whereNull('deep_response')
                ->count(),
        ];

        return view('admin.fortune.readings.index', [
            'readings' => $readings,
            'stats' => $stats,
            'pageTitle' => 'ประวัติการทำนาย',
        ]);
    }

    /**
     * แสดงรายละเอียดการทำนาย
     */
    public function show(FortuneReading $reading)
    {
        $reading->load('user');
        $reading->incrementViewCount();

        return view('admin.fortune.readings.show', [
            'reading' => $reading,
            'pageTitle' => 'รายละเอียดการทำนาย #'.$reading->id,
        ]);
    }

    /**
     * ลบการทำนาย
     */
    public function destroy(FortuneReading $reading)
    {
        $reading->delete();

        return redirect()
            ->route('admin.fortune.readings.index')
            ->with('success', 'ลบการทำนายสำเร็จ');
    }

    /**
     * แสดงฟอร์มแก้ไขการทำนาย (admin)
     *
     * รองรับการแก้ไข:
     *   - คำทำนาย (deep_response, basic_response, ai_response)
     *   - สถานะบิล (is_paid, paid_at, conversation_status)
     *   - จำนวนเงิน (amount_paid)
     */
    public function edit(FortuneReading $reading)
    {
        return view('admin.fortune.readings.edit', [
            'reading' => $reading,
            'pageTitle' => 'แก้ไขการทำนาย #'.$reading->id,
        ]);
    }

    /**
     * บันทึกการแก้ไขการทำนาย (admin)
     *
     * Audit log: ทุกการแก้ไขถูก log เพื่อตรวจสอบย้อนหลัง
     */
    public function update(Request $request, FortuneReading $reading)
    {
        $validated = $request->validate([
            'deep_response' => 'nullable|string|max:50000',
            'basic_response' => 'nullable|string|max:20000',
            'ai_response' => 'nullable|string|max:20000',
            // 💎 (2026-07-05) เพิ่มสถานะ Celtic 99 + payment + free/cancel ที่ตกสำรวจ
            //    เดิม in: มีแค่ deep/basic — แก้ไขบิล Celtic แล้ว validation fail
            'conversation_status' => 'required|in:new,awaiting_confirmation,basic_done,discovery_chat,discovery_confirm,collecting_birthdate,collecting_questions,collecting_tarot,tier_choice,awaiting_payment_method,pending_payment,pending_stripe_payment,celtic_pending_payment,celtic_picking,celtic_awaiting_question,celtic_generating,celtic_qa_prompt,free_predicted,free_declined,paid,completed,cancelled,expired',
            'is_paid' => 'required|boolean',
            'amount_paid' => 'nullable|numeric|min:0|max:999999',
            'paid_at' => 'nullable|date',
            'admin_note' => 'nullable|string|max:500',
            // 🌙 (2026-05-14) Admin manual edit fields
            'birth_date' => 'nullable|date',
            'questions_input' => 'nullable|string|max:5000',
            'pick_tarot_random' => 'nullable|boolean',
        ]);

        // เก็บ snapshot ก่อนแก้ — ใส่ใน conversation_state เพื่อ audit
        $beforeSnapshot = [
            'deep_response_len' => mb_strlen($reading->deep_response ?? ''),
            'basic_response_len' => mb_strlen($reading->basic_response ?? ''),
            'is_paid' => $reading->is_paid,
            'conversation_status' => $reading->conversation_status,
            'amount_paid' => $reading->amount_paid,
            'paid_at' => $reading->paid_at?->toIso8601String(),
            'birth_date' => $reading->birth_date?->toDateString(),
            'questions_count' => is_array($reading->questions) ? count($reading->questions) : 0,
            'tarot_count' => count($reading->getCollectedTarotCards()),
        ];

        // ถ้าเปลี่ยน is_paid เป็น true แต่ paid_at ว่าง → ใส่ now()
        if ($validated['is_paid'] && empty($validated['paid_at'])) {
            $validated['paid_at'] = now();
        }
        // ถ้าเปลี่ยน is_paid เป็น false → clear paid_at
        if (! $validated['is_paid']) {
            $validated['paid_at'] = null;
        }

        $adminNote = $validated['admin_note'] ?? null;
        $questionsInput = $validated['questions_input'] ?? null;
        $pickTarotRandom = (bool) ($validated['pick_tarot_random'] ?? false);
        unset($validated['admin_note'], $validated['questions_input'], $validated['pick_tarot_random']);

        // 🌙 (2026-05-14) แปลง questions_input (textarea) → questions array
        //   หนึ่งคำถามต่อบรรทัด
        if ($questionsInput !== null) {
            $lines = preg_split('/\r\n|\r|\n/', trim($questionsInput));
            $questions = array_values(array_filter(array_map('trim', $lines), fn ($q) => $q !== ''));
            $validated['questions'] = $questions;
        }

        $reading->update($validated);

        // 🃏 (2026-05-14) ถ้า admin ขอจับไพ่ random + ยังไม่มีไพ่ → จับให้
        if ($pickTarotRandom && count($reading->getCollectedTarotCards()) === 0) {
            try {
                $card = \App\Models\TarotCard::where('is_active', true)
                    ->inRandomOrder()
                    ->first();

                if ($card) {
                    $isReversed = (bool) random_int(0, 1);
                    $reading->addTarotCard(
                        questionIndex: 0,
                        cardId: $card->id,
                        cardNameTh: $card->getName('th'),
                        cardNameEn: $card->getName('en'),
                        isReversed: $isReversed,
                        meaning: $card->getMeaning($isReversed, 'th'),
                        imageUrl: $card->image_url,
                    );
                    Log::info('Admin: pick random tarot card สำเร็จ', [
                        'reading_id' => $reading->id,
                        'card_id' => $card->id,
                        'is_reversed' => $isReversed,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Admin: pick random tarot card ล้มเหลว', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Audit log ลง conversation_state
        $audits = $reading->getConversationState('admin_edits', []);
        $audits[] = [
            'edited_at' => now()->toIso8601String(),
            'edited_by' => auth()->user()?->name ?? 'unknown',
            'edited_by_id' => auth()->id(),
            'before' => $beforeSnapshot,
            'note' => $adminNote,
        ];
        $reading->setConversationState('admin_edits', $audits);

        Log::info('Admin: Edit fortune reading', [
            'reading_id' => $reading->id,
            'admin' => auth()->user()?->name,
            'before' => $beforeSnapshot,
            'note' => $adminNote,
        ]);

        return redirect()
            ->route('admin.fortune.readings.show', $reading)
            ->with('success', '✅ บันทึกการแก้ไขสำเร็จ');
    }

    /**
     * สถานะ reading สำหรับ polling จากหน้า show.blade
     *
     * Client poll endpoint นี้ทุก 3 วินาทีหลังกดปุ่ม "สร้างคำทำนาย"
     * เมื่อ ready=true → reload หน้า show ครั้งเดียว เพื่อดูคำทำนายเต็ม
     */
    public function status(FortuneReading $reading)
    {
        return response()->json([
            'id' => $reading->id,
            'conversation_status' => $reading->conversation_status,
            'has_deep_response' => ! empty($reading->deep_response),
            'is_paid' => (bool) $reading->is_paid,
            'updated_at' => $reading->updated_at?->toIso8601String(),
            // ready = สร้างคำทำนายเสร็จแล้ว (ไม่ว่าจะ status เป็น completed หรือยัง)
            'ready' => ! empty($reading->deep_response),
        ]);
    }

    /**
     * สร้างคำทำนายเชิงลึกใหม่ + ส่งให้ลูกค้า (Manual Retry)
     *
     * ใช้กรณี: ลูกค้าชำระเงินแล้ว แต่ระบบส่งคำทำนายไม่สำเร็จ
     * (เช่น background job ล้มเหลว, process crash, timeout)
     */
    public function retryDeepReading(FortuneReading $reading)
    {
        // ตรวจสอบเงื่อนไข: ต้องเป็น deep reading ที่ชำระเงินแล้ว
        if (! $reading->is_paid || $reading->reading_type !== 'deep') {
            return redirect()->back()->with('error', 'ไม่สามารถดำเนินการได้: ต้องเป็น deep reading ที่ชำระเงินแล้ว');
        }

        $userId = $reading->platform_user_id ?? $reading->facebook_user_id;
        $platform = $reading->platform ?: ((preg_match('/^U[0-9a-f]{32}$/i', $userId ?? '')) ? 'line' : 'facebook');

        if (empty($userId)) {
            return redirect()->back()->with('error', 'ไม่พบ user ID สำหรับส่งข้อความ');
        }

        // 🚨 (2026-05-14) ตรวจสอบข้อมูลครบก่อน trigger AI
        //   user spec: "ข้อมูลลูกค้ายังไม่มีจะสร้างไม่ได้ พวก ไพ่ วันเดือนปีเกิด"
        //   เคส Pay-First Deep 39: ลูกค้าจ่ายแล้ว แต่ยังไม่กรอกวันเกิด → AI gen ไม่ได้
        //   ก่อนหน้านี้: เช็คแค่ is_paid → dispatch Job → AI fail → status COMPLETED มี error
        //   ใหม่: ถ้า birth_date NULL → bounce กลับให้ admin ใช้ปุ่ม "ส่งขอวันเกิดใหม่" แทน
        if (empty($reading->birth_date)) {
            return redirect()->back()->with(
                'warning',
                '⚠️ ลูกค้ายังไม่กรอกวันเกิด — สร้างคำทำนายไม่ได้\n'.
                'กรุณากดปุ่ม "🛟 ส่งขอวันเกิดใหม่" เพื่อ push message ให้ลูกค้ากรอกข้อมูล'
            );
        }

        // ถ้ามี deep_response อยู่แล้ว → clear + ตั้ง status=PAID เพื่อให้ banner "AI กำลังสร้าง..." แสดง
        // (Artisan command จะข้ามถ้ามี deep_response + status=completed)
        //
        // 🩹 (2026-05-09) Clear delivery flags ใน conversation_state ด้วย — single update
        //    ProcessDeepFortuneReadingJob:401 เช็ค (!reading_sent_directly && !reading_notification_sent)
        //    ถ้า run ก่อนหน้าเคย push แล้ว → flags=true → push ใหม่จะถูกข้าม
        //    → admin เห็นคำทำนายใน DB แต่ลูกค้าไม่ได้รับ → ต้องกด "ส่งใหม่" เอง
        $existingState = is_array($reading->conversation_state) ? $reading->conversation_state : [];
        $clearedState = array_merge($existingState, [
            'reading_sent_directly' => false,
            'reading_notification_sent' => false,
            'reading_notification_attempted' => false,
            'reading_notification_retry_count' => 0,
            'reading_ready_sent' => false,
            'reading_ready_for_reply' => false,
            'delivered_by_push' => false,
            'delivered_by_reply_message' => false,
        ]);

        $reading->update([
            'deep_response' => null,
            'ai_response' => null,
            'conversation_status' => FortuneReading::STATUS_PAID,
            'conversation_state' => $clearedState,
        ]);

        Log::info('Admin: Manual retry deep reading queued', [
            'reading_id' => $reading->id,
            'platform' => $platform,
            'user_id' => $userId,
            'admin' => auth()->user()?->name,
        ]);

        // 🐛 (2026-05-02 - REVERTED) — terminating() approach broke admin retry
        //   เหตุผล: dispatchSmart() เรียก fastcgi_finish_request() + Artisan::call() อยู่แล้วภายใน
        //   ถ้าเรียก fastcgi_finish_request ซ้ำใน terminating callback → Laravel กำลัง teardown
        //   service container → Artisan::call ใน dispatchSmart พังเงียบ → job ไม่รัน
        //
        //   Auto flow (webhook/SMS) ใช้ dispatchSmart โดยตรง (ไม่ผ่าน HTTP) → ใช้ได้
        //   Admin path ต้องส่ง response กลับก่อน — ใช้ register_shutdown_function ซึ่งรัน
        //   หลัง Laravel teardown หมดแล้ว → service container clean → Artisan::call ทำงานได้
        $readingId = $reading->id;
        register_shutdown_function(function () use ($readingId, $platform, $userId) {
            try {
                ProcessDeepFortuneReadingJob::dispatchSmart($readingId, null, $platform, $userId);
            } catch (\Throwable $e) {
                Log::error('Admin retry: dispatch failed in shutdown', [
                    'reading_id' => $readingId,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        // Redirect ไปหน้า show — banner "AI กำลังสร้าง..." + auto-reload จะ kick in ทันที
        return redirect()
            ->route('admin.fortune.readings.show', $reading)
            ->with('success', '🔮 เริ่มสร้างคำทำนายเชิงลึก... หน้าจะอัปเดตอัตโนมัติเมื่อเสร็จ');
    }

    /**
     * ส่งคำทำนายเชิงลึกที่มีอยู่แล้วซ้ำให้ลูกค้า (Manual Resend)
     *
     * ใช้กรณี: มีคำทำนายอยู่แล้ว แต่ส่งให้ลูกค้าไม่สำเร็จ
     * (เช่น Messenger/LINE error, ข้อความไม่ถึง)
     */
    public function resendDeepReading(FortuneReading $reading)
    {
        // ตรวจสอบว่ามี deep_response
        if (empty($reading->deep_response)) {
            return redirect()->back()->with('error', 'ไม่มีคำทำนายเชิงลึก กรุณาใช้ปุ่ม "สร้างคำทำนายใหม่" แทน');
        }

        $userId = $reading->platform_user_id ?? $reading->facebook_user_id;
        $platform = $reading->platform ?: ((preg_match('/^U[0-9a-f]{32}$/i', $userId ?? '')) ? 'line' : 'facebook');

        if (empty($userId)) {
            return redirect()->back()->with('error', 'ไม่พบ user ID สำหรับส่งข้อความ');
        }

        try {
            $settings = FortuneTellingSetting::getSettings();
            $channelManager = new FortuneChannelManager($settings);

            // ส่ง Birth Chart ก่อน (ถ้ามี)
            if ($reading->reading_image_url) {
                try {
                    $platformService = $channelManager->getPlatform($platform);
                    if ($platformService) {
                        $platformService->sendImage($userId, $reading->reading_image_url);
                        usleep(500000); // รอ 0.5 วินาที
                    }
                } catch (\Exception $imgErr) {
                    Log::warning('Admin Resend: ส่ง chart image ไม่สำเร็จ', [
                        'error' => $imgErr->getMessage(),
                    ]);
                }
            }

            // ส่งคำทำนายเชิงลึก
            $channelManager->sendResponse($platform, $userId, [
                'action' => 'resend',
                'message' => $reading->deep_response,
            ], ['from_admin' => true]);

            // อัพเดท status เป็น completed ถ้ายังไม่ได้
            if ($reading->conversation_status !== FortuneReading::STATUS_COMPLETED) {
                $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);
            }

            Log::info('Admin: Manual resend deep reading สำเร็จ', [
                'reading_id' => $reading->id,
                'platform' => $platform,
                'user_id' => $userId,
                'admin' => auth()->user()?->name,
            ]);

            return redirect()->back()->with('success', '✅ ส่งคำทำนายเชิงลึกให้ลูกค้าสำเร็จ!');

        } catch (\Exception $e) {
            Log::error('Admin: Manual resend deep reading ล้มเหลว', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'ส่งไม่สำเร็จ: '.$e->getMessage());
        }
    }

    /**
     * 🛟 (2026-05-14) ส่งขอวันเกิดใหม่ — Pay-First Deep 39 recovery
     *
     * เคส: ลูกค้าจ่าย Deep 39 แล้ว แต่ยังไม่กรอกวันเกิด (status=COLLECTING_BIRTHDATE หรือ COMPLETED orphan)
     * → admin กดปุ่มนี้ → reset state + push "ขอวันเกิด" ใหม่ผ่าน POST_PURCHASE_UPDATE
     *
     * ผ่าน artisan: php artisan fortune:recover-paid-no-birthdate --id={$reading->id} --force
     */
    public function recoverPayFirstReading(FortuneReading $reading)
    {
        if (! $reading->is_paid || $reading->reading_type !== 'deep') {
            return redirect()->back()->with('error', 'ไม่สามารถดำเนินการได้: ต้องเป็น Deep reading ที่ชำระเงินแล้ว');
        }

        try {
            // ใช้ Artisan call เพื่อ reuse logic จาก FortuneRecoverPaidNoBirthdate command
            \Illuminate\Support\Facades\Artisan::call('fortune:recover-paid-no-birthdate', [
                '--id' => $reading->id,
                '--force' => true,
            ]);

            $output = \Illuminate\Support\Facades\Artisan::output();

            Log::info('Admin: Recover Pay-First reading', [
                'reading_id' => $reading->id,
                'admin' => auth()->user()?->name,
                'output_preview' => mb_substr($output, 0, 300),
            ]);

            // ตรวจ output ว่า push สำเร็จไหม
            if (str_contains($output, 'recover + push')) {
                return redirect()
                    ->route('admin.fortune.readings.show', $reading)
                    ->with('success', '🛟 ส่งข้อความ "ขอวันเกิด" ให้ลูกค้าแล้ว — รอลูกค้าตอบ');
            }

            if (str_contains($output, 'push ล้มเหลว')) {
                return redirect()
                    ->route('admin.fortune.readings.show', $reading)
                    ->with('warning', '⚠️ Reset state แล้ว แต่ push ล้มเหลว (อาจเกิน FB 24h window) — ลูกค้าทักกลับจะเข้า flow ใหม่');
            }

            return redirect()
                ->route('admin.fortune.readings.show', $reading)
                ->with('info', 'รัน recover คำสั่งแล้ว — ตรวจสอบ log สำหรับรายละเอียด');
        } catch (\Throwable $e) {
            Log::error('Admin: Recover Pay-First reading ล้มเหลว', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Recover ล้มเหลว: '.$e->getMessage());
        }
    }

    /**
     * ส่งออกข้อมูลเป็น CSV
     */
    public function export(Request $request)
    {
        $readings = FortuneReading::with('user')
            ->when($request->filled('ai_provider'), fn ($q) => $q->where('ai_provider', $request->ai_provider))
            ->when($request->filled('is_paid'), fn ($q) => $q->where('is_paid', $request->is_paid))
            ->when($request->filled('reading_type'), fn ($q) => $q->where('reading_type', $request->reading_type))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'fortune_readings_'.now()->format('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($readings) {
            $file = fopen('php://output', 'w');

            // BOM สำหรับ UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header
            fputcsv($file, ['ID', 'วันที่', 'ชื่อผู้ใช้', 'Facebook ID', 'คำถาม', 'ประเภทคำทำนาย', 'AI Provider', 'สถานะชำระเงิน', 'ราคา']);

            // Data
            foreach ($readings as $reading) {
                fputcsv($file, [
                    $reading->id,
                    $reading->created_at->format('Y-m-d H:i:s'),
                    $reading->facebook_user_name,
                    $reading->facebook_user_id,
                    implode(', ', $reading->questions),
                    $reading->reading_type === 'deep' ? 'เชิงลึก' : 'พื้นฐาน',
                    $reading->ai_provider,
                    $reading->is_paid ? 'ชำระแล้ว' : 'ฟรี',
                    $reading->amount_paid,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
