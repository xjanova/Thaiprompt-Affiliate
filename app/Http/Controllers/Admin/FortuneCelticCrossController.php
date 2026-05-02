<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneCelticQuestion;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\CelticCrossService;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use Illuminate\Http\Request;
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
        $settings = FortuneTellingSetting::getSettings();

        // สถิติ Celtic Cross
        $stats = [
            'total_readings' => FortuneReading::where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)->count(),
            'paid_readings' => FortuneReading::where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
                ->where('is_paid', true)->count(),
            'completed_today' => FortuneReading::where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
                ->whereDate('celtic_first_answered_at', today())->count(),
            'total_revenue' => (float) FortuneReading::where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
                ->where('is_paid', true)->sum('amount_paid'),
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
        if ($status = $request->input('status')) {
            if ($status === 'paid') {
                $query->where('is_paid', true);
            } elseif ($status === 'unpaid') {
                $query->where('is_paid', false);
            } elseif ($status === 'stuck') {
                // paid + 0 picked → stuck case
                $query->where('is_paid', true);
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

        $recentReadings = $query->latest()->paginate(20)->withQueryString();

        // Filter stuck (post-query — needs JSON inspect)
        if ($status === 'stuck') {
            $recentReadings->setCollection(
                $recentReadings->getCollection()->filter(fn ($r) => $r->getCelticPickedCount() < 10)
            );
        }

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
            'celtic_cross_price' => 'numeric|min:1|max:9999',
            'celtic_cross_max_questions' => 'integer|min:0|max:50', // 0 = ไม่จำกัด
            'celtic_cross_qa_window_minutes' => 'integer|min:5|max:1440',
            'celtic_cross_main_prompt' => 'nullable|string|max:10000',
            'celtic_cross_followup_prompt' => 'nullable|string|max:10000',
        ]);

        $settings = FortuneTellingSetting::getSettings();

        $settings->enable_celtic_cross = $request->boolean('enable_celtic_cross');
        $settings->celtic_cross_proactive_enabled = $request->boolean('celtic_cross_proactive_enabled');
        $settings->celtic_cross_price = $validated['celtic_cross_price'] ?? 99.00;
        $settings->celtic_cross_max_questions = $validated['celtic_cross_max_questions'] ?? 5;
        $settings->celtic_cross_qa_window_minutes = $validated['celtic_cross_qa_window_minutes'] ?? 60;

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
            'maxQuestions' => (int) ($settings->celtic_cross_max_questions ?? 5),
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
                        . "เจ้าชะตาสามารถเริ่มเปิดไพ่ใหม่ได้เลย — ค่าครูเดิมยังใช้ได้ ไม่ต้องจ่ายซ้ำ\n\n"
                        . "═══════════════════════\n\n"
                        . ($response['message'] ?? '');

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

            return back()->with('success', "✅ Reset Reading #{$reading->id} สำเร็จ" . ($notify ? ' + แจ้งลูกค้าแล้ว' : ''));
        } catch (\Throwable $e) {
            Log::error('Celtic admin reset failed (web)', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', "❌ Reset ล้มเหลว: {$e->getMessage()}");
        }
    }
}
