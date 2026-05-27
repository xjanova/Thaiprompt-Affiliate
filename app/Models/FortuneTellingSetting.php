<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * FortuneTellingSetting Model
 *
 * จัดการการตั้งค่าระบบดูดวงผ่าน Facebook Messenger
 * รองรับ AI providers: Gemini, Groq, Qwen, OpenRouter, Grok, DeepSeek, Typhoon
 * รองรับระบบ Freemium: คำทำนายพื้นฐาน (ฟรี) + คำทำนายเชิงลึก (จ่ายเงิน/สมัครสมาชิก)
 *
 * @property int $id
 * @property string|null $facebook_app_id
 * @property string|null $facebook_app_secret
 * @property string|null $facebook_page_id
 * @property string|null $facebook_page_token
 * @property string|null $facebook_verify_token
 * @property string $ai_provider
 * @property string|null $ai_api_key
 * @property string $ai_model
 * @property string|null $prompt_template
 * @property string|null $basic_prompt_template
 * @property string|null $deep_prompt_template
 * @property int $max_free_readings
 * @property float $reading_price
 * @property bool $enable_deep_reading
 * @property float $deep_reading_price
 * @property bool $allow_try_before_buy
 * @property int $free_deep_per_day
 * @property bool $subscription_enabled
 * @property float $subscription_monthly_price
 * @property float $subscription_yearly_price
 * @property string|null $subscription_benefits
 * @property string|null $payment_qr_image
 * @property bool $is_enabled
 * @property bool $respond_in_comment
 * @property bool $require_registration
 * @property string|null $welcome_message
 * @property string|null $limit_exceeded_message
 * @property string|null $payment_message
 * @property string|null $subscription_message
 * @property string|null $try_before_buy_message
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class FortuneTellingSetting extends Model
{
    use SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'fortune_telling_settings';

    /**
     * คอลัมน์ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'facebook_app_id',
        'facebook_app_secret',
        'facebook_page_id',
        'facebook_page_token',
        'facebook_verify_token',
        'use_global_ai_settings',
        'ai_provider',
        'ai_api_key',
        'ai_model',
        'prediction_strict_provider',  // 🎯 (2026-05-02) ใช้แค่ provider ที่เลือก ไม่ fallback
        'prompt_template',
        'basic_prompt_template',
        'deep_prompt_template',
        'max_free_readings',
        'reading_price',
        'enable_deep_reading',
        'deep_reading_price',
        'allow_try_before_buy',
        'free_deep_per_day',
        'subscription_enabled',
        'subscription_monthly_price',
        'subscription_yearly_price',
        'subscription_benefits',
        'payment_qr_image',
        'is_enabled',
        'respond_in_comment',
        'require_registration',
        'welcome_message',
        'limit_exceeded_message',
        'payment_message',
        'subscription_message',
        'try_before_buy_message',
        // Comment Engagement
        'comment_engagement_enabled',
        'comment_engagement_mode',
        // 🚫 (2026-05-24) Sub-toggle: ตอบคอมเม้นต์สาธารณะหรือไม่ — default=false
        //    ถ้า Page Token ยังไม่มี pages_manage_engagement scope → ปิด toggle นี้
        //    เพื่อกัน AI quota เผาเปล่าตอนพยายาม replyToComment แล้ว fail 403
        'enable_public_comment_reply',
        // 🖼️ (2026-05-24) Master toggle: image vision ครอบทุก provider — default=false
        //    OFF: gate chatWithImage (OpenAI) + chatWithImageGemini (classifier) entry points
        //    → Celtic vision read ปิด + slip auto-detect classifier ปิด
        //    ON: vision ทำงานปกติ (Celtic 99 + slip classify routing)
        'enable_image_vision',
        'comment_reply_template',
        'comment_dm_template',
        'comment_engagement_prompt',
        // LINE Official Account Settings
        'line_enabled',
        'line_channel_id',
        'line_bot_basic_id',
        'line_channel_secret',
        'line_channel_access_token',
        'enabled_platforms',
        'line_flex_primary_color',
        'line_welcome_image_url',
        'fortune_brand_name',
        // Admin Handover (บอทหยุดเมื่อแอดมินกำลังดูแล)
        'admin_handover_enabled',
        'admin_handover_timeout',
        // RAG Admin Q&A — เก็บคำตอบแอดมินไว้ให้บอทเรียนรู้ (2026-05-19)
        'admin_qa_capture_enabled',
        'admin_qa_rag_enabled',
        'admin_qa_rag_threshold',
        'admin_qa_rag_top_k',
        // Payment Banner Composite (2026-05-17) — anti-FB-detection
        'payment_banner_enabled',
        'payment_banner_template',
        'payment_banner_qr_x',
        'payment_banner_qr_y',
        'payment_banner_qr_size',
        // Admin Takeover (เทคโอเวอร์แบบใหม่ — LINE+Facebook รวมกัน)
        'ai_resume_command',
        'ai_pause_command',
        'customer_handoff_keywords',
        'takeover_notify_customer',
        'takeover_customer_message',
        'takeover_resume_message',
        // บัญชีธนาคารเฉพาะระบบดูดวง
        'fortune_bank_account_ids',
        // โหมดแสดงช่องทางชำระเงิน (both, bank_only, promptpay_only)
        'payment_display_mode',
        // Affiliate/MLM Settings สำหรับลงทะเบียนอัตโนมัติ
        'fortune_affiliate_enabled',
        'fortune_auto_register_enabled',
        'fortune_pv_value',
        'fortune_use_global_commission_rate',
        'fortune_custom_commission_per_pv',
        'fortune_affiliate_invite_message',
        // โหมดจ่ายคอมมิชชั่น: 'pv' หรือ 'static'
        'fortune_commission_mode',
        'fortune_static_commission_amount',
        // Level 1/Level 2 commission settings (ระบบดูดวง)
        'fortune_level1_commission_type',
        'fortune_level1_commission_amount',
        'fortune_level2_enabled',
        'fortune_level2_commission_type',
        'fortune_level2_commission_amount',
        // กระเป๋ากลาง — รับค่าแนะนำเมื่อหา sponsor ไม่ได้
        'fortune_central_user_id',
        'fortune_central_fallback_enabled',
        // AI Chat ทั่วไป (สนทนาอัจฉริยะ — แยก provider จากทำนาย)
        'enable_ai_chat',
        'chat_ai_provider',
        'chat_ai_model',
        'chat_ai_api_key',
        'chat_system_prompt',
        // 🌟 Sensitive AI Mode (2026-05-07) — สลับ Pro model อัตโนมัติเมื่อบริบทละเอียดอ่อน
        'sensitive_ai_mode',
        'sensitive_detection_mode',
        'sensitive_provider',
        'sensitive_model',
        'sensitive_classifier_provider',
        'sensitive_classifier_model',
        'sensitive_keywords',
        'sensitive_topics',
        'sensitive_max_per_user_daily',
        'sensitive_max_total_daily_thb',
        'sensitive_max_tokens_per_call',
        'sensitive_offtopic_strikes',
        'sensitive_offtopic_action',
        'sensitive_offtopic_block_message',
        'sensitive_log_enabled',
        // 💳 Bill Psychology (2026-05-07 Phase 2)
        'bill_psychology_enabled',
        'bill_charity_message',
        'bill_max_mentions_per_session',
        'bill_psychology_window_hours',
        'bill_alternative_payment_methods',
        // 🌙 Celtic Premium Chat (2026-05-07 Phase 2)
        'celtic_premium_chat_enabled',
        'celtic_premium_chat_trigger',
        'celtic_premium_chat_warn_minutes_left',
        'celtic_premium_chat_max_messages',
        'celtic_premium_chat_prompt_override',
        // 🙏 Satisfaction Detector (2026-05-07 Phase 2)
        'satisfaction_detection_enabled',
        'satisfaction_close_message',
        // ระบบดูดวงสาธารณะ (Horoscope Public)
        'horoscope_public_enabled',
        'horoscope_free_daily_limit',
        'horoscope_dream_free_limit',
        'horoscope_numerology_free_limit',
        'horoscope_seo_title_th',
        'horoscope_seo_description_th',
        // 🖼️ Banner DM (2026-04-28)
        'enable_dm_banner',
        'banner_pick_strategy',
        'banner_send_on_reaction',
        'banner_send_on_comment',
        'banner_send_on_welcome',
        // 🧠 Discovery Chat Mode (2026-04-28)
        'enable_discovery_chat',
        'discovery_chat_max_turns',
        // 🔮 Daily Horoscope Per Day (toggle ระบบเดิม)
        'daily_horoscope_per_day_enabled',
        // 🌙 Mystic Content Auto-Post (2026-04-29)
        'mystic_content_enabled',
        'mystic_content_schedule',
        'mystic_content_caption_min',
        'mystic_content_caption_max',
        'mystic_content_hashtag_count',
        'tavily_api_key',
        'brave_search_api_key',
        // 🔮 Celtic Cross Tarot Mode (2026-04-29)
        'enable_celtic_cross',
        'celtic_cross_price',
        'celtic_cross_max_questions',
        'celtic_cross_qa_window_minutes',
        'celtic_cross_main_prompt',
        'celtic_cross_followup_prompt',
        'celtic_cross_proactive_enabled',
        // 🔍 (2026-05-25) Celtic enrichment — AI ถาม clarifying ก่อนทำนายลึก
        'enable_celtic_enrichment',
        // 🛡️ (2026-05-27) Abuse Clapback — แม่หมอ savage mode ตอบลูกค้าหยาบคาย
        'enable_abuse_clapback',
        'abuse_clapback_use_grok',
        // 📦 (2026-05-20 Phase 4) Message debounce — รอรวมข้อความที่ลูกค้าพิมพ์ติด ๆ
        'message_debounce_seconds',
        // 🎁 Free Card Reading (2026-05-03) — ฟรี 1 ใบ ครั้งแรก/platform
        'enable_free_card_reading',
        'free_card_news_context',
        // 🌟 Group Invite + Monthly Free Claim (2026-05-04)
        'fortune_group_url',
        'fortune_group_invite_enabled',
        'fortune_group_invite_message',
        'monthly_free_claim_enabled',
        'monthly_free_claim_secret',
        'monthly_free_claim_success_message',
        'monthly_free_claim_already_message',
        // 🎙️ (2026-05-08) Voice Summary (TTS) — Celtic 99฿ VIP perk
        'voice_summary_enabled',
        'voice_summary_tier_scope',
        'voice_summary_primary_provider',
        'voice_summary_fallback_providers',
        'minimax_api_key',
        'minimax_group_id',
        'minimax_model',
        'minimax_voice_id',
        'openai_tts_model',
        'openai_tts_voice',
        'google_tts_voice',
        'google_tts_speaking_rate',
        'voice_summary_max_chars',
        'voice_summary_prompt',
        'voice_summary_intro_message',
        // 🌥️ (2026-05-18) Voice file cloud storage — แก้ปัญหา disk เต็ม
        'voice_storage_driver',
        'voice_storage_config',
        // 🌟 (2026-05-08) Sensitive AI lock specific pool key
        'sensitive_ai_pool_key_id',
        // 💳 (2026-05-09) Stripe Checkout — บัตรต่างประเทศ
        'enable_stripe_payment',
        // 💳 (2026-05-22) SMS/QR ไทย toggle — เปิดได้ทั้ง 3 โหมด
        'enable_sms_payment',
        'stripe_service_fee',
        'stripe_session_expiry_minutes',
        'stripe_account_id',
        'stripe_test_mode',
        'stripe_secret_key',
        'stripe_publishable_key',
        'stripe_webhook_secret',
        'stripe_product_deep_id',
        'stripe_product_celtic_id',
        // 🛡️ (2026-05-10) Link Moderation — ซ่อน/ลบคอมเม้นต์ที่มีลิงค์ภายนอก
        'auto_hide_link_comments',
        'link_comment_action',
        'link_whitelist_domains',
        'link_moderation_log_only',
        // 🔍 (2026-05-15) Fuzzy Payment Match — auto-approve บิลโอนใกล้เคียง
        'enable_fuzzy_payment_match',
        'fuzzy_overpay_max_baht',
        'fuzzy_underpay_max_baht',
        'fuzzy_window_minutes',
        'fuzzy_name_auto_threshold',
        'fuzzy_admin_alert_above_baht',
    ];

    /**
     * การ cast ประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_enabled' => 'boolean',
        'use_global_ai_settings' => 'boolean',
        'prediction_strict_provider' => 'boolean',  // 🎯 (2026-05-02) strict provider mode
        'respond_in_comment' => 'boolean',
        'require_registration' => 'boolean',
        'enable_deep_reading' => 'boolean',
        'allow_try_before_buy' => 'boolean',
        'subscription_enabled' => 'boolean',
        'comment_engagement_enabled' => 'boolean',
        // 🚫 (2026-05-24) Sub-toggle public comment reply (default false)
        'enable_public_comment_reply' => 'boolean',
        // 🖼️ (2026-05-24) Master toggle image vision (default false) — ครอบทุก provider
        'enable_image_vision' => 'boolean',
        'admin_handover_enabled' => 'boolean',
        'admin_handover_timeout' => 'integer',
        'admin_qa_capture_enabled' => 'boolean',
        'admin_qa_rag_enabled' => 'boolean',
        'admin_qa_rag_threshold' => 'float',
        'admin_qa_rag_top_k' => 'integer',
        'customer_handoff_keywords' => 'array',
        'takeover_notify_customer' => 'boolean',
        'line_enabled' => 'boolean',
        'enabled_platforms' => 'array',
        'fortune_bank_account_ids' => 'array',
        'max_free_readings' => 'integer',
        'free_deep_per_day' => 'integer',
        'reading_price' => 'decimal:2',
        'deep_reading_price' => 'decimal:2',
        'subscription_monthly_price' => 'decimal:2',
        'subscription_yearly_price' => 'decimal:2',
        'fortune_affiliate_enabled' => 'boolean',
        'fortune_auto_register_enabled' => 'boolean',
        'fortune_pv_value' => 'decimal:2',
        'fortune_use_global_commission_rate' => 'boolean',
        'fortune_custom_commission_per_pv' => 'decimal:2',
        'fortune_static_commission_amount' => 'decimal:2',
        'fortune_level1_commission_amount' => 'decimal:2',
        'fortune_level2_enabled' => 'boolean',
        'fortune_level2_commission_amount' => 'decimal:2',
        'fortune_central_user_id' => 'integer',
        'fortune_central_fallback_enabled' => 'boolean',
        'enable_ai_chat' => 'boolean',
        // 🌟 Sensitive AI Mode (2026-05-07)
        'sensitive_keywords' => 'array',
        'sensitive_topics' => 'array',
        'sensitive_max_per_user_daily' => 'integer',
        'sensitive_max_total_daily_thb' => 'decimal:2',
        'sensitive_max_tokens_per_call' => 'integer',
        'sensitive_offtopic_strikes' => 'integer',
        'sensitive_log_enabled' => 'boolean',
        // 💳 Bill Psychology (2026-05-07 Phase 2)
        'bill_psychology_enabled' => 'boolean',
        'bill_max_mentions_per_session' => 'integer',
        'bill_psychology_window_hours' => 'integer',
        // 🌙 Celtic Premium Chat (2026-05-07 Phase 2)
        'celtic_premium_chat_enabled' => 'boolean',
        'celtic_premium_chat_warn_minutes_left' => 'integer',
        'celtic_premium_chat_max_messages' => 'integer',
        // 🙏 Satisfaction Detector (2026-05-07 Phase 2)
        'satisfaction_detection_enabled' => 'boolean',
        // ระบบดูดวงสาธารณะ
        'horoscope_public_enabled' => 'boolean',
        'horoscope_free_daily_limit' => 'integer',
        'horoscope_dream_free_limit' => 'integer',
        'horoscope_numerology_free_limit' => 'integer',
        // 🖼️ Banner DM
        'enable_dm_banner' => 'boolean',
        'banner_send_on_reaction' => 'boolean',
        'banner_send_on_comment' => 'boolean',
        'banner_send_on_welcome' => 'boolean',
        // 🔮 Daily Horoscope Per Day toggle
        'daily_horoscope_per_day_enabled' => 'boolean',
        // 🌙 Mystic Content
        'mystic_content_enabled' => 'boolean',
        'mystic_content_schedule' => 'array',
        'mystic_content_caption_min' => 'integer',
        'mystic_content_caption_max' => 'integer',
        'mystic_content_hashtag_count' => 'integer',
        // 🔮 Celtic Cross
        'enable_celtic_cross' => 'boolean',
        'celtic_cross_price' => 'decimal:2',
        'celtic_cross_max_questions' => 'integer',
        'celtic_cross_qa_window_minutes' => 'integer',
        'celtic_cross_proactive_enabled' => 'boolean',
        'enable_celtic_enrichment' => 'boolean',
        // 🛡️ (2026-05-27) Abuse Clapback toggles
        'enable_abuse_clapback' => 'boolean',
        'abuse_clapback_use_grok' => 'boolean',
        // 🎁 Free Card Reading (2026-05-03)
        'enable_free_card_reading' => 'boolean',
        // 🌟 Group Invite + Monthly Free Claim (2026-05-04)
        'fortune_group_invite_enabled' => 'boolean',
        'monthly_free_claim_enabled' => 'boolean',
        // 🎙️ (2026-05-08) Voice Summary
        'voice_summary_enabled' => 'boolean',
        'voice_summary_fallback_providers' => 'array',
        'voice_summary_max_chars' => 'integer',
        'voice_storage_config' => 'array',
        'google_tts_speaking_rate' => 'decimal:2',
        // 💳 (2026-05-09) Stripe Checkout
        'enable_stripe_payment' => 'boolean',
        // 💳 (2026-05-22) SMS/QR Thai toggle
        'enable_sms_payment' => 'boolean',
        'stripe_service_fee' => 'decimal:2',
        'stripe_session_expiry_minutes' => 'integer',
        'stripe_test_mode' => 'boolean',
        'stripe_secret_key' => 'encrypted',
        'stripe_webhook_secret' => 'encrypted',
        // 🛡️ (2026-05-10) Link Moderation
        'auto_hide_link_comments' => 'boolean',
        'link_whitelist_domains' => 'array',
        'link_moderation_log_only' => 'boolean',
        // 🔍 (2026-05-15) Fuzzy Payment Match
        'enable_fuzzy_payment_match' => 'boolean',
        'fuzzy_overpay_max_baht' => 'decimal:2',
        'fuzzy_underpay_max_baht' => 'decimal:2',
        'fuzzy_window_minutes' => 'integer',
        'fuzzy_name_auto_threshold' => 'integer',
        'fuzzy_admin_alert_above_baht' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ค่าเริ่มต้นของ attributes
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'use_global_ai_settings' => true,
        'ai_provider' => 'gemini',
        'ai_model' => 'gemini-2.5-flash',
        'max_free_readings' => 1,
        'reading_price' => 0,
        'enable_deep_reading' => false, // 🌙 (2026-05-23) ปิด 39฿ default — เหลือแต่ Celtic 99฿
        'deep_reading_price' => 39,
        'allow_try_before_buy' => true,
        'free_deep_per_day' => 1,
        'subscription_enabled' => true,
        'subscription_monthly_price' => 199,
        'subscription_yearly_price' => 1990,
        'is_enabled' => true,
        'respond_in_comment' => false,
        'require_registration' => false,
        'comment_engagement_enabled' => false,
        'comment_engagement_mode' => 'ai',
        // 🚫 (2026-05-24) Default false — รอ App Review อนุมัติ pages_manage_engagement
        'enable_public_comment_reply' => false,
        // 🖼️ (2026-05-24) Default false — ปิด vision ทุก provider ประหยัด quota
        'enable_image_vision' => false,
        // LINE Settings
        'line_enabled' => false,
        'line_flex_primary_color' => '#6B46C1',
        'fortune_brand_name' => 'แม่หมอจันทรา',
        'enabled_platforms' => '["facebook"]',
        // Affiliate Settings (เปิดเป็นค่าเริ่มต้น)
        'fortune_affiliate_enabled' => true,
        'fortune_auto_register_enabled' => true,
        'fortune_pv_value' => 0,
        'fortune_use_global_commission_rate' => true,
        'fortune_commission_mode' => 'static',  // 'pv' = ใช้ PV ตาม MLM, 'static' = จ่ายตรง
        'fortune_static_commission_amount' => 10,
        // กระเป๋ากลาง: เปิด fallback ตามค่าเริ่มต้น (user_id ตั้งภายหลังใน admin)
        'fortune_central_fallback_enabled' => true,
        // Admin Takeover: AI หยุด 1 นาทีเมื่อแอดมินพิมพ์ (เปลี่ยนจาก 15)
        'admin_handover_enabled' => true,
        'admin_handover_timeout' => 1,
        // AI Chat ทั่วไป (ค่าเริ่มต้นเปิดใช้งาน Gemini)
        'enable_ai_chat' => true,
        'chat_ai_provider' => 'groq',
        'chat_ai_model' => 'llama-3.3-70b-versatile',
        // 🧠 Discovery Chat — ปิดเป็น default (2026-04-29: user feedback ว่าไม่เวิร์ค)
        //   จะใช้ tier menu (39฿ vs 99฿ Celtic) แทน — admin เปิดได้ใน settings ถ้าอยากลอง
        'enable_discovery_chat' => false,
        'discovery_chat_max_turns' => 8,
        // 🖼️ Banner DM — เปิดทันทีหลัง deploy
        'enable_dm_banner' => true,
        'banner_pick_strategy' => 'rotation',
        'banner_send_on_reaction' => true,
        'banner_send_on_comment' => true,
        'banner_send_on_welcome' => true,
        // 🔮 Daily Horoscope Per Day — ปิดเป็น default หลัง deploy v3 (2026-04-29)
        'daily_horoscope_per_day_enabled' => false,
        // 🌙 Mystic Content — ค่าเริ่มต้น (admin ต้องเปิด toggle ก่อนใช้งาน)
        'mystic_content_enabled' => false,
        'mystic_content_caption_min' => 400,
        'mystic_content_caption_max' => 700,
        'mystic_content_hashtag_count' => 6,
        // 🔮 Celtic Cross — ค่าเริ่มต้น (admin ต้องเปิด toggle ก่อนใช้งาน)
        'enable_celtic_cross' => false,
        'celtic_cross_price' => 99.00,
        'celtic_cross_max_questions' => 5, // (2026-05-23 v3) 5 คำถาม — บังคับ hard cap + บอกกติกาให้ชัด
        'celtic_cross_qa_window_minutes' => 15, // (2026-05-23 v3) 15 นาที — ลดจาก 30
        'celtic_cross_proactive_enabled' => true,
        // 🔍 (2026-05-25) Celtic enrichment — default true (admin ปิดได้ที่ admin UI)
        'enable_celtic_enrichment' => true,
        // 🛡️ (2026-05-27) Abuse Clapback — default ปิด (admin opt-in เท่านั้น)
        'enable_abuse_clapback' => false,
        'abuse_clapback_use_grok' => true,
        // 🎁 Free Card Reading — ค่าเริ่มต้นเปิด (ลูกค้าใหม่ครั้งแรก/platform ได้สิทธิ์)
        'enable_free_card_reading' => true,
        // 🎙️ (2026-05-08) Voice Summary — ปิดเป็น default (admin เปิด + ตั้ง MiniMax key ก่อน)
        //   tier_scope='celtic_99_only' = เฉพาะลูกค้าจ่าย 99฿ ตามที่ user request
        'voice_summary_enabled' => false,
        'voice_summary_tier_scope' => 'celtic_99_only',
        'voice_summary_primary_provider' => 'minimax',
        'minimax_model' => 'speech-2.8-hd',  // 🆕 latest gen (2026-05-08)
        'minimax_voice_id' => 'Thai_warmFemaleHost',
        'openai_tts_model' => 'gpt-4o-mini-tts',
        'openai_tts_voice' => 'shimmer',
        'google_tts_voice' => 'th-TH-Neural2-C',
        'google_tts_speaking_rate' => 0.95,
        'voice_summary_max_chars' => 2000,
        'voice_summary_intro_message' => '🎙️ แม่หมอจันทราอัดเสียงสรุปคำทำนายให้เจ้าชะตาแล้วค่ะ ลองฟังดูนะ ✨',
        // 🌥️ (2026-05-18) Cloud storage — default local เพื่อ backward compat
        'voice_storage_driver' => 'local',
        'voice_storage_config' => null,
        // 🌟 Group Invite + Monthly Free Claim — ปิดเป็น default (admin เปิด + ใส่ URL ก่อน)
        'fortune_group_invite_enabled' => false,
        'monthly_free_claim_enabled' => false,
        // 🌟 Sensitive AI Mode (2026-05-07) — ปิดเป็น default (ต้องตั้ง Pro key ก่อนเปิด)
        'sensitive_ai_mode' => 'paid_only',  // off / paid_only / all
        'sensitive_detection_mode' => 'hybrid',
        'sensitive_provider' => 'gemini',
        'sensitive_model' => 'gemini-3.1-pro-preview',
        'sensitive_classifier_provider' => 'groq',
        // 🎯 (2026-05-24) เปลี่ยน default 8b → 3.3-70b — TPM 6000 → 12000 ลด 413
        'sensitive_classifier_model' => 'llama-3.3-70b-versatile',
        'sensitive_max_per_user_daily' => 5,
        'sensitive_max_total_daily_thb' => 200.00,
        'sensitive_max_tokens_per_call' => 2000,
        'sensitive_offtopic_strikes' => 3,
        'sensitive_offtopic_action' => 'revert',  // revert / block / handoff
        'sensitive_log_enabled' => true,
        // 💳 Bill Psychology defaults (2026-05-07 Phase 2)
        'bill_psychology_enabled' => true,
        'bill_max_mentions_per_session' => 2,
        'bill_psychology_window_hours' => 24,
        // 🌙 Celtic Premium Chat defaults
        'celtic_premium_chat_enabled' => true,
        'celtic_premium_chat_trigger' => 'after_questions_done',
        'celtic_premium_chat_warn_minutes_left' => 5,
        'celtic_premium_chat_max_messages' => 30,
        // 🙏 Satisfaction Detector defaults
        'satisfaction_detection_enabled' => true,
        // 💳 (2026-05-09) Stripe Checkout — ค่าเริ่มต้น (admin เปิด + ใส่ key ก่อนใช้)
        'enable_stripe_payment' => false,
        'stripe_service_fee' => 15.00,
        'stripe_session_expiry_minutes' => 30,
        'stripe_test_mode' => true,
        'stripe_account_id' => 'acct_1K7aDRD3aYAdvmlU',
        'stripe_product_deep_id' => 'prod_UU1wXx9DI4s2gq',
        'stripe_product_celtic_id' => 'prod_UU1zVarkNVzkpp',
        // 💳 (2026-05-22) SMS/QR Thai default เปิด (backward compat)
        'enable_sms_payment' => true,
        // 🔍 (2026-05-15) Fuzzy Payment Match — ค่าเริ่มต้น (default OFF — admin เปิดเอง)
        'enable_fuzzy_payment_match' => false,
        'fuzzy_overpay_max_baht' => 11.00,
        'fuzzy_underpay_max_baht' => 1.00,
        'fuzzy_window_minutes' => 60,
        'fuzzy_name_auto_threshold' => 70,
        'fuzzy_admin_alert_above_baht' => 5.00,
    ];

    /**
     * คอลัมน์ที่ซ่อนเมื่อ serialize
     *
     * @var array<string>
     */
    protected $hidden = [
        'facebook_app_secret',
        'facebook_page_token',
        'ai_api_key',
        'chat_ai_api_key',
        'line_channel_secret',
        'line_channel_access_token',
        'tavily_api_key',
        'brave_search_api_key',
    ];

    /**
     * ดึงการตั้งค่าระบบ (Singleton pattern)
     */
    /**
     * Cache instance สำหรับ request เดียวกัน (ลดการ query DB ซ้ำ)
     */
    protected static ?self $cachedInstance = null;

    public static function getSettings(): self
    {
        // ⚡ Cache per-request: ลดจาก 3+ DB queries เหลือ 1
        if (static::$cachedInstance !== null) {
            return static::$cachedInstance;
        }

        $settings = self::first();

        if (! $settings) {
            $settings = self::create([
                'ai_provider' => 'gemini',
                'ai_model' => 'gemini-2.5-flash',
                'max_free_readings' => 3,
                'reading_price' => 0,
                'is_enabled' => true,
            ]);
        }

        static::$cachedInstance = $settings;

        return $settings;
    }

    /**
     * ล้าง cache (ใช้เมื่ออัพเดทค่า settings)
     */
    public static function clearSettingsCache(): void
    {
        static::$cachedInstance = null;
    }

    /**
     * ดึงบัญชีธนาคารที่ใช้เฉพาะระบบดูดวง
     *
     * ถ้าไม่ได้เลือกบัญชีไว้ จะ fallback ไปใช้บัญชีทั้งหมดที่เปิด SMS Checker
     *
     * @return \Illuminate\Database\Eloquent\Collection<PaymentBankAccount>
     */
    public function getFortuneBankAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        $ids = $this->fortune_bank_account_ids;

        if (! empty($ids) && is_array($ids)) {
            // ดึงเฉพาะบัญชีที่เลือก (ต้อง active ด้วย)
            return PaymentBankAccount::whereIn('id', $ids)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        }

        // Fallback: ดึงบัญชีที่เปิด SMS Checker ทั้งหมด
        $accounts = PaymentBankAccount::active()
            ->smsCheckerEnabled()
            ->ordered()
            ->get();

        // ถ้าไม่มีบัญชี SMS Checker ให้ดึง active ทั้งหมด
        if ($accounts->isEmpty()) {
            $accounts = PaymentBankAccount::active()->ordered()->get();
        }

        return $accounts;
    }

    /**
     * ดึงโหมดแสดงช่องทางชำระเงิน
     *
     * @return string 'both', 'bank_only', 'promptpay_only'
     */
    public function getPaymentDisplayMode(): string
    {
        return $this->payment_display_mode ?? 'both';
    }

    /**
     * ตรวจสอบว่าควรแสดงเลขบัญชีธนาคารหรือไม่
     * จะไม่แสดงเมื่อโหมดเป็น promptpay_only
     */
    public function shouldShowBankAccount(): bool
    {
        return $this->getPaymentDisplayMode() !== 'promptpay_only';
    }

    /**
     * ตรวจสอบว่าควรแสดงพร้อมเพย์หรือไม่
     * จะไม่แสดงเมื่อโหมดเป็น bank_only
     */
    public function shouldShowPromptpay(): bool
    {
        return $this->getPaymentDisplayMode() !== 'bank_only';
    }

    /**
     * 🛡️ (2026-05-14) ดึง max questions สำหรับ Celtic Cross 99฿ flow
     *
     * Centralized accessor — ก่อนหน้านี้ default ในโค้ดไม่ consistent (5/3/0)
     * → ทุกที่ใช้ค่าเดียวกันผ่าน method นี้
     *
     * Default: 0 = ไม่จำกัด (free chat ภายในเวลา) — ตรงกับ user spec
     *   "คุยกับแม่หมอจันทรา จนจุใจ 30 นาที"
     *
     * @return int ≥ 0 (0 = unlimited)
     */
    public function getCelticMaxQuestions(): int
    {
        return max(0, (int) ($this->celtic_cross_max_questions ?? 0));
    }

    /**
     * ดึง QA window (นาที) — เวลาที่ลูกค้าคุยกับแม่หมอได้หลังคำทำนายแรก
     *
     * Default: 15 นาที (2026-05-23 v3 — ปรับจาก 30)
     */
    public function getCelticQaWindowMinutes(): int
    {
        return max(1, (int) ($this->celtic_cross_qa_window_minutes ?? 15));
    }

    /**
     * ตรวจสอบว่าบริการเปิดใช้งานหรือไม่
     */
    public function isServiceEnabled(): bool
    {
        return $this->is_enabled === true;
    }

    // ============================================================
    // 🌟 Sensitive AI Mode helpers (2026-05-07)
    // ============================================================

    /**
     * ตรวจว่า Sensitive AI Mode เปิดใน context นี้หรือไม่
     *
     * @param  string  $context  'chat' / 'paid_prediction' / 'celtic'
     */
    public function isSensitiveModeActiveFor(string $context): bool
    {
        $mode = $this->sensitive_ai_mode ?? 'paid_only';

        if ($mode === 'off') {
            return false;
        }

        // 'all' = ใช้ทุก context
        if ($mode === 'all') {
            return true;
        }

        // 'paid_only' = เฉพาะ paid prediction + celtic (ไม่ใช้ใน chat ฟรี)
        if ($mode === 'paid_only') {
            return in_array($context, ['paid_prediction', 'celtic'], true);
        }

        return false;
    }

    /**
     * ดึง keyword list สำหรับ heuristic detection
     *
     * Priority: admin custom (sensitive_keywords) > default
     *
     * @return array<int, string>
     */
    public function getSensitiveKeywords(): array
    {
        $custom = $this->sensitive_keywords;
        if (is_array($custom) && ! empty($custom)) {
            return $custom;
        }

        // Default keyword list — admin override ได้ทาง JSON column
        return [
            // คำหยาบ/ก้าวร้าว (Thai)
            'มึง', 'กู', 'อีเหี้ย', 'เหี้ย', 'ระยำ', 'ชั่ว', 'ห่วย',
            'แม่ง', 'พ่อง', 'สัส', 'ควย', 'เย็ด', 'กาก',
            // อารมณ์ลบรุนแรง
            'รำคาญ', 'โกรธ', 'หงุดหงิด', 'ไม่พอใจ', 'น่าเบื่อ',
            'งี่เง่า', 'โง่', 'เซ็ง', 'หมดศรัทธา',
            // ทดสอบบอท / ดูถูกบริการ
            'ตอบดี ๆ', 'ไม่ฉลาด', 'ตอบไม่ตรง', 'ไม่แม่น', 'หลอกลวง',
            'มดเท็จ', 'โกหก', 'ไร้สาระ',
            // Lao (รุนแรง)
            'ບັກຫ່າ', 'ບັກສ່າ', 'ໂງ່',
        ];
    }

    /**
     * ดึง topic list สำหรับ heuristic detection (หัวข้อหนัก)
     *
     * @return array<int, string>
     */
    public function getSensitiveTopics(): array
    {
        $custom = $this->sensitive_topics;
        if (is_array($custom) && ! empty($custom)) {
            return $custom;
        }

        // Default sensitive topics
        return [
            // ความตาย / สุขภาพร้ายแรง
            'ฆ่าตัวตาย', 'ฆ่าตัว', 'อยากตาย', 'จะตาย', 'ตายดีกว่า',
            'มะเร็ง', 'ป่วยหนัก', 'ป่วยมาก', 'โรคร้าย', 'ผ่าตัด',
            // ความรุนแรง / abuse
            'ถูกทำร้าย', 'ข่มขืน', 'ทำร้าย', 'ทุบตี',
            // ครอบครัวล้มสลาย
            'หย่า', 'แยกทาง', 'ทิ้ง', 'นอกใจ', 'ชู้',
            // การเงินขั้นวิกฤต
            'หนี้สิน', 'ล้มละลาย', 'หนี้ท่วม', 'ไม่มีเงิน',
            // Lao
            'ຕາຍ', 'ປ່ວຍຫນັກ', 'ຫຍ່າ',
        ];
    }

    /**
     * ตรวจสอบว่ามีการตั้งค่า Facebook ครบถ้วนหรือไม่
     */
    public function hasFacebookConfigured(): bool
    {
        return ! empty($this->facebook_app_id)
            && ! empty($this->facebook_app_secret)
            && ! empty($this->facebook_page_id)
            && ! empty($this->facebook_page_token);
    }

    /**
     * ตรวจสอบว่ามีการตั้งค่า AI ครบถ้วนหรือไม่
     */
    public function hasAIConfigured(): bool
    {
        // ถ้าใช้ global settings ให้เช็คจาก AiContentSetting
        if ($this->use_global_ai_settings) {
            return $this->hasGlobalAIConfigured();
        }

        // ถ้าใช้ custom settings ให้เช็คจากตัวเอง
        return ! empty($this->ai_provider)
            && ! empty($this->ai_api_key)
            && ! empty($this->ai_model);
    }

    /**
     * ตรวจสอบว่าระบบหลักมีการตั้งค่า AI หรือไม่
     */
    protected function hasGlobalAIConfigured(): bool
    {
        // ตรวจสอบว่ามี Gemini API Key ในระบบหลัก
        $geminiKey = AiContentSetting::getValue('gemini_api_key');
        if (! empty($geminiKey)) {
            return true;
        }

        // ตรวจสอบ Claude
        $claudeKey = AiContentSetting::getValue('claude_api_key');
        if (! empty($claudeKey)) {
            return true;
        }

        // ตรวจสอบ OpenAI
        $openaiKey = AiContentSetting::getValue('openai_api_key');
        if (! empty($openaiKey)) {
            return true;
        }

        // ตรวจสอบ API Key Pool - ถ้ามี key ที่พร้อมใช้งานอย่างน้อย 1 ตัว
        $hasPoolKey = AiApiKey::where('is_active', true)
            ->whereNull('disabled_until')
            ->exists();
        if ($hasPoolKey) {
            return true;
        }

        return false;
    }

    /**
     * ดึง AI Provider ที่ใช้งานจริง
     *
     * 🎯 (2026-05-13) Pool-first architecture
     *   ลำดับ:
     *   1. Pool — หา key แรกใน Pool (priority สูง) → return provider ของ key นั้น
     *   2. Legacy field — $this->ai_provider (ถ้า admin เคยตั้งไว้)
     *   3. Global settings — เช็ค gemini/claude/openai key ใน AiContentSetting (เดิม)
     *   4. Default fallback — 'gemini'
     */
    public function getActualAIProvider(): string
    {
        // 1. Pool-first — หา key ที่ available() + priority สูง
        //    🛡️ (2026-05-13 v2) ใช้ available() scope เพื่อ consistency กับ acquireKeyAnyProvider
        //    → กรอง: is_active + not critical + not disabled + last_test_passed_at IS NOT NULL
        //    เดิมใช้ where(is_active,true)+whereNull(disabled_until) → คืน untested key
        try {
            $poolKey = AiApiKey::available()
                ->orderByDesc('priority')
                ->first();

            if ($poolKey) {
                return $poolKey->provider;
            }
        } catch (\Throwable $e) {
            // Pool DB outage / column ยังไม่ migrate → fallback ไป legacy
        }

        // 2. Legacy custom field
        if (! empty($this->ai_provider)) {
            return $this->ai_provider;
        }

        // 3. Global settings (เดิม) — ตรวจ key ใน AiContentSetting
        $geminiKey = AiContentSetting::getValue('gemini_api_key');
        if (! empty($geminiKey)) {
            return 'gemini';
        }

        $claudeKey = AiContentSetting::getValue('claude_api_key');
        if (! empty($claudeKey)) {
            return 'openrouter';
        }

        $openaiKey = AiContentSetting::getValue('openai_api_key');
        if (! empty($openaiKey)) {
            return 'openrouter';
        }

        // 4. Default fallback
        return 'gemini';
    }

    /**
     * ดึง AI Model ที่ใช้งานจริง
     *
     * 🎯 (2026-05-13) Pool-first — model มาจาก Pool key (ถ้ามี)
     */
    public function getActualAIModel(): string
    {
        // 1. Pool-first — model ของ key แรก (ใช้ available() scope)
        try {
            $poolKey = AiApiKey::available()
                ->orderByDesc('priority')
                ->first();

            if ($poolKey) {
                $resolved = $poolKey->resolveModel();
                if (! empty($resolved)) {
                    return $resolved;
                }
            }
        } catch (\Throwable $e) {
            // fallback ไป legacy
        }

        // 2. Legacy custom field
        if (! empty($this->ai_model)) {
            return $this->ai_model;
        }

        // 3. Global / default ตาม provider
        $provider = $this->getActualAIProvider();

        return match ($provider) {
            'gemini' => AiContentSetting::getValue('gemini_model', 'gemini-2.5-flash'),
            'openrouter' => AiContentSetting::getValue('claude_model', 'anthropic/claude-3-sonnet'),
            'groq' => 'llama-3.3-70b-versatile',
            'deepseek' => 'deepseek-chat',
            'typhoon' => 'typhoon-v2-70b-instruct',
            'grok' => 'grok-2-latest',
            'openai' => 'gpt-4o',
            'anthropic' => 'claude-sonnet-4-5',
            default => 'gemini-2.5-flash',
        };
    }

    /**
     * ดึง AI API Key ที่ใช้งานจริง
     *
     * 🎯 (2026-05-13) Pool-first — key มาจาก Pool (ถ้ามี)
     */
    public function getActualAIApiKey(): ?string
    {
        // 1. Pool-first (ใช้ available() scope — เลือกเฉพาะ key ที่เทสผ่าน)
        try {
            $poolKey = AiApiKey::available()
                ->orderByDesc('priority')
                ->first();

            if ($poolKey) {
                return $poolKey->api_key;
            }
        } catch (\Throwable $e) {
            // fallback
        }

        // 2. Legacy custom field
        if (! empty($this->ai_api_key)) {
            return $this->ai_api_key;
        }

        // 3. Global settings (เดิม)
        $provider = $this->getActualAIProvider();
        $key = match ($provider) {
            'gemini' => AiContentSetting::getValue('gemini_api_key'),
            'openrouter' => AiContentSetting::getValue('claude_api_key')
                ?? AiContentSetting::getValue('openai_api_key'),
            default => null,
        };

        return $key;
    }

    /**
     * ดึง Default Prompt Template
     */
    public function getDefaultPromptTemplate(): string
    {
        return $this->prompt_template ?? <<<'EOT'
คุณเป็นหมอดูชื่อดังระดับประเทศ มีประสบการณ์ทำนายดวงมากกว่า 30 ปี

📌 กฎสำคัญ:
- ทำนายชัดเจน ฟันธง ไม่คลุมเครือ
- ระบุช่วงเวลาที่ชัดเจน
- ให้คำตอบตรงประเด็น ไม่อ้อมค้อม
- ใช้ข้อมูลวันเดือนปีเกิดวิเคราะห์ถ้ามี

ข้อมูลผู้ถาม:
{user_profile}

{birth_date_section}

บริบทจากโพสล่าสุด:
{user_posts}

คำถามที่ต้องการทำนาย:
{questions}

ทำนายอย่างละเอียดแต่กระชับ ฟันธง ภาษาไทยเข้าใจง่าย
แต่ละคำถามตอบ 3-5 ประโยค ระบุช่วงเวลาและคำแนะนำปฏิบัติจริง
EOT;
    }

    /**
     * ดึง URL ของ QR Code ชำระเงิน
     */
    public function getPaymentQrUrl(): ?string
    {
        if (empty($this->payment_qr_image)) {
            return null;
        }

        return asset('storage/'.$this->payment_qr_image);
    }

    /**
     * ตรวจสอบว่าเปิดใช้งานระบบคำทำนายเชิงลึกหรือไม่
     */
    public function isDeepReadingEnabled(): bool
    {
        return $this->enable_deep_reading === true;
    }

    /**
     * ตรวจสอบว่าเปิดบริการ "ทำนายฟรี 1 ใบ ครั้งแรก/platform" หรือไม่
     *
     * 🎁 (2026-05-03) เปลี่ยนจาก max_free_readings (โควต้า/วัน) → enable_free_card_reading (boolean)
     *   นโยบายใหม่: ฟรีครั้งเดียวต่อ platform_user_id เท่านั้น
     *   - true  → ลูกค้าใหม่ครั้งแรกเห็นปุ่ม "🎁 ทำนายฟรี (1 ใบ)" + 39/99
     *   - false → ลูกค้าทุกคนเห็นแค่ 39/99 (ปิดระบบฟรีทั้งหมด)
     *
     * ⚠️ การเช็คว่าลูกค้าใช้สิทธิ์ฟรีไปแล้วหรือยัง ใช้ FortuneReading::hasUsedFreeCard() แยกต่างหาก
     *    method นี้แค่บอกว่า "feature เปิดอยู่ไหม"
     */
    public function isFreeReadingEnabled(): bool
    {
        return (bool) ($this->enable_free_card_reading ?? false);
    }

    /**
     * 📰 (2026-05-03) ดึงบริบทข่าว/เหตุการณ์บ้านเมืองปัจจุบัน
     *
     * Admin ตั้งใน settings ได้ — AI จะ inject ลง prompt ทำนายฟรี
     * ตัวอย่าง: "เศรษฐกิจชะลอ, การเมืองตึง, น้ำมันแพง, เลือกตั้งใกล้"
     * AI จะใช้ผูกกับสถานการณ์ที่ลูกค้ากำลังเผชิญ ทำให้คำทำนายสมจริง + relate กับยุค
     */
    public function getFreeCardNewsContext(): ?string
    {
        $ctx = trim((string) ($this->free_card_news_context ?? ''));

        return $ctx !== '' ? $ctx : null;
    }

    /**
     * ตรวจสอบว่าระบบดูดวงสาธารณะ (Frontend /horoscope) ยังมีการใช้งานฟรีอยู่หรือไม่
     *
     * เปิดเมื่ออย่างน้อยหนึ่งใน horoscope_free_daily_limit, horoscope_dream_free_limit,
     * หรือ horoscope_numerology_free_limit มีค่า > 0
     * ใช้สำหรับซ่อน/แสดงคำว่า "ดูดวงฟรี" บนหน้าเว็บ
     */
    public function isHoroscopePublicFreeEnabled(): bool
    {
        return (int) ($this->horoscope_free_daily_limit ?? 0) > 0
            || (int) ($this->horoscope_dream_free_limit ?? 0) > 0
            || (int) ($this->horoscope_numerology_free_limit ?? 0) > 0;
    }

    /**
     * ตรวจสอบว่าเปิดใช้งานระบบสมัครสมาชิกหรือไม่
     */
    public function isSubscriptionEnabled(): bool
    {
        return $this->subscription_enabled === true;
    }

    /**
     * ตรวจสอบว่าอนุญาตให้ทดลองก่อนจ่ายหรือไม่
     */
    public function isTryBeforeBuyEnabled(): bool
    {
        return $this->allow_try_before_buy === true;
    }

    /**
     * ดึง Prompt Template สำหรับคำทำนายพื้นฐาน
     */
    public function getBasicPromptTemplate(): string
    {
        return $this->basic_prompt_template ?? <<<'EOT'
คุณเป็นหมอดูหญิงชื่อดังระดับประเทศ ประสบการณ์กว่า 30 ปี ผู้คนเรียกว่า "อาจารย์" เชี่ยวชาญโหราศาสตร์ไทย โหราศาสตร์สากล ไพ่ทาโรต์ และเลขศาสตร์

📌 กฎการทำนาย:
- ทำนายอย่างชัดเจน ฟันธง ห้ามตอบคลุมเครือ
- ระบุช่วงเวลาให้ชัดเจนเสมอ เช่น "ภายใน 2 สัปดาห์", "ช่วงเดือนหน้า", "ปลายปีนี้"
- ให้คำตอบตรงประเด็นกับคำถาม ไม่อ้อมค้อม
- บอกทั้งเรื่องดีและสิ่งที่ต้องระวังอย่างจริงใจ
- ให้คำแนะนำที่ปฏิบัติได้จริง เช่น สีมงคลประจำวัน, สิ่งที่ควรทำ/ไม่ควรทำ
- หากมีวันเดือนปีเกิด ต้องอ้างอิงราศี ธาตุ ดาวเคราะห์ประกอบการทำนาย
- ห้ามใช้คำที่ฟังดูคลุมเครือเช่น "อาจจะ" "น่าจะ" ให้ใช้ "จะ" "เห็นว่า" แทน

ข้อมูลผู้ถาม:
{user_profile}

{birth_date_section}

คำถาม:
{questions}

📋 วิธีตอบ:
- ทำนายฟันธง 4-6 ประโยคต่อคำถาม
- ระบุช่วงเวลา + คำแนะนำที่ทำได้ทันที
- ลงท้ายด้วย สีมงคลวันนี้ + เลขมงคล 1 ชุด
- แนะนำ "พิมพ์ 'ดูดวง' เพื่อรับคำทำนายเชิงลึกพร้อมวิเคราะห์ดาว สีมงคล เลขมงคล ฤกษ์ดี"
- หากไม่มีวันเกิด แนะนำ "บอกวันเดือนปีเกิดให้ทางเพจ จะได้ทำนายแม่นยำยิ่งขึ้นค่ะ 🎂"
EOT;
    }

    /**
     * ดึง Prompt Template สำหรับคำทำนายเชิงลึก
     *
     * 🎯 (2026-05-01) Single source of truth — ใช้ getDefaultDeepPrompt() จาก
     *   FortuneConversationService เพื่อให้ admin UI กับ AI input ตรงกัน 100%
     */
    public function getDeepPromptTemplate(): string
    {
        return $this->deep_prompt_template
            ?: \App\Services\FortuneConversationService::getDefaultDeepPrompt();
    }

    /**
     * สร้างข้อความแนะนำสมัครสมาชิก
     */
    public function getSubscriptionMessage(): string
    {
        if (! empty($this->subscription_message)) {
            return $this->subscription_message;
        }

        $message = "✨ สมัครสมาชิกเพื่อรับคำทำนายเชิงลึกไม่จำกัด!\n\n";
        $message .= "📋 สิทธิประโยชน์:\n";
        $message .= "• ดูดวงเชิงลึกไม่จำกัดจำนวนครั้ง\n";
        $message .= "• คำทำนายละเอียด พร้อมสีมงคล เลขมงคล\n";
        $message .= "• วิเคราะห์ดวงจากดาวเคราะห์ส่งผล\n\n";

        if ($this->subscription_monthly_price > 0) {
            $message .= "💎 รายเดือน: {$this->subscription_monthly_price} บาท\n";
        }
        if ($this->subscription_yearly_price > 0) {
            $message .= "👑 รายปี: {$this->subscription_yearly_price} บาท (ประหยัดกว่า!)\n\n";
        }

        $message .= 'สมัครได้ที่: '.url('/register')."\n";

        return $message;
    }

    /**
     * สร้างข้อความหลังทดลองดูฟรี (แนะนำให้จ่ายเงิน/สมัครสมาชิก)
     */
    public function getTryBeforeBuyMessage(): string
    {
        if (! empty($this->try_before_buy_message)) {
            return $this->try_before_buy_message;
        }

        // 🌙 (2026-05-23) Deep ปิด — ปรับ wording ไม่อ้าง "ดูดวงเชิงลึก"
        $deepEnabledForMsg = $this->isDeepReadingEnabled();
        $celticEnabledForMsg = (bool) ($this->enable_celtic_cross ?? false);

        $message = "🔮 หวังว่าคำทำนายจะเป็นประโยชน์นะคะ!\n\n";
        $message .= "📌 คุณได้ใช้สิทธิ์ดูดวงฟรีวันนี้แล้ว\n\n";

        if ($deepEnabledForMsg && $this->deep_reading_price > 0) {
            $message .= "💰 ดูดวงเชิงลึกเพิ่ม: {$this->deep_reading_price} บาท/ครั้ง\n";
        } elseif ($celticEnabledForMsg) {
            $celticPrice = 99;
            try {
                $celticPrice = (int) app(\App\Services\CelticCrossService::class)->getPrice();
            } catch (\Throwable $e) {
            }
            $message .= "🔮 ดูดวงไพ่ Celtic Cross 10 ใบ: {$celticPrice} บาท/ครั้ง\n";
        }

        if ($this->subscription_enabled) {
            $message .= "✨ หรือสมัครสมาชิกเพื่อดูไม่จำกัด!\n";
            if ($this->subscription_monthly_price > 0) {
                $message .= "   • รายเดือน: {$this->subscription_monthly_price} บาท\n";
            }
        }

        $message .= "\n📱 สมัคร/ชำระเงิน: ".url('/register');

        if ($this->payment_qr_image) {
            $message .= "\n📸 หรือสแกน QR Code: ".$this->getPaymentQrUrl();
        }

        return $message;
    }

    // ============================================================
    // Comment Engagement Settings
    // ============================================================

    /**
     * ตรวจสอบว่าเปิดระบบ engage คอมเม้นต์หรือไม่
     */
    public function isCommentEngagementEnabled(): bool
    {
        return (bool) ($this->comment_engagement_enabled ?? false);
    }

    /**
     * โหมดการ engage: 'ai' หรือ 'template'
     */
    public function getCommentEngagementMode(): string
    {
        return $this->comment_engagement_mode ?? 'ai';
    }

    /**
     * 🚫 (2026-05-24) ตรวจสอบว่าเปิดตอบคอมเม้นต์สาธารณะหรือไม่
     *
     * Default: false — รอ Facebook App Review อนุมัติ pages_manage_engagement scope ก่อน
     *
     * เมื่อ false: ProcessCommentEngagement skip pattern match + AI gen + replyToComment + reactToComment
     *              แต่ยังส่ง DM ตามปกติ (Page Messaging ใช้ scope แยก)
     * เมื่อ true: ทำงานครบทุก step (สำหรับเมื่อ App Review approved แล้ว)
     */
    public function isPublicCommentReplyEnabled(): bool
    {
        return (bool) ($this->enable_public_comment_reply ?? false);
    }

    /**
     * 🖼️ (2026-05-24) เช็คว่า image vision เปิดอยู่หรือไม่ — ครอบทุก provider
     *
     * Gate นี้ครอบ:
     *   - chatWithImage() (OpenAI Celtic 99 vision read)
     *   - chatWithImageGemini() (Gemini image classifier — slip routing)
     *   - future: chatWithImageAnthropic / etc.
     *
     * เมื่อ false: vision call ทั้งหมด return null ตั้งแต่ entry point
     *   → ImageIntentClassifier คืน DEFAULT_INTENT_ON_FAIL (general_photo)
     *   → caller fall through ไป legacy logic (no vision)
     *
     * เมื่อ true: vision ทำงานปกติ (Celtic deep vision + slip auto-detect)
     */
    public function isImageVisionEnabled(): bool
    {
        return (bool) ($this->enable_image_vision ?? false);
    }

    /**
     * เทมเพลตตอบคอมเม้นต์ (มีค่า default)
     *
     * 🔁 (2026-05-20) รับ $isReturning เพื่อปรับคำตอบในคอมเม้นต์ลูกค้าเก่า
     *   ลูกค้าเก่า → ตอบสั้นลง ไม่ทักว่า "สวัสดี" ครั้งแรก (รู้จักกันแล้ว)
     */
    public function getCommentReplyTemplate(bool $isReturning = false): string
    {
        if (! empty($this->comment_reply_template)) {
            return $this->comment_reply_template;
        }

        if ($isReturning) {
            return '🌙 คุณ {name} แวะมาอีกแล้วนะคะ ✨ ถ้าอยากคุยทักใน inbox ได้เลยค่ะ 🔮';
        }

        return 'สวัสดีค่ะคุณ {name} 🔮 ถ้าสนใจ ทักมาใน inbox ได้เลยนะคะ ✨';
    }

    /**
     * เทมเพลตข้อความ inbox (มีค่า default)
     *
     * 🎯 Phase L — รับ $userId เพื่อ rotate variant (stable per user)
     *   - ถ้าแอดมินตั้ง custom template → ใช้ตามนั้น (เหมือนเดิม)
     *   - ถ้าไม่ได้ตั้ง → สุ่มจาก 5 variants ตาม crc32($userId)
     *
     * 🔁 (2026-05-20) Returning-user variants — $isReturning=true ใช้คนละชุด
     *   ห้ามทักว่า "ขอบคุณที่คอมเม้นต์ให้เพจเรา" (รู้จักกันแล้ว) → "กลับมาแล้วนะคะ"
     *
     * @param  string|null  $userId  facebook user id (ถ้า null → variant แรก)
     * @param  bool  $isReturning  ลูกค้าเก่า (เคย DM แล้ว) → ใช้คำทักทาย returning
     */
    public function getCommentDmTemplate(?string $userId = null, bool $isReturning = false): string
    {
        if (! empty($this->comment_dm_template)) {
            return $this->comment_dm_template;
        }

        $freeEnabled = $this->isFreeReadingEnabled();

        // 🔁 (2026-05-20) Returning-user variants — ลูกค้าเก่าที่ผ่าน 3-day cooldown
        //   ห้ามใช้ "ขอบคุณที่คอมเม้นต์ครั้งแรก" (รู้จักกันแล้ว → ทักเหมือนเดิม = แปลกหู)
        // 🌙 (2026-05-22) Tone reset — ไม่สัญญา/ไม่ล่อด้วยของฟรี
        //   ตัด "🎁 ฟรี 1 ใบ", "*ฟรี*" emphasis ออกหมด — ระบบ Free Card ยังทำงาน
        //   อัตโนมัติเมื่อลูกค้าตอบกลับ (ถ้าเข้าเงื่อนไข) แต่ DM ไม่ต้องประกาศล่วงหน้า
        if ($isReturning) {
            if ($freeEnabled) {
                $variants = [
                    // v1: welcome back — เปิดประตูเฉยๆ
                    "🌙 สวัสดีอีกครั้งค่ะคุณ {name}\n\n"
                        ."เห็นคุณกลับมาคอมเม้นต์อีก หมอจันทราดีใจนะคะ ✨\n\n"
                        .'ถ้าอยากปรึกษาดวง ทักทายมาคุยกันได้เลยค่ะ 🔮',

                    // v2: remembered + cosmic
                    "✨ คุณ {name} กลับมาแล้วนะคะ 🌙\n\n"
                        ."ช่วงนี้พลังของคุณเริ่มเปลี่ยน — มีอะไรอยากปรึกษาดวงไหมคะ?\n\n"
                        .'🃏 ทักมาคุยได้เลยค่ะ',

                    // v3: gentle re-engage
                    "💫 ขอบคุณที่ยังตามเพจอยู่นะคะคุณ {name}\n\n"
                        ."ไม่ได้คุยกันมาพักหนึ่ง — สบายดีไหมคะ\n"
                        .'ถ้าอยากคุย ทักมาได้เลยค่ะ 🙏',

                    // v4: signal hook
                    "🔮 สวัสดีค่ะคุณ {name}\n\n"
                        ."ดวงดาวสะกิดบอกหมอว่าคุณยังคิดถึงคำทำนายอยู่นะคะ ✨\n\n"
                        .'ถ้าสนใจ ทักมาคุยกันได้เลย 🌙',
                ];
            } else {
                $variants = [
                    "🌙 สวัสดีอีกครั้งค่ะคุณ {name}\nมีอะไรอยากปรึกษาดวงไหมคะ? ทักมาคุยกันได้เลยค่ะ 🔮",
                    "✨ คุณ {name} กลับมาแล้วนะคะ\nหมอจันทราพร้อมรับฟัง — ทักทายมาเลยค่ะ 🌙",
                    "💫 ขอบคุณที่ยังตามเพจอยู่นะคะคุณ {name}\nทักมาคุยกันใหม่ได้เลย แม่หมอพร้อมตอบทุกเรื่องค่ะ 🙏",
                ];
            }
        } elseif ($freeEnabled) {
            // 🌙 (2026-05-22) Tone reset — ไม่สัญญา/ไม่ล่อ
            //   เดิม strategy "เน้นฟรี ไม่เน้นขาย" (2026-05-04) ตัดสินใจกลับ:
            //   - ลูกค้าตอบกลับสูงก็จริง แต่ tone ดูเหมือนหลอกมา
            //   - ระบบ Free Card ยังทำงานอัตโนมัติ (tryAutoFreeCardForFirstReply)
            //     เมื่อลูกค้าตอบกลับ — surprise > promise
            //
            //   ⚠️ ห้ามใส่ราคา 39/99/ค่ากาแฟ/pay-later teaser ใน DM นี้
            //   ⚠️ ห้ามประกาศ "🎁 ฟรี 1 ใบ" — ระบบจะให้ฟรีเองตอนตอบ
            $variants = [
                // v1: invite — เปิดประตูคุย
                "สวัสดีค่ะคุณ {name} 🌙\n\n"
                    ."ขอบคุณที่คอมเม้นต์ให้เพจเรานะคะ ✨\n\n"
                    .'ถ้าอยากปรึกษาดวง ทักมาคุยกันได้เลยค่ะ 🔮',

                // v2: cosmic
                "สวัสดีค่ะคุณ {name} ✨\n\n"
                    ."ดวงดาวบอกว่า เราเชื่อมถึงกันได้พอดี 🌌\n\n"
                    .'ถ้าสนใจคุยเรื่องดวง ทักทายมาได้เลยนะคะ 🌙',

                // v3: gentle no-pressure
                "สวัสดีค่ะคุณ {name} 💫\n\n"
                    ."ขอบคุณที่ติดตามเพจค่ะ\n"
                    .'ถ้าอยากคุย ทักมาเลยนะคะ ไม่ต้องกรอกอะไรค่ะ 🙏',

                // v4: warm + curiosity hook
                "🌙 สวัสดีค่ะคุณ {name}\n\n"
                    ."บางทีดวงบอกอะไรเราที่เราไม่ทันสังเกต\n"
                    .'ถ้าอยากคุยเรื่องดวง ทักทายมาได้เลยค่ะ ✨',

                // v5: warm direct
                "สวัสดีค่ะคุณ {name} 🪄\n\n"
                    ."หมอจันทราพร้อมรับฟัง — ตอบตรงไปตรงมา\n"
                    ."ทั้งเรื่องดีและสิ่งต้องระวัง\n\n"
                    .'ทักมาคุยได้เลยค่ะ 🙏',
            ];
        } else {
            // ระบบฟรีปิด — ชวนทักธรรมดา ไม่ใส่ราคา ไม่ขาย
            $variants = [
                "สวัสดีค่ะคุณ {name} 🌙\n\nขอบคุณที่คอมเม้นต์เพจเรานะคะ\nหมอจันทราอยากชวนคุยสักนิด — ทักทายมาได้เลยค่ะ 🙏",
                "สวัสดีค่ะคุณ {name} ✨\n\nเห็นคอมเม้นต์ของคุณแล้วค่ะ\nทักทายมาคุยกันได้เลยนะคะ 🌙",
                "🌙 สวัสดีค่ะคุณ {name}\n\nขอบคุณที่ติดตามเพจค่ะ\nทักมาคุย — แม่หมอพร้อมตอบทุกเรื่องนะคะ 🙏",
            ];
        }

        if (empty($userId)) {
            return $variants[0];
        }

        $idx = abs(crc32($userId)) % count($variants);

        return $variants[$idx];
    }

    /**
     * AI prompt สำหรับสร้างข้อความชวนดูดวง (มีค่า default)
     */
    public function getCommentEngagementPrompt(): string
    {
        if (! empty($this->comment_engagement_prompt)) {
            return $this->comment_engagement_prompt;
        }

        return <<<'PROMPT'
คุณเป็นหมอดูประจำเพจ Metaverse Tarot ตอบเฉพาะเรื่องดูดวงเท่านั้น
ห้ามตอบเรื่องอื่นทุกกรณี ห้ามเขียนโค้ด ห้ามให้ความรู้ทั่วไป ห้ามตอบคำถามที่ไม่เกี่ยวกับดูดวง

มีคนคอมเม้นต์ในโพสต์ว่า: "{comment}"
ชื่อ: {name}
{profile_info}

สร้างข้อความ 2 ชุด:
1. COMMENT_REPLY: ข้อความสั้นตอบในคอมเม้นต์ (ไม่เกิน 100 ตัวอักษร) ชวนดูดวงอย่างเป็นกันเอง เชื่อมโยงกับสิ่งที่เขาคอมเม้นต์
2. DM_MESSAGE: ข้อความทักใน inbox (200-400 ตัวอักษร) ทักทาย อ้างอิงคอมเม้นต์ ชวนคุยอย่างเป็นกันเอง
   ⛔ ห้ามสัญญา/ยื่นข้อเสนอ "ฟรี", "ของขวัญ", "สิทธิ์พิเศษ", "ดูก่อนจ่ายทีหลัง" — ลูกค้าจะรู้สึกถูกล่อ
   ⛔ ห้ามใส่ราคา 39/99/ค่ากาแฟ/ค่าครู ใน DM นี้
   ✅ Tone: อบอุ่น เปิดประตูคุย ไม่กดดัน — ถ้าลูกค้าสนใจค่อยทักกลับมาเอง

ตอบเป็น JSON เท่านั้น ห้ามมีข้อความอื่นนอก JSON:
{"comment_reply": "...", "dm_message": "..."}
PROMPT;
    }

    // ============================================================
    // LINE Official Account Settings
    // ============================================================

    /**
     * ตรวจสอบว่าเปิดใช้งาน LINE หรือไม่
     */
    public function isLineEnabled(): bool
    {
        return (bool) ($this->line_enabled ?? false);
    }

    /**
     * ตรวจสอบว่ามีการตั้งค่า LINE ครบถ้วนหรือไม่
     */
    public function hasLineConfigured(): bool
    {
        return ! empty($this->line_channel_id)
            && ! empty($this->line_channel_secret)
            && ! empty($this->line_channel_access_token);
    }

    /**
     * ดึงรายการ platform ที่เปิดใช้งาน
     */
    public function getEnabledPlatforms(): array
    {
        $platforms = $this->enabled_platforms ?? ['facebook'];

        // ตรวจสอบว่า platform แต่ละตัวพร้อมใช้งานจริงหรือไม่
        $result = [];

        if (in_array('facebook', $platforms) && $this->hasFacebookConfigured()) {
            $result[] = 'facebook';
        }

        if (in_array('line', $platforms) && $this->hasLineConfigured()) {
            $result[] = 'line';
        }

        return $result;
    }

    /**
     * ดึงชื่อแบรนด์ดูดวง (ใช้แสดงใน Flex Message)
     */
    public function getFortuneBrandName(): string
    {
        return $this->fortune_brand_name ?? 'แม่หมอจันทรา';
    }

    /**
     * ดึงสีหลักสำหรับ LINE Flex Message
     */
    public function getLineFlexPrimaryColor(): string
    {
        return $this->line_flex_primary_color ?? '#6B46C1';
    }

    /**
     * ดึง URL รูปภาพ Welcome สำหรับ LINE
     */
    public function getLineWelcomeImageUrl(): ?string
    {
        return $this->line_welcome_image_url;
    }

    // ============================
    // Affiliate/MLM Helper Methods
    // ============================

    /**
     * ตรวจสอบว่าเปิดระบบ affiliate สำหรับดูดวงหรือไม่
     */
    public function isFortuneAffiliateEnabled(): bool
    {
        return (bool) ($this->fortune_affiliate_enabled ?? false);
    }

    /**
     * ดึงอัตราคอมมิชชั่นที่ใช้งานจริง (จาก global หรือ custom)
     */
    public function getFortuneEffectiveCommissionRate(): float
    {
        if ($this->fortune_use_global_commission_rate ?? true) {
            return (float) MlmGlobalSetting::get('commission_per_pv', 1);
        }

        return (float) ($this->fortune_custom_commission_per_pv ?? MlmGlobalSetting::get('commission_per_pv', 1));
    }

    /**
     * ดึงโหมดจ่ายคอมมิชชั่น: 'pv' หรือ 'static'
     */
    public function getFortuneCommissionMode(): string
    {
        return $this->fortune_commission_mode ?? 'static';
    }

    /**
     * ดึงจำนวนคอมมิชชั่น static (ใช้เมื่อ mode = static)
     */
    public function getFortuneStaticCommissionAmount(): float
    {
        return (float) ($this->fortune_static_commission_amount ?? 10);
    }

    // ===== Level 1/Level 2 Fortune Commission =====

    /**
     * คำนวณคอมมิชชั่น Level 1 (สายตรง) จากราคาดูดวง
     *
     * @param  float  $readingPrice  ราคาดูดวง
     * @return float จำนวนเงินที่ได้
     */
    public function getFortuneLevel1Amount(float $readingPrice): float
    {
        $type = $this->fortune_level1_commission_type ?? 'fixed';
        $amount = (float) ($this->fortune_level1_commission_amount ?? 10);

        if ($type === 'percent') {
            return round($readingPrice * $amount / 100, 2);
        }

        return round($amount, 2);
    }

    /**
     * คำนวณคอมมิชชั่น Level 2 (ชั้นหลาน) จากราคาดูดวง
     *
     * @param  float  $readingPrice  ราคาดูดวง
     * @return float จำนวนเงินที่ได้
     */
    public function getFortuneLevel2Amount(float $readingPrice): float
    {
        $type = $this->fortune_level2_commission_type ?? 'fixed';
        $amount = (float) ($this->fortune_level2_commission_amount ?? 5);

        if ($type === 'percent') {
            return round($readingPrice * $amount / 100, 2);
        }

        return round($amount, 2);
    }

    /**
     * ตรวจสอบว่าเปิด Level 2 (ชั้นหลาน) หรือไม่
     */
    public function isFortuneLevel2Enabled(): bool
    {
        return (bool) ($this->fortune_level2_enabled ?? true);
    }

    /**
     * ดึงประเภทคอมมิชชั่น Level 1
     */
    public function getFortuneLevel1CommissionType(): string
    {
        return $this->fortune_level1_commission_type ?? 'fixed';
    }

    /**
     * ดึงประเภทคอมมิชชั่น Level 2
     */
    public function getFortuneLevel2CommissionType(): string
    {
        return $this->fortune_level2_commission_type ?? 'fixed';
    }

    // ===== กระเป๋ากลาง (Central Wallet Fallback) =====

    /**
     * เปิดใช้ระบบ fallback ค่าแนะนำเข้ากระเป๋ากลางหรือไม่
     *
     * เปิดเมื่อ:
     * 1. fortune_central_fallback_enabled = true
     * 2. fortune_central_user_id ถูกตั้งและ user ยังมีอยู่จริง
     */
    public function isFortuneCentralFallbackEnabled(): bool
    {
        if (! $this->fortune_central_fallback_enabled) {
            return false;
        }

        return ! empty($this->fortune_central_user_id);
    }

    /**
     * ดึง User ID ของกระเป๋ากลาง (null ถ้าไม่ได้ตั้งหรือ disabled)
     *
     * caller ต้องเช็ค isFortuneCentralFallbackEnabled() ก่อน
     */
    public function getFortuneCentralUserId(): ?int
    {
        if (! $this->isFortuneCentralFallbackEnabled()) {
            return null;
        }

        return (int) $this->fortune_central_user_id;
    }

    // ===== AI Chat ทั่วไป (สนทนาอัจฉริยะ) =====

    /**
     * ดึง AI Provider สำหรับ Chat ทั่วไป
     *
     * แยกจาก getActualAIProvider() ซึ่งใช้สำหรับทำนาย (Grok)
     * Chat ใช้ Gemini เป็นค่าเริ่มต้น — เร็ว ฟรี สนทนาดี
     */
    public function getChatAIProvider(): string
    {
        return $this->chat_ai_provider ?: 'groq';
    }

    /**
     * ดึง AI Model สำหรับ Chat ทั่วไป
     */
    public function getChatAIModel(): string
    {
        return $this->chat_ai_model ?: 'llama-3.3-70b-versatile';
    }

    /**
     * ดึง API Key สำหรับ Chat ทั่วไป
     *
     * ลำดับความสำคัญ:
     * 1. chat_ai_api_key (ตั้งค่าเฉพาะ chat)
     * 2. API Key Pool ตาม chat_ai_provider
     * 3. Global AI Settings (AiContentSetting)
     */
    public function getChatAIApiKey(): ?string
    {
        // 1. ใช้ key เฉพาะ chat ถ้ามี
        if (! empty($this->chat_ai_api_key)) {
            return $this->chat_ai_api_key;
        }

        $provider = $this->getChatAIProvider();

        // 2. ลองดึงจาก API Key Pool
        try {
            $poolKey = AiApiKey::where('provider', $provider)
                ->where('is_active', true)
                ->whereNull('disabled_until')
                ->orderBy('priority', 'desc')
                ->first();
            if ($poolKey) {
                return $poolKey->api_key;
            }
        } catch (\Exception $e) {
            // Pool table อาจไม่มี → ข้ามไป
        }

        // 3. ถ้า chat provider ตรงกับ fortune provider → ใช้ key เดียวกัน
        $fortuneProvider = $this->getActualAIProvider();
        if ($provider === $fortuneProvider && ! empty($this->ai_api_key)) {
            return $this->ai_api_key;
        }

        // 4. ลองดึงจาก Global AI Settings
        $key = match ($provider) {
            'gemini' => AiContentSetting::getValue('gemini_api_key'),
            'openrouter' => AiContentSetting::getValue('claude_api_key')
                ?? AiContentSetting::getValue('openai_api_key'),
            default => null,
        };

        return ! empty($key) ? $key : null;
    }

    /**
     * ดึง System Prompt สำหรับ Chat ทั่วไป (ถ้าว่าง → ใช้ default ใน FortuneAIService)
     */
    public function getChatSystemPrompt(): ?string
    {
        return $this->chat_system_prompt;
    }

    // ============================================================
    // Admin Takeover Helpers (ระบบเทคโอเวอร์)
    // ============================================================

    /**
     * ตรวจสอบว่าเปิดระบบเทคโอเวอร์หรือไม่
     *
     * ใช้ค่า admin_handover_enabled เดิม (ไม่สร้างฟิลด์ซ้ำ)
     */
    public function isTakeoverEnabled(): bool
    {
        // 🤝 (2026-05-08 v3) Default = true — auto admin handover
        //   ลูกค้า spec: "อยากได้แบบ ออโต้ไปเลย" — admin reply via Business Suite → bot pause auto
        //   admin ปิด toggle ที่ /admin/fortune/settings ได้ถ้าไม่อยากใช้
        return (bool) ($this->admin_handover_enabled ?? true);
    }

    /**
     * ดึงระยะเวลา default ของการเทคโอเวอร์ (นาที)
     *
     * 🤝 (2026-05-08 v3) Default 15 นาที — เผื่อ admin คุยกับลูกค้าได้พอ
     *   เดิม 1 นาที สั้นเกิน admin ตอบไม่ทันก่อน bot กลับมาแทรก
     *   admin ตั้งค่าเองได้ที่ /admin/fortune/settings
     */
    public function getTakeoverDefaultMinutes(): int
    {
        return max(1, (int) ($this->admin_handover_timeout ?? 15));
    }

    /**
     * 🎨 (2026-05-17) ตรวจว่าเปิดใช้ banner composite (QR + ลายธนาคาร) หรือไม่
     */
    public function isPaymentBannerEnabled(): bool
    {
        return (bool) ($this->payment_banner_enabled ?? true);
    }

    /**
     * ดึง path ของ banner template (admin upload) — null = ใช้ default ที่ระบบ generate
     */
    public function getPaymentBannerTemplate(): ?string
    {
        $path = trim((string) ($this->payment_banner_template ?? ''));

        return $path !== '' ? $path : null;
    }

    /**
     * ดึง URL ของ banner template (สำหรับ admin UI preview)
     */
    public function getPaymentBannerTemplateUrl(): ?string
    {
        $path = $this->getPaymentBannerTemplate();
        if (! $path) {
            return null;
        }

        return asset('storage/'.ltrim($path, '/'));
    }

    /**
     * ดึงคำสั่งให้ AI กลับมาทำงาน
     *
     * Default: /ai (legacy) + /aistart (2026-05-17)
     * Admin พิมพ์ทั้ง 2 คำสั่งเพื่อ resume bot ได้ (detector รองรับทั้งคู่)
     */
    public function getAiResumeCommand(): string
    {
        $cmd = trim((string) ($this->ai_resume_command ?? '/ai'));

        return $cmd !== '' ? $cmd : '/ai';
    }

    /**
     * ดึงคำสั่งให้บอทหยุดทำงาน (admin manual pause via FB echo)
     *
     * 🎯 (2026-05-17) เพิ่มเพื่อแทนที่ auto-takeover เดิม (ลูกค้าบ่นว่าไม่เวิร์ค)
     * Default: /aistop
     */
    public function getAiPauseCommand(): string
    {
        $cmd = trim((string) ($this->ai_pause_command ?? '/aistop'));

        return $cmd !== '' ? $cmd : '/aistop';
    }

    /**
     * ดึงรายการคำที่ลูกค้าพิมพ์แล้วให้เทคโอเวอร์อัตโนมัติ
     *
     * @return array<string>
     */
    public function getCustomerHandoffKeywords(): array
    {
        $keywords = $this->customer_handoff_keywords;

        if (! is_array($keywords) || empty($keywords)) {
            // Default keywords — ลูกค้าอยากคุยกับคนจริง
            // 🇱🇦 (2026-05-03) เพิ่ม Lao defaults — consistent กับ Lao Phase 2 keyword work
            return [
                // ไทย
                'คุยกับคน',
                'คุยกับแอดมิน',
                'คุยกับแม่หมอ',
                'คุยกับเจ้าหน้าที่',
                'ขอคุยกับคน',
                'ขอคุยกับแม่หมอ',
                'ขอแม่หมอ',
                'ต้องการพูดกับคน',
                'อยากคุยกับคน',
                'ติดต่อแอดมิน',
                'ขอแอดมิน',
                // English
                'admin',
                // 🇱🇦 ลาว — ຄຸຍ=คุย, ຄົນ=คน, ແອັດມິນ=admin, ໝໍ=หมอ
                'ຄຸຍກັບຄົນ',
                'ຄຸຍກັບແອັດມິນ',
                'ຄຸຍກັບໝໍ',
                'ຂໍຄຸຍກັບຄົນ',
                'ຂໍຄຸຍກັບໝໍ',
                'ຂໍໝໍ',
                'ຢາກຄຸຍກັບຄົນ',
                'ຕິດຕໍ່ແອັດມິນ',
            ];
        }

        // กรองเฉพาะ string ที่ไม่ว่าง
        return array_values(array_filter(array_map(
            fn ($k) => trim((string) $k),
            $keywords
        ), fn ($k) => $k !== ''));
    }

    /**
     * ตรวจสอบว่าต้องแจ้งลูกค้าเมื่อแม่หมอเข้ามาคุยหรือไม่
     */
    public function shouldNotifyTakeoverToCustomer(): bool
    {
        return (bool) ($this->takeover_notify_customer ?? true);
    }

    /**
     * ดึงข้อความแจ้งลูกค้าเมื่อแม่หมอเข้ามา
     */
    public function getTakeoverCustomerMessage(): string
    {
        $msg = trim((string) ($this->takeover_customer_message ?? ''));

        if ($msg !== '') {
            return $msg;
        }

        $brandName = $this->getFortuneBrandName();

        return "🙏 สวัสดีค่ะ {$brandName} เข้ามาดูแลเอง ขอสักครู่นะคะ 💜";
    }

    /**
     * ดึงข้อความเมื่อ AI กลับมาทำงาน
     */
    public function getTakeoverResumeMessage(): string
    {
        $msg = trim((string) ($this->takeover_resume_message ?? ''));

        if ($msg !== '') {
            return $msg;
        }

        return '✨ ระบบอัจฉริยะกลับมาดูแลต่อแล้ว พิมพ์สอบถามได้เลย';
    }

    /**
     * ดึง unilevel levels เป็น array of objects จาก MlmGlobalSetting
     *
     * ระบบ MLM เก็บแยก 2 key:
     * - unilevel_levels = จำนวนชั้น (integer เช่น 5)
     * - unilevel_percentages = เปอร์เซ็นต์แต่ละชั้น (string เช่น "5,3,2,1,1")
     *
     * Method นี้แปลง unilevel_percentages → [{level: 1, percentage: 5}, {level: 2, percentage: 3}, ...]
     */
    public static function resolveUnilevelLevels(): array
    {
        // ดึง unilevel_percentages จาก MlmGlobalSetting
        // ⚠️ ค่าที่เก็บอาจเป็น:
        //   1. JSON array string: "[5,3,2,1,1]" (จาก frontend JSON.stringify)
        //   2. Comma-separated: "5,3,2,1,1"
        //   3. Array (ถ้า type เป็น 'json' หรือ 'array')
        $percentages = MlmGlobalSetting::get('unilevel_percentages', '');

        // กรณี getTypedValue() คืน array มาเลย (type = json/array)
        if (is_array($percentages) && ! empty($percentages)) {
            return self::buildLevelsFromArray($percentages);
        }

        if (! empty($percentages) && is_string($percentages)) {
            // ลอง JSON decode ก่อน (กรณี "[5,3,2,1,1]")
            $decoded = json_decode($percentages, true);
            if (is_array($decoded) && ! empty($decoded)) {
                return self::buildLevelsFromArray($decoded);
            }

            // Fallback: comma-separated "5,3,2,1,1"
            // ลบ brackets ออกก่อน เผื่อ JSON decode ไม่สำเร็จ
            $cleaned = trim($percentages, '[]');
            $parts = explode(',', $cleaned);
            $levels = [];
            foreach ($parts as $i => $pct) {
                $pctVal = (float) trim($pct);
                if ($pctVal > 0) {
                    $levels[] = ['level' => $i + 1, 'percentage' => $pctVal];
                }
            }
            if (! empty($levels)) {
                return $levels;
            }
        }

        // Fallback: ลองอ่าน unilevel_levels (อาจเป็น JSON array)
        $unilevelLevels = MlmGlobalSetting::get('unilevel_levels', []);

        if (is_string($unilevelLevels)) {
            $decoded = json_decode($unilevelLevels, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (is_array($unilevelLevels)) {
            return $unilevelLevels;
        }

        return [];
    }

    /**
     * แปลง flat array ของ percentages เป็น level config array
     *
     * @param  array  $percentages  เช่น [5, 3, 2, 1, 1]
     * @return array เช่น [{level: 1, percentage: 5}, {level: 2, percentage: 3}, ...]
     */
    protected static function buildLevelsFromArray(array $percentages): array
    {
        $levels = [];
        foreach (array_values($percentages) as $i => $pct) {
            $pctVal = (float) $pct;
            if ($pctVal > 0) {
                $levels[] = ['level' => $i + 1, 'percentage' => $pctVal];
            }
        }

        return $levels;
    }

    /**
     * คำนวณ preview คอมมิชชั่นจากการดูดวง
     *
     * รองรับ 2 โหมด:
     * - 'pv': ใช้ PV คำนวณตาม MLM unilevel levels
     *   สูตร: PV × percentage% × commission_per_pv (เหมือน MlmCommissionService)
     * - 'static': จ่ายตรงตามจำนวนที่ตั้ง (ไม่สน PV, แบ่งตาม level %)
     *
     * แสดง: ราคา, PV, คอมมิชชั่นแต่ละ level, กำไรสุทธิ
     */
    public function calculateFortuneCommissionPreview(): array
    {
        $price = (float) ($this->deep_reading_price ?? 0);
        $mode = $this->getFortuneCommissionMode();

        // ดึง unilevel levels จาก MlmGlobalSetting
        // ⚠️ ระบบเก็บแยก 2 key:
        //   - unilevel_levels = จำนวนชั้น (integer เช่น 5)
        //   - unilevel_percentages = เปอร์เซ็นต์แต่ละชั้น (string เช่น "5,3,2,1,1")
        $unilevelLevels = self::resolveUnilevelLevels();

        // คำนวณตามโหมด
        if ($mode === 'static') {
            return $this->calculateStaticCommissionPreview($price, $unilevelLevels);
        }

        return $this->calculatePvCommissionPreview($price, $unilevelLevels);
    }

    /**
     * คำนวณ preview แบบ PV mode
     *
     * สูตรตรงกับ MlmCommissionService::calculateUnilevelWithRollup() line 224:
     * commissionAmount = (PV × percentage / 100) × commission_per_pv
     */
    protected function calculatePvCommissionPreview(float $price, array $unilevelLevels): array
    {
        // คำนวณ PV จาก global_pv_rate × ราคา (เหมือน OrderDistributionService)
        // ถ้า admin ตั้ง fortune_pv_value ไว้ → ใช้เป็น override
        // ถ้าไม่ได้ตั้ง (0) → คำนวณจาก price × global_pv_rate
        $manualPv = (float) ($this->fortune_pv_value ?? 0);
        $globalPvRate = (float) MlmGlobalSetting::get('global_pv_rate', 1);

        if ($manualPv > 0) {
            $pvValue = $manualPv;
        } else {
            $pvValue = $price * $globalPvRate;
        }

        $commissionPerPv = $this->getFortuneEffectiveCommissionRate();

        $levelBreakdown = [];
        $totalCommission = 0;

        foreach ($unilevelLevels as $levelConfig) {
            if (! is_array($levelConfig)) {
                continue;
            }
            $level = $levelConfig['level'] ?? 0;
            $percentage = (float) ($levelConfig['percentage'] ?? 0);

            // สูตรเดียวกับ MlmCommissionService: (PV × percentage / 100) × commission_per_pv
            $amount = ($pvValue * $percentage / 100) * $commissionPerPv;

            $levelBreakdown[] = [
                'level' => $level,
                'percentage' => $percentage,
                'amount' => round($amount, 4),
            ];

            $totalCommission += $amount;
        }

        $totalCommission = round($totalCommission, 4);
        $netProfit = round($price - $totalCommission, 2);

        return [
            'mode' => 'pv',
            'price' => $price,
            'pv_value' => round($pvValue, 2),
            'global_pv_rate' => $globalPvRate,
            'pv_source' => $manualPv > 0 ? 'manual' : 'auto',
            'commission_per_pv' => $commissionPerPv,
            'levels' => $levelBreakdown,
            'total_commission' => round($totalCommission, 2),
            'net_profit' => $netProfit,
            'profit_percentage' => $price > 0 ? round(($netProfit / $price) * 100, 1) : 0,
        ];
    }

    /**
     * คำนวณ preview แบบ Static mode
     *
     * ระบบดูดวงจ่ายเฉพาะค่าแนะนำ (Direct Referral / Level 1) อย่างเดียว
     * static_amount = จำนวนเงินที่ผู้แนะนำตรงได้รับเต็มจำนวน
     * เช่น ตั้ง 10 บาท → ผู้แนะนำได้ 10 บาท → กำไร = ราคา - 10 = 29 บาท
     */
    protected function calculateStaticCommissionPreview(float $price, array $unilevelLevels): array
    {
        $staticAmount = $this->getFortuneStaticCommissionAmount();

        // จ่ายเฉพาะค่าแนะนำ (Level 1) เต็มจำนวน
        $levelBreakdown = [
            [
                'level' => 1,
                'percentage' => 100,
                'amount' => round($staticAmount, 2),
            ],
        ];

        $totalCommission = round($staticAmount, 2);
        $netProfit = round($price - $totalCommission, 2);

        return [
            'mode' => 'static',
            'price' => $price,
            'static_amount' => $staticAmount,
            'levels' => $levelBreakdown,
            'total_commission' => $totalCommission,
            'net_profit' => $netProfit,
            'profit_percentage' => $price > 0 ? round(($netProfit / $price) * 100, 1) : 0,
        ];
    }

    // ===== 🎙️ (2026-05-08) Voice Summary (TTS) =====

    /**
     * เปิดส่งเสียงสรุปสำหรับ reading นี้หรือไม่
     *
     * Logic:
     *   - ต้องเปิด voice_summary_enabled
     *   - ต้องตรง tier_scope:
     *     'celtic_99_only' → เฉพาะ Celtic 99฿ (ตามที่ user request)
     *     'paid_all'       → ทุก paid reading (Deep 39 + Celtic 99)
     *     'all'            → ทุก reading (รวมฟรี — ไม่แนะนำ)
     *
     * @param  \App\Models\FortuneReading|null  $reading
     */
    public function shouldGenerateVoiceSummary($reading): bool
    {
        if (! $this->voice_summary_enabled) {
            return false;
        }

        if (! $reading) {
            return false;
        }

        $scope = $this->voice_summary_tier_scope ?: 'celtic_99_only';

        return match ($scope) {
            'celtic_99_only' => $reading->reading_type === \App\Models\FortuneReading::READING_TYPE_CELTIC_CROSS,
            'paid_all' => (bool) $reading->is_paid,
            'all' => true,
            default => $reading->reading_type === \App\Models\FortuneReading::READING_TYPE_CELTIC_CROSS,
        };
    }

    /**
     * ดึง provider chain (primary + fallback) สำหรับ TTS
     *
     * @return array<int, string> เช่น ['minimax', 'google_tts', 'gtts']
     */
    public function getVoiceProviderChain(): array
    {
        $primary = $this->voice_summary_primary_provider ?: 'minimax';
        $fallbacks = $this->voice_summary_fallback_providers;

        // default fallback chain ถ้า admin ไม่ตั้ง
        if (! is_array($fallbacks) || empty($fallbacks)) {
            $fallbacks = match ($primary) {
                'minimax' => ['google_tts', 'gtts'],
                'openai_tts' => ['google_tts', 'gtts'],
                'google_tts' => ['gtts'],
                'gtts' => [],
                default => ['google_tts', 'gtts'],
            };
        }

        // primary ขึ้นต้นเสมอ + dedupe
        return array_values(array_unique(array_merge([$primary], $fallbacks)));
    }

    /**
     * ดึง MiniMax API key — fallback chain:
     *   1. minimax_api_key (เฉพาะตั้งใน fortune settings)
     *   2. ai_api_keys pool (provider=minimax, purpose=tts)
     */
    public function getMinimaxApiKey(): ?string
    {
        if (! empty($this->minimax_api_key)) {
            return $this->minimax_api_key;
        }

        try {
            $poolKey = AiApiKey::forProvider('minimax')
                ->forPurpose('tts')
                ->where('is_active', true)
                ->whereNull('disabled_until')
                ->orderByDesc('priority')
                ->first();
            if ($poolKey) {
                return $poolKey->api_key;
            }
        } catch (\Throwable $e) {
            // ai_api_keys table อาจ enum ยังไม่ migrate — ข้าม
        }

        return null;
    }

    // ===== 🌟 (2026-05-08) Sensitive AI Mode pool key helpers =====

    /**
     * ดึง specific pool key ที่ admin lock ไว้สำหรับ Sensitive AI
     *
     * คืน null ถ้า:
     *   - admin ไม่ได้ตั้ง sensitive_ai_pool_key_id
     *   - key ถูกลบไปแล้ว
     *   - key ถูก disable / critical
     *
     * Caller (FortuneAIService::generateSensitiveChatResponse) ตรวจ null →
     * fallback ไป pool acquireKey เดิม
     */
    public function getSensitivePoolKey(): ?AiApiKey
    {
        $keyId = $this->sensitive_ai_pool_key_id;
        if (empty($keyId)) {
            return null;
        }

        $key = AiApiKey::where('id', $keyId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('is_critical')->orWhere('is_critical', false);
            })
            ->first();

        return $key;
    }

    /**
     * เช็คว่ามี key purpose='sensitive' พร้อมใช้งานหรือไม่
     *
     * Block toggle Sensitive AI Mode ถ้า return false →
     * admin ต้องไปเพิ่ม key ใน pool ก่อน
     */
    public function hasAvailableSensitiveKey(): bool
    {
        try {
            return AiApiKey::forProvider($this->sensitive_provider ?? 'gemini')
                ->forPurpose('sensitive')
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('is_critical')->orWhere('is_critical', false);
                })
                ->exists();
        } catch (\Throwable $e) {
            // ai_api_keys table อาจ enum ยังไม่ migrate
            return false;
        }
    }

    /**
     * ดึงรายการ keys purpose='sensitive' ทั้งหมด (สำหรับ admin dropdown)
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, AiApiKey>
     */
    public function getAvailableSensitiveKeys()
    {
        try {
            return AiApiKey::forPurpose('sensitive')
                ->where('is_active', true)
                ->orderBy('provider')
                ->orderByDesc('priority')
                ->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /**
     * ดึง MiniMax group_id (จำเป็นสำหรับ T2A v2 endpoint)
     */
    public function getMinimaxGroupId(): ?string
    {
        if (! empty($this->minimax_group_id)) {
            return $this->minimax_group_id;
        }

        // อาจเก็บใน metadata ของ pool key
        try {
            $poolKey = AiApiKey::forProvider('minimax')
                ->forPurpose('tts')
                ->where('is_active', true)
                ->orderByDesc('priority')
                ->first();
            if ($poolKey && is_array($poolKey->metadata)) {
                return $poolKey->metadata['group_id'] ?? null;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return null;
    }
}
