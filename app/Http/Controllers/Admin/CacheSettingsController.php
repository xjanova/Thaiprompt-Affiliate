<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CacheManagementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Exception;

/**
 * Cache Settings Controller
 *
 * จัดการตั้งค่าระบบแคชในหน้า Admin
 * รองรับ File, Database, Redis, และ Memcached
 */
class CacheSettingsController extends Controller
{
    /**
     * Cache Management Service
     *
     * @var CacheManagementService
     */
    protected CacheManagementService $cacheService;

    /**
     * Constructor
     */
    public function __construct(CacheManagementService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * แสดงหน้าตั้งค่า Cache
     *
     * @return View
     */
    public function index(): View
    {
        // ดึงสถานะของ cache drivers ทั้งหมด
        $driversStatus = $this->cacheService->getAllDriversStatus();

        // ดึงข้อมูลสถิติ cache ปัจจุบัน
        $currentStats = $this->cacheService->getCurrentCacheStats();

        // ดึง current driver จาก config
        $currentDriver = config('cache.default');

        return view('admin.cache-settings', [
            'pageTitle' => 'ตั้งค่าระบบแคช',
            'driversStatus' => $driversStatus,
            'currentStats' => $currentStats,
            'currentDriver' => $currentDriver,
        ]);
    }

    /**
     * ทดสอบการเชื่อมต่อ cache driver
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function testConnection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'driver' => 'required|string|in:file,database,redis,memcached',
        ]);

        $result = $this->cacheService->testConnection($validated['driver']);

        return response()->json($result);
    }

    /**
     * ดึงสถานะของ cache drivers ทั้งหมด
     *
     * @return JsonResponse
     */
    public function getDriversStatus(): JsonResponse
    {
        $statuses = $this->cacheService->getAllDriversStatus();

        return response()->json([
            'success' => true,
            'data' => $statuses,
        ]);
    }

    /**
     * ดึงข้อมูลสถิติ cache ปัจจุบัน
     *
     * @return JsonResponse
     */
    public function getStats(): JsonResponse
    {
        $stats = $this->cacheService->getCurrentCacheStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * ล้างแคชทั้งหมด
     *
     * @return JsonResponse
     */
    public function clearCache(): JsonResponse
    {
        try {
            $success = $this->cacheService->clearAllCache();

            if ($success) {
                // ล้าง compiled views และ route cache ด้วย
                Artisan::call('view:clear');
                Artisan::call('route:clear');

                return response()->json([
                    'success' => true,
                    'message' => 'ล้างแคชทั้งหมดสำเร็จ',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถล้างแคชได้',
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * เปลี่ยน cache driver
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function changeDriver(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'driver' => 'required|string|in:file,database,redis,memcached',
        ]);

        $driver = $validated['driver'];

        // ทดสอบ connection ก่อนเปลี่ยน
        $testResult = $this->cacheService->testConnection($driver);

        if (!$testResult['status']) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถใช้งาน ' . $driver . ' ได้: ' . $testResult['message'],
            ], 422);
        }

        try {
            // อัพเดท .env file
            $this->updateEnvFile('CACHE_DRIVER', $driver);

            // Clear cache เดิมก่อนเปลี่ยน
            $this->cacheService->clearAllCache();

            // Clear config cache
            Artisan::call('config:clear');

            return response()->json([
                'success' => true,
                'message' => 'เปลี่ยน Cache Driver เป็น ' . $driver . ' สำเร็จ',
                'driver' => $driver,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ดึงคำแนะนำการติดตั้งสำหรับ driver
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getInstallationGuide(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'driver' => 'required|string|in:file,database,redis,memcached',
        ]);

        $guide = $this->cacheService->getInstallationGuide($validated['driver']);

        return response()->json([
            'success' => true,
            'data' => $guide,
        ]);
    }

    /**
     * อัพเดท .env file
     *
     * @param string $key
     * @param string $value
     * @return void
     *
     * @throws Exception
     */
    protected function updateEnvFile(string $key, string $value): void
    {
        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            throw new Exception('ไม่พบไฟล์ .env');
        }

        $envContent = File::get($envPath);

        // เช็คว่ามี key อยู่แล้วหรือไม่
        if (preg_match("/^{$key}=/m", $envContent)) {
            // แทนที่ค่าเดิม
            $envContent = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                $envContent
            );
        } else {
            // เพิ่มค่าใหม่ท้ายไฟล์
            $envContent .= "\n{$key}={$value}\n";
        }

        File::put($envPath, $envContent);
    }

    /**
     * Optimize cache (warm up)
     *
     * @return JsonResponse
     */
    public function optimize(): JsonResponse
    {
        try {
            // Run optimization commands
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');

            return response()->json([
                'success' => true,
                'message' => 'ปรับแต่งแคชสำเร็จ (Config, Routes, Views ถูก cache แล้ว)',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ล้าง cache เฉพาะส่วน
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function clearSpecific(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:config,route,view,all',
        ]);

        try {
            switch ($validated['type']) {
                case 'config':
                    Artisan::call('config:clear');
                    $message = 'ล้าง Config Cache สำเร็จ';
                    break;

                case 'route':
                    Artisan::call('route:clear');
                    $message = 'ล้าง Route Cache สำเร็จ';
                    break;

                case 'view':
                    Artisan::call('view:clear');
                    $message = 'ล้าง View Cache สำเร็จ';
                    break;

                case 'all':
                    $this->cacheService->clearAllCache();
                    Artisan::call('config:clear');
                    Artisan::call('route:clear');
                    Artisan::call('view:clear');
                    $message = 'ล้าง Cache ทั้งหมดสำเร็จ';
                    break;

                default:
                    $message = 'ล้าง Cache สำเร็จ';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }
}
