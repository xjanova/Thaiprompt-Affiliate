<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneCelticQuestion;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use Illuminate\Http\Request;

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

        // Recent readings
        $recentReadings = FortuneReading::where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
            ->withCount('celticQuestions')
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.fortune.celtic-cross.index', [
            'settings' => $settings,
            'stats' => $stats,
            'recentReadings' => $recentReadings,
            'pageTitle' => 'Celtic Cross Tarot Mode',
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
            'celtic_cross_max_questions' => 'integer|min:1|max:10',
            'celtic_cross_qa_window_minutes' => 'integer|min:5|max:1440',
            'celtic_cross_main_prompt' => 'nullable|string|max:10000',
            'celtic_cross_followup_prompt' => 'nullable|string|max:10000',
        ]);

        $settings = FortuneTellingSetting::getSettings();

        $settings->enable_celtic_cross = $request->boolean('enable_celtic_cross');
        $settings->celtic_cross_proactive_enabled = $request->boolean('celtic_cross_proactive_enabled');
        $settings->celtic_cross_price = $validated['celtic_cross_price'] ?? 99.00;
        $settings->celtic_cross_max_questions = $validated['celtic_cross_max_questions'] ?? 3;
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

        return view('admin.fortune.celtic-cross.show', [
            'reading' => $reading,
            'cards' => $cards,
            'positions' => FortuneReading::CELTIC_POSITIONS,
            'pageTitle' => "Celtic Cross #{$reading->id}",
        ]);
    }
}
