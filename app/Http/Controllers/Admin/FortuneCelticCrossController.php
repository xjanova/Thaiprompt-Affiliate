<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneCelticQuestion;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\CelticCrossService;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use App\Services\SmsPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * จัดการระบบ Celtic Cross Tarot Mode (99 บาท ค่าครู)
 *
 * Features:
 * - Toggle เปิด/ปิด mode + proactive AI suggestion
 * - ตั้งราคา ค่าครู + จำนวนคำถามต่อบิล + 1hr window
 * - แก้ AI prompt 2 ตัว (main + followup)
 * - ดู readings ที่ผ่าน Celtic Cross + ทุก Q&A
 */
class FortuneCelticCrossController extends Controller
{
    /**
     * แสดงหน้าตั้งค่า + รายการ readings
     */
    public function index(Request $request)
    {
        // 🛡️ (2026-06-26) อ่าน settings สดจาก DB เสมอ — กันหน้าตั้งค่าโชว์ค่าเก่า
        //   (php-fpm มีหลาย process แต่ละตัวถือ static cache แยก → process ที่เสิร์ฟหน้านี้
        //    อาจถือค่าเก่าที่ไม่ถูกล้างตอนกดบันทึก = แอดมินเห็น "ค่าไม่จำ" ทั้งที่ DB อัปเดตแล้ว)
        FortuneTellingSetting::clearSettingsCache();
        $settings = FortuneTellingSetting::getSettings();

        // สถิติ Celtic Cross
        $stats = [
            'total_readings' => FortuneReading::where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)->count(),
            'paid_readings' => FortuneReading::where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
                ->where('is_paid', true)->count(),
            // ⚠️ ตัวนี้นับจาก celtic_first_answered_at = "วันที่แม่หมอตอบคำถามแรก" ไม่ใช่ "ปิดจ๊อบ"
            //    ป้ายเดิมเขียนว่า "เสร็จวันนี้" ซึ่งไม่ตรงกับสิ่งที่นับ → เปลี่ยนป้ายที่ blade แล้ว
            'answered_today' => FortuneReading::where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
                ->whereDate('celtic_first_answered_at', today())->count(),
            // ⏳ (2026-08-07) บิลที่ยัง "ลุ้นได้เงิน" อยู่จริง — แอดมินต้องเห็นตัวเลขนี้ที่หน้าแรก
            //    (prod ตอนตรวจ: 418 บิล ซึ่งเดิมไม่มี KPI ไหนแสดงเลย)
            'pending_readings' => FortuneReading::where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
                ->where('is_paid', false)
                ->whereIn('conversation_status', FortuneReading::PENDING_DISPLAY_STATUSES)
                ->count(),
            // 💰 (2026-08-07) เงินที่ "ได้รับจริง" — COALESCE(amount_received, amount_paid)
            //   เดิม sum('amount_paid') = ยอดที่ "ออกบิลไป" ไม่ใช่ยอดที่เข้าจริง
            //   prod: 223 บิลจ่ายแล้วมี amount_received = NULL (ตัดผ่าน SMS/แอดมินไม่ได้บันทึกยอด)
            //   + 56 บิลยอดรับ ≠ ยอดบิล → ต้อง fallback เป็นยอดบิลเฉพาะตัวที่ไม่มียอดรับ
            'total_revenue' => (float) FortuneReading::where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
                ->where('is_paid', true)
                ->selectRaw('COALESCE(SUM(COALESCE(amount_received, amount_paid)), 0) AS s')
                ->value('s'),
            'total_questions' => FortuneCelticQuestion::whereHas('reading', function ($q) {
                $q->where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS);
            })->count(),
        ];

        // 🔍 (2026-05-03) Filterable readings list
        $query = FortuneReading::where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
            ->withCount('celticQuestions');

        // Filter: search ชื่อ user / bill ref / facebook id
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('facebook_user_name', 'LIKE', "%{$search}%")
                    ->orWhere('bill_reference', 'LIKE', "%{$search}%")
                    ->orWhere('facebook_user_id', 'LIKE', "%{$search}%")
                    ->orWhere('id', $search);
            });
        }

        // Filter: status
        //
        // 🩹 (2026-08-07) รื้อใหม่ทั้งบล็อก — เดิมรายงานสถานะบิลผิดหลายจุด (ตรวจกับ prod จริง):
        //   • "รอชำระ" ผูกกับ celtic_pending_payment อย่างเดียว = เจอ **1 บิล**
        //     ทั้งที่บิลรอจ่ายจริงอยู่ที่ awaiting_payment_method **417 บิล** → แอดมินมองไม่เห็นเลย
        //   • "ยกเลิก" ผูกกับ conversation_status='cancelled' = เจอ 11 บิล
        //     แต่บิลที่ถูกยกเลิกจริงเก็บเป็น completed + is_paid=0 + cancellation_reason ใน state
        //     (ดู FortuneReading::isCancelled()) = **727 บิล**
        //   • "ค้าง" กรองหลัง paginate → ตัวเลขหน้า/ยอดรวมเพี้ยน + หน้าท้าย ๆ ว่างเปล่า
        if ($status = $request->input('status')) {
            if ($status === 'paid') {
                $query->where('is_paid', true);
            } elseif ($status === 'unpaid') {
                $query->where('is_paid', false);
            } elseif ($status === 'pending') {
                // 💤 รอชำระจริง — ยังไม่จ่าย + อยู่ในสถานะที่ระบบยังรอเงินอยู่
                //    ใช้ PENDING_DISPLAY_STATUSES (ของกลางจาก audit 2026-07-05) ไม่ตั้งลิสต์ใหม่
                $query->where('is_paid', false)
                    ->whereIn('conversation_status', FortuneReading::PENDING_DISPLAY_STATUSES);
            } elseif ($status === 'cancelled') {
                // ❌ ยกเลิกจริง — ล้อเงื่อนไขเดียวกับ FortuneReading::isCancelled() เป๊ะ
                $query->where('is_paid', false)
                    ->where('conversation_status', FortuneReading::STATUS_COMPLETED)
                    ->whereNotNull('conversation_state->cancellation_reason');
            } elseif ($status === 'abandoned') {
                // 🕳️ ปิดเงียบ — จบ conversation ไปเฉย ๆ ไม่จ่าย และไม่มีเหตุผลยกเลิกบันทึกไว้
                $query->where('is_paid', false)
                    ->where('conversation_status', FortuneReading::STATUS_COMPLETED)
                    ->whereNull('conversation_state->cancellation_reason');
            } elseif ($status === 'stuck') {
                // 🧊 จ่ายแล้วแต่เปิดไพ่ไม่ครบ 10 ใบ
                //   จำนวนไพ่อยู่ใน conversation_state (JSON) นับด้วย SQL ตรง ๆ ไม่ได้
                //   → หา id ที่เข้าเงื่อนไขก่อน แล้วค่อย whereIn เพื่อให้ paginate นับถูก
                //
                //   ⚠️ ต้อง chunk + select เฉพาะ 2 คอลัมน์ ห้าม get() รวดเดียว —
                //      conversation_state เป็น JSON ก้อนใหญ่ (เก็บไพ่ 10 ใบ + คำทำนาย + state ทั้งหมด)
                //      โหลดบิลจ่ายแล้วทั้งหมดพร้อมกันชน memory_limit 128M จริง (เจอตอนทดสอบบน prod)
                $stuckIds = [];
                FortuneReading::where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
                    ->where('is_paid', true)
                    ->select(['id', 'conversation_state'])
                    ->chunk(200, function ($rows) use (&$stuckIds) {
                        foreach ($rows as $r) {
                            if ($r->getCelticPickedCount() < 10) {
                                $stuckIds[] = $r->id;
                            }
                        }
                    });

                $query->whereIn('id', $stuckIds ?: [0]);
            } else {
                $query->where('conversation_status', $status);
            }
        }

        // Filter: date range
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // 🔼 (2026-06-01) เรียงตาม updated_at — บิลที่เพิ่ง active/recovered (กู้ด้วยสลิป) โผล่บนสุด
        //   เดิม latest() = created_at → บิลที่ reuse (created เก่า เช่น entony 4544) จมท้าย แอดมินหาไม่เจอ
        $recentReadings = $query->orderByDesc('updated_at')->orderByDesc('id')->paginate(20)->withQueryString();

        // 🗑️ (2026-08-07) ลบการกรอง stuck หลัง paginate ทิ้ง — ย้ายไปกรองใน query แล้ว
        //    ของเดิมตัดแถวออกจาก "หน้าปัจจุบัน" อย่างเดียว: total/จำนวนหน้ายังเป็นของบิลจ่ายแล้วทั้งหมด
        //    → บอกว่าเจอ 542 แต่โชว์จริงไม่กี่แถว และหน้า 2 ขึ้นไปว่างเปล่า

        return view('admin.fortune.celtic-cross.index', [
            'settings' => $settings,
            'stats' => $stats,
            'recentReadings' => $recentReadings,
            'pageTitle' => 'Celtic Cross Tarot Mode',
            'filters' => [
                'search' => $search ?? '',
                'status' => $status ?? '',
                'date_from' => $dateFrom ?? '',
                'date_to' => $dateTo ?? '',
            ],
        ]);
    }

    /**
     * บันทึกการตั้งค่า
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'enable_celtic_cross' => 'sometimes|boolean',
            'celtic_cross_proactive_enabled' => 'sometimes|boolean',
            // 🪬 (2026-06-24) โหมดดูคุณไสย์ / มนต์ดำ — gate ปุ่มลูกค้า + toggle แอดมิน
            'enable_celtic_black_magic_mode' => 'sometimes|boolean',
            // 🔊 (2026-06-26) บังคับฟังเสียงกติกา + กรอกรหัสท้ายคลิปก่อนสร้างบิล + เลือกโมเดล TTS เจนรหัส
            'enable_consent_audio_code' => 'sometimes|boolean',
            'consent_audio_code_voice_provider' => 'nullable|string|in:minimax,openai_tts,google_tts,gtts',
            'consent_audio_code_min_unpaid_bills' => 'nullable|integer|min:0|max:99',
            // 📋 (2026-07-11) แบบสอบถามยืนยันเจตนา 5 ข้อ ก่อนสร้างบิล (เฉพาะคนสร้างบิลแล้วไม่จ่าย)
            'enable_consent_quiz' => 'sometimes|boolean',
            'consent_quiz_min_unpaid_bills' => 'nullable|integer|min:0|max:99',
            'consent_quiz_ban_days' => 'nullable|integer|min:1|max:365',
            // ⚡ ข้ามกล่องกติกาทั้งหมด → สร้างบิลทันที
            'consent_gate_bypass' => 'sometimes|boolean',
            // 🎚️ สวิตช์พฤติกรรมเชิงรุก (กระตุ้นการขาย / กระตุ้นจ่ายบิล / ถามก่อนยกเลิก — รวมไว้หน้านี้)
            'enable_sales_pitch' => 'sometimes|boolean',
            'enable_bill_payment_nudge' => 'sometimes|boolean',
            'fortune_consent_cancel_enabled' => 'sometimes|boolean',
            'celtic_cross_price' => 'numeric|min:1|max:9999',
            // 🔢 (2026-06-27) min:0 — 0 = ไม่จำกัดคำถาม (ตาม design unlimited 2026-06-07 + blade ระบุ "0 = ไม่จำกัด")
            //    ⚠️ เดิม min:1 → validation reject 0 → admin ตั้งไม่จำกัดไม่ได้ + ทั้งหน้าเซฟไม่ผ่าน
            //    ถ้าเผลอแก้เป็น >=1 เพื่อให้เซฟผ่าน = ทับ "ไม่จำกัด" กลับเป็น cap (เคส FTU-260627-U1003 ถูกตัดที่ 5Q)
            'celtic_cross_max_questions' => 'integer|min:0|max:50',
            'celtic_cross_qa_window_minutes' => 'integer|min:5|max:1440',
            'celtic_cross_main_prompt' => 'nullable|string|max:10000',
            'celtic_cross_followup_prompt' => 'nullable|string|max:10000',
        ]);

        $settings = FortuneTellingSetting::getSettings();

        $settings->enable_celtic_cross = $request->boolean('enable_celtic_cross');
        $settings->celtic_cross_proactive_enabled = $request->boolean('celtic_cross_proactive_enabled');
        // 🪬 (2026-06-24) โหมดดูคุณไสย์ / มนต์ดำ
        $settings->enable_celtic_black_magic_mode = $request->boolean('enable_celtic_black_magic_mode');
        // 🔊 (2026-06-26) บังคับฟังเสียงกติกา + รหัสยืนยัน + โมเดล TTS เจนรหัส (default minimax)
        $settings->enable_consent_audio_code = $request->boolean('enable_consent_audio_code');
        $settings->consent_audio_code_voice_provider = $validated['consent_audio_code_voice_provider'] ?? 'minimax';
        $settings->consent_audio_code_min_unpaid_bills = (int) ($validated['consent_audio_code_min_unpaid_bills'] ?? 0);
        // 📋 (2026-07-11) แบบสอบถามยืนยันเจตนา 5 ข้อ — toggle + เกณฑ์บิลค้าง + จำนวนวันแบน
        $settings->enable_consent_quiz = $request->boolean('enable_consent_quiz');
        $settings->consent_quiz_min_unpaid_bills = (int) ($validated['consent_quiz_min_unpaid_bills'] ?? 2);
        $settings->consent_quiz_ban_days = (int) ($validated['consent_quiz_ban_days'] ?? 7);
        // ⚡ ข้ามกล่องกติกาทั้งหมด → สร้างบิลทันที
        $settings->consent_gate_bypass = $request->boolean('consent_gate_bypass');
        // 🎚️ สวิตช์พฤติกรรมเชิงรุก
        $settings->enable_sales_pitch = $request->boolean('enable_sales_pitch');
        $settings->enable_bill_payment_nudge = $request->boolean('enable_bill_payment_nudge');
        // ถามก่อนยกเลิกบิล (ของเดิม fortune_consent_cancel_enabled — ย้ายมาคุมที่หน้านี้ด้วย)
        $settings->fortune_consent_cancel_enabled = $request->boolean('fortune_consent_cancel_enabled');
        $settings->celtic_cross_price = $validated['celtic_cross_price'] ?? 99.00;
        // ❗ ห้าม default 5 — ถ้า field ไม่ถูกส่งมา ให้คงค่าเดิม (กันทับ "ไม่จำกัด" 0 → 5 โดยไม่ตั้งใจ)
        if (array_key_exists('celtic_cross_max_questions', $validated)) {
            $settings->celtic_cross_max_questions = (int) $validated['celtic_cross_max_questions'];
        }
        $settings->celtic_cross_qa_window_minutes = $validated['celtic_cross_qa_window_minutes'] ?? 15;

        // เก็บ prompt เฉพาะถ้าส่งมา (เว้นว่าง = ใช้ default ใน CelticCrossService)
        $settings->celtic_cross_main_prompt = $validated['celtic_cross_main_prompt'] ?? null;
        $settings->celtic_cross_followup_prompt = $validated['celtic_cross_followup_prompt'] ?? null;

        $settings->save();
        FortuneTellingSetting::clearSettingsCache();

        return redirect()->route('admin.fortune.celtic-cross.index')
            ->with('success', 'บันทึกการตั้งค่า Celtic Cross สำเร็จ ✅');
    }

    /**
     * แสดงรายละเอียด reading + Q&A ทั้งหมด
     */
    public function showReading(FortuneReading $reading)
    {
        if ($reading->reading_type !== FortuneReading::READING_TYPE_CELTIC_CROSS) {
            abort(404);
        }

        $reading->load('celticQuestions');
        $cards = $reading->getCelticCards();
        $settings = FortuneTellingSetting::getSettings();

        return view('admin.fortune.celtic-cross.show', [
            'reading' => $reading,
            'cards' => $cards,
            'positions' => FortuneReading::CELTIC_POSITIONS,
            'maxQuestions' => (int) ($settings->celtic_cross_max_questions ?? 0), // 0 = ไม่จำกัด
            'pageTitle' => "Celtic Cross #{$reading->id}",
        ]);
    }

    /**
     * 🔄 (2026-05-03) Reset reading — admin force ให้ลูกค้าเปิดไพ่ใหม่
     *
     * Use case: flow ไม่สมบูรณ์ (FB push fail, network drop, AI error)
     */
    public function resetReading(Request $request, FortuneReading $reading)
    {
        if ($reading->reading_type !== FortuneReading::READING_TYPE_CELTIC_CROSS) {
            abort(404);
        }

        if (! $reading->is_paid) {
            return back()->with('error', 'Reading นี้ยังไม่ได้ชำระเงิน — reset ไม่ได้');
        }

        $notify = (bool) $request->input('notify', true);

        try {
            // 1. ล้างไพ่
            try {
                app(CelticCrossService::class)->resetPickedCards($reading);
            } catch (\Throwable $e) {
                $reading->setConversationState('celtic_cards', []);
            }

            // 2. ล้าง Q&A
            $reading->celticQuestions()->delete();

            // 3. Reset counters + status
            $reading->update([
                'celtic_questions_used' => 0,
                'conversation_status' => FortuneReading::STATUS_CELTIC_PICKING,
            ]);

            $reading->setConversationState('reading_sent_directly', false);
            $reading->setConversationState('celtic_qa_started_at', null);
            // 🆕 (2026-05-17) Admin reset → คืนโควต้า "สับใหม่" ให้ลูกค้า
            $reading->setConversationState('celtic_shuffle_count', 0);
            $reading->refresh();

            // 4. แจ้งลูกค้า (optional)
            if ($notify) {
                $platform = $reading->platform
                    ?? (preg_match('/^U[0-9a-f]{32}$/i', $reading->platform_user_id ?? $reading->facebook_user_id) ? 'line' : 'facebook');
                $userId = $reading->platform_user_id ?? $reading->facebook_user_id;

                if (! empty($userId)) {
                    $settings = FortuneTellingSetting::getSettings();
                    $conversationService = new FortuneConversationService($settings);
                    $channelManager = new FortuneChannelManager($settings);

                    $response = $conversationService->onCelticPaymentConfirmed($reading);
                    $response['message'] = "🔄 *แอดมินรีเซ็ตการดูดวงให้แล้วค่ะ*\n"
                        ."เจ้าชะตาสามารถเริ่มเปิดไพ่ใหม่ได้เลย — ค่าครูเดิมยังใช้ได้ ไม่ต้องจ่ายซ้ำ\n\n"
                        ."═══════════════════════\n\n"
                        .($response['message'] ?? '');

                    $channelManager->sendResponse($platform, $userId, $response, [
                        'from_admin' => true,
                        'message_tag' => 'POST_PURCHASE_UPDATE',
                    ]);
                }
            }

            Log::info('Celtic admin reset (web)', [
                'reading_id' => $reading->id,
                'admin_id' => auth()->id(),
                'notify' => $notify,
            ]);

            return back()->with('success', "✅ Reset Reading #{$reading->id} สำเร็จ".($notify ? ' + แจ้งลูกค้าแล้ว' : ''));
        } catch (\Throwable $e) {
            Log::error('Celtic admin reset failed (web)', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', "❌ Reset ล้มเหลว: {$e->getMessage()}");
        }
    }

    /**
     * 🗑️ (2026-05-04) Cancel/Delete Celtic reading
     *
     * Use case: บิลขัดกัน (เช่น user รายงาน FTU-260504-T8747) — ลูกค้ามี Celtic
     * pending payment ค้าง + กด 99 ใหม่ → 2 บิลขัด → admin ต้องลบบิลที่ไม่ใช้
     *
     * Action:
     *   - cancel UPA (ปลดล็อกยอดทศนิยม)
     *   - update reading.conversation_status = COMPLETED (ปิด conversation)
     *   - log
     *   - แจ้งลูกค้า (optional via ?notify=1)
     *
     * Safety:
     *   - ห้าม cancel reading ที่ is_paid=true (ลูกค้าจ่ายแล้ว ใช้ reset แทน)
     *   - ห้าม cancel reading ที่ celtic_questions_used > 0 (เคยใช้สิทธิ์)
     */
    public function cancelReading(Request $request, FortuneReading $reading)
    {
        if ($reading->reading_type !== FortuneReading::READING_TYPE_CELTIC_CROSS) {
            abort(404);
        }

        if ($reading->is_paid) {
            return back()->with('error', '❌ Reading นี้จ่ายแล้ว — ใช้ปุ่ม "Reset" แทน (กัน refund ผิด)');
        }

        if ((int) ($reading->celtic_questions_used ?? 0) > 0) {
            return back()->with('error', '❌ Reading นี้ใช้สิทธิ์ถามไปแล้ว — ใช้ปุ่ม "Reset" แทน');
        }

        $notify = (bool) $request->input('notify', false);
        $billRef = $reading->bill_reference ?? "FR-{$reading->id}";

        try {
            DB::transaction(function () use ($reading) {
                // 1. Cancel UPA (ปลดล็อกยอดทศนิยม)
                $upa = $reading->uniquePaymentAmount;
                if ($upa && $upa->status === 'reserved') {
                    $upa->cancel();
                }

                // 2. Mark reading completed (ปิด conversation)
                $reading->update([
                    'conversation_status' => FortuneReading::STATUS_COMPLETED,
                ]);
            });

            // 3. แจ้งลูกค้า (optional)
            if ($notify) {
                $platform = $reading->platform
                    ?? (preg_match('/^U[0-9a-f]{32}$/i', $reading->platform_user_id ?? $reading->facebook_user_id) ? 'line' : 'facebook');
                $userId = $reading->platform_user_id ?? $reading->facebook_user_id;

                if (! empty($userId)) {
                    $settings = FortuneTellingSetting::getSettings();
                    $channelManager = new FortuneChannelManager($settings);

                    $name = $reading->facebook_user_name ?? 'เจ้าชะตา';
                    $channelManager->sendResponse($platform, $userId, [
                        'action' => 'celtic_cancelled',
                        'message' => "🙏 *แอดมินยกเลิกบิลให้แล้วค่ะ คุณ{$name}*\n\n"
                            ."📋 บิล: {$billRef}\n\n"
                            ."หากต้องการดูดวง Celtic Cross อีกครั้ง พิมพ์ 'celtic' ได้เลย\n"
                            .'ระบบจะสร้างบิลใหม่ให้ — ค่าครู 99 บาท ✨',
                        'reading' => $reading,
                    ], [
                        'from_admin' => true,
                        'message_tag' => 'POST_PURCHASE_UPDATE',
                    ]);
                }
            }

            Log::info('Celtic admin cancel reading', [
                'reading_id' => $reading->id,
                'bill_reference' => $billRef,
                'admin_id' => auth()->id(),
                'notify' => $notify,
            ]);

            return back()->with('success', "✅ ยกเลิกบิล {$billRef} สำเร็จ".($notify ? ' + แจ้งลูกค้าแล้ว' : ''));
        } catch (\Throwable $e) {
            Log::error('Celtic admin cancel failed', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', "❌ ยกเลิกล้มเหลว: {$e->getMessage()}");
        }
    }

    /**
     * 🚀 (2026-05-08) Force Approve — admin มาร์คบิลเป็นจ่ายแล้ว + push เริ่มเปิดไพ่
     *
     * Use case: ลูกค้าโอนยอดไม่ตรง (เช่น 99.00 แทน 99.37) → SMS app จับคู่ไม่ได้
     *           แอดมินยืนยันว่าเงินเข้าจริง → กดปุ่มนี้แทนการเปิด SMS app มือถือ
     *
     * Action:
     *   1. confirmPayment(null) → is_paid=true, paid_at=now, conversation_status=STATUS_PAID,
     *      mark UPA as 'used' (ปลด unique amount slot)
     *   2. SmsPaymentService::handleCelticPaymentMatched(null notification)
     *      → onCelticPaymentConfirmed → CELTIC_PICKING + push prompt ใบ 1
     *
     * Safety:
     *   - ห้ามถ้า is_paid=true อยู่แล้ว (ใช้ปุ่ม Reset แทน)
     *   - ห้ามถ้าไม่ใช่ Celtic reading
     *   - ห้ามถ้าไม่มี user_id
     */
    public function forceApprove(Request $request, FortuneReading $reading)
    {
        if ($reading->reading_type !== FortuneReading::READING_TYPE_CELTIC_CROSS) {
            abort(404);
        }

        if ($reading->is_paid) {
            return back()->with('error', '❌ Reading นี้จ่ายแล้ว — ใช้ปุ่ม "Reset" แทน');
        }

        $userId = $reading->platform_user_id ?? $reading->facebook_user_id;
        if (empty($userId)) {
            return back()->with('error', '❌ Reading นี้ไม่มี user_id — push ไปไม่ได้');
        }

        // จำนวนเงินที่ลูกค้าโอนจริง (admin กรอก) — default = ยอดบิลที่ตั้งไว้
        $actualAmount = (float) $request->input('actual_amount', $reading->amount_paid ?? 99.00);
        if ($actualAmount <= 0) {
            return back()->with('error', '❌ จำนวนเงินไม่ถูกต้อง');
        }

        $platform = $reading->platform
            ?: (preg_match('/^U[0-9a-f]{32}$/i', $userId) ? 'line' : 'facebook');

        try {
            // 1. มาร์คบิลเป็นจ่ายแล้ว (notification = null = admin force)
            $reading->confirmPayment(null);
            $reading = $reading->fresh();

            // 2. ส่ง flow Celtic เริ่มเปิดไพ่ใบ 1 (SMS notification = null)
            $dispatched = app(SmsPaymentService::class)->handleCelticPaymentMatched(
                $reading,
                null,
                $platform,
                (string) $userId,
                $actualAmount
            );

            Log::info('Celtic admin force approve (web)', [
                'reading_id' => $reading->id,
                'bill_reference' => $reading->bill_reference,
                'admin_id' => auth()->id(),
                'platform' => $platform,
                'actual_amount' => $actualAmount,
                'expected_amount' => $reading->amount_paid,
                'dispatched' => $dispatched,
            ]);

            $msg = "✅ Force Approve สำเร็จ — บิล {$reading->bill_reference} มาร์คจ่ายแล้ว";
            if ($dispatched) {
                $msg .= ' + ส่งให้ลูกค้าเริ่มเปิดไพ่';
            } else {
                $msg .= ' (push ไปลูกค้าไม่สำเร็จ — ลูกค้าจะได้ตอนทักกลับ)';
            }

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            Log::error('Celtic admin force approve failed (web)', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', "❌ Force Approve ล้มเหลว: {$e->getMessage()}");
        }
    }

    /**
     * ⛔ (2026-06-08) ยกเลิกการอนุมัติบิล (Void Approval)
     *
     * Use case: แอดมินกด Force Approve ผิดบิล/ผิดคน → บิลขึ้น "จ่ายแล้ว ✓"
     *   ทั้งที่ลูกค้าไม่ได้จ่ายจริง (ค้างที่ celtic_picking, ลูกค้าจริงไม่ได้อยู่ตรงนั้น)
     *   ปุ่มนี้ถอยกลับเป็น "ยังไม่จ่าย" + ปลด UPA/SMS + ดึงคืน commission (ถ้ามี)
     *
     * รองรับทั้ง Celtic + Deep — engine กลางอยู่ที่ FortuneReading::voidApproval()
     *
     * Safety:
     *   - บิลที่ลูกค้าใช้บริการไปแล้ว (เปิดไพ่/ได้คำทำนาย) ต้องส่ง force=1 (กันเผลอยกเลิกลูกค้าจริง)
     *   - idempotent: ถ้าบิล is_paid=false อยู่แล้ว engine คืน ok=false เฉยๆ
     */
    public function voidApproval(Request $request, FortuneReading $reading)
    {
        $reason = trim((string) $request->input('reason', ''));
        $force = (bool) $request->input('force', false);

        // กันยกเลิกบิลที่ลูกค้าใช้บริการไปแล้วจริง (เปิดไพ่/ได้คำทำนาย) — ต้อง force
        $consumed = $reading->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS
            ? ($reading->getCelticPickedCount() > 0 || (int) ($reading->celtic_questions_used ?? 0) > 0)
            : ! empty($reading->deep_response);

        if ($consumed && ! $force) {
            return back()->with('error', '⚠️ บิลนี้ลูกค้าใช้บริการไปแล้ว (เปิดไพ่/ได้คำทำนาย) — ถ้าแน่ใจว่าอนุมัติผิดจริง ให้ยืนยันซ้ำ (force)');
        }

        $result = $reading->voidApproval($reason !== '' ? $reason : null, auth()->id());

        if (! ($result['ok'] ?? false)) {
            return back()->with('error', $result['message'] ?? '❌ ยกเลิกการอนุมัติไม่สำเร็จ');
        }

        Log::warning('Celtic admin VOID approval (web)', [
            'reading_id' => $reading->id,
            'bill_reference' => $reading->bill_reference,
            'admin_id' => auth()->id(),
            'reason' => $reason,
            'reverted' => $result['reverted'] ?? [],
            'warnings' => $result['warnings'] ?? [],
        ]);

        $msg = "✅ ยกเลิกการอนุมัติบิล {$reading->bill_reference} แล้ว — คืนเป็น \"ยังไม่จ่าย\"";
        if (! empty($result['warnings'])) {
            $msg .= ' ⚠️ '.implode('; ', $result['warnings']);
        }

        return back()->with('success', $msg);
    }

    /**
     * 🔄 (2026-05-16) คืนสถานะ "กำลังดูอยู่" — เปิด Pro Session ใหม่ให้ลูกค้าคุยต่อ
     *
     * Use case: ลูกค้ากดปุ่ม "🛑 ยุติการทำนาย" + ยืนยัน "ใช่" โดยเข้าใจผิด
     *   → status=COMPLETED + pro_session_active=false
     *   → ลูกค้าทักมาขอ admin ให้กลับมาคุยต่อ
     *
     * Action:
     *   1. conversation_status → STATUS_CELTIC_AWAITING_QUESTION (กลับเข้า chat-style)
     *   2. Pro Session active = true
     *   3. คงเวลาเดิมถ้ายังเหลือ — ถ้าหมดแล้ว → reset window ใหม่ตาม settings
     *   4. แจ้งลูกค้า "แอดมินเปิดให้กลับมาคุยต่อแล้ว"
     *
     * Safety:
     *   - ห้ามถ้าไม่ใช่ Celtic reading
     *   - ห้ามถ้ายังไม่ได้จ่าย (is_paid=false)
     *   - ห้ามถ้าเปิดไพ่ไม่ครบ 10 ใบ (ยังไม่เคยเข้า chat session)
     */
    public function restoreActiveChat(Request $request, FortuneReading $reading)
    {
        if ($reading->reading_type !== FortuneReading::READING_TYPE_CELTIC_CROSS) {
            abort(404);
        }

        if (! $reading->is_paid) {
            return back()->with('error', '❌ Reading นี้ยังไม่ได้ชำระเงิน — restore ไม่ได้');
        }

        if ($reading->getCelticPickedCount() < 10) {
            return back()->with('error', '❌ ลูกค้ายังเปิดไพ่ไม่ครบ 10 ใบ — ใช้ปุ่ม Reset แทน');
        }

        $userId = $reading->platform_user_id ?? $reading->facebook_user_id;
        if (empty($userId)) {
            return back()->with('error', '❌ Reading นี้ไม่มี user_id — push ไปไม่ได้');
        }

        $notify = (bool) $request->input('notify', true);
        $settings = FortuneTellingSetting::getSettings();
        $defaultWindow = (int) ($settings->celtic_cross_qa_window_minutes ?? 15);

        try {
            // คำนวณเวลาที่เหลือจาก session เดิม
            $startedAtRaw = $reading->getConversationState('pro_session_started_at');
            $oldWindow = (int) $reading->getConversationState('pro_session_window_minutes', $defaultWindow);
            $remainingMin = 0;

            if (! empty($startedAtRaw)) {
                try {
                    $startedAt = \Carbon\Carbon::parse($startedAtRaw);
                    $elapsed = (int) $startedAt->diffInMinutes(now(), true);
                    $remainingMin = max(0, $oldWindow - $elapsed);
                } catch (\Throwable $e) {
                    $remainingMin = 0;
                }
            }

            // ถ้าเหลือเวลามากกว่า 5 นาที → คงเวลาเดิม (ใช้ started_at เดิม + window เดิม)
            // ถ้าน้อยกว่า → reset เวลาใหม่ตาม settings (กันลูกค้าได้เวลาน้อยไป)
            $usedExisting = $remainingMin > 5;

            if (! $usedExisting) {
                $reading->setConversationState('pro_session_started_at', now()->toIso8601String());
                $reading->setConversationState('pro_session_window_minutes', $defaultWindow);
                $remainingMin = $defaultWindow;
            }

            // เปิด Pro Session กลับ
            $reading->setConversationState('pro_session_active', true);
            $reading->setConversationState('pro_session_type', 'celtic');
            $reading->setConversationState('pro_session_pending_exit', false);

            // กลับเข้า chat state
            $reading->update([
                'conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
            ]);
            $reading->refresh();

            // แจ้งลูกค้า
            if ($notify) {
                $platform = $reading->platform
                    ?? (preg_match('/^U[0-9a-f]{32}$/i', $userId) ? 'line' : 'facebook');

                $channelManager = new FortuneChannelManager($settings);
                $name = $reading->facebook_user_name ?? 'เจ้าชะตา';

                $channelManager->sendResponse($platform, (string) $userId, [
                    'action' => 'celtic_restored',
                    'message' => "🌙✨ *แม่หมอจันทรากลับมาแล้วค่ะ คุณ{$name}* ✨🌙\n\n"
                        ."🙏 แอดมินเปิดประตูพลังกลับให้แล้ว — เจ้าชะตาคุยต่อกับแม่หมอได้เลย\n\n"
                        ."⏳ *เหลือเวลาคุยกัน {$remainingMin} นาที*\n\n"
                        ."💬 พิมพ์คำถามที่ค้างคาใจมาได้เลย — แม่หมอจะอ่านพลังงานจากไพ่ทั้ง 10 ใบให้ค่ะ ✨\n\n"
                        .'🛑 หรือพิมพ์ *"ยุติการทำนาย"* เมื่อพอใจแล้วนะคะ',
                    'reading' => $reading,
                ], [
                    'from_admin' => true,
                    'message_tag' => 'POST_PURCHASE_UPDATE',
                ]);
            }

            Log::info('Celtic admin restore active chat', [
                'reading_id' => $reading->id,
                'admin_id' => auth()->id(),
                'used_existing_window' => $usedExisting,
                'remaining_min' => $remainingMin,
                'notify' => $notify,
            ]);

            $msg = "✅ คืนสถานะกำลังดู #{$reading->id} สำเร็จ (เหลือ {$remainingMin} นาที)";
            if ($notify) {
                $msg .= ' + แจ้งลูกค้าแล้ว';
            }

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            Log::error('Celtic admin restore active chat failed', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', "❌ คืนสถานะล้มเหลว: {$e->getMessage()}");
        }
    }

    /**
     * ⏰ (2026-05-22) เพิ่มเวลา Pro Session — admin มอบเวลาคุยกับแม่หมอเพิ่ม
     *
     * Use case:
     *   - ลูกค้ากำลังคุยเพลิน แต่เวลาใกล้หมด → admin extend ให้ +30 นาที
     *   - ลูกค้าหมดเวลาแล้ว/COMPLETED → admin reactivate session ใหม่
     *   - ลูกค้า complain เวลาน้อย → admin compensate
     *
     * Params:
     *   - minutes: int 1-300 (จำนวนนาทีที่จะเพิ่ม/รีเซ็ตเป็น)
     *   - mode: 'add' (default) เพิ่มจาก window เดิม | 'reset' เริ่มเวลาใหม่จากตอนนี้
     *   - notify: bool (default true) แจ้งลูกค้าให้รู้ว่าได้เวลาเพิ่ม
     *
     * Logic:
     *   - active + remaining>0 + mode=add → pro_session_window_minutes += minutes (started_at คงเดิม)
     *   - active + mode=reset → started_at=now, window=minutes
     *   - inactive/expired → reactivate ทั้งหมด (active=true, type=celtic, started_at=now, window=minutes)
     *   - ถ้า status=COMPLETED → กลับเป็น CELTIC_AWAITING_QUESTION
     *
     * Safety:
     *   - ต้องเป็น Celtic reading + is_paid=true + เปิดไพ่ครบ 10 ใบ
     *   - ต้องมี user_id (ถ้า notify=true)
     */
    public function extendProSession(Request $request, FortuneReading $reading)
    {
        if ($reading->reading_type !== FortuneReading::READING_TYPE_CELTIC_CROSS) {
            abort(404);
        }

        if (! $reading->is_paid) {
            return back()->with('error', '❌ Reading นี้ยังไม่ได้ชำระเงิน — เพิ่มเวลาไม่ได้');
        }

        if ($reading->getCelticPickedCount() < 10) {
            return back()->with('error', '❌ ลูกค้ายังเปิดไพ่ไม่ครบ 10 ใบ — ยังไม่เข้า Pro Session');
        }

        $validated = $request->validate([
            'minutes' => 'required|integer|min:1|max:300',
            'mode' => 'sometimes|in:add,reset',
            'notify' => 'sometimes|boolean',
        ]);

        $minutes = (int) $validated['minutes'];
        $mode = $validated['mode'] ?? 'add';
        $notify = (bool) $request->input('notify', true);

        $settings = FortuneTellingSetting::getSettings();
        $defaultWindow = (int) ($settings->celtic_cross_qa_window_minutes ?? 15);

        try {
            // อ่าน state ปัจจุบัน
            $startedAtRaw = $reading->getConversationState('pro_session_started_at');
            $oldWindow = (int) $reading->getConversationState('pro_session_window_minutes', $defaultWindow);
            $isActive = (bool) $reading->getConversationState('pro_session_active', false);

            $remainingBefore = 0;
            if ($isActive && ! empty($startedAtRaw)) {
                try {
                    $elapsed = (int) \Carbon\Carbon::parse($startedAtRaw)->diffInMinutes(now(), true);
                    $remainingBefore = max(0, $oldWindow - $elapsed);
                } catch (\Throwable $e) {
                    $remainingBefore = 0;
                }
            }

            $sessionLive = $isActive && $remainingBefore > 0;
            $action = '';
            $newRemaining = 0;

            if ($sessionLive && $mode === 'add') {
                // 🟢 เพิ่มเวลาจาก window เดิม (started_at คงเดิม)
                $newWindow = $oldWindow + $minutes;
                $reading->setConversationState('pro_session_window_minutes', $newWindow);
                $newRemaining = $remainingBefore + $minutes;
                $action = "เพิ่ม +{$minutes} นาที (เวลาเดิม {$remainingBefore}m → ใหม่ {$newRemaining}m)";
            } elseif ($sessionLive && $mode === 'reset') {
                // 🟡 รีเซ็ตเวลาใหม่จากตอนนี้
                $reading->setConversationState('pro_session_started_at', now()->toIso8601String());
                $reading->setConversationState('pro_session_window_minutes', $minutes);
                $newRemaining = $minutes;
                $action = "รีเซ็ตเวลาใหม่ {$minutes} นาที (เดิมเหลือ {$remainingBefore}m)";
            } else {
                // 🔴 Session ปิด/หมดเวลา → reactivate ใหม่ทั้งหมด
                $reading->setConversationState('pro_session_active', true);
                $reading->setConversationState('pro_session_type', 'celtic');
                $reading->setConversationState('pro_session_started_at', now()->toIso8601String());
                $reading->setConversationState('pro_session_window_minutes', $minutes);
                $reading->setConversationState('pro_session_pending_exit', false);

                $newRemaining = $minutes;
                $action = "เปิด Pro Session ใหม่ {$minutes} นาที (session ปิดอยู่)";
            }

            // 🆕 Flip status กลับ active Celtic state ถ้าจำเป็น (ครอบคลุมทุก mode)
            // ครอบคลุม: COMPLETED, celtic_qa_window_expired, expired, cancelled, อื่นๆ
            $activeCelticStates = [
                FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
                FortuneReading::STATUS_CELTIC_GENERATING,
                FortuneReading::STATUS_CELTIC_QA_PROMPT,
            ];
            if (! in_array($reading->conversation_status, $activeCelticStates, true)) {
                $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);
            }

            $reading->refresh();

            // แจ้งลูกค้า
            $pushed = false;
            $pushNote = '';
            if ($notify) {
                $userId = $reading->platform_user_id ?? $reading->facebook_user_id;
                if (empty($userId)) {
                    $pushNote = ' (push ไม่ได้ — ไม่มี user_id)';
                } else {
                    $platform = $reading->platform
                        ?: (preg_match('/^U[0-9a-f]{32}$/i', (string) $userId) ? 'line' : 'facebook');
                    $name = $reading->facebook_user_name ?? 'เจ้าชะตา';
                    $channelManager = new FortuneChannelManager($settings);

                    $verb = $mode === 'reset' ? "เริ่มเวลาใหม่ {$minutes} นาที" : "เพิ่มเวลาอีก {$minutes} นาที";

                    $msg = "🌙✨ *แม่หมอจันทราเปิดประตูพลังเพิ่มเวลาให้ค่ะ คุณ{$name}* ✨🌙\n\n"
                        ."🎁 แอดมินส่งพลังพิเศษให้ — *{$verb}*\n\n"
                        ."⏳ *เหลือเวลาคุยกับแม่หมอ {$newRemaining} นาที*\n\n"
                        ."💬 พิมพ์คำถามต่อมาได้เลยค่ะ แม่หมอรอฟังอยู่ ✨\n\n"
                        .'🛑 หรือพิมพ์ *"ยุติการทำนาย"* เมื่อพอใจแล้วนะคะ';

                    $pushed = (bool) $channelManager->sendResponse($platform, (string) $userId, [
                        'action' => 'celtic_time_extended',
                        'message' => $msg,
                        'reading' => $reading,
                    ], [
                        'from_admin' => true,
                        'message_tag' => 'POST_PURCHASE_UPDATE',
                    ]);
                    $pushNote = $pushed ? ' + แจ้งลูกค้าแล้ว ✓' : ' (push ล้มเหลว — ลูกค้าจะเห็นตอนทักกลับ)';
                }
            }

            // Log takeover
            try {
                \App\Models\FortuneTakeoverLog::create([
                    'fortune_reading_id' => $reading->id,
                    'user_id' => auth()->id(),
                    'platform' => $reading->platform ?? 'unknown',
                    'action' => 'extend',
                    'reason' => 'manual',
                    'duration_minutes' => $minutes,
                    'message' => "[EXTEND {$mode}] {$action}",
                ]);
            } catch (\Throwable $logErr) {
                // non-critical
            }

            Log::info('Celtic admin extend pro session', [
                'reading_id' => $reading->id,
                'admin_id' => auth()->id(),
                'mode' => $mode,
                'minutes' => $minutes,
                'session_was_live' => $sessionLive,
                'remaining_before' => $remainingBefore,
                'remaining_after' => $newRemaining,
                'notified' => $notify,
                'pushed' => $pushed,
            ]);

            return back()->with('success', "✅ {$action}{$pushNote}");
        } catch (\Throwable $e) {
            Log::error('Celtic admin extend pro session failed', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', "❌ เพิ่มเวลาล้มเหลว: {$e->getMessage()}");
        }
    }

    /**
     * 🤖 (2026-05-17 Phase 2) Admin Ask AI — AJAX endpoint, sync, return JSON
     *
     * Flow:
     *   1. Admin กดปุ่ม "ทดสอบ" ใน form → JavaScript AJAX POST
     *   2. Controller validate + call CelticCrossService::askQuestionAsAdmin() sync
     *   3. Return JSON ที่มี success/response/sequence/pushed/ai_provider/tokens
     *   4. JavaScript แสดงผลใน UI ทันที (admin เห็น 30-60s + loading + result)
     *
     * Per user spec (2026-05-17):
     *   - ไม่ตัดโควต้าลูกค้า (admin sovereign)
     *   - บันทึก record + push เหมือนลูกค้าถามเอง
     *   - ไม่กระทบ flow ปกติ
     *
     * @set_time_limit 300 (5 minutes — กัน FPM/PHP timeout)
     */
    public function adminAskAi(Request $request, FortuneReading $reading): \Illuminate\Http\JsonResponse
    {
        @set_time_limit(300);

        if ($reading->reading_type !== FortuneReading::READING_TYPE_CELTIC_CROSS) {
            return response()->json([
                'success' => false,
                'message' => 'Reading นี้ไม่ใช่ Celtic Cross',
            ], 422);
        }

        $validated = $request->validate([
            'question' => 'required|string|min:3|max:1000',
            // 🪬 (2026-06-24) โหมดคุณไสย์ — toggle จากหน้า Admin Ask AI
            'black_magic_mode' => 'sometimes|boolean',
        ]);

        if (! $reading->is_paid) {
            return response()->json([
                'success' => false,
                'message' => 'Reading นี้ยังไม่ได้ชำระเงิน',
            ], 422);
        }

        if ($reading->getCelticPickedCount() < 10) {
            return response()->json([
                'success' => false,
                'message' => 'ลูกค้ายังเปิดไพ่ไม่ครบ 10 ใบ (มี '.$reading->getCelticPickedCount().' ใบ)',
            ], 422);
        }

        $userId = $reading->platform_user_id ?? $reading->facebook_user_id;
        if (empty($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'Reading นี้ไม่มี user_id — push ไปไม่ได้',
            ], 422);
        }

        $platform = $reading->platform
            ?? (preg_match('/^U[0-9a-f]{32}$/i', $userId) ? 'line' : 'facebook');

        Log::info('Celtic admin ask AI: เริ่ม (AJAX sync)', [
            'reading_id' => $reading->id,
            'admin_id' => auth()->id(),
            'platform' => $platform,
            'question_len' => mb_strlen($validated['question']),
        ]);

        // 📝 บันทึก takeover log
        try {
            \App\Models\FortuneTakeoverLog::create([
                'fortune_reading_id' => $reading->id,
                'user_id' => auth()->id(),
                'platform' => $platform,
                'action' => 'message',
                'reason' => 'admin_ai_assist',
                'message' => '[ADMIN ASK AI] '.mb_substr($validated['question'], 0, 500),
            ]);
        } catch (\Throwable $logErr) {
            // non-critical
        }

        // 🤖 เรียก service sync
        $settings = FortuneTellingSetting::getSettings();

        // 🪬 (2026-06-24) โหมดคุณไสย์ — แอดมิน toggle หน้านี้ → ตั้ง/ปลดธงบน reading (gate ด้วย master setting)
        //   buildBlackMagicDirective (ผ่าน isBlackMagicModeForced) อ่านธงนี้ → เทเรื่องคุณไสย์ 100%
        //   ใช้ทั้งคำตอบของแอดมิน + คำถามที่ลูกค้าถามเองต่อ (ติดถาวรจนแอดมินปิด)
        if ($request->has('black_magic_mode')) {
            $bmOn = $request->boolean('black_magic_mode')
                && (bool) ($settings->enable_celtic_black_magic_mode ?? true);
            $reading->setConversationState('black_magic_mode', $bmOn);

            // sync carrier cache ให้ตรงกัน (กัน carrier เก่าค้างย้อนเปิด/ปิดโหมดสวนกับธง)
            $bmUid = $reading->platform_user_id ?? $reading->facebook_user_id;
            if (! empty($bmUid)) {
                if ($bmOn) {
                    \Illuminate\Support\Facades\Cache::put('fortune:force_black_magic:'.$bmUid, true, now()->addHours(2));
                } else {
                    \Illuminate\Support\Facades\Cache::forget('fortune:force_black_magic:'.$bmUid);
                }
            }

            Log::info('Celtic admin ask AI: ตั้งค่าโหมดคุณไสย์', [
                'reading_id' => $reading->id,
                'black_magic_mode' => $bmOn,
                'admin_id' => auth()->id(),
            ]);
        }

        $service = new CelticCrossService($settings);
        $result = $service->askQuestionAsAdmin($reading, $validated['question']);

        $result['platform'] = $platform;

        return response()->json($result);
    }

    /**
     * 🚨 Emergency Recovery — แสดงหน้าฟอร์มกู้บิลด่วน
     *
     * ใช้กรณีลูกค้าจ่ายแล้วบอทเงียบ — แอดมินใส่เลขบิลเพื่อ re-push prompt ทันที
     */
    public function emergencyRecover()
    {
        // นับ readings ค้างที่ scanner จะเจอ (ตัวอย่าง 5 นาทีตามค่า default)
        $cutoff = now()->subMinutes(5);
        $excluded = ['cancelled', 'celtic_qa_window_expired', 'expired'];
        $stuckCount = FortuneReading::where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
            ->where('is_paid', true)
            ->whereNotIn('conversation_status', $excluded)
            ->where('updated_at', '<=', $cutoff)
            ->where(function ($q) {
                $q->whereNull('celtic_questions_used')->orWhere('celtic_questions_used', 0);
            })
            ->count();

        return view('admin.fortune.celtic-cross.emergency-recover', [
            'pageTitle' => '🚨 Emergency Recovery — Celtic Cross',
            'stuckCount' => $stuckCount,
            'results' => null,
        ]);
    }

    /**
     * 🚨 Emergency Recovery — ดำเนินการกู้
     *
     * รับ:
     *   - mode=bills: textarea "bills" (1 บรรทัด/บิล รับทั้ง bill_reference และ numeric id)
     *   - mode=auto: scan Celtic ที่ paid + questions_used=0 + ค้าง > N นาที
     *   - notify_message (optional): override header message
     *
     * Action ต่อแต่ละ reading:
     *   1. status=CELTIC_PENDING_PAYMENT → onCelticPaymentConfirmed (transition + prompt ใบ 1)
     *   2. status='new' + is_paid=true → 🚨 Force-promote → CELTIC_PICKING + prompt ใบ 1
     *      (เคสที่ slip matcher transition ไม่ครบ — บิล FTU-260505-J1439 เจอแบบนี้)
     *   3. อื่นๆ → buildCelticResumeResponse (resume ที่จุดเดิม)
     */
    public function emergencyRecoverAction(Request $request)
    {
        $mode = $request->input('mode', 'bills');
        $minutes = max(1, (int) $request->input('minutes', 5));
        $customHeader = trim((string) $request->input('notify_message', ''));

        $readings = collect();
        $notFound = [];

        if ($mode === 'bills') {
            $billsRaw = trim((string) $request->input('bills', ''));
            if ($billsRaw === '') {
                return back()->with('error', '❌ กรุณาระบุเลขบิลอย่างน้อย 1 รายการ');
            }
            $tokens = preg_split('/[\s,]+/', $billsRaw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $tokens = array_unique(array_map('trim', $tokens));

            foreach ($tokens as $tok) {
                if ($tok === '') {
                    continue;
                }
                $r = is_numeric($tok)
                    ? FortuneReading::find((int) $tok)
                    : FortuneReading::where('bill_reference', $tok)->first();
                if ($r) {
                    $readings->push($r);
                } else {
                    $notFound[] = $tok;
                }
            }
        } elseif ($mode === 'auto') {
            $cutoff = now()->subMinutes($minutes);
            $excluded = ['cancelled', 'celtic_qa_window_expired', 'expired'];
            $candidates = FortuneReading::where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
                ->where('is_paid', true)
                ->whereNotIn('conversation_status', $excluded)
                ->where('updated_at', '<=', $cutoff)
                ->get();
            $readings = $candidates->filter(fn ($r) => (int) ($r->celtic_questions_used ?? 0) === 0);
        } else {
            return back()->with('error', '❌ mode ไม่ถูกต้อง');
        }

        if ($readings->isEmpty()) {
            $msg = '✅ ไม่พบ reading ที่ต้องกู้';
            if (! empty($notFound)) {
                $msg .= ' — บิลที่ไม่พบ: '.implode(', ', $notFound);
            }

            return back()->with('error', $msg);
        }

        $settings = FortuneTellingSetting::getSettings();
        $svc = new FortuneConversationService($settings);
        $cm = new FortuneChannelManager($settings);

        $results = [];
        $okCount = 0;
        $failCount = 0;

        foreach ($readings as $r) {
            $row = [
                'id' => $r->id,
                'bill' => $r->bill_reference ?? '-',
                'user' => $r->facebook_user_name ?? '-',
                'platform' => $r->platform ?? '-',
                'status_before' => $r->conversation_status,
                'picked' => $r->getCelticPickedCount(),
                'ok' => false,
                'msg' => '',
            ];

            try {
                $platform = $r->platform
                    ?? (preg_match('/^U[0-9a-f]{32}$/i', $r->platform_user_id ?? $r->facebook_user_id ?? '') ? 'line' : 'facebook');
                $userId = $r->platform_user_id ?? $r->facebook_user_id;

                if (empty($userId)) {
                    $row['msg'] = '❌ ไม่มี user_id';
                    $results[] = $row;
                    $failCount++;

                    continue;
                }

                // 🩹 Force-promote stuck-at-'new' reading (slip matched + transition skipped)
                $forcePromoted = false;
                if ($r->is_paid
                    && in_array($r->conversation_status, [FortuneReading::STATUS_NEW, FortuneReading::STATUS_CELTIC_PENDING_PAYMENT], true)
                    && $r->getCelticPickedCount() === 0) {
                    if ($r->reading_type !== FortuneReading::READING_TYPE_CELTIC_CROSS) {
                        $r->update(['reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS]);
                    }
                    $r->update(['conversation_status' => FortuneReading::STATUS_CELTIC_PENDING_PAYMENT]);
                    $response = $svc->onCelticPaymentConfirmed($r->fresh());
                    $forcePromoted = true;
                } else {
                    $response = $svc->buildCelticResumeResponse($r->fresh(), false);
                }

                $defaultHeader = "🔔 *ขออภัยที่ทำให้รอนะคะ*\n"
                    ."ระบบกู้สถานะให้แล้ว — ดำเนินการต่อได้เลยค่ะ ⬇️\n\n"
                    ."═══════════════════════\n\n";
                $header = $customHeader !== ''
                    ? rtrim($customHeader)."\n\n═══════════════════════\n\n"
                    : $defaultHeader;
                $response['message'] = $header.($response['message'] ?? '');

                $sent = $cm->sendResponse($platform, $userId, $response, [
                    'from_admin' => true,
                    'message_tag' => 'POST_PURCHASE_UPDATE',
                ]);

                $row['ok'] = (bool) $sent;
                $row['msg'] = $sent
                    ? ($forcePromoted ? '✅ Force-promote + ส่งสำเร็จ' : '✅ ส่งสำเร็จ')
                    : '❌ ส่งไม่สำเร็จ (ดู log)';
                $row['status_after'] = $r->fresh()->conversation_status;

                $sent ? $okCount++ : $failCount++;

                Log::info('Celtic Emergency Recovery', [
                    'reading_id' => $r->id,
                    'bill' => $r->bill_reference,
                    'platform' => $platform,
                    'force_promoted' => $forcePromoted,
                    'sent' => $sent,
                    'admin_id' => auth()->id(),
                ]);
            } catch (\Throwable $e) {
                $row['msg'] = '❌ Error: '.mb_substr($e->getMessage(), 0, 120);
                $results[] = $row;
                $failCount++;
                Log::error('Celtic Emergency Recovery: exception', [
                    'reading_id' => $r->id,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            $results[] = $row;
        }

        $cutoff = now()->subMinutes(5);
        $excluded = ['cancelled', 'celtic_qa_window_expired', 'expired'];
        $stuckCount = FortuneReading::where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
            ->where('is_paid', true)
            ->whereNotIn('conversation_status', $excluded)
            ->where('updated_at', '<=', $cutoff)
            ->where(function ($q) {
                $q->whereNull('celtic_questions_used')->orWhere('celtic_questions_used', 0);
            })
            ->count();

        $summary = sprintf(
            '📊 กู้สำเร็จ %d | ล้มเหลว %d%s',
            $okCount,
            $failCount,
            $notFound ? ' | ไม่พบบิล: '.implode(', ', $notFound) : ''
        );

        return view('admin.fortune.celtic-cross.emergency-recover', [
            'pageTitle' => '🚨 Emergency Recovery — Celtic Cross',
            'stuckCount' => $stuckCount,
            'results' => $results,
            'summary' => $summary,
            'okCount' => $okCount,
            'failCount' => $failCount,
            'notFound' => $notFound,
        ]);
    }
}
