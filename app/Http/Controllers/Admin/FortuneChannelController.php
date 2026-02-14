<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use App\Services\LineFortuneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Fortune Channel Controller
 *
 * จัดการการตั้งค่าช่องทางรับส่งข้อความสำหรับระบบดูดวง
 * รองรับ: Facebook Messenger, LINE Official Account
 */
class FortuneChannelController extends Controller
{
    /**
     * แสดงหน้าจัดการช่องทาง
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $settings = FortuneTellingSetting::getSettings();

        // สถิติการใช้งานตามช่องทาง
        $stats = $this->getChannelStats();

        return view('admin.fortune.channels.index', [
            'settings' => $settings,
            'stats' => $stats,
            'pageTitle' => 'จัดการช่องทาง',
        ]);
    }

    /**
     * บันทึกการตั้งค่าช่องทาง
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            // Enabled platforms
            'enabled_platforms' => 'nullable|array',
            'enabled_platforms.*' => 'in:facebook,line',

            // LINE Settings
            'line_enabled' => 'boolean',
            'line_channel_id' => 'nullable|string|max:100',
            'line_channel_secret' => 'nullable|string|max:255',
            'line_channel_access_token' => 'nullable|string',
            'line_flex_primary_color' => 'nullable|string|max:7',
            'line_welcome_image_url' => 'nullable|url|max:500',
        ]);

        $settings = FortuneTellingSetting::getSettings();

        // จัดการ checkbox (ถ้าไม่ส่งมา = false)
        if (! $request->has('line_enabled')) {
            $validated['line_enabled'] = false;
        }

        // จัดการ enabled_platforms
        if (! $request->has('enabled_platforms')) {
            $validated['enabled_platforms'] = [];
        }

        // เช็คว่าถ้าเปิด line_enabled ต้องใส่ค่าที่จำเป็น
        if (! empty($validated['line_enabled'])) {
            if (empty($validated['line_channel_id']) ||
                empty($validated['line_channel_secret']) ||
                empty($validated['line_channel_access_token'])) {
                return redirect()
                    ->route('admin.fortune.channels.index')
                    ->with('error', 'กรุณากรอกข้อมูล LINE Channel ให้ครบถ้วนก่อนเปิดใช้งาน');
            }
        }

        $settings->update($validated);

        Log::info('Fortune channels settings updated', [
            'enabled_platforms' => $validated['enabled_platforms'] ?? [],
            'line_enabled' => $validated['line_enabled'] ?? false,
        ]);

        return redirect()
            ->route('admin.fortune.channels.index')
            ->with('success', 'บันทึกการตั้งค่าช่องทางสำเร็จ');
    }

    /**
     * ทดสอบการเชื่อมต่อ LINE
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function testLine()
    {
        try {
            $settings = FortuneTellingSetting::getSettings();

            if (! $settings->hasLineConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => 'กรุณาตั้งค่า LINE Channel ก่อนทดสอบ',
                ]);
            }

            $lineService = new LineFortuneService($settings);
            $result = $lineService->testConnection();

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result['data'] ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('LINE connection test failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * ดึงสถิติการใช้งานตามช่องทาง
     */
    protected function getChannelStats(): array
    {
        // สถิติ Facebook
        $facebookStats = FortuneReading::where('platform', 'facebook')
            ->selectRaw('
                COUNT(*) as total,
                COUNT(CASE WHEN reading_type = "deep" THEN 1 END) as deep_count,
                COUNT(CASE WHEN is_paid = 1 THEN 1 END) as paid_count,
                SUM(CASE WHEN is_paid = 1 THEN amount_paid ELSE 0 END) as total_revenue,
                COUNT(DISTINCT platform_user_id) as unique_users
            ')
            ->first();

        // สถิติ LINE
        $lineStats = FortuneReading::where('platform', 'line')
            ->selectRaw('
                COUNT(*) as total,
                COUNT(CASE WHEN reading_type = "deep" THEN 1 END) as deep_count,
                COUNT(CASE WHEN is_paid = 1 THEN 1 END) as paid_count,
                SUM(CASE WHEN is_paid = 1 THEN amount_paid ELSE 0 END) as total_revenue,
                COUNT(DISTINCT platform_user_id) as unique_users
            ')
            ->first();

        // สถิติ 7 วันล่าสุด
        $last7DaysStats = FortuneReading::where('created_at', '>=', now()->subDays(7))
            ->selectRaw('
                platform,
                DATE(created_at) as date,
                COUNT(*) as count
            ')
            ->groupBy('platform', 'date')
            ->orderBy('date')
            ->get()
            ->groupBy('platform');

        return [
            'facebook' => [
                'total' => (int) ($facebookStats->total ?? 0),
                'deep_count' => (int) ($facebookStats->deep_count ?? 0),
                'paid_count' => (int) ($facebookStats->paid_count ?? 0),
                'total_revenue' => (float) ($facebookStats->total_revenue ?? 0),
                'unique_users' => (int) ($facebookStats->unique_users ?? 0),
            ],
            'line' => [
                'total' => (int) ($lineStats->total ?? 0),
                'deep_count' => (int) ($lineStats->deep_count ?? 0),
                'paid_count' => (int) ($lineStats->paid_count ?? 0),
                'total_revenue' => (float) ($lineStats->total_revenue ?? 0),
                'unique_users' => (int) ($lineStats->unique_users ?? 0),
            ],
            'daily' => $last7DaysStats,
        ];
    }

    /**
     * ดึงสถิติ API สำหรับ AJAX
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statsApi()
    {
        $stats = $this->getChannelStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * ตั้งค่า Facebook Messenger Profile (Get Started button และ Persistent Menu)
     *
     * ตั้งค่าปุ่ม "เริ่มต้นใช้งาน" และเมนูถาวรใน Messenger
     * ต้องเรียกครั้งเดียวหลังจากตั้งค่า Facebook Page เสร็จ
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setupFacebookMessenger()
    {
        try {
            $settings = FortuneTellingSetting::getSettings();

            if (empty($settings->facebook_page_access_token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'กรุณาตั้งค่า Facebook Page Access Token ก่อนทดสอบ',
                ]);
            }

            $facebookService = new FacebookWebhookService($settings);
            $result = $facebookService->setupMessengerProfile();

            Log::info('Facebook Messenger Profile setup result', $result);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Facebook Messenger Profile setup failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * ดึงค่า Facebook Messenger Profile ปัจจุบัน
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFacebookMessengerProfile()
    {
        try {
            $settings = FortuneTellingSetting::getSettings();

            if (empty($settings->facebook_page_access_token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'กรุณาตั้งค่า Facebook Page Access Token ก่อน',
                ]);
            }

            $facebookService = new FacebookWebhookService($settings);
            $result = $facebookService->getMessengerProfile();

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * ทดสอบการเชื่อมต่อ Facebook
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function testFacebook()
    {
        try {
            $settings = FortuneTellingSetting::getSettings();

            if (empty($settings->facebook_page_access_token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'กรุณาตั้งค่า Facebook Page Access Token ก่อน',
                ]);
            }

            $facebookService = new FacebookWebhookService($settings);

            // ทดสอบด้วยการดึง Page info
            $result = $facebookService->getMessengerProfile();

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'เชื่อมต่อ Facebook สำเร็จ!',
                    'data' => $result['data'] ?? null,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'เชื่อมต่อไม่สำเร็จ',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ]);
        }
    }
}
