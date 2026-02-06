<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Fortune Settings Controller
 *
 * จัดการการตั้งค่าระบบดูดวง
 */
class FortuneSettingsController extends Controller
{
    /**
     * แสดงหน้าตั้งค่า
     */
    public function index()
    {
        $settings = FortuneTellingSetting::getSettings();

        return view('admin.fortune.settings.index', [
            'settings' => $settings,
            'pageTitle' => 'ตั้งค่าระบบดูดวง',
        ]);
    }

    /**
     * บันทึกการตั้งค่า
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            // การตั้งค่า Facebook
            'facebook_app_id' => 'nullable|string|max:100',
            'facebook_app_secret' => 'nullable|string|max:255',
            'facebook_page_id' => 'nullable|string|max:100',
            'facebook_page_token' => 'nullable|string',
            'facebook_verify_token' => 'nullable|string|max:100',
            // การตั้งค่า AI
            'ai_provider' => 'required|in:gemini,groq,qwen,openrouter',
            'ai_api_key' => 'nullable|string',
            'ai_model' => 'required|string|max:100',
            'basic_prompt_template' => 'nullable|string',
            'deep_prompt_template' => 'nullable|string',
            'use_global_ai_settings' => 'boolean',
            // การตั้งค่าพื้นฐาน
            'max_free_readings' => 'required|integer|min:0|max:100',
            'reading_price' => 'required|numeric|min:0',
            'is_enabled' => 'boolean',
            'respond_in_comment' => 'boolean',
            'require_registration' => 'boolean',
            // ระบบ Freemium: คำทำนายเชิงลึก
            'enable_deep_reading' => 'boolean',
            'deep_reading_price' => 'nullable|numeric|min:0',
            'allow_try_before_buy' => 'boolean',
            'free_deep_per_day' => 'nullable|integer|min:0|max:10',
            // ระบบสมัครสมาชิก
            'subscription_enabled' => 'boolean',
            'subscription_monthly_price' => 'nullable|numeric|min:0',
            'subscription_yearly_price' => 'nullable|numeric|min:0',
            'subscription_benefits' => 'nullable|string',
            // ข้อความอัตโนมัติ
            'welcome_message' => 'nullable|string',
            'limit_exceeded_message' => 'nullable|string',
            'payment_message' => 'nullable|string',
            'subscription_message' => 'nullable|string',
            'try_before_buy_message' => 'nullable|string',
            // QR Code
            'payment_qr_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            // Comment Engagement
            'comment_engagement_enabled' => 'boolean',
            'comment_engagement_mode' => 'nullable|in:ai,template',
            'comment_reply_template' => 'nullable|string|max:500',
            'comment_dm_template' => 'nullable|string|max:2000',
            'comment_engagement_prompt' => 'nullable|string|max:3000',
        ]);

        $settings = FortuneTellingSetting::getSettings();

        // จัดการ checkbox fields (ถ้าไม่ส่งมา = false)
        $checkboxFields = [
            'is_enabled', 'respond_in_comment', 'require_registration',
            'enable_deep_reading', 'allow_try_before_buy', 'subscription_enabled',
            'use_global_ai_settings', 'comment_engagement_enabled',
        ];
        foreach ($checkboxFields as $field) {
            if (!$request->has($field)) {
                $validated[$field] = false;
            }
        }

        // อัพโหลด QR Code ถ้ามี
        if ($request->hasFile('payment_qr_image')) {
            // ลบรูปเก่า
            if ($settings->payment_qr_image) {
                Storage::disk('public')->delete($settings->payment_qr_image);
            }

            // บันทึกรูปใหม่
            $path = $request->file('payment_qr_image')->store('fortune/qr-codes', 'public');
            $validated['payment_qr_image'] = $path;
        }

        $settings->update($validated);

        return redirect()
            ->route('admin.fortune.settings.index')
            ->with('success', 'บันทึกการตั้งค่าสำเร็จ');
    }

    /**
     * ทดสอบการเชื่อมต่อ AI
     */
    public function testAI(Request $request)
    {
        $settings = FortuneTellingSetting::getSettings();
        $aiService = new FortuneAIService($settings);

        $result = $aiService->testConnection();

        return response()->json($result);
    }

    /**
     * ตรวจสอบสถานะระบบทั้งหมด (AI + Facebook + DB)
     * แสดงผลแบบ JSON สำหรับ diagnostic panel
     */
    public function diagnose()
    {
        $settings = FortuneTellingSetting::getSettings();
        $checks = [];

        // 1. ตรวจสอบ AI Configuration
        $aiProvider = $settings->getActualAIProvider();
        $aiModel = $settings->getActualAIModel();
        $aiApiKey = $settings->getActualAIApiKey();
        $useGlobal = $settings->use_global_ai_settings ?? false;

        $checks['ai_config'] = [
            'label' => 'การตั้งค่า AI',
            'status' => !empty($aiApiKey) ? 'ok' : 'error',
            'details' => [
                'provider' => $aiProvider,
                'model' => $aiModel,
                'has_api_key' => !empty($aiApiKey),
                'api_key_prefix' => $aiApiKey ? mb_substr($aiApiKey, 0, 8) . '...' : 'ไม่มี',
                'use_global' => $useGlobal,
            ],
            'message' => !empty($aiApiKey)
                ? "ใช้ {$aiProvider} / {$aiModel}" . ($useGlobal ? ' (จากระบบหลัก)' : '')
                : "❌ ไม่พบ API Key สำหรับ {$aiProvider} - กรุณาตั้งค่า",
        ];

        // 2. ตรวจสอบ API Key Pool
        try {
            $poolService = new \App\Services\AiApiKeyPoolService();
            $poolKey = $poolService->getKey($aiProvider);
            $checks['api_pool'] = [
                'label' => 'API Key Pool',
                'status' => $poolKey ? 'ok' : 'warning',
                'message' => $poolKey
                    ? "พบ key ในpool: {$poolKey->name} (ID: {$poolKey->id})"
                    : 'ไม่มี key ใน pool - ใช้ key จาก settings',
            ];
        } catch (\Exception $e) {
            $checks['api_pool'] = [
                'label' => 'API Key Pool',
                'status' => 'warning',
                'message' => 'Pool ไม่พร้อม: ' . mb_substr($e->getMessage(), 0, 100),
            ];
        }

        // 3. ทดสอบเรียก AI จริง
        try {
            $aiService = new FortuneAIService($settings);
            $testResult = $aiService->testConnection();
            $checks['ai_connection'] = [
                'label' => 'การเชื่อมต่อ AI',
                'status' => $testResult['success'] ? 'ok' : 'error',
                'message' => $testResult['message'],
                'details' => $testResult['debug'] ?? [],
                'preview' => isset($testResult['preview']) ? mb_substr($testResult['preview'], 0, 200) : null,
            ];
        } catch (\Exception $e) {
            $checks['ai_connection'] = [
                'label' => 'การเชื่อมต่อ AI',
                'status' => 'error',
                'message' => 'เชื่อมต่อ AI ไม่ได้: ' . mb_substr($e->getMessage(), 0, 200),
            ];
        }

        // 4. ตรวจสอบ Facebook Configuration
        $hasFacebook = $settings->hasFacebookConfigured();
        $checks['facebook'] = [
            'label' => 'Facebook Messenger',
            'status' => $hasFacebook ? 'ok' : 'error',
            'message' => $hasFacebook
                ? 'ตั้งค่า Facebook ครบถ้วน (Page ID: ***' . mb_substr($settings->facebook_page_id ?? '', -4) . ')'
                : 'ยังตั้งค่า Facebook ไม่ครบ',
            'details' => [
                'has_app_id' => !empty($settings->facebook_app_id),
                'has_app_secret' => !empty($settings->facebook_app_secret),
                'has_page_id' => !empty($settings->facebook_page_id),
                'has_page_token' => !empty($settings->facebook_page_token),
                'has_verify_token' => !empty($settings->facebook_verify_token),
            ],
        ];

        // 5. ตรวจสอบ Database Tables
        $tables = ['fortune_readings', 'fortune_telling_settings', 'fortune_comment_engagements'];
        $dbChecks = [];
        foreach ($tables as $table) {
            try {
                $exists = \Schema::hasTable($table);
                $count = $exists ? \DB::table($table)->count() : 0;
                $dbChecks[$table] = ['exists' => $exists, 'count' => $count];
            } catch (\Exception $e) {
                $dbChecks[$table] = ['exists' => false, 'error' => $e->getMessage()];
            }
        }

        $allTablesOk = collect($dbChecks)->every(fn($t) => $t['exists'] ?? false);
        $checks['database'] = [
            'label' => 'ฐานข้อมูล',
            'status' => $allTablesOk ? 'ok' : 'error',
            'message' => $allTablesOk ? 'ตาราง DB ครบถ้วน' : 'มีตารางที่หายไป',
            'details' => $dbChecks,
        ];

        // 6. สรุปสถานะรวม
        $hasError = collect($checks)->contains(fn($c) => $c['status'] === 'error');
        $hasWarning = collect($checks)->contains(fn($c) => $c['status'] === 'warning');

        return response()->json([
            'overall' => $hasError ? 'error' : ($hasWarning ? 'warning' : 'ok'),
            'checks' => $checks,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Debug endpoint - ตรวจสอบสถานะระบบ comment engagement
     */
    public function debugEngagement()
    {
        $settings = FortuneTellingSetting::getSettings();

        $debug = [
            'comment_engagement_enabled' => $settings->comment_engagement_enabled,
            'comment_engagement_mode' => $settings->comment_engagement_mode ?? 'ai',
            'queue_connection' => config('queue.default'),
            'facebook_page_id' => $settings->facebook_page_id ? '***' . substr($settings->facebook_page_id, -4) : null,
        ];

        // ตรวจสอบตาราง fortune_comment_engagements
        try {
            $count = \DB::table('fortune_comment_engagements')->count();
            $debug['table_exists'] = true;
            $debug['engagements_count'] = $count;
        } catch (\Exception $e) {
            $debug['table_exists'] = false;
            $debug['table_error'] = $e->getMessage();
        }

        // ตรวจสอบ columns ในตาราง fortune_telling_settings
        try {
            $hasColumn = \Schema::hasColumn('fortune_telling_settings', 'comment_engagement_enabled');
            $debug['settings_column_exists'] = $hasColumn;
        } catch (\Exception $e) {
            $debug['settings_column_error'] = $e->getMessage();
        }

        // ดู failed jobs ล่าสุด
        try {
            $failedJobs = \DB::table('failed_jobs')
                ->where('payload', 'like', '%CommentEngagement%')
                ->orWhere('payload', 'like', '%FortuneTelling%')
                ->orderBy('failed_at', 'desc')
                ->limit(5)
                ->get(['id', 'queue', 'failed_at', 'exception']);

            $debug['failed_jobs'] = $failedJobs->map(function ($job) {
                return [
                    'id' => $job->id,
                    'queue' => $job->queue,
                    'failed_at' => $job->failed_at,
                    'exception' => substr($job->exception, 0, 300),
                ];
            });
        } catch (\Exception $e) {
            $debug['failed_jobs_error'] = $e->getMessage();
        }

        // ดู recent logs จาก webhook
        try {
            $logFile = storage_path('logs/laravel.log');
            if (file_exists($logFile)) {
                $lines = array_slice(file($logFile), -100);
                $webhookLogs = array_filter($lines, function ($line) {
                    return str_contains($line, 'Webhook') || str_contains($line, 'Engagement') || str_contains($line, 'fortune');
                });
                $debug['recent_webhook_logs'] = array_values(array_map('trim', array_slice($webhookLogs, -20)));
            }
        } catch (\Exception $e) {
            $debug['logs_error'] = $e->getMessage();
        }

        return response()->json($debug, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
