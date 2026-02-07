<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneTellingSetting;
use App\Models\PaymentBankAccount;
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

        // ดึงบัญชีธนาคารที่ active สำหรับให้เลือกใช้กับระบบดูดวง
        $bankAccounts = PaymentBankAccount::active()
            ->ordered()
            ->get();

        // ดึงรายชื่อธนาคารที่รองรับ
        $supportedBanks = PaymentBankAccount::supportedBanks();

        // เตรียมข้อมูลบัญชีธนาคารสำหรับ Alpine.js (JSON-safe)
        $selectedIds = $settings->fortune_bank_account_ids ?? [];
        $bankAccountsJson = $bankAccounts->map(function ($a) use ($selectedIds) {
            return [
                'id' => $a->id,
                'bank_code' => $a->bank_code,
                'bank_name' => $a->bank_name,
                'account_number' => $a->account_number,
                'account_name' => $a->account_name,
                'promptpay_id' => $a->promptpay_id,
                'sms_checker_enabled' => (bool) $a->sms_checker_enabled,
                'selected' => is_array($selectedIds) && in_array($a->id, $selectedIds),
            ];
        })->values()->toArray();

        return view('admin.fortune.settings.index', [
            'settings' => $settings,
            'bankAccounts' => $bankAccounts,
            'bankAccountsJson' => $bankAccountsJson,
            'supportedBanks' => $supportedBanks,
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
            'ai_provider' => 'required|in:gemini,groq,grok,qwen,openrouter,deepseek,typhoon',
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
            // บัญชีธนาคารเฉพาะระบบดูดวง
            'fortune_bank_account_ids' => 'nullable|array',
            'fortune_bank_account_ids.*' => 'exists:payment_bank_accounts,id',
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

        // จัดการ fortune_bank_account_ids (empty array → null)
        if (empty($validated['fortune_bank_account_ids'])) {
            $validated['fortune_bank_account_ids'] = null;
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
                : "ไม่พบ API Key สำหรับ {$aiProvider}",
            'fix' => empty($aiApiKey)
                ? 'ตั้งค่า API Key ในส่วน "การตั้งค่า AI Provider" ด้านล่าง หรือเปิดใช้ "ใช้การตั้งค่า AI จากระบบหลัก"'
                : null,
        ];

        // 2. ตรวจสอบ API Key Pool
        try {
            $poolService = new \App\Services\AiApiKeyPoolService();
            $poolKey = $poolService->getKey($aiProvider);
            $checks['api_pool'] = [
                'label' => 'API Key Pool',
                'status' => $poolKey ? 'ok' : 'warning',
                'message' => $poolKey
                    ? "พบ key ใน pool: {$poolKey->name} (ID: {$poolKey->id})"
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
                'fix' => !($testResult['success'] ?? false)
                    ? 'ตรวจสอบ API Key ว่าถูกต้องและยังไม่หมดอายุ แล้วกด "ตรวจเช็คทั้งหมด" อีกครั้ง'
                    : null,
            ];
        } catch (\Exception $e) {
            $checks['ai_connection'] = [
                'label' => 'การเชื่อมต่อ AI',
                'status' => 'error',
                'message' => 'เชื่อมต่อ AI ไม่ได้: ' . mb_substr($e->getMessage(), 0, 200),
                'fix' => 'ตรวจสอบ API Key และ Provider ว่าตั้งค่าถูกต้อง',
            ];
        }

        // 4. ตรวจสอบ Facebook Configuration
        $hasFacebook = $settings->hasFacebookConfigured();
        $fbMissing = [];
        if (empty($settings->facebook_app_id)) $fbMissing[] = 'App ID';
        if (empty($settings->facebook_app_secret)) $fbMissing[] = 'App Secret';
        if (empty($settings->facebook_page_id)) $fbMissing[] = 'Page ID';
        if (empty($settings->facebook_page_token)) $fbMissing[] = 'Page Token';
        if (empty($settings->facebook_verify_token)) $fbMissing[] = 'Verify Token';

        $checks['facebook'] = [
            'label' => 'Facebook Messenger',
            'status' => $hasFacebook ? 'ok' : 'error',
            'message' => $hasFacebook
                ? 'ตั้งค่า Facebook ครบถ้วน (Page ID: ***' . mb_substr($settings->facebook_page_id ?? '', -4) . ')'
                : 'ยังตั้งค่า Facebook ไม่ครบ - ขาด: ' . implode(', ', $fbMissing),
            'details' => [
                'has_app_id' => !empty($settings->facebook_app_id),
                'has_app_secret' => !empty($settings->facebook_app_secret),
                'has_page_id' => !empty($settings->facebook_page_id),
                'has_page_token' => !empty($settings->facebook_page_token),
                'has_verify_token' => !empty($settings->facebook_verify_token),
            ],
            'fix' => !$hasFacebook
                ? 'กรอกค่า ' . implode(', ', $fbMissing) . ' ในส่วน "การตั้งค่า Facebook" ด้านล่าง'
                : null,
        ];

        // 5. ตรวจสอบ Database Tables
        // แบ่งเป็นตารางหลัก (จำเป็น) และตารางเสริม (ไม่จำเป็นสำหรับระบบดูดวงหลัก)
        $requiredTables = ['fortune_readings', 'fortune_telling_settings'];
        $optionalTables = ['fortune_comment_engagements'];
        $allTables = array_merge($requiredTables, $optionalTables);

        $dbChecks = [];
        $missingRequired = [];
        $missingOptional = [];

        foreach ($allTables as $table) {
            try {
                $exists = \Schema::hasTable($table);
                $count = $exists ? \DB::table($table)->count() : 0;
                $dbChecks[$table] = ['exists' => $exists, 'count' => $count];

                if (!$exists) {
                    if (in_array($table, $requiredTables)) {
                        $missingRequired[] = $table;
                    } else {
                        $missingOptional[] = $table;
                    }
                }
            } catch (\Exception $e) {
                $dbChecks[$table] = ['exists' => false, 'error' => $e->getMessage()];
                if (in_array($table, $requiredTables)) {
                    $missingRequired[] = $table;
                } else {
                    $missingOptional[] = $table;
                }
            }
        }

        // กำหนดสถานะ: ตารางหลักหาย = error, ตารางเสริมหาย = warning
        $dbStatus = 'ok';
        $dbMessage = 'ตาราง DB ครบถ้วน';
        $dbFix = null;
        $hasPendingMigrations = false;

        if (!empty($missingRequired)) {
            $dbStatus = 'error';
            $dbMessage = 'ตารางหลักหาย: ' . implode(', ', $missingRequired);
            $dbFix = 'กดปุ่ม "แก้ไขฐานข้อมูล" ด้านล่างเพื่อรัน migration อัตโนมัติ';
            $hasPendingMigrations = true;
        } elseif (!empty($missingOptional)) {
            $dbStatus = 'warning';
            $dbMessage = 'ตารางเสริมหาย: ' . implode(', ', $missingOptional) . ' (ระบบดูดวงหลักยังใช้ได้)';
            $dbFix = 'กดปุ่ม "แก้ไขฐานข้อมูล" เพื่อสร้างตารางเสริมที่ขาดหายไป';
            $hasPendingMigrations = true;
        }

        $checks['database'] = [
            'label' => 'ฐานข้อมูล',
            'status' => $dbStatus,
            'message' => $dbMessage,
            'details' => $dbChecks,
            'fix' => $dbFix,
            'has_pending_migrations' => $hasPendingMigrations,
        ];

        // 6. ตรวจสอบสถานะบริการ (is_enabled)
        $isEnabled = $settings->isServiceEnabled();
        $checks['service_status'] = [
            'label' => 'สถานะบริการ',
            'status' => $isEnabled ? 'ok' : 'error',
            'message' => $isEnabled
                ? 'บริการเปิดอยู่ - บอทพร้อมตอบข้อความ'
                : '⚠️ บริการปิดอยู่! บอทจะไม่ตอบข้อความใดๆ',
            'fix' => !$isEnabled
                ? 'เปิดสวิตช์ "เปิดใช้งาน" ในการตั้งค่าด้านบน'
                : null,
        ];

        // 7. ตรวจสอบ Admin Handover
        $handoverEnabled = $settings->admin_handover_enabled ?? false;
        $handoverTimeout = $settings->admin_handover_timeout ?? 15;
        $checks['admin_handover'] = [
            'label' => 'Admin Handover',
            'status' => $handoverEnabled ? 'warning' : 'ok',
            'message' => $handoverEnabled
                ? "เปิดอยู่ (timeout: {$handoverTimeout} นาที) - ถ้าแอดมินตอบข้อความในเพจ บอทจะหยุดทำงาน {$handoverTimeout} นาที"
                : 'ปิดอยู่ - บอทตอบทุกข้อความ ไม่ถูกบล็อกจาก Admin',
            'details' => [
                'enabled' => $handoverEnabled,
                'timeout_minutes' => $handoverTimeout,
            ],
        ];

        // 8. ทดสอบส่งข้อความ Facebook จริง (ดูว่า token ใช้ได้ไหม)
        if ($hasFacebook) {
            try {
                $fbService = new \App\Services\FacebookWebhookService($settings);
                // ทดสอบ API ด้วย Messaging Feature Review (ไม่ส่งข้อความจริง)
                $testResponse = \Illuminate\Support\Facades\Http::timeout(15)
                    ->get("https://graph.facebook.com/v21.0/me", [
                        'fields' => 'id,name',
                        'access_token' => $settings->facebook_page_token,
                    ]);

                if ($testResponse->successful()) {
                    $pageData = $testResponse->json();
                    $checks['facebook_token'] = [
                        'label' => 'Facebook Token',
                        'status' => 'ok',
                        'message' => "Token ใช้ได้ - เพจ: {$pageData['name']} (ID: {$pageData['id']})",
                        'details' => [
                            'page_name' => $pageData['name'] ?? '',
                            'page_id' => $pageData['id'] ?? '',
                        ],
                    ];
                } else {
                    $errorBody = $testResponse->json();
                    $errorMsg = $errorBody['error']['message'] ?? $testResponse->body();
                    $errorCode = $errorBody['error']['code'] ?? $testResponse->status();
                    $checks['facebook_token'] = [
                        'label' => 'Facebook Token',
                        'status' => 'error',
                        'message' => "❌ Token ใช้ไม่ได้ (Error {$errorCode}): {$errorMsg}",
                        'fix' => 'สร้าง Page Access Token ใหม่จาก Facebook Developer Console แล้วอัพเดทในการตั้งค่า',
                    ];
                }
            } catch (\Exception $e) {
                $checks['facebook_token'] = [
                    'label' => 'Facebook Token',
                    'status' => 'error',
                    'message' => 'ทดสอบ Facebook Token ไม่ได้: ' . mb_substr($e->getMessage(), 0, 200),
                    'fix' => 'ตรวจสอบ Page Access Token และ internet connection',
                ];
            }
        }

        // 9. สรุปสถานะรวม
        $hasError = collect($checks)->contains(fn($c) => $c['status'] === 'error');
        $hasWarning = collect($checks)->contains(fn($c) => $c['status'] === 'warning');

        return response()->json([
            'overall' => $hasError ? 'error' : ($hasWarning ? 'warning' : 'ok'),
            'checks' => $checks,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * รัน migration ที่ยังค้างอยู่
     * สำหรับแก้ไขปัญหาตารางหายจากหน้า admin
     */
    public function runMigrations()
    {
        try {
            // รัน pending migrations
            \Artisan::call('migrate', ['--force' => true]);
            $output = \Artisan::output();

            // ตรวจสอบผลลัพธ์
            $hasChanges = !str_contains($output, 'Nothing to migrate');

            return response()->json([
                'success' => true,
                'message' => $hasChanges
                    ? 'รัน migration สำเร็จ!'
                    : 'ไม่มี migration ที่ต้องรัน (ฐานข้อมูลอัพเดทแล้ว)',
                'output' => trim($output),
                'has_changes' => $hasChanges,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'รัน migration ไม่สำเร็จ: ' . mb_substr($e->getMessage(), 0, 300),
            ], 500);
        }
    }

    /**
     * หน้า AI Playground สำหรับทดสอบสนทนากับ AI
     */
    public function playground()
    {
        $settings = FortuneTellingSetting::getSettings();

        return view('admin.fortune.playground.index', [
            'settings' => $settings,
            'pageTitle' => 'AI Playground - ทดสอบดูดวง',
        ]);
    }

    /**
     * API สำหรับส่งข้อความใน Playground
     */
    public function playgroundChat(Request $request)
    {
        $validated = $request->validate([
            'messages' => 'required|array|min:1',
            'messages.*.role' => 'required|in:user,assistant',
            'messages.*.content' => 'required|string|max:2000',
            'reading_type' => 'nullable|in:basic,deep',
        ]);

        $settings = FortuneTellingSetting::getSettings();

        try {
            $aiService = new FortuneAIService($settings);
            $result = $aiService->playgroundChat(
                $validated['messages'],
                $validated['reading_type'] ?? 'basic'
            );

            return response()->json([
                'success' => true,
                'response' => $result['response'],
                'debug' => [
                    'provider' => $result['provider'],
                    'model' => $result['model'],
                    'tokens_used' => $result['tokens_used'],
                    'response_time_ms' => $result['response_time_ms'] ?? 0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'debug' => [
                    'provider' => $settings->getActualAIProvider(),
                    'model' => $settings->getActualAIModel(),
                    'has_api_key' => !empty($settings->getActualAIApiKey()),
                ],
            ], 500);
        }
    }

    /**
     * Debug endpoint - ทดสอบ AI call แบบเดียวกับ webhook flow
     * ใช้ generateWithRetryAndFallback เหมือนที่ Facebook webhook เรียก
     */
    public function debugWebhookAI(Request $request)
    {
        $settings = FortuneTellingSetting::getSettings();
        $message = $request->input('message', 'ดูดวงความรัก');
        $debug = [
            'test_message' => $message,
            'provider' => $settings->getActualAIProvider(),
            'model' => $settings->getActualAIModel(),
            'has_api_key' => !empty($settings->getActualAIApiKey()),
            'use_global_ai_settings' => $settings->use_global_ai_settings,
        ];

        try {
            // จำลอง flow เดียวกับ FortuneConversationService::startNewConversation
            $aiService = new \App\Services\FortuneAIService($settings);

            // สร้าง prompt แบบเดียวกับ ConversationService
            $userProfile = ['name' => 'TestUser', 'id' => 'debug_test'];
            $basicPrompt = "คุณเป็นหมอดูหญิงชื่อดัง พูดจาอบอุ่นเป็นกันเอง ใช้คำแทนตัวว่า \"ทางเพจ\" ทำนายแบบฟันธงแต่อ่อนโยน ไม่เกิน 250 คำ\n\nข้อมูลผู้ขอดูดวง:\n- ชื่อ: TestUser\n\nข้อความที่ส่งมา: {$message}\n\nตอบอย่างเป็นมิตร ทำนายฟันธง ชัดเจน";

            $debug['prompt_length'] = mb_strlen($basicPrompt);

            $result = $aiService->generateWithRetryAndFallback(
                [$message],
                $userProfile,
                null,
                $basicPrompt,
                'basic'
            );

            $debug['success'] = true;
            $debug['response_preview'] = mb_substr($result['response'], 0, 200);
            $debug['result_provider'] = $result['provider'];
            $debug['result_model'] = $result['model'];
            $debug['tokens_used'] = $result['tokens_used'];

        } catch (\Exception $e) {
            $debug['success'] = false;
            $debug['error'] = $e->getMessage();
            $debug['error_class'] = get_class($e);
            $debug['trace'] = mb_substr($e->getTraceAsString(), 0, 2000);
        }

        return response()->json($debug, $debug['success'] ?? false ? 200 : 500);
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

    /**
     * สร้างบัญชีธนาคารใหม่สำหรับระบบดูดวง (AJAX)
     *
     * สร้างใน payment_bank_accounts แล้ว auto-select ใน fortune settings
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeBankAccount(Request $request)
    {
        $validated = $request->validate([
            'bank_code' => 'required|string|max:20',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:255',
            'promptpay_id' => 'nullable|string|max:50',
            'sms_checker_enabled' => 'boolean',
        ]);

        $validated['sms_checker_enabled'] = $request->boolean('sms_checker_enabled', true);
        $validated['is_active'] = true;
        $validated['sort_order'] = PaymentBankAccount::max('sort_order') + 1;

        // สร้างบัญชีใหม่
        $account = PaymentBankAccount::create($validated);

        // Auto-select ใน fortune settings
        $settings = FortuneTellingSetting::getSettings();
        $currentIds = $settings->fortune_bank_account_ids ?? [];
        $currentIds[] = $account->id;
        $settings->update(['fortune_bank_account_ids' => $currentIds]);

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มบัญชี ' . $account->bank_name . ' สำเร็จ',
            'account' => [
                'id' => $account->id,
                'bank_code' => $account->bank_code,
                'bank_name' => $account->bank_name,
                'account_number' => $account->account_number,
                'account_name' => $account->account_name,
                'promptpay_id' => $account->promptpay_id,
                'sms_checker_enabled' => $account->sms_checker_enabled,
            ],
        ]);
    }

    /**
     * แก้ไขบัญชีธนาคาร (AJAX)
     *
     * @param Request $request
     * @param PaymentBankAccount $account
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateBankAccount(Request $request, PaymentBankAccount $account)
    {
        $validated = $request->validate([
            'bank_code' => 'required|string|max:20',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:255',
            'promptpay_id' => 'nullable|string|max:50',
            'sms_checker_enabled' => 'boolean',
        ]);

        $validated['sms_checker_enabled'] = $request->boolean('sms_checker_enabled');

        $account->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทบัญชี ' . $account->bank_name . ' สำเร็จ',
            'account' => [
                'id' => $account->id,
                'bank_code' => $account->bank_code,
                'bank_name' => $account->bank_name,
                'account_number' => $account->account_number,
                'account_name' => $account->account_name,
                'promptpay_id' => $account->promptpay_id,
                'sms_checker_enabled' => $account->sms_checker_enabled,
            ],
        ]);
    }

    /**
     * ลบบัญชีธนาคาร (AJAX)
     *
     * ลบจาก payment_bank_accounts และ remove จาก fortune_bank_account_ids
     *
     * @param PaymentBankAccount $account
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteBankAccount(PaymentBankAccount $account)
    {
        $bankName = $account->bank_name;

        // Remove จาก fortune_bank_account_ids
        $settings = FortuneTellingSetting::getSettings();
        $currentIds = $settings->fortune_bank_account_ids ?? [];
        $currentIds = array_values(array_filter($currentIds, fn($id) => $id != $account->id));
        $settings->update([
            'fortune_bank_account_ids' => !empty($currentIds) ? $currentIds : null,
        ]);

        // Soft delete บัญชี
        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบบัญชี ' . $bankName . ' สำเร็จ',
        ]);
    }
}
