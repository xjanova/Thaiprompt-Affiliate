<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiGenProvider;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\AiGen\CloudflareAiProvider;
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

        // Cloudflare AI provider (สำหรับ section เจนภาพดวงประจำวัน)
        $cloudflareAi = $this->getCloudflareAiStatus();

        return view('admin.fortune.channels.index', [
            'settings' => $settings,
            'stats' => $stats,
            'cloudflareAi' => $cloudflareAi,
            'pageTitle' => 'จัดการช่องทาง',
        ]);
    }

    /**
     * ดึงสถานะการตั้งค่า Cloudflare Workers AI (ใช้กับเจนภาพดวงประจำวัน)
     *
     * Source ของค่า: AiGenProvider config (DB) → fallback ไป config('services.cloudflare.*')
     */
    protected function getCloudflareAiStatus(): array
    {
        $provider = AiGenProvider::where('slug', 'cloudflare-ai')->first();

        if (! $provider) {
            return [
                'available' => false,
                'configured' => false,
                'has_db_config' => false,
                'using_env_fallback' => false,
                'masked_token' => '',
                'account_id' => '',
                'reason' => 'AiGenProvider slug=cloudflare-ai ยังไม่ถูก seed (รัน php artisan db:seed --class=AiGenSeeder)',
            ];
        }

        $dbToken = $provider->getConfig('api_key');
        $dbAccount = $provider->getConfig('account_id');
        $envToken = config('services.cloudflare.api_token');
        $envAccount = config('services.cloudflare.account_id');

        // decrypt DB token (ถูก encrypt ตอน setConfig(api_key, ..., true))
        $dbTokenDecrypted = '';
        if (! empty($dbToken)) {
            try {
                $dbTokenDecrypted = decrypt($dbToken);
            } catch (\Throwable $e) {
                $dbTokenDecrypted = '';
            }
        }

        $effectiveToken = $dbTokenDecrypted ?: $envToken;
        $effectiveAccount = $dbAccount ?: $envAccount;
        $usingEnv = empty($dbTokenDecrypted) && ! empty($envToken);

        return [
            'available' => true,
            'configured' => ! empty($effectiveToken) && ! empty($effectiveAccount),
            'has_db_config' => ! empty($dbTokenDecrypted) && ! empty($dbAccount),
            'using_env_fallback' => $usingEnv,
            'masked_token' => $this->maskToken($effectiveToken),
            'account_id' => $effectiveAccount ?? '',
        ];
    }

    /**
     * ปกปิด token (แสดงแค่ 4 ตัวแรก + 4 ตัวสุดท้าย)
     */
    protected function maskToken(?string $token): string
    {
        if (empty($token)) {
            return '';
        }
        $len = strlen($token);
        if ($len <= 8) {
            return str_repeat('•', $len);
        }

        return substr($token, 0, 4).str_repeat('•', max(4, $len - 8)).substr($token, -4);
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
            'line_bot_basic_id' => 'nullable|string|max:100',
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

            // ใช้ field ตามที่ FacebookWebhookService ใช้: facebook_page_token
            $token = $settings->facebook_page_token ?? $settings->facebook_page_access_token ?? null;
            if (empty($token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'กรุณาตั้งค่า Facebook Page Token (facebook_page_token) ก่อนทดสอบ',
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

            $token = $settings->facebook_page_token ?? $settings->facebook_page_access_token ?? null;
            if (empty($token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'กรุณาตั้งค่า Facebook Page Token (facebook_page_token) ก่อน',
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

            $token = $settings->facebook_page_token ?? $settings->facebook_page_access_token ?? null;
            if (empty($token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'กรุณาตั้งค่า Facebook Page Token (facebook_page_token) ก่อน',
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

    /**
     * บันทึกการตั้งค่า Cloudflare Workers AI (ใช้กับเจนภาพดวงประจำวัน)
     *
     * รับ API Token + Account ID จากฟอร์มแอดมิน → เก็บใน AiGenProvider config
     * - api_key: encrypted (true)
     * - account_id: plain (false) — สำหรับ display ใน admin
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateCloudflareAi(Request $request)
    {
        $validated = $request->validate([
            'cloudflare_api_token' => 'nullable|string|max:255',
            'cloudflare_account_id' => 'nullable|string|max:64',
        ]);

        $provider = AiGenProvider::where('slug', 'cloudflare-ai')->first();
        if (! $provider) {
            return redirect()
                ->route('admin.fortune.channels.index')
                ->with('error', 'ไม่พบ Cloudflare AI provider — กรุณารัน php artisan db:seed --class=AiGenSeeder ก่อน');
        }

        $token = trim($validated['cloudflare_api_token'] ?? '');
        $accountId = trim($validated['cloudflare_account_id'] ?? '');

        // ถ้า user ส่งค่าใหม่ — บันทึก (ถ้าค่าว่าง ตีความเป็น "ไม่เปลี่ยน")
        if ($token !== '') {
            $provider->setConfig('api_key', $token, true);  // encrypt
        }
        if ($accountId !== '') {
            $provider->setConfig('account_id', $accountId, false);
        }

        Log::info('Cloudflare AI credentials updated', [
            'has_token' => $token !== '',
            'has_account_id' => $accountId !== '',
        ]);

        return redirect()
            ->route('admin.fortune.channels.index')
            ->with('success', 'บันทึก Cloudflare AI สำเร็จ');
    }

    /**
     * ทดสอบการเชื่อมต่อ Cloudflare Workers AI
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function testCloudflareAi()
    {
        try {
            $provider = AiGenProvider::where('slug', 'cloudflare-ai')->first();
            if (! $provider) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบ Cloudflare AI provider — รัน AiGenSeeder ก่อน',
                ]);
            }

            $cf = new CloudflareAiProvider($provider);

            if (! $cf->isConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ยังไม่ได้ตั้งค่า API Token หรือ Account ID — กรอกค่าก่อนทดสอบ',
                ]);
            }

            $result = $cf->testConnection();

            return response()->json([
                'success' => $result['success'] ?? false,
                'message' => $result['message'] ?? 'ทดสอบเรียบร้อย',
            ]);
        } catch (\Exception $e) {
            Log::error('Cloudflare AI test failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * แสดงหน้าจัดการ Facebook Page Integration
     *
     * หน้าสาธิตสำหรับ Facebook App Review
     * แสดงฟีเจอร์: pages_show_list, pages_manage_metadata,
     * pages_messaging, pages_read_engagement, business_management
     *
     * @return \Illuminate\View\View
     */
    public function facebookPageManagement()
    {
        $settings = FortuneTellingSetting::getSettings();

        // ดึงสถิติ Facebook
        $fbStats = [
            'total_readings' => FortuneReading::where('platform', 'facebook')->count(),
            'total_deep' => FortuneReading::where('platform', 'facebook')->where('is_deep_reading', true)->count(),
            'unique_users' => FortuneReading::where('platform', 'facebook')->distinct('platform_user_id')->count('platform_user_id'),
            'total_revenue' => FortuneReading::where('platform', 'facebook')->where('is_paid', true)->sum('amount_paid'),
            'recent_readings' => FortuneReading::where('platform', 'facebook')
                ->latest()
                ->take(5)
                ->get(),
        ];

        // ดึง comment engagement stats ถ้ามี
        $commentStats = [];
        if (class_exists(\App\Models\FortuneCommentEngagement::class)) {
            $commentStats = [
                'total' => \App\Models\FortuneCommentEngagement::count(),
                'replied' => \App\Models\FortuneCommentEngagement::where('status', 'replied')->count(),
                'dm_sent' => \App\Models\FortuneCommentEngagement::where('dm_sent', true)->count(),
            ];
        }

        return view('admin.fortune.channels.facebook-page-management', [
            'settings' => $settings,
            'fbStats' => $fbStats,
            'commentStats' => $commentStats,
            'pageTitle' => 'จัดการ Facebook Page',
        ]);
    }
}
