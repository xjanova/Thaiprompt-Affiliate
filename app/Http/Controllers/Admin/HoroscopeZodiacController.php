<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HoroscopeDailyPrediction;
use App\Models\HoroscopeZodiacSign;
use App\Services\HoroscopeDailyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * HoroscopeZodiacController — จัดการ 12 ราศี + ดวงรายวัน
 *
 * จัดการ:
 * - ดู/แก้ไข 12 ราศี (ไม่มี create/delete เพราะ seed มาตรฐาน)
 * - สั่ง generate ดวงรายวัน (AI)
 * - ดู/ลบ daily predictions
 */
class HoroscopeZodiacController extends Controller
{
    /**
     * @var HoroscopeDailyService
     */
    protected HoroscopeDailyService $dailyService;

    /**
     * Constructor
     */
    public function __construct(HoroscopeDailyService $dailyService)
    {
        $this->dailyService = $dailyService;
    }

    /**
     * แสดงรายการ 12 ราศี
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $zodiacs = HoroscopeZodiacSign::orderBy('sort_order')
            ->withCount('dailyPredictions')
            ->get();

        // ดวงวันนี้
        $todayPredictions = HoroscopeDailyPrediction::where('target_date', today())
            ->where('prediction_type', 'zodiac')
            ->get()
            ->keyBy('zodiac_sign_id');

        return view('admin.fortune.horoscope-public.zodiac.index', [
            'pageTitle' => '⭐ จัดการ 12 ราศี',
            'zodiacs' => $zodiacs,
            'todayPredictions' => $todayPredictions,
        ]);
    }

    /**
     * แสดงฟอร์มแก้ไขราศี
     *
     * @param HoroscopeZodiacSign $zodiac
     * @return \Illuminate\View\View
     */
    public function edit(HoroscopeZodiacSign $zodiac)
    {
        return view('admin.fortune.horoscope-public.zodiac.form', [
            'pageTitle' => "✏️ แก้ไขราศี {$zodiac->name_th}",
            'zodiac' => $zodiac,
        ]);
    }

    /**
     * อัพเดทข้อมูลราศี
     *
     * @param Request $request
     * @param HoroscopeZodiacSign $zodiac
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, HoroscopeZodiacSign $zodiac)
    {
        $validated = $request->validate([
            'name_th' => 'required|string|max:100',
            'name_en' => 'required|string|max:100',
            'date_range_display_th' => 'nullable|string|max:255',
            'element' => 'required|string|max:50',
            'ruling_planet' => 'required|string|max:100',
            'quality' => 'nullable|string|max:50',
            'symbol_emoji' => 'required|string|max:10',
            'color_hex' => 'required|string|max:10',
            'gradient_from' => 'nullable|string|max:10',
            'gradient_to' => 'nullable|string|max:10',
            'description_th' => 'nullable|string',
            'personality_th' => 'nullable|string',
            'strengths_th' => 'nullable|string',
            'weaknesses_th' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'required|integer|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $zodiac->update($validated);

        return redirect()
            ->route('admin.fortune.horoscope-public.zodiac.index')
            ->with('success', "✅ อัพเดทราศี {$zodiac->name_th} สำเร็จ");
    }

    /**
     * สั่ง generate ดวงรายวัน AI สำหรับวันที่ระบุ
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generateDaily(Request $request)
    {
        $request->validate([
            'target_date' => 'nullable|date',
            'type' => 'required|in:zodiac,birth_day,both',
        ]);

        $date = $request->input('target_date')
            ? Carbon::parse($request->input('target_date'))
            : Carbon::today();

        $type = $request->input('type', 'both');

        try {
            $results = [];

            if (in_array($type, ['zodiac', 'both'])) {
                $zodiacResults = $this->dailyService->generateDailyForAllZodiacs($date);
                $results['zodiac'] = $zodiacResults;
            }

            if (in_array($type, ['birth_day', 'both'])) {
                $birthDayResults = $this->dailyService->generateDailyForAllBirthDays($date);
                $results['birth_day'] = $birthDayResults;
            }

            // นับจำนวนที่สำเร็จ
            $successCount = 0;
            $failCount = 0;
            foreach ($results as $type => $items) {
                foreach ($items as $item) {
                    if (isset($item['status']) && $item['status'] === 'generated') {
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                }
            }

            $message = "✅ สร้างดวงวันที่ {$date->format('d/m/Y')} สำเร็จ {$successCount} รายการ";
            if ($failCount > 0) {
                $message .= " (ล้มเหลว {$failCount})";
            }

            return redirect()
                ->route('admin.fortune.horoscope-public.zodiac.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Generate daily predictions ผิดพลาด: ' . $e->getMessage());

            return redirect()
                ->route('admin.fortune.horoscope-public.zodiac.index')
                ->with('error', '❌ เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * แสดงรายการ daily predictions
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function predictions(Request $request)
    {
        $query = HoroscopeDailyPrediction::with('zodiacSign')
            ->orderByDesc('target_date')
            ->orderBy('prediction_type');

        // กรองตามวันที่
        if ($request->filled('date')) {
            $query->where('target_date', $request->input('date'));
        }

        // กรองตามประเภท
        if ($request->filled('type')) {
            $query->where('prediction_type', $request->input('type'));
        }

        // กรองตามสถานะ
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $predictions = $query->paginate(20);

        return view('admin.fortune.horoscope-public.zodiac.predictions', [
            'pageTitle' => '📊 ดวงรายวันทั้งหมด',
            'predictions' => $predictions,
        ]);
    }

    /**
     * ลบ prediction
     *
     * @param HoroscopeDailyPrediction $prediction
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyPrediction(HoroscopeDailyPrediction $prediction)
    {
        $prediction->delete();

        return redirect()
            ->back()
            ->with('success', '✅ ลบรายการสำเร็จ');
    }
}
