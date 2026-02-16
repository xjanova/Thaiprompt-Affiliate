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
     * วินิจฉัยทุก step: font, GD, buildPng, saveFile
     * เพื่อหาสาเหตุจริงว่า chart generation ล้มเหลวตรงไหน
     */
    public function testPngChart(Request $request)
    {
        $results = [
            'gd_loaded' => extension_loaded('gd'),
            'php_version' => PHP_VERSION,
            'freetype_support' => function_exists('gd_info') ? (gd_info()['FreeType Support'] ?? false) : false,
        ];

        // Step 1: เช็ค font files
        $thaiFont = resource_path('fonts/NotoSansThai-Bold.ttf');
        $symbolFont = resource_path('fonts/DejaVuSans.ttf');
        $results['fonts'] = [
            'thai_font_path' => $thaiFont,
            'thai_font_exists' => @file_exists($thaiFont),
            'thai_font_size' => @file_exists($thaiFont) ? @filesize($thaiFont) : null,
            'symbol_font_path' => $symbolFont,
            'symbol_font_exists' => @file_exists($symbolFont),
            'symbol_font_size' => @file_exists($symbolFont) ? @filesize($symbolFont) : null,
            'resource_path' => resource_path('fonts'),
            'resource_dir_exists' => @is_dir(resource_path('fonts')),
        ];

        // Step 2: เช็ค resources/fonts directory
        try {
            $fontDir = resource_path('fonts');
            $results['font_dir_contents'] = @is_dir($fontDir) ? @scandir($fontDir) : 'directory not found';
        } catch (\Throwable $e) {
            $results['font_dir_contents'] = 'error: '.$e->getMessage();
        }

        // Step 3: เช็ค storage/app/public/fortune/charts writable
        try {
            $testPath = 'fortune/charts/test-write-'.time().'.txt';
            \Illuminate\Support\Facades\Storage::disk('public')->put($testPath, 'test');
            $results['storage_writable'] = true;
            $results['storage_url'] = \Illuminate\Support\Facades\Storage::disk('public')->url($testPath);
            \Illuminate\Support\Facades\Storage::disk('public')->delete($testPath);
        } catch (\Throwable $e) {
            $results['storage_writable'] = false;
            $results['storage_error'] = $e->getMessage();
        }

        // Step 4: ทดสอบ imagettftext พื้นฐาน
        if (extension_loaded('gd')) {
            try {
                $testImg = @imagecreatetruecolor(100, 100);
                if ($testImg) {
                    $white = imagecolorallocate($testImg, 255, 255, 255);
                    $fontToTest = @file_exists($thaiFont) ? $thaiFont : (@file_exists($symbolFont) ? $symbolFont : null);

                    if ($fontToTest) {
                        $ttfResult = @imagettftext($testImg, 14, 0, 10, 50, $white, $fontToTest, 'Test');
                        $results['imagettftext_test'] = $ttfResult !== false ? 'OK' : 'FAILED (returned false)';

                        // ทดสอบภาษาไทย
                        $ttfThaiResult = @imagettftext($testImg, 14, 0, 10, 80, $white, $fontToTest, 'ทดสอบ');
                        $results['imagettftext_thai_test'] = $ttfThaiResult !== false ? 'OK' : 'FAILED';
                    } else {
                        $results['imagettftext_test'] = 'SKIPPED - no font file found';
                    }

                    imagedestroy($testImg);
                } else {
                    $results['imagettftext_test'] = 'FAILED - imagecreatetruecolor returned false';
                }
            } catch (\Throwable $e) {
                $results['imagettftext_test'] = 'ERROR: '.$e->getMessage();
                $results['imagettftext_error_class'] = get_class($e);
            }
        }

        // Step 5: ทดสอบ generateBirthChart ตรงๆ (ดักจับ error จาก internal catch)
        try {
            $chartService = new FortuneChartService;
            $chartUrl = $chartService->generateBirthChart('1990-05-15', 'ทดสอบ PNG', null);
            $results['chart_url'] = $chartUrl;
            $results['success'] = ! empty($chartUrl);
        } catch (\Throwable $e) {
            $results['success'] = false;
            $results['error'] = $e->getMessage();
            $results['error_class'] = get_class($e);
            $results['error_trace'] = substr($e->getTraceAsString(), 0, 1000);
        }

        // Step 6: ถ้า generateBirthChart return null → ลอง log ล่าสุด
        if (empty($results['chart_url'])) {
            $results['note'] = 'chart_url is null — error ถูก catch ภายใน generateBirthChart() แล้ว return null. ดู Laravel log สำหรับ error จริง';

            // ลอง read ไฟล์ log ล่าสุด
            try {
                $logFile = storage_path('logs/laravel.log');
                if (@file_exists($logFile)) {
                    // อ่าน 5000 bytes สุดท้าย
                    $fh = @fopen($logFile, 'r');
                    if ($fh) {
                        $fileSize = @filesize($logFile);
                        $readSize = min(5000, $fileSize);
                        @fseek($fh, -$readSize, SEEK_END);
                        $lastLines = @fread($fh, $readSize);
                        @fclose($fh);

                        // หาบรรทัดที่มี FortuneChart
                        $lines = explode("\n", $lastLines);
                        $chartLines = array_filter($lines, fn ($l) => str_contains($l, 'FortuneChart'));
                        $results['recent_chart_logs'] = array_values(array_slice($chartLines, -5));
                    }
                }
            } catch (\Throwable $logErr) {
                $results['log_read_error'] = $logErr->getMessage();
            }
        }

        return response()->json($results, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
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
