<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiApiKey;
use App\Models\AiContentSetting;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\PaymentBankAccount;
use App\Services\FortuneAIService;
use App\Services\FortuneConversationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Fortune Settings Controller
 *
 * จัดการการตั้งค่าระบบดูดวง + Dashboard
 */
class FortuneSettingsController extends Controller
{
    /**
     * แสดง Dashboard ภาพรวมระบบดูดวง
     */
    public function dashboard()
    {
        $today = Carbon::today();
        $weekAgo = Carbon::now()->subDays(7);
        $monthStart = Carbon::now()->startOfMonth();

        // สถิติภาพรวม
        $stats = [
            'total' => FortuneReading::count(),
            'today' => FortuneReading::whereDate('created_at', $today)->count(),
            'this_week' => FortuneReading::where('created_at', '>=', $weekAgo)->count(),
            'this_month' => FortuneReading::where('created_at', '>=', $monthStart)->count(),
            'basic_count' => FortuneReading::where('reading_type', 'basic')->count(),
            'deep_count' => FortuneReading::where('reading_type', 'deep')->count(),
            'paid_count' => FortuneReading::paid()->count(),
            'free_count' => FortuneReading::free()->count(),
            'total_revenue' => FortuneReading::paid()->sum('amount_paid'),
            'today_revenue' => FortuneReading::paid()->whereDate('created_at', $today)->sum('amount_paid'),
            'week_revenue' => FortuneReading::paid()->where('created_at', '>=', $weekAgo)->sum('amount_paid'),
            'month_revenue' => FortuneReading::paid()->where('created_at', '>=', $monthStart)->sum('amount_paid'),
            'unique_users' => FortuneReading::distinct('facebook_user_id')->count('facebook_user_id'),
            'avg_tokens' => (int) FortuneReading::whereNotNull('tokens_used')->avg('tokens_used'),
            'conversion_rate' => FortuneReading::count() > 0
                ? round(FortuneReading::where('reading_type', 'deep')->count() / FortuneReading::count() * 100, 1)
                : 0,
        ];

        // กราฟ 7 วัน
        $dailyData = FortuneReading::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(CASE WHEN is_paid = 1 THEN amount_paid ELSE 0 END) as revenue'),
            DB::raw('SUM(CASE WHEN reading_type = "basic" THEN 1 ELSE 0 END) as basic_count'),
            DB::raw('SUM(CASE WHEN reading_type = "deep" THEN 1 ELSE 0 END) as deep_count')
        )
            ->where('created_at', '>=', $weekAgo)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // เตรียมข้อมูลกราฟ
        $chartLabels = [];
        $chartReadings = [];
        $chartRevenue = [];
        $chartBasic = [];
        $chartDeep = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dayLabel = Carbon::now()->subDays($i)->locale('th')->isoFormat('D MMM');
            $chartLabels[] = $dayLabel;

            $dayData = $dailyData->firstWhere('date', $date);
            $chartReadings[] = $dayData->count ?? 0;
            $chartRevenue[] = (float) ($dayData->revenue ?? 0);
            $chartBasic[] = $dayData->basic_count ?? 0;
            $chartDeep[] = $dayData->deep_count ?? 0;
        }

        // Top 5 ผู้ใช้บ่อย
        $topUsers = FortuneReading::select('facebook_user_id', 'facebook_user_name',
            DB::raw('COUNT(*) as reading_count'),
            DB::raw('MAX(created_at) as last_reading'))
            ->groupBy('facebook_user_id', 'facebook_user_name')
            ->orderByDesc('reading_count')
            ->limit(5)
            ->get();

        // 10 รายการล่าสุด
        $recentReadings = FortuneReading::orderByDesc('created_at')
            ->limit(10)
            ->get();

        // สถิติตามหมวดหมู่
        $categoryStats = FortuneReading::whereNotNull('categories')
            ->where('categories', '!=', '[]')
            ->get()
            ->pluck('categories')
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(6);

        // สถานะ AI
        $settings = FortuneTellingSetting::getSettings();
        $aiStatus = [
            'provider' => $settings->getActualAIProvider(),
            'model' => $settings->getActualAIModel(),
            'has_key' => ! empty($settings->getActualAIApiKey()),
            'enabled' => $settings->is_enabled,
        ];

        return view('admin.fortune.dashboard', compact(
            'stats', 'chartLabels', 'chartReadings', 'chartRevenue',
            'chartBasic', 'chartDeep', 'topUsers', 'recentReadings',
            'categoryStats', 'aiStatus'
        ));
    }

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

        // 🎙️ (2026-05-08) Google Cloud TTS — ตรวจ credentials ที่มีอยู่
        //    ระบบใช้ Google Translate / Vision / Speech-to-Text อยู่แล้ว →
        //    key/Service Account เดียวกันใช้ TTS ได้เลย ถ้าเปิด API ใน project
        $googleTtsDiag = \App\Services\Tts\GoogleCloudTtsProvider::diagnoseCredentials();

        // 🌟 (2026-05-08) ดึง keys purpose='sensitive' ทั้งหมดสำหรับ admin dropdown
        //    + flag ว่ามี key พร้อมใช้หรือไม่ (block toggle ถ้าไม่มี)
        $sensitiveKeys = $settings->getAvailableSensitiveKeys()
            ->map(function ($k) {
                return [
                    'id' => $k->id,
                    'name' => $k->name,
                    'provider' => $k->provider,
                    'model' => $k->resolveModel(),
                    'is_critical' => (bool) ($k->is_critical ?? false),
                    'is_disabled' => $k->disabled_until && $k->disabled_until > now(),
                    'masked_key' => $k->masked_key,
                    'tokens_used_today' => (int) $k->tokens_used_today,
                    'consecutive_errors' => (int) ($k->consecutive_errors ?? 0),
                ];
            })
            ->values()
            ->toArray();

        return view('admin.fortune.settings.index', [
            'settings' => $settings,
            'bankAccounts' => $bankAccounts,
            'bankAccountsJson' => $bankAccountsJson,
            'supportedBanks' => $supportedBanks,
            'defaultBasicPrompt' => FortuneConversationService::getDefaultBasicPrompt(),
            'defaultDeepPrompt' => FortuneConversationService::getDefaultDeepPrompt(),
            'googleTtsDiag' => $googleTtsDiag,
            'sensitiveKeys' => $sensitiveKeys,
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
            // 🎯 (2026-05-13) Pool-first architecture — UI ในหน้านี้ไม่มี ai_provider/ai_model field
            //   ขณะที่ DB column ยังอยู่ (legacy backward compat สำหรับบิลเก่า)
            //   → เปลี่ยน required → nullable เพื่อให้ submit form ใหม่ (ไม่มี field) ผ่าน validation
            'ai_provider' => 'nullable|in:gemini,groq,grok,qwen,openrouter,deepseek,typhoon,openai,anthropic',
            'ai_api_key' => 'nullable|string',
            'ai_model' => 'nullable|string|max:100',
            'prediction_strict_provider' => 'boolean',  // 🎯 (2026-05-02) strict mode
            'basic_prompt_template' => 'nullable|string',
            'deep_prompt_template' => 'nullable|string',
            'use_global_ai_settings' => 'boolean',
            // การตั้งค่าพื้นฐาน
            // ⚠️ (2026-05-03) max_free_readings deprecated — แทนด้วย enable_free_card_reading
            //   เก็บ validation ไว้เผื่อ backward compat (admin ส่งมาจาก form เก่า)
            'max_free_readings' => 'nullable|integer|min:0|max:100',
            'reading_price' => 'required|numeric|min:0',
            // 🎁 (2026-05-03) ระบบทำนายฟรี 1 ใบ ครั้งแรก/ลูกค้า
            'enable_free_card_reading' => 'boolean',
            'free_card_news_context' => 'nullable|string|max:1000',
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
            // โหมดแสดงช่องทางชำระเงิน (โอนเงิน / พร้อมเพย์ / ทั้งสองอย่าง)
            'payment_display_mode' => 'nullable|in:both,bank_only,promptpay_only',
            // 🔍 (2026-05-16) Fuzzy Payment Match — รับโอนยอดไม่ตรง (39.00 แทน 39.37)
            'enable_fuzzy_payment_match' => 'boolean',
            'fuzzy_overpay_max_baht' => 'nullable|numeric|min:0|max:500',
            'fuzzy_underpay_max_baht' => 'nullable|numeric|min:0|max:100',
            'fuzzy_window_minutes' => 'nullable|integer|min:5|max:1440',
            'fuzzy_name_auto_threshold' => 'nullable|integer|min:0|max:100',
            'fuzzy_admin_alert_above_baht' => 'nullable|numeric|min:0|max:1000',
            // Affiliate/MLM Settings สำหรับดูดวง
            'fortune_affiliate_enabled' => 'boolean',
            'fortune_auto_register_enabled' => 'boolean',
            'fortune_pv_value' => 'nullable|numeric|min:0',
            'fortune_use_global_commission_rate' => 'boolean',
            'fortune_custom_commission_per_pv' => 'nullable|numeric|min:0',
            'fortune_affiliate_invite_message' => 'nullable|string|max:2000',
            // โหมดจ่ายคอมมิชชั่น: pv หรือ static
            'fortune_commission_mode' => 'nullable|in:pv,static',
            'fortune_static_commission_amount' => 'nullable|numeric|min:0',
            // Level 1/Level 2 commission settings
            'fortune_level1_commission_type' => 'nullable|in:fixed,percent',
            'fortune_level1_commission_amount' => 'nullable|numeric|min:0',
            'fortune_level2_enabled' => 'boolean',
            'fortune_level2_commission_type' => 'nullable|in:fixed,percent',
            'fortune_level2_commission_amount' => 'nullable|numeric|min:0',
            // กระเป๋ากลาง (รับค่าแนะนำเมื่อหา sponsor ไม่ได้)
            'fortune_central_user_id' => 'nullable|integer|exists:users,id',
            'fortune_central_fallback_enabled' => 'boolean',
            // AI Chat ทั่วไป (สนทนาอัจฉริยะ)
            'enable_ai_chat' => 'boolean',
            'chat_ai_provider' => 'nullable|in:gemini,groq,grok,qwen,openrouter,deepseek,typhoon',
            'chat_ai_model' => 'nullable|string|max:100',
            'chat_ai_api_key' => 'nullable|string',
            'chat_system_prompt' => 'nullable|string|max:5000',
            // Admin Takeover (เทคโอเวอร์ — LINE + Facebook รวมกัน)
            'admin_handover_enabled' => 'boolean',
            'admin_handover_timeout' => 'nullable|integer|min:1|max:1440',
            'ai_resume_command' => 'nullable|string|max:50',
            'customer_handoff_keywords' => 'nullable|string',
            'takeover_notify_customer' => 'boolean',
            'takeover_customer_message' => 'nullable|string|max:500',
            'takeover_resume_message' => 'nullable|string|max:500',
            // 🌟 (2026-05-04) Group Invite + Monthly Free Claim
            'fortune_group_url' => 'nullable|url|max:500',
            'fortune_group_invite_enabled' => 'boolean',
            'fortune_group_invite_message' => 'nullable|string|max:1000',
            'monthly_free_claim_enabled' => 'boolean',
            'monthly_free_claim_secret' => 'nullable|string|max:128',
            'monthly_free_claim_success_message' => 'nullable|string|max:1000',
            'monthly_free_claim_already_message' => 'nullable|string|max:1000',
            // 🌟 (2026-05-07) Sensitive AI Mode — สลับ Pro model อัตโนมัติเมื่อบริบทละเอียดอ่อน
            'sensitive_ai_mode' => 'nullable|in:off,paid_only,all',
            'sensitive_detection_mode' => 'nullable|in:heuristic,hybrid,hybrid_with_brain',
            'sensitive_provider' => 'nullable|in:gemini,openai,anthropic,openrouter,grok,groq,qwen,deepseek,typhoon,xiaomi',
            'sensitive_model' => 'nullable|string|max:100',
            // 🌟 (2026-05-08) Lock specific pool key — ต้องมี key purpose='sensitive' จริงเท่านั้น
            'sensitive_ai_pool_key_id' => 'nullable|integer|exists:ai_api_keys,id',
            'sensitive_classifier_provider' => 'nullable|in:gemini,openai,groq',
            'sensitive_classifier_model' => 'nullable|string|max:100',
            'sensitive_keywords_text' => 'nullable|string|max:5000',  // textarea — 1 keyword/line
            'sensitive_topics_text' => 'nullable|string|max:5000',     // textarea — 1 topic/line
            'sensitive_max_per_user_daily' => 'nullable|integer|min:0|max:100',
            'sensitive_max_total_daily_thb' => 'nullable|numeric|min:0|max:10000',
            'sensitive_max_tokens_per_call' => 'nullable|integer|min:200|max:8000',
            'sensitive_offtopic_strikes' => 'nullable|integer|min:1|max:20',
            'sensitive_offtopic_action' => 'nullable|in:revert,block,handoff',
            'sensitive_offtopic_block_message' => 'nullable|string|max:1000',
            'sensitive_log_enabled' => 'boolean',
            // 💳 (2026-05-07 Phase 2) Bill Psychology
            'bill_psychology_enabled' => 'boolean',
            'bill_charity_message' => 'nullable|string|max:2000',
            'bill_max_mentions_per_session' => 'nullable|integer|min:1|max:10',
            'bill_psychology_window_hours' => 'nullable|integer|min:1|max:168',
            'bill_alternative_payment_methods' => 'nullable|string|max:3000',
            // 🌙 Celtic Premium Chat
            'celtic_premium_chat_enabled' => 'boolean',
            'celtic_premium_chat_trigger' => 'nullable|in:after_questions_done,always_after_q1',
            'celtic_premium_chat_warn_minutes_left' => 'nullable|integer|min:1|max:30',
            'celtic_premium_chat_max_messages' => 'nullable|integer|min:0|max:200',
            'celtic_premium_chat_prompt_override' => 'nullable|string|max:8000',
            // 🙏 Satisfaction Detector
            'satisfaction_detection_enabled' => 'boolean',
            'satisfaction_close_message' => 'nullable|string|max:1000',
            // 🎙️ (2026-05-08) Voice Summary (TTS)
            'voice_summary_enabled' => 'boolean',
            'voice_summary_tier_scope' => 'nullable|in:celtic_99_only,paid_all,all',
            'voice_summary_primary_provider' => 'nullable|in:minimax,openai_tts,google_tts,gtts',
            'voice_summary_fallback_providers' => 'nullable|array',
            'voice_summary_fallback_providers.*' => 'in:minimax,openai_tts,google_tts,gtts',
            'minimax_api_key' => 'nullable|string|max:5000',
            'minimax_group_id' => 'nullable|string|max:100',
            'minimax_model' => 'nullable|string|max:50',
            'minimax_voice_id' => 'nullable|string|max:100',
            'openai_tts_model' => 'nullable|string|max:50',
            'openai_tts_voice' => 'nullable|in:alloy,echo,fable,onyx,nova,shimmer',
            'google_tts_voice' => 'nullable|string|max:100',
            'google_tts_speaking_rate' => 'nullable|numeric|min:0.25|max:4.0',
            'voice_summary_max_chars' => 'nullable|integer|min:200|max:5000',
            'voice_summary_prompt' => 'nullable|string|max:5000',
            'voice_summary_intro_message' => 'nullable|string|max:500',
            // 💳 (2026-05-09) Stripe payment settings
            'enable_stripe_payment' => 'boolean',
            'stripe_service_fee' => 'nullable|numeric|min:0|max:1000',
            'stripe_session_expiry_minutes' => 'nullable|integer|min:30|max:1440',
            'stripe_test_mode' => 'boolean',
            'stripe_account_id' => 'nullable|string|max:64',
            'stripe_secret_key' => 'nullable|string|max:255',
            'stripe_publishable_key' => 'nullable|string|max:255',
            'stripe_webhook_secret' => 'nullable|string|max:255',
            'stripe_product_deep_id' => 'nullable|string|max:64',
            'stripe_product_celtic_id' => 'nullable|string|max:64',
            // 🛡️ (2026-05-10) Link Moderation
            'auto_hide_link_comments' => 'boolean',
            'link_comment_action' => 'nullable|in:hide,delete',
            'link_whitelist_domains_text' => 'nullable|string|max:5000',
            'link_moderation_log_only' => 'boolean',
        ]);

        // 🛡️ แปลง textarea → array สำหรับ link_whitelist_domains
        if (array_key_exists('link_whitelist_domains_text', $validated)) {
            $raw = (string) $validated['link_whitelist_domains_text'];
            $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', $raw)));
            $validated['link_whitelist_domains'] = ! empty($lines) ? array_values($lines) : null;
            unset($validated['link_whitelist_domains_text']);
        }

        // 🌟 (2026-05-07) แปลง textarea → array สำหรับ keywords/topics
        if (array_key_exists('sensitive_keywords_text', $validated)) {
            $raw = (string) $validated['sensitive_keywords_text'];
            $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', $raw)));
            $validated['sensitive_keywords'] = ! empty($lines) ? array_values($lines) : null;
            unset($validated['sensitive_keywords_text']);
        }
        if (array_key_exists('sensitive_topics_text', $validated)) {
            $raw = (string) $validated['sensitive_topics_text'];
            $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', $raw)));
            $validated['sensitive_topics'] = ! empty($lines) ? array_values($lines) : null;
            unset($validated['sensitive_topics_text']);
        }

        // แปลง customer_handoff_keywords จาก textarea → array
        if (isset($validated['customer_handoff_keywords'])) {
            $raw = (string) $validated['customer_handoff_keywords'];
            $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', $raw)));
            $validated['customer_handoff_keywords'] = ! empty($lines) ? array_values($lines) : null;
        }

        $settings = FortuneTellingSetting::getSettings();

        // จัดการ checkbox fields (ถ้าไม่ส่งมา = false)
        $checkboxFields = [
            'is_enabled', 'respond_in_comment', 'require_registration',
            'enable_deep_reading', 'allow_try_before_buy', 'subscription_enabled',
            'use_global_ai_settings', 'comment_engagement_enabled',
            'fortune_affiliate_enabled', 'fortune_auto_register_enabled',
            'fortune_use_global_commission_rate', 'fortune_level2_enabled',
            'fortune_central_fallback_enabled',
            'enable_ai_chat',
            'admin_handover_enabled', 'takeover_notify_customer',
            // 🎁 (2026-05-03) ระบบทำนายฟรี 1 ใบ
            'enable_free_card_reading',
            // 🌟 (2026-05-04) Group Invite + Monthly Claim
            'fortune_group_invite_enabled',
            'monthly_free_claim_enabled',
            // 🌟 (2026-05-07) Sensitive AI Mode
            'sensitive_log_enabled',
            // 💳 (2026-05-07 Phase 2)
            'bill_psychology_enabled',
            'celtic_premium_chat_enabled',
            'satisfaction_detection_enabled',
            // 🎙️ (2026-05-08) Voice Summary
            'voice_summary_enabled',
            // 💳 (2026-05-09) Stripe payment
            'enable_stripe_payment',
            'stripe_test_mode',
            // 🛡️ (2026-05-10) Link Moderation
            'auto_hide_link_comments',
            'link_moderation_log_only',
        ];
        foreach ($checkboxFields as $field) {
            if (! $request->has($field)) {
                $validated[$field] = false;
            }
        }

        // 💳 (2026-05-09) Stripe secrets — ถ้า admin เคลียร์ field (เว้นว่าง) ห้ามเขียน null ทับ
        //   เคส: admin โหลดหน้า settings → ฟิลด์ password ว่าง (security: ไม่แสดง secret กลับ)
        //         → submit form → field=null → ทับ secret เดิม → key หาย
        //   วิธี: ถ้า request->stripe_secret_key หรือ webhook_secret เป็น empty string → unset ออก
        //         → DB row ไม่ update field นั้น (เก็บค่าเดิม)
        foreach (['stripe_secret_key', 'stripe_webhook_secret'] as $secretField) {
            if (array_key_exists($secretField, $validated) && $validated[$secretField] === '') {
                unset($validated[$secretField]);
            }
        }

        // จัดการ fortune_bank_account_ids (empty array → null)
        if (empty($validated['fortune_bank_account_ids'])) {
            $validated['fortune_bank_account_ids'] = null;
        }

        // 🎙️ (2026-05-08) Voice fallback providers (empty array → null)
        if (array_key_exists('voice_summary_fallback_providers', $validated)
            && empty($validated['voice_summary_fallback_providers'])) {
            $validated['voice_summary_fallback_providers'] = null;
        }

        // 🌟 (2026-05-08) Sensitive AI Mode — Block enable ถ้าไม่มี key purpose='sensitive'
        //    ป้องกัน admin เปิดโหมดแล้วลูกค้า hit แต่ไม่มี key → fallback ไป chat ปกติ
        //    เปลี่ยนแปลงเล็กน้อย: ถ้า set sensitive_ai_pool_key_id → ใช้ key นั้นเลย (ไม่ตรวจ pool)
        $requestedMode = $validated['sensitive_ai_mode'] ?? 'off';
        if ($requestedMode !== 'off') {
            $hasLockedKey = ! empty($validated['sensitive_ai_pool_key_id']);
            $hasPoolKey = false;

            if (! $hasLockedKey) {
                try {
                    $hasPoolKey = \App\Models\AiApiKey::forProvider($validated['sensitive_provider'] ?? 'gemini')
                        ->forPurpose('sensitive')
                        ->where('is_active', true)
                        ->exists();
                } catch (\Throwable $e) {
                    $hasPoolKey = false;
                }
            }

            if (! $hasLockedKey && ! $hasPoolKey) {
                // Force off — แจ้งใน flash message
                $validated['sensitive_ai_mode'] = 'off';
                session()->flash(
                    'warning',
                    '⚠️ Sensitive AI Mode ถูกบังคับปิด — ยังไม่มี key purpose=\'sensitive\' ใน account pool '
                    .'(เพิ่ม key ที่ /admin/ai-api-keys → set purpose=sensitive ก่อน)'
                );
            }
        }

        // ถ้า lock specific key — verify ว่า purpose='sensitive' จริง (ป้องกัน admin เลือกผิด)
        if (! empty($validated['sensitive_ai_pool_key_id'])) {
            $lockedKey = \App\Models\AiApiKey::find($validated['sensitive_ai_pool_key_id']);
            if (! $lockedKey || $lockedKey->purpose !== 'sensitive') {
                session()->flash(
                    'warning',
                    '⚠️ Key ที่เลือกไม่ใช่ purpose=\'sensitive\' — reset เป็น pool rotation'
                );
                $validated['sensitive_ai_pool_key_id'] = null;
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

    // ============================================================
    // 🎨 Payment Banner Admin (2026-05-17)
    //   Anti-FB-detection composite: banner + QR + ยอด → ภาพเดียว
    //   - GET  /admin/fortune/payment-banner          → paymentBanner (show)
    //   - POST /admin/fortune/payment-banner          → updatePaymentBanner (save)
    //   - POST /admin/fortune/payment-banner/preview  → previewBanner (AJAX test)
    //   - POST /admin/fortune/payment-banner/reset    → resetBanner (กลับ default)
    // ============================================================

    /**
     * แสดงหน้าจัดการ Payment Banner — upload + ปรับตำแหน่ง + preview
     */
    public function paymentBanner()
    {
        $settings = FortuneTellingSetting::getSettings();

        // Generate default banner ถ้ายังไม่มี — ให้ admin preview ทันที
        $defaultBannerPath = null;
        try {
            $svc = app(\App\Services\Fortune\PaymentBannerService::class);
            $defaultBannerPath = $svc->getOrGenerateDefaultBanner();
        } catch (\Throwable $e) {
            \Log::warning('Admin Banner: generate default ล้มเหลว', ['error' => $e->getMessage()]);
        }

        $defaultBannerUrl = $defaultBannerPath
            ? asset('storage/'.str_replace(storage_path('app/public/'), '', $defaultBannerPath))
            : null;

        return view('admin.fortune.payment-banner', [
            'settings' => $settings,
            'customBannerUrl' => $settings->getPaymentBannerTemplateUrl(),
            'defaultBannerUrl' => $defaultBannerUrl,
        ]);
    }

    /**
     * Update banner settings — upload + ตำแหน่ง QR + size
     */
    public function updatePaymentBanner(Request $request)
    {
        $validated = $request->validate([
            'payment_banner_enabled' => 'nullable|boolean',
            'payment_banner_image' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
            'payment_banner_qr_x' => 'nullable|integer|min:0|max:2000',
            'payment_banner_qr_y' => 'nullable|integer|min:0|max:2000',
            'payment_banner_qr_size' => 'nullable|integer|min:100|max:1000',
        ]);

        $settings = FortuneTellingSetting::getSettings();

        $update = [
            'payment_banner_enabled' => (bool) ($validated['payment_banner_enabled'] ?? false),
            'payment_banner_qr_x' => $validated['payment_banner_qr_x'] ?? 100,
            'payment_banner_qr_y' => $validated['payment_banner_qr_y'] ?? 150,
            'payment_banner_qr_size' => $validated['payment_banner_qr_size'] ?? 400,
        ];

        // Upload banner image ถ้ามี
        if ($request->hasFile('payment_banner_image')) {
            // ลบรูปเก่า (ถ้าไม่ใช่ default)
            if ($settings->payment_banner_template
                && ! str_starts_with($settings->payment_banner_template, 'fortune/payment-banner-default')) {
                Storage::disk('public')->delete($settings->payment_banner_template);
            }

            $path = $request->file('payment_banner_image')->store('fortune/banner-templates', 'public');
            $update['payment_banner_template'] = $path;
        }

        $settings->update($update);

        return redirect()
            ->route('admin.fortune.payment-banner.index')
            ->with('success', '🎨 บันทึก Payment Banner สำเร็จ');
    }

    /**
     * Preview banner — generate test composite (AJAX)
     */
    public function previewBanner(Request $request)
    {
        try {
            $settings = FortuneTellingSetting::getSettings();
            $svc = app(\App\Services\Fortune\PaymentBannerService::class, ['settings' => $settings]);

            // Test data
            $provider = new \App\Services\Payment\PromptPayProvider;
            $promptpayId = $request->input('test_promptpay', '0066885782958');
            $amount = (float) $request->input('test_amount', 39.07);
            $payload = $provider->buildPromptPayPayload($promptpayId, 'phone', $amount);

            if (empty($payload)) {
                return response()->json([
                    'success' => false,
                    'error' => 'สร้าง PromptPay payload ไม่ได้ — ตรวจค่า PromptPay ID',
                ]);
            }

            $url = $svc->generateCompositeBanner(
                amount: $amount,
                billRef: 'PREVIEW-'.substr(md5(microtime(true)), 0, 8),
                qrPayload: $payload,
                bankName: $request->input('test_bank', 'กสิกรไทย'),
                accountNumber: $request->input('test_account', '066-8-85782958'),
            );

            if (! $url) {
                return response()->json([
                    'success' => false,
                    'error' => 'Composite ล้มเหลว — ตรวจ GD library / BaconQrCode',
                ]);
            }

            return response()->json([
                'success' => true,
                'preview_url' => $url,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Admin Banner Preview: ล้มเหลว', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset banner template — กลับไปใช้ default ที่ระบบ generate
     *
     * 🎯 (2026-05-17 v2) Default qr_x/y = 0 → service auto-center
     *   qr_size = 800 (ขนาดดีสำหรับ banner 1200x1600)
     */
    public function resetBanner()
    {
        $settings = FortuneTellingSetting::getSettings();

        // ลบไฟล์ admin upload (ถ้ามี)
        if ($settings->payment_banner_template
            && ! str_starts_with($settings->payment_banner_template, 'fortune/payment-banner-default')) {
            Storage::disk('public')->delete($settings->payment_banner_template);
        }

        // ลบ default banner เก่า (จะ regenerate ครั้งถัดไปด้วย design ใหม่)
        $defaultPath = storage_path('app/public/fortune/payment-banner-default.png');
        if (file_exists($defaultPath)) {
            @unlink($defaultPath);
        }

        $settings->update([
            'payment_banner_template' => null,
            'payment_banner_qr_x' => 0,    // 0 = service auto-center
            'payment_banner_qr_y' => 0,
            'payment_banner_qr_size' => 800,
        ]);

        return redirect()
            ->route('admin.fortune.payment-banner.index')
            ->with('success', '🔄 รีเซ็ตเป็น banner default แล้ว + ลบไฟล์เก่าเพื่อ regenerate ใหม่');
    }

    /**
     * 📥 (2026-05-17) Download Banner Template — ภาพเปล่า ให้ admin แก้ external
     *
     * ส่ง banner default ปัจจุบัน (ไม่มี QR/text ทับ) เป็น PNG ให้ admin โหลด
     * Admin แก้ใน Photoshop/Figma → upload กลับเข้าระบบ → ตำแหน่ง QR ตรงกัน
     */
    public function downloadBannerTemplate()
    {
        try {
            $svc = app(\App\Services\Fortune\PaymentBannerService::class);
            $path = $svc->getOrGenerateDefaultBanner();

            if (! $path || ! file_exists($path)) {
                return redirect()
                    ->route('admin.fortune.payment-banner.index')
                    ->with('error', 'ไม่สามารถสร้าง template ได้');
            }

            return response()->download(
                $path,
                'fortune-banner-template-1200x1600.png',
                ['Content-Type' => 'image/png']
            );
        } catch (\Throwable $e) {
            \Log::error('Banner Template Download fail', ['error' => $e->getMessage()]);

            return redirect()
                ->route('admin.fortune.payment-banner.index')
                ->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * คำนวณ preview คอมมิชชั่นดูดวง (AJAX)
     *
     * แสดง: ราคา, PV, คอมมิชชั่นแต่ละ level, กำไรสุทธิ
     */
    public function fortuneCommissionPreview(Request $request)
    {
        try {
            $settings = FortuneTellingSetting::getSettings();

            // ถ้ามี parameter จาก form → ใช้ค่าจาก form (preview ก่อนบันทึก)
            if ($request->has('pv_value')) {
                $settings->fortune_pv_value = $request->input('pv_value', 0);
            }
            if ($request->has('use_global')) {
                $settings->fortune_use_global_commission_rate = (bool) $request->input('use_global', true);
            }
            if ($request->has('custom_rate')) {
                $settings->fortune_custom_commission_per_pv = $request->input('custom_rate');
            }
            if ($request->has('commission_mode')) {
                $settings->fortune_commission_mode = $request->input('commission_mode', 'pv');
            }
            if ($request->has('static_amount')) {
                $settings->fortune_static_commission_amount = $request->input('static_amount', 0);
            }
            // Level 1/Level 2 settings preview
            if ($request->has('level1_type')) {
                $settings->fortune_level1_commission_type = $request->input('level1_type', 'fixed');
            }
            if ($request->has('level1_amount')) {
                $settings->fortune_level1_commission_amount = $request->input('level1_amount', 10);
            }
            if ($request->has('level2_enabled')) {
                $settings->fortune_level2_enabled = (bool) $request->input('level2_enabled', true);
            }
            if ($request->has('level2_type')) {
                $settings->fortune_level2_commission_type = $request->input('level2_type', 'fixed');
            }
            if ($request->has('level2_amount')) {
                $settings->fortune_level2_commission_amount = $request->input('level2_amount', 5);
            }

            $preview = $settings->calculateFortuneCommissionPreview();

            return response()->json([
                'success' => true,
                'data' => $preview,
            ]);
        } catch (\Exception $e) {
            // คืน JSON เสมอ ไม่ให้ return HTML error page
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการคำนวณ: '.$e->getMessage(),
            ], 500);
        }
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
     * 🌟 (2026-05-08) ทดสอบ Sensitive AI Mode — ใช้ key ที่ admin lock จริง
     *
     * เรียกจาก Playground ปุ่ม "ทดสอบ Sensitive AI"
     * - ใช้ key ที่ admin set ใน sensitive_ai_pool_key_id (หรือ pool acquireKey)
     * - ส่ง message ของ scenario sensitive ที่ admin พิมพ์
     * - return response + provider/model/key ที่ใช้จริง + tokens + latency
     *
     * ใช้สำหรับ verify ว่า key purpose='sensitive' ใช้งานได้จริง
     * ก่อน enable Sensitive AI Mode บน production
     */
    public function testSensitive(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|min:5|max:1000',
            'pool_key_id' => 'nullable|integer|exists:ai_api_keys,id',
            // 🌟 (2026-05-08) ขยาย: รองรับ scenario type — ทดสอบ system prompt ที่ตรงกับ feature
            //    sensitive = generic sensitive chat (default)
            //    bill      = Bill Psychology (ลูกค้าไม่กล้าโอน)
            //    celtic    = Celtic Premium chat (post-Q&A discussion)
            'scenario' => 'nullable|in:sensitive,bill,celtic',
        ]);

        $settings = FortuneTellingSetting::getSettings();
        $message = $validated['message'];
        $forceKeyId = $validated['pool_key_id'] ?? null;
        $scenario = $validated['scenario'] ?? 'sensitive';

        try {
            // ถ้า admin ส่ง pool_key_id มาทดสอบ — temporary lock ใน-memory
            $originalLockedId = $settings->sensitive_ai_pool_key_id;
            if ($forceKeyId) {
                $forceKey = AiApiKey::where('id', $forceKeyId)
                    ->where('purpose', 'sensitive')
                    ->where('is_active', true)
                    ->first();
                if (! $forceKey) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Key ที่เลือกไม่ใช่ purpose=\'sensitive\' หรือไม่ active',
                    ], 422);
                }
                $settings->sensitive_ai_pool_key_id = $forceKeyId;
            }

            try {
                $startTime = microtime(true);
                $aiService = new FortuneAIService($settings);

                // เรียก method ตาม scenario — ทุกอันใช้ key purpose='sensitive' ร่วมกัน
                $userProfile = ['name' => 'ทดสอบ'];
                $result = match ($scenario) {
                    'bill' => $aiService->generateBillPsychologyResponse(
                        $message,
                        $userProfile,
                        [],
                        // mock bill context — ลูกค้าค้างจ่าย Deep 39 มา 30 นาที
                        [
                            'package' => 'ดูดวงเชิงลึก 39 บาท',
                            'amount' => 39,
                            'hours_since' => 0,
                            'minutes_since' => 30,
                            'mention_count' => 1,
                            'max_mentions' => 2,
                        ],
                        false,  // aggressiveCounter
                        false   // reachedMentionCap
                    ),
                    'celtic' => $aiService->generateCelticPremiumResponse(
                        $message,
                        $userProfile,
                        [],
                        // mock celtic context
                        [
                            'minutes_remaining' => 25,
                            'message_count' => 3,
                            'max_messages' => 30,
                        ],
                        // mock context text — ไพ่ + Q&A เดิม
                        "ไพ่ที่เปิดได้: 1.The Star (ตั้งตรง)  2.The Lovers (กลับหัว) ... (10 ใบ)\n\nคำถาม Q1: เรื่องความรัก → ตอบ: ...\nคำถาม Q2: เรื่องการงาน → ตอบ: ..."
                    ),
                    default => $aiService->generateSensitiveChatResponse($message, $userProfile, []),
                };

                $elapsedMs = (int) round((microtime(true) - $startTime) * 1000);

                if ($result === null) {
                    return response()->json([
                        'success' => false,
                        'error' => 'ไม่มี key purpose=\'sensitive\' ที่พร้อมใช้งาน — ต้อง add ใน /admin/ai-api-keys + set purpose=sensitive',
                    ], 422);
                }

                // ดึงข้อมูล key ที่ถูกใช้จริง (locked หรือ pool)
                $usedKey = $settings->getSensitivePoolKey();
                $keyInfo = $usedKey ? [
                    'key_id' => $usedKey->id,
                    'key_name' => $usedKey->name,
                    'provider' => $usedKey->provider,
                    'model' => $usedKey->resolveModel(),
                    'source' => 'locked',
                ] : [
                    'key_id' => null,
                    'provider' => $settings->sensitive_provider ?? 'gemini',
                    'model' => $settings->sensitive_model ?? '?',
                    'source' => 'pool_rotation',
                ];

                return response()->json([
                    'success' => true,
                    'scenario' => $scenario,
                    'response' => $result['response'] ?? '',
                    'tokens_used' => (int) ($result['tokens_used'] ?? 0),
                    'response_time_ms' => $elapsedMs,
                    'key_info' => $keyInfo,
                ]);
            } finally {
                // Restore original locked id (ไม่ persist override)
                $settings->sensitive_ai_pool_key_id = $originalLockedId;
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('FortuneSettings: testSensitive exception', [
                'error' => $e->getMessage(),
                'scenario' => $scenario,
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
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
            'status' => ! empty($aiApiKey) ? 'ok' : 'error',
            'details' => [
                'provider' => $aiProvider,
                'model' => $aiModel,
                'has_api_key' => ! empty($aiApiKey),
                'api_key_prefix' => $aiApiKey ? mb_substr($aiApiKey, 0, 8).'...' : 'ไม่มี',
                'use_global' => $useGlobal,
            ],
            'message' => ! empty($aiApiKey)
                ? "ใช้ {$aiProvider} / {$aiModel}".($useGlobal ? ' (จากระบบหลัก)' : '')
                : "ไม่พบ API Key สำหรับ {$aiProvider}",
            'fix' => empty($aiApiKey)
                ? 'ตั้งค่า API Key ในส่วน "การตั้งค่า AI Provider" ด้านล่าง หรือเปิดใช้ "ใช้การตั้งค่า AI จากระบบหลัก"'
                : null,
        ];

        // 2. ตรวจสอบ API Key Pool
        try {
            $poolService = new \App\Services\AiApiKeyPoolService;
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
                'message' => 'Pool ไม่พร้อม: '.mb_substr($e->getMessage(), 0, 100),
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
                'fix' => ! ($testResult['success'] ?? false)
                    ? 'ตรวจสอบ API Key ว่าถูกต้องและยังไม่หมดอายุ แล้วกด "ตรวจเช็คทั้งหมด" อีกครั้ง'
                    : null,
            ];
        } catch (\Exception $e) {
            $checks['ai_connection'] = [
                'label' => 'การเชื่อมต่อ AI',
                'status' => 'error',
                'message' => 'เชื่อมต่อ AI ไม่ได้: '.mb_substr($e->getMessage(), 0, 200),
                'fix' => 'ตรวจสอบ API Key และ Provider ว่าตั้งค่าถูกต้อง',
            ];
        }

        // 4. ตรวจสอบ Facebook Configuration
        $hasFacebook = $settings->hasFacebookConfigured();
        $fbMissing = [];
        if (empty($settings->facebook_app_id)) {
            $fbMissing[] = 'App ID';
        }
        if (empty($settings->facebook_app_secret)) {
            $fbMissing[] = 'App Secret';
        }
        if (empty($settings->facebook_page_id)) {
            $fbMissing[] = 'Page ID';
        }
        if (empty($settings->facebook_page_token)) {
            $fbMissing[] = 'Page Token';
        }
        if (empty($settings->facebook_verify_token)) {
            $fbMissing[] = 'Verify Token';
        }

        $checks['facebook'] = [
            'label' => 'Facebook Messenger',
            'status' => $hasFacebook ? 'ok' : 'error',
            'message' => $hasFacebook
                ? 'ตั้งค่า Facebook ครบถ้วน (Page ID: ***'.mb_substr($settings->facebook_page_id ?? '', -4).')'
                : 'ยังตั้งค่า Facebook ไม่ครบ - ขาด: '.implode(', ', $fbMissing),
            'details' => [
                'has_app_id' => ! empty($settings->facebook_app_id),
                'has_app_secret' => ! empty($settings->facebook_app_secret),
                'has_page_id' => ! empty($settings->facebook_page_id),
                'has_page_token' => ! empty($settings->facebook_page_token),
                'has_verify_token' => ! empty($settings->facebook_verify_token),
            ],
            'fix' => ! $hasFacebook
                ? 'กรอกค่า '.implode(', ', $fbMissing).' ในส่วน "การตั้งค่า Facebook" ด้านล่าง'
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

                if (! $exists) {
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

        if (! empty($missingRequired)) {
            $dbStatus = 'error';
            $dbMessage = 'ตารางหลักหาย: '.implode(', ', $missingRequired);
            $dbFix = 'กดปุ่ม "แก้ไขฐานข้อมูล" ด้านล่างเพื่อรัน migration อัตโนมัติ';
            $hasPendingMigrations = true;
        } elseif (! empty($missingOptional)) {
            $dbStatus = 'warning';
            $dbMessage = 'ตารางเสริมหาย: '.implode(', ', $missingOptional).' (ระบบดูดวงหลักยังใช้ได้)';
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
            'fix' => ! $isEnabled
                ? 'เปิดสวิตช์ "เปิดใช้งาน" ในการตั้งค่าด้านบน'
                : null,
        ];

        // 7. ตรวจสอบ Admin Handover
        // 🤝 (2026-05-08 v3) Default true — ตรงกับ runtime FortuneTellingSetting::isTakeoverEnabled
        //   เดิม false ทำให้ diagnostics ขึ้น "ปิด" แต่บอท pause จริงตอน admin reply
        $handoverEnabled = $settings->admin_handover_enabled ?? true;
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
                    ->get('https://graph.facebook.com/v21.0/me', [
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
                    'message' => 'ทดสอบ Facebook Token ไม่ได้: '.mb_substr($e->getMessage(), 0, 200),
                    'fix' => 'ตรวจสอบ Page Access Token และ internet connection',
                ];
            }
        }

        // 9. สรุปสถานะรวม
        $hasError = collect($checks)->contains(fn ($c) => $c['status'] === 'error');
        $hasWarning = collect($checks)->contains(fn ($c) => $c['status'] === 'warning');

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
            $hasChanges = ! str_contains($output, 'Nothing to migrate');

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
                'message' => 'รัน migration ไม่สำเร็จ: '.mb_substr($e->getMessage(), 0, 300),
            ], 500);
        }
    }

    /**
     * หน้า AI Playground สำหรับทดสอบสนทนากับ AI
     */
    public function playground()
    {
        $settings = FortuneTellingSetting::getSettings();

        // รวบรวม providers ที่มี API key พร้อมใช้
        $availableProviders = $this->getAvailableProvidersForPlayground($settings);

        // 🆕 (2026-05-06) ส่ง MODELS_BY_PROVIDER ให้ FE — ใช้ populate model picker
        //   ทำให้ admin ทดสอบ model อะไรก็ได้ของ provider นั้นก่อนล็อกเป็น default
        $modelsByProvider = AiApiKey::MODELS_BY_PROVIDER;

        return view('admin.fortune.playground.index', [
            'settings' => $settings,
            'availableProviders' => $availableProviders,
            'modelsByProvider' => $modelsByProvider,
            'pageTitle' => 'AI Playground - ทดสอบดูดวง',
        ]);
    }

    /**
     * รวบรวม AI providers ที่มี API key พร้อมใช้สำหรับ Playground
     */
    protected function getAvailableProvidersForPlayground(FortuneTellingSetting $settings): array
    {
        $providers = [];
        $addedKeys = []; // ป้องกัน duplicate

        // ค่า default จาก settings ปัจจุบัน
        $defaultProvider = $settings->getActualAIProvider();
        $defaultModel = $settings->getActualAIModel();
        $defaultKey = $settings->getActualAIApiKey();

        if (! empty($defaultKey)) {
            $key = $defaultProvider.':'.$defaultModel;
            $providers[] = [
                'provider' => $defaultProvider,
                'model' => $defaultModel,
                'label' => ucfirst($defaultProvider).' - '.$defaultModel.' (ค่าเริ่มต้น)',
                'source' => 'settings',
            ];
            $addedKeys[] = $key;
        }

        // ดึงจาก API Key Pool
        try {
            $poolKeys = AiApiKey::where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('disabled_until')
                        ->orWhere('disabled_until', '<', now());
                })
                ->orderBy('priority', 'desc')
                ->get();

            // 🆕 (2026-05-13) เพิ่ม openai/anthropic/xiaomi — user report "playground ทดสอบคีย์ไม่ได้"
            $defaultModels = [
                'gemini' => 'gemini-2.5-flash',
                'groq' => 'llama-3.3-70b-versatile',
                'grok' => 'grok-2-latest',
                'qwen' => 'Qwen/Qwen2.5-72B-Instruct',
                'openrouter' => 'anthropic/claude-3-haiku',
                'deepseek' => 'deepseek-chat',
                'typhoon' => 'typhoon-v2-70b-instruct',
                'openai' => 'gpt-4o-mini',
                'anthropic' => 'claude-haiku-4-5',
                'xiaomi' => 'mimo-v2.5-pro',
            ];

            foreach ($poolKeys as $poolKey) {
                // 🎯 (2026-05-06) ใช้ resolveModel() แทน metadata['model'] โดยตรง
                //   resolveModel จะอ่านจาก column `model` ก่อน (per-key override)
                //   fallback ไป metadata['model'] → settings default
                $model = method_exists($poolKey, 'resolveModel')
                    ? $poolKey->resolveModel()
                    : ($poolKey->metadata['model'] ?? ($defaultModels[$poolKey->provider] ?? $poolKey->provider));
                $key = $poolKey->provider.':'.$model.':'.$poolKey->id;

                if (in_array($key, $addedKeys)) {
                    continue;
                }

                $providerLabel = AiApiKey::PROVIDERS[$poolKey->provider] ?? ucfirst($poolKey->provider);

                // 🆕 (2026-05-06) แสดง purpose badge ใน label
                //   admin จะเห็นว่า key นี้ถูก lock ใช้งานเฉพาะอะไร (prediction/chat/free_card)
                $purpose = $poolKey->purpose ?? 'any';
                $purposeBadge = match ($purpose) {
                    'prediction' => ' 🎯[prediction]',
                    'chat' => ' 💬[chat]',
                    'free_card' => ' 🎁[free_card]',
                    default => '',
                };

                $providers[] = [
                    'provider' => $poolKey->provider,
                    'model' => $model,
                    'label' => $providerLabel.' - '.$model.$purposeBadge.' (Pool: '.$poolKey->name.')',
                    'source' => 'pool',
                    'pool_key_id' => $poolKey->id,
                    'purpose' => $purpose,
                ];
                $addedKeys[] = $key;
            }
        } catch (\Exception $e) {
            // ตาราง pool อาจยังไม่มี
        }

        // ดึงจาก Global AI Settings (AiContentSetting)
        try {
            $globalKeys = [
                'gemini' => ['key_field' => 'gemini_api_key', 'model_field' => 'gemini_model', 'default_model' => 'gemini-2.5-flash'],
                'openrouter' => ['key_field' => 'claude_api_key', 'model_field' => 'claude_model', 'default_model' => 'anthropic/claude-3-haiku'],
            ];

            foreach ($globalKeys as $provider => $config) {
                $apiKey = AiContentSetting::getValue($config['key_field']);
                if (empty($apiKey)) {
                    continue;
                }
                $model = AiContentSetting::getValue($config['model_field'], $config['default_model']);
                $key = $provider.':'.$model;

                if (in_array($key, $addedKeys)) {
                    continue;
                }

                $providerLabel = AiApiKey::PROVIDERS[$provider] ?? ucfirst($provider);
                $providers[] = [
                    'provider' => $provider,
                    'model' => $model,
                    'label' => $providerLabel.' - '.$model.' (Global Settings)',
                    'source' => 'global',
                ];
                $addedKeys[] = $key;
            }
        } catch (\Exception $e) {
            // ข้ามถ้า AiContentSetting ไม่พร้อม
        }

        return $providers;
    }

    /**
     * API สำหรับส่งข้อความใน Playground
     * รองรับเลือก provider/model ที่ต้องการทดสอบ
     */
    public function playgroundChat(Request $request)
    {
        $validated = $request->validate([
            'messages' => 'required|array|min:1',
            'messages.*.role' => 'required|in:user,assistant',
            'messages.*.content' => 'required|string|max:2000',
            'reading_type' => 'nullable|in:basic,deep',
            'provider' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:100',
            'pool_key_id' => 'nullable|integer',
        ]);

        $settings = FortuneTellingSetting::getSettings();
        $overrideProvider = $validated['provider'] ?? null;
        $overrideModel = $validated['model'] ?? null;
        $poolKeyId = $validated['pool_key_id'] ?? null;

        try {
            $aiService = new FortuneAIService($settings);

            // Override provider/model ถ้าเลือกจาก Playground
            if ($overrideProvider) {
                $apiKey = null;

                // หา API key ตาม source
                if ($poolKeyId) {
                    $poolKey = AiApiKey::where('id', $poolKeyId)->where('is_active', true)->first();
                    if ($poolKey) {
                        $apiKey = $poolKey->api_key;
                    }
                }

                if (empty($apiKey)) {
                    // ลองหาจาก Pool ตาม provider
                    $poolKey = AiApiKey::where('provider', $overrideProvider)
                        ->where('is_active', true)
                        ->where(function ($q) {
                            $q->whereNull('disabled_until')
                                ->orWhere('disabled_until', '<', now());
                        })
                        ->orderBy('priority', 'desc')
                        ->first();
                    if ($poolKey) {
                        $apiKey = $poolKey->api_key;
                    }
                }

                if (empty($apiKey)) {
                    // ลองจาก Global Settings
                    $globalKeyMap = [
                        'gemini' => 'gemini_api_key',
                        'openrouter' => 'claude_api_key',
                    ];
                    if (isset($globalKeyMap[$overrideProvider])) {
                        $apiKey = AiContentSetting::getValue($globalKeyMap[$overrideProvider]);
                    }
                }

                if (empty($apiKey)) {
                    // Fallback: ใช้จาก settings ถ้า provider เดียวกัน
                    if ($overrideProvider === $settings->getActualAIProvider()) {
                        $apiKey = $settings->getActualAIApiKey();
                    }
                }

                if (empty($apiKey)) {
                    throw new \Exception("ไม่พบ API Key สำหรับ {$overrideProvider}");
                }

                $aiService->overrideForPlayground($overrideProvider, $overrideModel, $apiKey);
            }

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
                    'provider' => $overrideProvider ?? $settings->getActualAIProvider(),
                    'model' => $overrideModel ?? $settings->getActualAIModel(),
                    'has_api_key' => ! empty($settings->getActualAIApiKey()),
                ],
            ], 500);
        }
    }

    /**
     * 🧪 (2026-05-02) Test Deep Prediction
     *
     * ทดสอบสร้างคำทำนายเชิงลึกตัวอย่าง (โดยไม่กระทบ FortuneReading จริง)
     * เพื่อให้ admin ตรวจสอบคุณภาพ prompt + ตัดสินใจเลือก provider/priority
     *
     * Flow:
     *   1. รับ name/gender/birth_date/question/(tarot_card_id) จาก admin
     *   2. เรียก FortuneConversationService->buildDeepPromptForTest() สร้าง prompt จริง
     *   3. เลือก provider override (จาก dropdown ของ playground)
     *   4. ส่ง prompt ให้ AI → return response + prompt + metrics
     */
    public function testDeepPrediction(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:50',
            'gender' => 'nullable|in:male,female',
            'birth_date' => 'required|date_format:Y-m-d|before:today',
            'question' => 'required|string|max:500',
            'tarot_card_id' => 'nullable|integer',
            'provider' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:100',
            'pool_key_id' => 'nullable|integer',
            'show_prompt' => 'nullable|boolean',
        ]);

        $settings = FortuneTellingSetting::getSettings();
        $overrideProvider = $validated['provider'] ?? null;
        $overrideModel = $validated['model'] ?? null;
        $poolKeyId = $validated['pool_key_id'] ?? null;
        $showPrompt = (bool) ($validated['show_prompt'] ?? true);

        try {
            // 1) สร้าง user profile + (optional) tarot card
            $userProfile = [
                'name' => trim((string) ($validated['name'] ?? '')),
                'gender' => $validated['gender'] ?? null,
            ];

            $tarotCard = null;
            if (! empty($validated['tarot_card_id'])) {
                $tarot = \App\Models\TarotCard::find($validated['tarot_card_id']);
                if ($tarot) {
                    $tarotCard = [
                        'card_name_th' => $tarot->name_th ?? $tarot->name_en ?? 'ไพ่',
                        'card_name_en' => $tarot->name_en ?? '',
                        'is_reversed' => false,
                        'meaning' => $tarot->upright_meaning_th ?? $tarot->description_th ?? '',
                    ];
                }
            }

            // 2) สร้าง prompt deep reading (เหมือนที่ใช้จริง)
            $convService = new FortuneConversationService($settings);
            $prompt = $convService->buildDeepPromptForTest(
                $userProfile,
                $validated['question'],
                $validated['birth_date'],
                $tarotCard
            );

            // 3) เลือก provider — reuse logic เดียวกับ playgroundChat
            $aiService = new FortuneAIService($settings);
            if ($overrideProvider) {
                $apiKey = null;
                if ($poolKeyId) {
                    $poolKey = AiApiKey::where('id', $poolKeyId)->where('is_active', true)->first();
                    if ($poolKey) {
                        $apiKey = $poolKey->api_key;
                    }
                }
                if (empty($apiKey)) {
                    $poolKey = AiApiKey::where('provider', $overrideProvider)
                        ->where('is_active', true)
                        ->where(function ($q) {
                            $q->whereNull('disabled_until')->orWhere('disabled_until', '<', now());
                        })
                        ->orderBy('priority', 'desc')
                        ->first();
                    if ($poolKey) {
                        $apiKey = $poolKey->api_key;
                    }
                }
                if (empty($apiKey)) {
                    $globalKeyMap = ['gemini' => 'gemini_api_key', 'openrouter' => 'claude_api_key'];
                    if (isset($globalKeyMap[$overrideProvider])) {
                        $apiKey = AiContentSetting::getValue($globalKeyMap[$overrideProvider]);
                    }
                }
                if (empty($apiKey) && $overrideProvider === $settings->getActualAIProvider()) {
                    $apiKey = $settings->getActualAIApiKey();
                }
                if (empty($apiKey)) {
                    throw new \Exception("ไม่พบ API Key สำหรับ {$overrideProvider}");
                }
                $aiService->overrideForPlayground($overrideProvider, $overrideModel, $apiKey);
            }

            // 4) 🔄 (2026-05-02) ส่ง prompt ให้ AI ผ่าน generateWithRetryAndFallback
            //    เดิมใช้ playgroundChat → prompt ถูกส่งเป็น "user message"
            //    แต่ production (auto/admin retry) ส่ง prompt เป็น "system message"
            //    AI ตีความต่างกัน → output ต่างกัน → ทดสอบไม่ตรงกับของจริง!
            //
            //    generateWithRetryAndFallback ใช้ promptTemplate เป็น system role
            //    เหมือนที่ processPaymentConfirmed ทำ → output ตรงกับของลูกค้าจริง
            $startTime = microtime(true);
            $result = $aiService->generateWithRetryAndFallback(
                [$validated['question']],   // questions
                $userProfile,
                null,                       // userPosts
                $prompt,                    // promptTemplate → system role
                'deep',
                $validated['birth_date']
            );
            $elapsedMs = (int) round((microtime(true) - $startTime) * 1000);

            return response()->json([
                'success' => true,
                'response' => $result['response'] ?? '',
                'prompt' => $showPrompt ? $prompt : null,
                'prompt_length' => mb_strlen($prompt),
                'debug' => [
                    'provider' => $result['provider'] ?? $overrideProvider,
                    'model' => $result['model'] ?? $overrideModel,
                    'tokens_used' => $result['tokens_used'] ?? 0,
                    'response_time_ms' => $result['response_time_ms'] ?? $elapsedMs,
                ],
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Admin test deep prediction failed', [
                'error' => $e->getMessage(),
                'provider' => $overrideProvider,
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'debug' => [
                    'provider' => $overrideProvider ?? $settings->getActualAIProvider(),
                    'model' => $overrideModel ?? $settings->getActualAIModel(),
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
            'has_api_key' => ! empty($settings->getActualAIApiKey()),
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
            'facebook_page_id' => $settings->facebook_page_id ? '***'.substr($settings->facebook_page_id, -4) : null,
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
            'message' => 'เพิ่มบัญชี '.$account->bank_name.' สำเร็จ',
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
            'message' => 'อัพเดทบัญชี '.$account->bank_name.' สำเร็จ',
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteBankAccount(PaymentBankAccount $account)
    {
        $bankName = $account->bank_name;

        // Remove จาก fortune_bank_account_ids
        $settings = FortuneTellingSetting::getSettings();
        $currentIds = $settings->fortune_bank_account_ids ?? [];
        $currentIds = array_values(array_filter($currentIds, fn ($id) => $id != $account->id));
        $settings->update([
            'fortune_bank_account_ids' => ! empty($currentIds) ? $currentIds : null,
        ]);

        // Soft delete บัญชี
        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบบัญชี '.$bankName.' สำเร็จ',
        ]);
    }
}
