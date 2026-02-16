<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FortuneChartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Fortune Astrology Controller
 *
 * จัดการการตั้งค่าโหราศาสตร์ไทย (เจ้าชนะ) + Preview Birth Chart
 */
class FortuneAstrologyController extends Controller
{
    /**
     * แสดงหน้าตั้งค่าโหราศาสตร์
     */
    public function index()
    {
        // ข้อมูลดาวเคราะห์ 9 ดวง
        $planets = FortuneChartService::PLANETS;

        // ข้อมูลภพ 12 ภพ
        $houses = FortuneChartService::HOUSES;

        // ตารางเจ้าชนะ 7 วัน
        $chaochana = FortuneChartService::CHAOCHANA;

        // แปลง day number เป็นชื่อวัน
        $dayNames = [
            0 => 'อาทิตย์',
            1 => 'จันทร์',
            2 => 'อังคาร',
            3 => 'พุธ',
            4 => 'พฤหัสบดี',
            5 => 'ศุกร์',
            6 => 'เสาร์',
        ];

        return view('admin.fortune.astrology.index', compact(
            'planets', 'houses', 'chaochana', 'dayNames'
        ));
    }

    /**
     * Preview Birth Chart จากวันเกิดที่ระบุ
     */
    public function previewChart(Request $request)
    {
        $request->validate([
            'birth_date' => 'required|date',
            'name' => 'required|string|max:100',
            'gender' => 'nullable|in:male,female',
        ]);

        try {
            $chartService = new FortuneChartService;
            $chartUrl = $chartService->generateBirthChartSvg(
                $request->birth_date,
                $request->name,
                $request->gender
            );

            return response()->json([
                'success' => true,
                'chart_url' => $chartUrl,
            ]);
        } catch (\Throwable $e) {
            Log::error('Astrology: preview chart failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ทดสอบสร้าง PNG Birth Chart (ใช้ GD library)
     *
     * เพื่อตรวจสอบว่า GD extension ทำงานได้บน production server
     */
    public function testPngChart(Request $request)
    {
        $results = [
            'gd_loaded' => extension_loaded('gd'),
            'gd_info' => function_exists('gd_info') ? gd_info() : 'gd_info() not available',
            'php_version' => PHP_VERSION,
            'freetype_support' => function_exists('gd_info') ? (gd_info()['FreeType Support'] ?? false) : false,
        ];

        try {
            $chartService = new FortuneChartService;
            $chartUrl = $chartService->generateBirthChart('1990-05-15', 'ทดสอบ PNG', null);
            $results['chart_url'] = $chartUrl;
            $results['success'] = ! empty($chartUrl);
        } catch (\Throwable $e) {
            $results['success'] = false;
            $results['error'] = $e->getMessage();
            $results['error_class'] = get_class($e);
        }

        return response()->json($results);
    }

    /**
     * ทดสอบคำนวณดาวจากวันเกิด
     */
    public function testCalculation(Request $request)
    {
        $request->validate([
            'birth_date' => 'required|date',
        ]);

        try {
            $birthDate = \Carbon\Carbon::parse($request->birth_date);
            $dayOfWeek = (int) $birthDate->format('w'); // 0=Sun, 6=Sat

            $dayNames = [
                0 => 'อาทิตย์', 1 => 'จันทร์', 2 => 'อังคาร', 3 => 'พุธ',
                4 => 'พฤหัสบดี', 5 => 'ศุกร์', 6 => 'เสาร์',
            ];

            $chartService = new FortuneChartService;
            $positions = $chartService->calculatePlanetPositions($dayOfWeek);

            // ข้อมูลเจ้าชนะ
            $chaochana = FortuneChartService::CHAOCHANA[$dayOfWeek] ?? null;
            $rulingPlanet = $chaochana ? (FortuneChartService::PLANETS[$chaochana['planet']] ?? null) : null;

            return response()->json([
                'success' => true,
                'birth_date' => $birthDate->format('d/m/Y'),
                'day_of_week' => $dayNames[$dayOfWeek] ?? '?',
                'day_number' => $dayOfWeek,
                'ruling_planet' => $rulingPlanet ? [
                    'name' => $rulingPlanet['name'],
                    'symbol' => $rulingPlanet['symbol'],
                    'element' => $chaochana['element'] ?? '',
                ] : null,
                'friends' => $chaochana['friends'] ?? [],
                'enemies' => $chaochana['enemies'] ?? [],
                'lucky_color' => $chaochana['lucky_color'] ?? '',
                'planet_positions' => collect($positions)->map(function ($planets, $houseNum) {
                    $house = FortuneChartService::HOUSES[$houseNum] ?? null;

                    return [
                        'house_number' => $houseNum,
                        'house_name' => $house['name'] ?? "ภพ $houseNum",
                        'planets' => collect($planets)->map(function ($pKey) {
                            $p = FortuneChartService::PLANETS[$pKey] ?? null;

                            return $p ? [
                                'key' => $pKey,
                                'name' => $p['name'],
                                'symbol' => $p['symbol'],
                                'color' => $p['color'],
                            ] : null;
                        })->filter()->values()->toArray(),
                    ];
                })->values()->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Astrology: test calculation failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * อัปเดตการตั้งค่า (สำหรับ future use)
     */
    public function update(Request $request)
    {
        // Reserved for future use - custom astrology settings
        return redirect()->route('admin.fortune.astrology.index')
            ->with('success', 'บันทึกการตั้งค่าโหราศาสตร์สำเร็จ');
    }
}
