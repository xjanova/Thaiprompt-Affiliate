<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * AI API Key Model
 *
 * จัดการ API Keys ของ AI Providers สำหรับ Pool Management
 *
 * @property int $id
 * @property string $name ชื่อ key
 * @property string $provider ผู้ให้บริการ (grok, openai, gemini, etc.)
 * @property string $api_key API Key (encrypted)
 * @property bool $is_active สถานะเปิด/ปิด
 * @property int $priority ลำดับความสำคัญ
 * @property int $tokens_used_today Tokens ใช้วันนี้
 * @property int $tokens_used_month Tokens ใช้เดือนนี้
 * @property int $tokens_used_total Tokens ใช้ทั้งหมด
 * @property int|null $tokens_limit_daily จำกัด tokens/วัน
 * @property int|null $tokens_limit_monthly จำกัด tokens/เดือน
 * @property int $requests_today Requests วันนี้
 * @property int $requests_minute Requests นาทีนี้
 * @property int|null $rate_limit_per_minute Rate limit
 * @property Carbon|null $last_used_at ใช้งานล่าสุด
 * @property int $consecutive_errors Errors ติดต่อกัน
 * @property string|null $last_error Error ล่าสุด
 * @property Carbon|null $last_error_at เวลา error ล่าสุด
 * @property Carbon|null $disabled_until ปิดใช้งานจนถึง
 * @property array|null $metadata ข้อมูลเพิ่มเติม
 * @property string|null $notes หมายเหตุ
 */
class AiApiKey extends Model
{
    use SoftDeletes;

    /**
     * ชื่อตาราง
     */
    protected $table = 'ai_api_keys';

    /**
     * Providers ที่รองรับ
     *
     * 🎙️ (2026-05-08) เพิ่ม minimax — ใช้สำหรับ TTS (text-to-speech)
     *    purpose='tts' จะ filter เฉพาะ key ที่ไว้ใช้สังเคราะห์เสียง
     */
    public const PROVIDERS = [
        'grok' => 'Grok (xAI)',
        'groq' => 'Groq',
        'openai' => 'OpenAI',
        'gemini' => 'Google Gemini',
        'qwen' => 'Qwen (Alibaba)',
        'openrouter' => 'OpenRouter',
        'anthropic' => 'Anthropic Claude',
        'deepseek' => 'DeepSeek',
        'typhoon' => 'Typhoon (SCB 10X)',
        'xiaomi' => 'Xiaomi MiMo',
        'minimax' => 'MiniMax (TTS / Audio)',
    ];

    /**
     * 🎯 (2026-05-01) Default base URL ต่อ provider — แต่ละ key สามารถ override ได้ผ่าน $key->base_url
     */
    public const DEFAULT_BASE_URLS = [
        'grok' => 'https://api.x.ai/v1',
        'groq' => 'https://api.groq.com/openai/v1',
        'openai' => 'https://api.openai.com/v1',
        'gemini' => 'https://generativelanguage.googleapis.com/v1beta',
        'qwen' => 'https://dashscope-intl.aliyuncs.com/compatible-mode/v1',
        'openrouter' => 'https://openrouter.ai/api/v1',
        'anthropic' => 'https://api.anthropic.com/v1',
        'deepseek' => 'https://api.deepseek.com/v1',
        'typhoon' => 'https://api.opentyphoon.ai/v1',
        'xiaomi' => 'https://api.xiaomimimo.com/v1',
        // 🎙️ (2026-05-08) MiniMax v2 endpoint — `.io` not `.chat`
        //   ก่อนหน้า api.minimax.chat = v1 (legacy) — ใช้ v2 ที่ api.minimax.io
        'minimax' => 'https://api.minimax.io/v1',
    ];

    /**
     * 🎯 (2026-05-01) Models ที่มีให้เลือกแต่ละ provider — สำหรับ admin dropdown
     *
     * ⚠️ Maintenance: เมื่อ provider ออกโมเดลใหม่ ให้เพิ่มที่นี่ — ลำดับแสดง = ลำดับใน array
     *    ตัวแรกของแต่ละ provider = default model
     */
    public const MODELS_BY_PROVIDER = [
        'grok' => [
            // 🆕 (2026-05-22) Grok 4 — newest generation (mid-2026)
            'grok-4-latest',                  // ⭐ flagship — รุ่นใหม่สุด
            'grok-4',                         // stable snapshot
            'grok-4-fast',                    // low-latency variant
            // Grok 3 — reasoning models (Feb 2026)
            'grok-3-latest',                  // ⭐ apidog/xAI default (recommended)
            'grok-3',                         // stable
            'grok-3-mini',                    // ถูก + เร็ว
            'grok-3-mini-fast',               // mini optimized for speed
            'grok-3-fast',                    // full-size, optimized latency
            // 🚫 (2026-05-22) Grok 2 series + grok-beta — DEPRECATED by xAI (400 "Model not found")
            //   ลบออกจาก dropdown แล้ว — migration 2026_05_22_210000 อัปเดต DB อัตโนมัติ
        ],
        'groq' => [
            // 🚫 (2026-05-24) ลบ models ที่ Groq ลบไปแล้ว — verify จาก console.groq.com/docs/models
            //   - llama-3.1-70b-versatile          (shutdown 2025-01-24 → llama-3.3-70b-versatile)
            //   - meta-llama/llama-4-maverick-...  (shutdown 2026-03-09 → openai/gpt-oss-120b)
            //   - gemma2-9b-it                     (shutdown 2025-10-08 → llama-3.1-8b-instant)
            //   - mixtral-8x7b-32768               (shutdown 2025-03-20 → llama-3.3-70b-versatile)
            //   ลบจาก dropdown แล้ว — migration 2026_05_24_120000 อัปเดต DB อัตโนมัติ
            'llama-3.3-70b-versatile',                          // ⭐ production default
            'llama-3.1-8b-instant',                             // production — เร็ว+ถูก
            'meta-llama/llama-4-scout-17b-16e-instruct',        // preview — Llama 4 Scout
            'qwen/qwen3-32b',                                   // preview — Qwen 3
            'openai/gpt-oss-120b',                              // production — flagship
            'openai/gpt-oss-20b',                               // production — เร็วสุด 1000 t/s
        ],
        'openai' => [
            // 🔬 (2026-08-17) Verified live กับ key prod (id 37) บน /v1/responses:
            //   ✅ valid : gpt-5.6-luna / gpt-5.6-terra / gpt-5.6-sol (ยิงจริงผ่านทั้ง 3, effort=low → reasoning 0 token)
            //   ✅ valid : gpt-5.4-mini / gpt-5.4-nano / gpt-5.4 / gpt-5.5 / gpt-5.5-pro / gpt-5-mini / gpt-5 / gpt-4o*
            //   ❌ 400 model_not_found : gpt-5.5-mini  ← ไม่มีจริง! ลบออกแล้ว (เคยอยู่ใน dropdown ผิด)
            //   ราคา/1M tokens (in/out · cached-in = 10% ของ in):
            //     5.6-luna=$0.20/$1.20 · 5.6-terra=$2/$12 · 5.6-sol=$5/$30   (Terra+Luna ลดราคา 30 ก.ค. 2026)
            //     5.5=$5/$30 · 5.5-pro=$30/$180 · 5.4=$2.50/$15 · 5.4-mini=$0.75/$4.50 · 5.4-nano=$0.20/$1.25
            //   ⚠️ 5.6 ทุกตัว: context 1.05M / max output 128K — แต่คำขอที่ input เกิน 272K
            //      จะถูกคิด input 2× และ output 1.5× "ทั้งคำขอ" (ของเราสูงสุด ~12K → ปลอดภัย)
            //   ⚠️ ตัวแรก = default ของ resolveModel() เมื่อ key ไม่ได้ตั้ง model →
            //      วาง "ตัวคุ้มราคา" (5.6-luna) ไว้แรก กัน key ใหม่เผลอ default เป็นตัวแพงสุด
            //
            // GPT-5.6 family (2026) — ⭐ generation ปัจจุบัน
            //   🚫 ข้าม Terra: Artificial Analysis ยืนยัน Sol กับ Luna นำ Terra "ทุกจุด" บนกราฟ intelligence-vs-cost
            //      (Luna max=52 ชนะ Terra high=50 ทั้งที่ถูกกว่า 10 เท่า) — คงไว้ใน dropdown เพื่อความครบ แต่ไม่แนะนำ
            'gpt-5.6-luna',                   // ⭐ DEFAULT — ถูกสุด+ฉลาดกว่า 5.4-mini ($0.20/$1.20, AA index 52)
            'gpt-5.6-sol',                    // flagship 5.6 ($5/$30, AA index 59) — ใช้กับ sensitive/งานยากจริง
            'gpt-5.6-terra',                  // ⚠️ dominated by luna+sol ($2/$12, AA index 55) — ไม่แนะนำ
            // GPT-5.4 family — gen ก่อนหน้า (ยังใช้ได้ แต่แพงกว่า+ฉลาดน้อยกว่า luna)
            'gpt-5.4-mini',                   // เดิม DEFAULT ($0.75/$4.50, AA index ~41)
            'gpt-5.4',                        // workhorse คุณภาพสูง ($2.50/$15)
            'gpt-5.4-nano',                   // ($0.20/$1.25) — ราคาพอกัน luna แต่ฉลาดน้อยกว่าชัดเจน → ไม่มีเหตุผลเลือก
            'gpt-5.4-pro',                    // 5.4 ตัวฉลาดสุด
            // GPT-5.5 family — flagship ปัจจุบัน (แพง — ใช้เมื่อต้องการคุณภาพสูงสุด)
            'gpt-5.5',                        // flagship multimodal ($5/$30)
            'gpt-5.5-pro',                    // ฉลาดสุด reasoning+agentic ($30/$180 — แพงมาก)
            // 🗑️ (2026-05-29) ลบ 'gpt-5.5-mini' — OpenAI ไม่มีรุ่นนี้ (ยืนยัน 400 model_not_found)
            //    migration 2026_05_29_120000 รีแมป DB row ที่ติด gpt-5.5-mini → gpt-5.4-mini อัตโนมัติ
            // Legacy GPT-5 (ส.ค. 2025) — ยังใช้ได้ แต่ gen เก่ากว่า 5.4
            'gpt-5',
            'gpt-5-mini',
            // GPT-4 family — stable (ใช้ได้ตลอด, ถูก แต่ไม่ใช่ reasoning gen → อ่อนกับงาน premium)
            'gpt-4o',                         // $2.50/$10
            'gpt-4o-mini',                    // $0.15/$0.60 — ถูกสุด
            'gpt-4-turbo',
            'gpt-4',
            'gpt-3.5-turbo',
            // o-series reasoning models
            'o1-preview',
            'o1-mini',
            'o3-mini',
            // 🎙️ TTS models — เลือกได้ถ้า key purpose='tts'
            'gpt-4o-mini-tts',
            'tts-1',
            'tts-1-hd',
        ],
        'gemini' => [
            // 🚫 (2026-05-24) ลบ Gemini 2.0/1.5 ที่ Google deprecated — verify ai.google.dev/gemini-api/docs/models
            //   - gemini-2.0-flash-001 / -lite / -exp → "will be shut down soon"
            //   - gemini-1.5-flash / -flash-8b / -pro → not listed (likely removed)
            //   migration 2026_05_24_120000 อัปเดต DB rows อัตโนมัติ → gemini-2.5-flash
            //
            // 🚫 (2026-05-26) ลบ gemini-3.1-flash-lite-preview — Google shut down แล้ว
            //   error: "This model models/gemini-3.1-flash-lite-preview is no longer available"
            //   ใช้ stable version แทน: gemini-3.1-flash-lite (drop `-preview` suffix)
            //   migration 2026_05_26_140000 อัปเดต DB rows อัตโนมัติ → gemini-3.1-flash-lite
            //
            // 🚫 (2026-05-26 v2) ลบ ALL preview models — admin directive "ใช้ได้จริงเท่านั้น"
            //   Google docs: "Most production apps should use a specific stable model"
            //                 "Preview models will be deprecated with at least 2 weeks notice"
            //   ลบ gemini-3.1-pro-preview + gemini-3-flash-preview (no stable Gemini 3 Pro yet)
            //   → ใช้ gemini-2.5-pro สำหรับ reasoning, gemini-3.5-flash สำหรับ frontier flash
            //   migration 2026_05_26_150000 อัปเดต DB rows: preview → gemini-2.5-pro

            // ⭐ Stable models only — production-grade, ไม่มีความเสี่ยง shutdown 2-week notice
            // Gemini 3.x stable (Q2 2026 release)
            'gemini-3.5-flash',               // ⭐ frontier flash — multimodal/agentic, รุ่นใหม่สุด
            'gemini-3.1-flash-lite',          // ⭐ ถูกที่สุด, high-volume

            // Gemini 2.5 stable (legacy แต่ใช้ได้นาน)
            'gemini-2.5-pro',                 // ⭐ ฉลาดสุด — deep reasoning, แทน 3.1-pro-preview
            'gemini-2.5-flash',               // ⭐ balanced — default ปลอดภัย ใช้กันแพร่หลายในระบบเรา
            'gemini-2.5-flash-lite',          // ถูก
        ],
        'qwen' => [
            'qwen-max',
            'qwen-plus',
            'qwen-turbo',
            'qwen2.5-72b-instruct',
            'qwen2.5-coder-32b-instruct',
        ],
        'openrouter' => [
            // 🔄 (2026-05-02) อัปเดตชื่อ model ตาม OpenRouter ปัจจุบัน
            //   ตัวเก่า "anthropic/claude-3.5-sonnet" ถูก deprecate → 404
            'anthropic/claude-sonnet-4',          // ⭐ Claude Sonnet 4 — แทน 3.5-sonnet
            'anthropic/claude-haiku-4.5',         // ⭐ Claude Haiku 4.5 — เร็ว+ถูก
            'anthropic/claude-3-5-sonnet',        // hyphenated alias (ถ้ายังมี)
            'anthropic/claude-3.7-sonnet',
            'openai/gpt-4o',
            'openai/gpt-4o-mini',
            'meta-llama/llama-3.3-70b-instruct',
            'meta-llama/llama-3.1-405b-instruct',
            'google/gemini-2.0-flash-001',        // ⭐ stable แทน :free experimental
            'mistralai/mistral-large',
            'deepseek/deepseek-chat',
            'x-ai/grok-2-1212',
        ],
        'anthropic' => [
            'claude-opus-4-7',
            'claude-sonnet-4-6',
            'claude-haiku-4-5',
            'claude-opus-4',
            'claude-sonnet-4',
            'claude-3-5-sonnet-20241022',
            'claude-3-5-haiku-20241022',
            'claude-3-opus-20240229',
        ],
        'deepseek' => [
            'deepseek-chat',
            'deepseek-coder',
            'deepseek-reasoner',
        ],
        'typhoon' => [
            'typhoon-v2-70b-instruct',
            'typhoon-v2-8b-instruct',
            'typhoon-v1.5x-70b-instruct',
        ],
        'xiaomi' => [
            'mimo-v2.5-pro',
            'mimo-v2-pro',
            'mimo-v2.5',
            'mimo-v2-omni',
            'mimo-v2-flash',
        ],
        // 🎙️ (2026-05-08) MiniMax TTS — text-to-audio
        //   ใช้กับ purpose='tts' — สังเคราะห์เสียงสรุปคำทำนาย Celtic 99฿
        //   📚 https://platform.minimax.io/docs/api-reference/speech-t2a-http
        //   v2 endpoint = https://api.minimax.io/v1/t2a_v2 (ไม่ต้อง group_id ใน URL แล้ว)
        //   models เรียงจากใหม่→เก่า — ใหม่กว่า = quality ดีกว่า + ราคาถูกกว่า ตามทั่วไป
        'minimax' => [
            'speech-2.8-hd',          // ⭐ ใหม่สุด — HD quality (current generation)
            'speech-2.8-turbo',       // ⭐ ใหม่สุด — turbo (เร็ว+ถูก)
            'speech-2.6-hd',          // gen ก่อน 2.8
            'speech-2.6-turbo',
            'speech-02-hd',           // older HD
            'speech-02-turbo',        // older turbo
            'speech-01-hd',           // legacy
            'speech-01-turbo',        // legacy
        ],
    ];

    /**
     * 🎯 (2026-05-22) Smart RPM defaults สำหรับ FREE tier (provider + model aware)
     *
     * ใช้เมื่อ admin ไม่ตั้ง rate_limit_per_minute (NULL):
     *   - NULL → smart default (สมมติว่าเป็น free key)
     *   - 0    → ไม่เช็ค RPM เลย (admin บอกชัดว่า unlimited / paid generous)
     *   - N    → ใช้ N (admin ตั้งเอง)
     *
     * เป้าหมาย: ป้องกันยิงเกิน free quota → 429 น้อยลง → free key ยืนยาว
     *           → ไม่ drift ไป paid key (ที่ admin ตั้ง priority ต่ำเป็น backup)
     *
     * ⚠️ Paid tier: admin ต้องตั้ง rate_limit_per_minute เอง (เช่น 1000 RPM)
     *    ไม่งั้นจะถูก throttle เท่า free tier!
     *
     * อ้างอิงฟรี tier จริง (verified 2026-05):
     *   - Groq: https://console.groq.com/docs/rate-limits (ส่วนมาก 30 RPM)
     *   - Gemini: https://ai.google.dev/gemini-api/docs/rate-limits
     *
     * ค่าในตารางลบ safety margin 5-10% — กัน boundary 429
     */
    /**
     * 🪙 (2026-05-23 Phase 5) TPM (Tokens Per Minute) free-tier limits
     *
     * พบ 2026-05-23: Groq llama-3.3-70b TPM=6000 แต่ avg/call=7946 tokens
     * → ทะลุตั้งแต่ call แรก! RPM check ที่ 28 ไม่ช่วย (TPM ตันก่อน)
     *
     * Strategy: ก่อน acquire ดู accumulated TPM ของ key — ถ้า > 90% limit → skip
     * (แม้ยังไม่ทะลุ ก็ส่งไปคีย์อื่น เผื่อ next call ทะลุ)
     *
     * Verified 2026-05 (free tier):
     */
    public const MODEL_TPM_FREE_TIER = [
        'groq' => [
            // 🚫 (2026-05-24) ลบ entries ของ models ที่ Groq ลบไปแล้ว (ดู MODELS_BY_PROVIDER comment)
            //
            // 🔧 (2026-05-24 v2) Verified จาก https://console.groq.com/docs/rate-limits — ค่าจริง free tier
            //    ก่อนหน้านี้ผิด: 8b-instant=30000 (จริง=6000), 70b-versatile=6000 (จริง=12000)
            //    → 8b ทะลุง่ายมาก → 413 "Request too large"
            //    → 70b ถูก filter ทิ้งเร็วเกิน เพราะคิดว่าทะลุที่ 6000
            '_default' => 6000,
            'llama-3.3-70b-versatile' => 12000,                  // ✅ verified Groq free
            'llama-3.1-8b-instant' => 6000,                      // ✅ verified — ก่อนหน้านี้ใส่ 30000 ผิด!
            'meta-llama/llama-4-scout-17b-16e-instruct' => 30000, // ✅ verified — สูงสุด, เหมาะ heavy prompts
            'qwen/qwen3-32b' => 6000,                            // ✅ verified preview
            'openai/gpt-oss-120b' => 8000,                       // ✅ verified
            'openai/gpt-oss-20b' => 8000,                        // ✅ verified
        ],
        'gemini' => [
            // Gemini ใช้ Project quota เป็นหลัก (RPD/TPD) — TPM ไม่ใช่ bottleneck หลัก
            // แต่ใส่ไว้กันเหนียว
            // 🚫 (2026-05-24) ลบ entries 2.0/1.5 ที่ Google deprecated
            '_default' => 250000,
            // Gemini 3.x stable
            'gemini-3.5-flash' => 250000,
            'gemini-3.1-flash-lite' => 250000,
            // 🚫 (2026-05-26 v2) ลบ ALL preview models — production-grade only
            //   - gemini-3.1-pro-preview, gemini-3-flash-preview, gemini-3.1-flash-lite-preview shut down
            // Gemini 2.5 stable
            'gemini-2.5-pro' => 250000,
            'gemini-2.5-flash' => 250000,
            'gemini-2.5-flash-lite' => 250000,
        ],
        'grok' => ['_default' => 100000],
        'openai' => ['_default' => 200000],                  // Tier 1
        'openrouter' => ['_default' => 60000],
        'deepseek' => ['_default' => 200000],
        'qwen' => ['_default' => 60000],
        'typhoon' => ['_default' => 60000],
        'xiaomi' => ['_default' => 60000],
        'anthropic' => ['_default' => 40000],
        'minimax' => ['_default' => 60000],
    ];

    public const MODEL_RPM_FREE_TIER = [
        'groq' => [
            // 🚫 (2026-05-24) ลบ entries ของ models ที่ Groq ลบไปแล้ว (ดู MODELS_BY_PROVIDER comment)
            // 🔧 (2026-05-24 v2) Verified Groq free RPM — ทุกตัว 30 ยกเว้น qwen3-32b = 60
            '_default' => 28,                                      // 30 actual - safety 5%
            'llama-3.3-70b-versatile' => 28,
            'llama-3.1-8b-instant' => 28,
            'meta-llama/llama-4-scout-17b-16e-instruct' => 28,
            'qwen/qwen3-32b' => 55,                                // ⭐ 60 actual — ดี ใช้สลับเป็น overflow
            'openai/gpt-oss-120b' => 28,
            'openai/gpt-oss-20b' => 28,
        ],
        'gemini' => [
            // 🚫 (2026-05-24) ลบ entries 2.0/1.5 ที่ Google deprecated/removed
            '_default' => 9,                                       // เริ่มที่ 2.5 Flash
            // Gemini 3.x stable
            'gemini-3.5-flash' => 9,                               // 10 actual
            'gemini-3.1-flash-lite' => 14,                         // 15 actual
            // 🚫 (2026-05-26 v2) ลบ ALL preview models — production-grade only
            //   - gemini-3.1-pro-preview, gemini-3-flash-preview, gemini-3.1-flash-lite-preview shut down
            // Gemini 2.5 stable
            'gemini-2.5-pro' => 4,                                 // 5 actual
            'gemini-2.5-flash' => 9,                               // 10 actual
            'gemini-2.5-flash-lite' => 14,                         // 15 actual
        ],
        'grok' => ['_default' => 55],                              // xAI ~60 RPM
        'openai' => ['_default' => 450],                           // Tier 1 ~500 RPM
        'openrouter' => ['_default' => 18],                        // free ~20 RPM
        'deepseek' => ['_default' => 55],
        'qwen' => ['_default' => 28],                              // HF router ~30 RPM
        'typhoon' => ['_default' => 55],
        'xiaomi' => ['_default' => 55],
        'anthropic' => ['_default' => 45],                         // Tier 1 ~50 RPM
        'minimax' => ['_default' => 18],
    ];

    /**
     * 🎯 (2026-05-02) วัตถุประสงค์การใช้ key
     *
     * 🚫 (2026-05-24) ลบ 'any' ออกจาก dropdown — User: "เอา 'ใช้ทุกอย่าง' ออกไปด้วย"
     *                Pool service ban 'any' อยู่แล้ว (commit 2026-05-23) — UI ตามไปด้วย
     *                key เดิมใน DB ที่ purpose='any' จะแสดง badge ⚠️[DEPRECATED any]
     *                และ Pool reject ก่อน group tier (AiApiKeyPoolService:533)
     *
     * prediction = เฉพาะคำทำนาย deep readings (paid Deep 39 / Celtic 99)
     * chat       = เฉพาะ chat conversation (กัน prediction ดูดหมด)
     * free_card  = (2026-05-05) เฉพาะทำนายฟรีหลัง DM react/comment
     *              เจาะจงกว่า 'prediction' — priority สูงกว่าตอนเลือก key
     * sensitive  = 🌟 (2026-05-07) เฉพาะบริบทละเอียดอ่อน (Pro model only)
     *              ใช้เมื่อลูกค้าอารมณ์ร้าย / คำหยาบ / คำถามซับซ้อน /
     *              หัวข้อหนัก (ตาย/ป่วย/หย่า/ฆ่าตัวตาย)
     *              ⚠️ STRICT scope — ไม่ fallback ไป prediction
     *              เพราะ Pro model แพง 5-15 เท่าของ default
     */
    public const PURPOSES = [
        'chat' => '💬 แชทสนทนา (คุยกับลูกค้าทั่วไป)',
        // 💙 (2026-05-23) Paid chat — last resort หลัง free chat + any หมด
        //    STRICT scope (เหมือน 'sensitive') — caller=null ไม่ใช้
        //    เผา quota paid; caller='chat' เท่านั้นที่จะถึงได้
        'chat_paid' => '💙 แชทพิเศษ (Paid Chat — last resort หลัง free หมด)',
        'prediction' => '🔮 ทำนายทั่วไป (ใช้ทั้ง Deep 39 + Celtic 99 ถ้าไม่แยก)',
        // 🎯 (2026-05-13) แยกตามแพคเกจ — admin มาร์คได้ละเอียด
        'prediction_deep' => '🌟 ทำนาย Deep 39฿ เฉพาะ (เน้น speed)',
        'prediction_celtic' => '💎 ทำนาย Celtic 99฿ เฉพาะ (เน้นคุณภาพ — ลูกค้าจ่ายแพง)',
        'free_card' => '🎁 ทำนายฟรี 1 ใบ (หลัง DM — สงวน paid keys)',
        'sensitive' => '🌟 STRICT — Pro Session (Sensitive AI / Bill / Celtic Premium)',
        // 🎙️ (2026-05-08) Voice synthesis — Celtic 99฿ summary audio
        //    STRICT scope (เหมือน 'sensitive') — ไม่ fallback ไป any
        //    เหตุผล: TTS API คนละ schema — ใช้ key ผิดทางจะ fail แน่ๆ
        'tts' => '🎙️ STRICT — TTS (สังเคราะห์เสียง MiniMax/OpenAI)',
    ];

    /**
     * โหมดการวนใช้
     */
    public const ROTATION_MODES = [
        'smart' => 'Smart (⭐ กระจาย load อัจฉริยะ — แนะนำ)',
        'round_robin' => 'Round Robin (วนตามลำดับ)',
        'least_used' => 'Least Used (ใช้น้อยสุดก่อน)',
        'priority' => 'Priority (ตาม priority สูง→ต่ำ)',
        'random' => 'Random (สุ่ม)',
        'failover' => 'Failover (ใช้ตัวหลัก, สำรองเมื่อ error)',
    ];

    /**
     * คอลัมน์ที่สามารถ mass assign ได้
     */
    protected $fillable = [
        'name',
        'provider',
        'model',                  // 🎯 (2026-05-01) Per-key model override
        'base_url',               // 🌐 (2026-05-01) Per-key base URL override
        'api_key',
        'is_active',
        'is_critical',            // 🔴 (2026-05-01) 3-strikes critical state
        'priority',
        'purpose',                // 🎯 (2026-05-02) any/prediction/chat
        'tokens_used_today',
        'tokens_used_month',
        'tokens_used_total',
        'tokens_limit_daily',
        'tokens_limit_monthly',
        'requests_today',
        'requests_minute',
        'rate_limit_per_minute',
        'last_rate_limit_reset',
        'last_used_at',
        'consecutive_errors',
        'error_check_attempts',   // 🩺 (2026-05-01) จำนวนครั้ง re-check หลัง disabled
        'last_health_check_at',   // 🩺 (2026-05-01) เวลา health check ล่าสุด
        'last_test_passed_at',    // 🛡️ (2026-05-13) เทส connectivity ผ่านล่าสุด — gate ใน scopeAvailable
        'last_test_failed_at',    // 🛡️ (2026-05-13) เทส fail ล่าสุด (track for re-test)
        'last_test_message',      // 🛡️ (2026-05-13) ข้อความ test ล่าสุด (success/error)
        'last_error',
        'last_error_at',
        'disabled_until',
        'metadata',
        'notes',
        'tokens_reset_date',
        // 🩺 (2026-05-07) Auto-recheck banned keys
        'last_recheck_at',
        'recheck_failure_count',
        'next_recheck_at',
        'auto_recovered_at',
    ];

    /**
     * การ cast ประเภทข้อมูล
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_critical' => 'boolean',
        'priority' => 'integer',
        'tokens_used_today' => 'integer',
        'tokens_used_month' => 'integer',
        'tokens_used_total' => 'integer',
        'tokens_limit_daily' => 'integer',
        'tokens_limit_monthly' => 'integer',
        'requests_today' => 'integer',
        'requests_minute' => 'integer',
        'rate_limit_per_minute' => 'integer',
        'consecutive_errors' => 'integer',
        'error_check_attempts' => 'integer',
        'last_rate_limit_reset' => 'datetime',
        'last_used_at' => 'datetime',
        'last_error_at' => 'datetime',
        'last_health_check_at' => 'datetime',
        'last_test_passed_at' => 'datetime',     // 🛡️ (2026-05-13)
        'last_test_failed_at' => 'datetime',     // 🛡️ (2026-05-13)
        'disabled_until' => 'datetime',
        'metadata' => 'array',
        'tokens_reset_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        // 🩺 (2026-05-07) Auto-recheck banned keys
        'last_recheck_at' => 'datetime',
        'recheck_failure_count' => 'integer',
        'next_recheck_at' => 'datetime',
        'auto_recovered_at' => 'datetime',
    ];

    /**
     * ค่าเริ่มต้น
     */
    protected $attributes = [
        'is_active' => true,
        'priority' => 0,
        'tokens_used_today' => 0,
        'tokens_used_month' => 0,
        'tokens_used_total' => 0,
        'requests_today' => 0,
        'requests_minute' => 0,
        'consecutive_errors' => 0,
    ];

    // ============================================================
    // Accessors & Mutators
    // ============================================================

    /**
     * Encrypt API key เมื่อบันทึก
     */
    public function setApiKeyAttribute($value): void
    {
        $this->attributes['api_key'] = Crypt::encryptString($value);
    }

    /**
     * Decrypt API key เมื่อดึงข้อมูล
     */
    public function getApiKeyAttribute($value): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * ดึงชื่อ provider แบบเต็ม
     */
    public function getProviderNameAttribute(): string
    {
        return self::PROVIDERS[$this->provider] ?? $this->provider;
    }

    /**
     * ดึง masked API key สำหรับแสดงผล
     */
    public function getMaskedKeyAttribute(): string
    {
        $key = $this->api_key;
        $length = strlen($key);

        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($key, 0, 4).str_repeat('*', $length - 8).substr($key, -4);
    }

    /**
     * 🩹 (2026-05-05) ดึง requests/minute แบบ real-time จาก Cache (TTL 60s)
     *   ใช้แทน column requests_minute ที่ DB อาจ stale (sync ตอน recordUsage เท่านั้น)
     *   เคสที่ค่า real-time ต่างจาก DB:
     *     - Cache TTL 60s — ค่าใน cache รีเซ็ตเองทุกนาที (DB column ไม่)
     *     - หลัง recordUsage ล่าสุด เกิน 60s แต่ DB ยังเก็บค่าเดิม → DB stale
     *
     * @return int RPM ปัจจุบัน (real-time)
     */
    public function getCurrentRpmAttribute(): int
    {
        return (int) Cache::get("pool:rpm:{$this->provider}:{$this->id}", 0);
    }

    /**
     * 🩹 (2026-05-05) ดึง concurrent in-flight requests แบบ real-time
     */
    public function getCurrentInflightAttribute(): int
    {
        return (int) Cache::get("pool:inflight:{$this->provider}:{$this->id}", 0);
    }

    /**
     * คำนวณ % การใช้งาน tokens วันนี้
     */
    public function getDailyUsagePercentAttribute(): ?float
    {
        if (! $this->tokens_limit_daily) {
            return null;
        }

        return min(100, round(($this->tokens_used_today / $this->tokens_limit_daily) * 100, 1));
    }

    /**
     * คำนวณ % การใช้งาน tokens เดือนนี้
     */
    public function getMonthlyUsagePercentAttribute(): ?float
    {
        if (! $this->tokens_limit_monthly) {
            return null;
        }

        return min(100, round(($this->tokens_used_month / $this->tokens_limit_monthly) * 100, 1));
    }

    /**
     * คำนวณ tokens ที่เหลือวันนี้
     */
    public function getDailyTokensRemainingAttribute(): ?int
    {
        if (! $this->tokens_limit_daily) {
            return null;
        }

        return max(0, $this->tokens_limit_daily - $this->tokens_used_today);
    }

    /**
     * คำนวณ tokens ที่เหลือเดือนนี้
     */
    public function getMonthlyTokensRemainingAttribute(): ?int
    {
        if (! $this->tokens_limit_monthly) {
            return null;
        }

        return max(0, $this->tokens_limit_monthly - $this->tokens_used_month);
    }

    // ============================================================
    // Relationships
    // ============================================================

    /**
     * ความสัมพันธ์กับ usage logs
     */
    public function usageLogs(): HasMany
    {
        return $this->hasMany(AiApiKeyUsageLog::class);
    }

    // ============================================================
    // Scopes
    // ============================================================

    /**
     * Scope: เฉพาะ keys ที่ active
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: เฉพาะ provider ที่ระบุ
     */
    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope: Keys ที่พร้อมใช้งาน
     *  - active = true
     *  - is_critical = false (🔴 critical keys ไม่ใช้อัตโนมัติ — 2026-05-01)
     *  - ไม่ถูก disable ชั่วคราว
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('is_critical')
                    ->orWhere('is_critical', false);
            })
            ->where(function ($q) {
                $q->whereNull('disabled_until')
                    ->orWhere('disabled_until', '<=', now());
            })
            // 🛡️ (2026-05-13) Health gate — ต้องเทสผ่านมาแล้ว
            //   user spec: "ทุกคีย์ต้องเทสผ่านมาแล้วจึงจะนำมาลงสนาม"
            //   filter: last_test_passed_at IS NOT NULL
            //   ⚠️ ถ้า migration ยังไม่รัน → column ไม่มี → ใช้ try/catch ใน caller
            //      หรือ admin คลิก "ทดสอบ" ใน UI ก่อนใช้
            ->whereNotNull('last_test_passed_at');
    }

    /**
     * 🎯 (2026-05-02) Scope: filter ตาม purpose (hierarchical)
     *
     * @param  string  $purpose  'prediction' / 'chat' / 'free_card' / 'sensitive'
     *
     * Hierarchy (เจาะจงสูง → ทั่วไป):
     *   - 'sensitive'         → 🌟 STRICT: match ['sensitive'] เท่านั้น
     *                           ❌ ไม่ fallback ไป any/prediction (Pro model only)
     *                           เหตุผล: Pro model แพง 5-15 เท่า — ไม่ควร burn budget
     *                           เมื่อ admin ไม่ได้ตั้งค่า key sensitive ไว้
     *                           Caller ต้องเช็ค null + fallback เอง
     *   - 'tts'               → STRICT: match ['tts'] (API schema คนละแบบ)
     *   - 'prediction_deep'   → 🆕 match [prediction_deep, prediction, any, null]
     *                           (Deep 39฿ — ถ้ามาร์คเจาะจงใช้, ไม่งั้น fallback prediction)
     *   - 'prediction_celtic' → 🆕 match [prediction_celtic, prediction, any, null]
     *                           (Celtic 99฿ — ลูกค้าจ่ายแพง ใช้ key คุณภาพดีได้)
     *   - 'free_card'         → match [free_card, prediction, any, null]
     *                           เพราะ free_card เป็น subset ของ prediction
     *   - 'prediction'        → match [prediction, any, null]
     *                           (legacy — ใช้ทั้ง Deep + Celtic ถ้าไม่แยก)
     *   - 'chat'              → match [chat, any, null]
     *                           (ไม่ใช้ sensitive key — Pro แพง สงวน)
     *
     * 🩹 (2026-05-05) Update — รองรับ free_card hierarchy
     * 🌟 (2026-05-07) Update — รองรับ sensitive (strict, no fallback)
     * 🎯 (2026-05-13) Update — แยก prediction_deep + prediction_celtic
     */
    public function scopeForPurpose($query, string $purpose)
    {
        // 🚫 (2026-05-23) BAN purpose='any' ใน fallback ทุกที่ — User spec:
        //   "เอา purpose any ออกไปเลย — มันเป็นสาเหตุที่ทำให้ มั่วกันเยอะ"
        //   เคสจริง: OpenAI key purpose='any' priority สูง → ทุก request ที่ purpose
        //   specific หมด/429 จะ fallback มาที่นี่ → admin "ตั้ง OpenAI ใช้แค่ Celtic"
        //   แต่ token รั่วไป Deep/Chat ทำให้ quota หมดเร็ว
        //   Pool ต้องห้าม pick 'any' — caller ต้องระบุ purpose ตรงเสมอ
        $query->where('purpose', '!=', 'any');

        // 🌟 sensitive = STRICT scope (ไม่ fallback)
        // เหตุผล: Pro model แพง 5-15x — ไม่ใช้ key อื่นแทน
        if ($purpose === 'sensitive') {
            return $query->where('purpose', 'sensitive');
        }

        // 🎙️ (2026-05-08) tts = STRICT scope (ไม่ fallback)
        //    เหตุผล: TTS API คนละ schema (request/response format) — ใช้ key chat/prediction
        //    มาทำ TTS จะ fail ทั้งหมด. caller ต้อง handle null + fallback ไป provider ฟรี
        if ($purpose === 'tts') {
            return $query->where('purpose', 'tts');
        }

        // 💙 (2026-05-23) chat_paid = STRICT scope (เรียกตรงเท่านั้น)
        if ($purpose === 'chat_paid') {
            return $query->where('purpose', 'chat_paid');
        }

        // 🎯 (2026-05-13) prediction_deep — Deep 39฿ เจาะจง
        //   Fallback chain ใหม่ (2026-05-23): prediction_deep → prediction → null
        //   🚫 ลบ 'any' ออก
        if ($purpose === 'prediction_deep') {
            return $query->where(function ($q) {
                $q->whereNull('purpose')
                    ->orWhereIn('purpose', ['prediction', 'prediction_deep']);
            });
        }

        // 🎯 (2026-05-13) prediction_celtic — Celtic 99฿ เจาะจง (ลูกค้าจ่ายแพง คุณภาพสูง)
        //   Fallback chain ใหม่ (2026-05-23): prediction_celtic → prediction → null
        if ($purpose === 'prediction_celtic') {
            return $query->where(function ($q) {
                $q->whereNull('purpose')
                    ->orWhereIn('purpose', ['prediction', 'prediction_celtic']);
            });
        }

        // free_card เป็น subset ของ prediction → fallback ให้ prediction key ทำได้
        if ($purpose === 'free_card') {
            return $query->where(function ($q) {
                $q->whereNull('purpose')
                    ->orWhereIn('purpose', ['prediction', 'free_card']);
            });
        }

        // prediction = ห้าม free_card / sensitive / deep / celtic key (สงวนไว้เฉพาะของมัน)
        if ($purpose === 'prediction') {
            return $query->where(function ($q) {
                $q->whereNull('purpose')
                    ->orWhere('purpose', 'prediction');
            });
        }

        // 💬 (2026-05-23) chat = match [chat, null, chat_paid]
        //    chat_paid = Tier 3 last-resort fallback ใน acquireKeyAnyProvider
        if ($purpose === 'chat') {
            return $query->where(function ($q) {
                $q->whereNull('purpose')
                    ->orWhereIn('purpose', ['chat', 'chat_paid']);
            });
        }

        // อื่นๆ = match purpose ตรง + null เท่านั้น (ห้าม any)
        return $query->where(function ($q) use ($purpose) {
            $q->whereNull('purpose')
                ->orWhere('purpose', $purpose);
        });
    }

    /**
     * Scope: เรียงตาม priority (สูง → ต่ำ)
     */
    public function scopeOrderByPriority($query)
    {
        return $query->orderByDesc('priority');
    }

    /**
     * Scope: เรียงตามการใช้งานน้อยสุด
     */
    public function scopeOrderByLeastUsed($query)
    {
        return $query->orderBy('tokens_used_today');
    }

    // ============================================================
    // Methods
    // ============================================================

    /**
     * ตรวจสอบว่า key พร้อมใช้งานหรือไม่
     */
    public function isAvailable(): bool
    {
        // ไม่ active
        if (! $this->is_active) {
            return false;
        }

        // 🔴 (2026-05-01) Critical = ไม่ใช้อัตโนมัติ
        if ($this->is_critical) {
            return false;
        }

        // ถูก disable ชั่วคราว
        if ($this->disabled_until && $this->disabled_until > now()) {
            return false;
        }

        // เกิน daily limit
        if ($this->tokens_limit_daily && $this->tokens_used_today >= $this->tokens_limit_daily) {
            return false;
        }

        // เกิน monthly limit
        if ($this->tokens_limit_monthly && $this->tokens_used_month >= $this->tokens_limit_monthly) {
            return false;
        }

        // ⭐ เกิน per-key rate limit ต่อนาที (ใช้ Cache real-time)
        //   🎯 (2026-05-22) ใช้ getEffectiveRpmLimit() แทนการอ่าน field ตรง
        //     - admin set N    → ใช้ N
        //     - admin set 0    → return 0 = unlimited (ข้าม check)
        //     - admin set NULL → smart default ตาม provider+model (free tier)
        //   เดิม: ถ้า NULL จะข้าม check → key ที่ admin ลืมตั้งก็ยิงไม่จำกัด → 429
        $rpmLimit = $this->getEffectiveRpmLimit();
        if ($rpmLimit > 0) {
            $rpm = (int) Cache::get("pool:rpm:{$this->provider}:{$this->id}", 0);
            if ($rpm >= $rpmLimit) {
                return false;
            }
        }

        return true;
    }

    // ============================================================
    // 🎯 (2026-05-01) Per-key model + base URL resolution
    // ============================================================

    /**
     * คืน model ที่จะใช้สำหรับ key นี้
     *
     * Priority:
     *   1. $this->model (per-key override)
     *   2. ตัวแรกของ MODELS_BY_PROVIDER[provider]
     *   3. null
     */
    public function resolveModel(): ?string
    {
        if (! empty($this->model)) {
            return $this->model;
        }

        $list = self::MODELS_BY_PROVIDER[$this->provider] ?? [];

        return $list[0] ?? null;
    }

    /**
     * คืน base URL ที่จะใช้สำหรับ key นี้
     *
     * Priority:
     *   1. $this->base_url (per-key override)
     *   2. DEFAULT_BASE_URLS[provider]
     *   3. null
     */
    public function resolveBaseUrl(): ?string
    {
        if (! empty($this->base_url)) {
            return $this->base_url;
        }

        return self::DEFAULT_BASE_URLS[$this->provider] ?? null;
    }

    /**
     * 🎯 (2026-05-22) คืน RPM limit ที่ effective สำหรับ key นี้
     *
     * Resolution order:
     *   1. admin-explicit (rate_limit_per_minute ในตาราง)
     *      - N > 0 → ใช้ N
     *      - 0     → unlimited (return 0 — caller ข้าม RPM check)
     *      - NULL  → ตกไปข้อ 2
     *   2. smart default ตาม MODEL_RPM_FREE_TIER[provider][model]
     *   3. smart default ตาม MODEL_RPM_FREE_TIER[provider]['_default']
     *   4. fallback conservative = 30
     *
     * เหตุผล: free-tier provider มี RPM ต่ำกว่า 60 มาก
     *   - Groq free: 30 RPM ทุก model
     *   - Gemini 2.5 Flash free: 10 RPM
     *   - Gemini 2.5 Pro free: 5 RPM
     *   ถ้าใช้ default 60 → ยิงเกิน → 429 → cooldown → fallback paid → quota เสียหาย
     *
     * ⚠️ paid tier: admin ต้องตั้งค่าเอง (เช่น 1000) — default จะ assume เป็น free
     */
    public function getEffectiveRpmLimit(): int
    {
        // 1. Admin-explicit value (รวม 0 = unlimited) ชนะ smart default
        $configured = $this->attributes['rate_limit_per_minute'] ?? null;
        if ($configured !== null && $configured !== '') {
            return (int) $configured;
        }

        // 2-3. Smart default ตาม provider + model
        $providerMap = self::MODEL_RPM_FREE_TIER[$this->provider] ?? null;
        if (! is_array($providerMap)) {
            return 30; // fallback conservative สำหรับ provider ที่ไม่มีในตาราง
        }

        $model = ! empty($this->model) ? $this->model : null;
        if ($model !== null && isset($providerMap[$model])) {
            return (int) $providerMap[$model];
        }

        return (int) ($providerMap['_default'] ?? 30);
    }

    /**
     * 🪙 (2026-05-23 Phase 5) คืน TPM (Tokens Per Minute) limit ที่ใช้จริง
     *
     * Logic เหมือน getEffectiveRpmLimit แต่ดู MODEL_TPM_FREE_TIER
     *
     * @return int  TPM limit (0 = unlimited)
     */
    public function getEffectiveTpmLimit(): int
    {
        // Admin override ผ่าน metadata.tpm_limit (optional — ส่วนใหญ่ไม่ต้องตั้ง)
        $metadata = $this->metadata ?? [];
        if (isset($metadata['tpm_limit']) && is_numeric($metadata['tpm_limit'])) {
            return (int) $metadata['tpm_limit'];
        }

        $providerMap = self::MODEL_TPM_FREE_TIER[$this->provider] ?? null;
        if (! is_array($providerMap)) {
            return 0; // unknown provider → unlimited (เพราะไม่อยากบล็อก provider ใหม่)
        }

        $model = ! empty($this->model) ? $this->model : null;
        if ($model !== null && isset($providerMap[$model])) {
            return (int) $providerMap[$model];
        }

        return (int) ($providerMap['_default'] ?? 0);
    }

    // ============================================================
    // 🩺 (2026-05-01) Smart Error Handling + 3-Strikes Critical
    // ============================================================

    /**
     * บันทึก error แบบฉลาด — แยก 429 (rate limit) จาก error อื่น
     *
     * Behavior:
     *   - 429 → ไม่นับ consecutive_errors (เป็นเรื่องชั่วคราว)
     *           ใช้ retry_after header ถ้ามี (default 60s)
     *   - error อื่น → exponential backoff 5/15/30 min
     *   - error_check_attempts >= 3 → mark critical, ไม่ใช้อัตโนมัติ รอ admin
     *
     * @param  string  $errorMessage  ข้อความ error
     * @param  string|null  $model  model ที่ใช้ตอน error (สำหรับ log)
     * @param  bool  $isRateLimit  true ถ้าเป็น 429 หรือ rate limit
     * @param  int|null  $retryAfter  retry_after seconds (จาก response header)
     */
    public function recordSmartError(
        string $errorMessage,
        ?string $model = null,
        bool $isRateLimit = false,
        ?int $retryAfter = null,
        ?array $context = null
    ): void {
        // ถ้า critical แล้ว ไม่ทำอะไร (รอ admin)
        if ($this->is_critical) {
            return;
        }

        // 🛡️ (2026-05-01) Concurrent-error guard
        //   ถ้า key ถูก disable อยู่แล้ว (cooldown ยังไม่หมด) → error นี้คือ in-flight call ที่ยังไม่ทันรู้ว่าโดน disable
        //   ไม่ควรนับ strike ซ้ำ — แค่ update last_error
        $stillInCooldown = $this->disabled_until && $this->disabled_until->isFuture();
        if ($stillInCooldown) {
            $this->update([
                'last_error' => $errorMessage,
                'last_error_at' => now(),
            ]);
            $this->usageLogs()->create(array_merge([
                'model' => $model,
                'is_success' => false,
                'error_message' => '[CONCURRENT] '.$errorMessage,
            ], $this->customerContextColumns($context)));

            return;
        }

        // 🎯 (2026-05-01) 429 ไม่นับ strike toward critical
        //   เหตุผล: rate-limit เป็นเรื่องชั่วคราว, ไม่ใช่สัญญาณว่า key พัง
        //   admin จะตัดสินใจ mark critical เองเมื่อเห็นว่า key โดน ban จริง
        //
        // 🪙 (2026-05-24) 413 "Request too large" = TPM exhausted (Groq) — treat like 429
        //   ปกติ caller จะส่ง $isRateLimit=true เอง แต่กันลืม — auto-detect ด้วย
        //   ถ้า error message มี "413" หรือ "Request too large" → cooldown 60s no-strike
        $msgLower = mb_strtolower($errorMessage);
        if (! $isRateLimit) {
            $isRateLimit = str_contains($errorMessage, '413')
                || str_contains($msgLower, 'request too large')
                || str_contains($msgLower, 'rate_limit_exceeded')
                || str_contains($msgLower, 'tokens per minute');
        }

        if ($isRateLimit) {
            $cooldownSeconds = max(60, min($retryAfter ?? 60, 600));
            $this->update([
                'last_error' => $errorMessage,
                'last_error_at' => now(),
                'disabled_until' => now()->addSeconds($cooldownSeconds),
                'last_health_check_at' => now(),
                // ⚠️ ไม่แตะ consecutive_errors / error_check_attempts
            ]);
            $this->usageLogs()->create(array_merge([
                'model' => $model,
                'is_success' => false,
                'error_message' => "[RATE_LIMIT] {$errorMessage}",
            ], $this->customerContextColumns($context)));

            return;
        }

        // 🌐 (2026-05-24) Transient error detection — 5xx + timeout = provider infra issue
        //   เดิม: 5xx/timeout = +1 strike → 3 strikes → key disabled 5min (อันตราย ทำ key ดีๆ พัง)
        //   ใหม่: provider พังเป็นช่วงๆ (เช่น Gemini Q4 2025 มี 503 spike เป็นชั่วโมง)
        //         → cooldown 90s no-strike ไม่ลงโทษ key
        $isTransient = str_contains($errorMessage, '500')
            || str_contains($errorMessage, '502')
            || str_contains($errorMessage, '503')
            || str_contains($errorMessage, '504')
            || str_contains($msgLower, 'timeout')
            || str_contains($msgLower, 'timed out')
            || str_contains($msgLower, 'connection refused')
            || str_contains($msgLower, 'connection reset')
            || str_contains($msgLower, 'curl error 28')   // cURL timeout
            || str_contains($msgLower, 'curl error 7')    // cURL connect failed
            || str_contains($msgLower, 'service unavailable')
            || str_contains($msgLower, 'bad gateway')
            || str_contains($msgLower, 'gateway timeout')
            || str_contains($msgLower, 'internal server error')
            || str_contains($msgLower, 'overloaded')
            || str_contains($msgLower, 'temporarily unavailable');

        if ($isTransient) {
            $this->update([
                'last_error' => $errorMessage,
                'last_error_at' => now(),
                'disabled_until' => now()->addSeconds(90),  // cooldown 90s ลองใหม่
                'last_health_check_at' => now(),
                // ⚠️ ไม่แตะ consecutive_errors / error_check_attempts
            ]);
            $this->usageLogs()->create(array_merge([
                'model' => $model,
                'is_success' => false,
                'error_message' => "[TRANSIENT] {$errorMessage}",
            ], $this->customerContextColumns($context)));

            return;
        }

        // 💳 (2026-05-24) Payment/credit errors — fast-track to critical (no point retrying)
        //   402 = payment required (OpenRouter, Anthropic billing)
        //   "insufficient credit" / "out of credits" / "billing" → permanent until admin acts
        $isPaymentError = str_contains($errorMessage, '402')
            || str_contains($msgLower, 'insufficient credit')
            || str_contains($msgLower, 'out of credits')
            || str_contains($msgLower, 'requires more credits')
            || str_contains($msgLower, 'payment required')
            || str_contains($msgLower, 'billing');

        if ($isPaymentError) {
            $this->update([
                'is_critical' => true,
                'is_active' => false,
                'last_error' => $errorMessage,
                'last_error_at' => now(),
                'error_check_attempts' => max(3, $this->error_check_attempts ?? 0),  // ⭐ skip 3 strikes
                'disabled_until' => null,  // permanent disable
                'last_health_check_at' => now(),
            ]);
            $this->usageLogs()->create(array_merge([
                'model' => $model,
                'is_success' => false,
                'error_message' => "[BILLING_CRITICAL] {$errorMessage}",
            ], $this->customerContextColumns($context)));

            // 🔔 Alert admin via LineAlertService — billing requires manual action
            //   มีอยู่แล้วในระบบ — public alertSystemError(string, array)
            try {
                if (class_exists('\App\Services\LineAlertService')) {
                    /** @var \App\Services\LineAlertService $alert */
                    $alert = app(\App\Services\LineAlertService::class);
                    $alert->alertSystemError(
                        "AI Key billing error — {$this->provider}/{$this->name}",
                        [
                            'key_id' => (string) $this->id,
                            'provider' => (string) $this->provider,
                            'model' => (string) ($model ?? '(default)'),
                            'message' => mb_substr($errorMessage, 0, 200),
                            'action' => 'admin ต้อง refill credits / เปลี่ยน key',
                        ]
                    );
                }
            } catch (\Throwable $e) {
                // silent — alert ไม่ block flow
                Log::critical("AI Key billing exhausted (alert failed): {$this->provider} key#{$this->id} — {$errorMessage}");
            }

            return;
        }

        // Non-429 error: นับ strike + exponential backoff
        $checkAttempts = ($this->error_check_attempts ?? 0) + 1;

        // 🔴 3 strikes → critical → ไม่ใช้อัตโนมัติ
        if ($checkAttempts >= 3) {
            $this->update([
                'is_critical' => true,
                'is_active' => false,
                'last_error' => $errorMessage,
                'last_error_at' => now(),
                'error_check_attempts' => $checkAttempts,
                'disabled_until' => null,
                'last_health_check_at' => now(),
            ]);

            // log critical
            $this->usageLogs()->create(array_merge([
                'model' => $model,
                'is_success' => false,
                'error_message' => "[CRITICAL] {$errorMessage}",
            ], $this->customerContextColumns($context)));

            return;
        }

        // คำนวณ cooldown — exponential backoff 5 / 15 / 30 min
        $multiplier = [1, 3, 6][$checkAttempts - 1] ?? 1;
        $cooldownSeconds = 5 * 60 * $multiplier;

        $this->update([
            'consecutive_errors' => ($this->consecutive_errors ?? 0) + 1,
            'last_error' => $errorMessage,
            'last_error_at' => now(),
            'disabled_until' => now()->addSeconds($cooldownSeconds),
            'error_check_attempts' => $checkAttempts,
            'last_health_check_at' => now(),
        ]);

        $this->usageLogs()->create(array_merge([
            'model' => $model,
            'is_success' => false,
            'error_message' => $errorMessage,
        ], $this->customerContextColumns($context)));
    }

    /**
     * Pluck only the customer-context keys (reading_id/user_id/fb_user_id/
     * customer_name) from a free-form context array so we never accidentally
     * mass-assign arbitrary attributes onto AiApiKeyUsageLog.
     * Customer name is truncated to fit the column (varchar 120).
     *
     * @param  array<string,mixed>|null  $context
     * @return array<string,mixed>
     */
    private function customerContextColumns(?array $context): array
    {
        if (! $context) {
            return [];
        }
        $out = [];
        if (isset($context['reading_id']) && is_numeric($context['reading_id'])) {
            $out['reading_id'] = (int) $context['reading_id'];
        }
        if (isset($context['user_id']) && is_numeric($context['user_id'])) {
            $out['user_id'] = (int) $context['user_id'];
        }
        if (! empty($context['fb_user_id'])) {
            // Truncate to fb_user_id column width (varchar(100), matches
            // sibling tables fortune_readings.facebook_user_id etc.)
            $out['fb_user_id'] = mb_substr((string) $context['fb_user_id'], 0, 100);
        }
        if (! empty($context['customer_name'])) {
            $out['customer_name'] = mb_substr((string) $context['customer_name'], 0, 120);
        }

        return $out;
    }

    /**
     * ปลด critical (เมื่อ admin ตรวจสอบแล้ว)
     */
    public function clearCritical(): void
    {
        $this->update([
            'is_critical' => false,
            'is_active' => true,
            'consecutive_errors' => 0,
            'error_check_attempts' => 0,
            'disabled_until' => null,
            'last_error' => null,
            'last_error_at' => null,
        ]);
    }

    /**
     * บันทึกการใช้งาน tokens
     *
     * @param  array<string,mixed>|null  $context  Optional customer identity to denormalize
     *   onto the log row. Keys: reading_id, user_id, fb_user_id, customer_name.
     *   Used by warroom /workers → /chat deep-link.
     */
    public function recordUsage(int $inputTokens, int $outputTokens, ?string $model = null, ?int $responseTimeMs = null, string $requestType = 'general', ?array $context = null): void
    {
        $totalTokens = $inputTokens + $outputTokens;

        // Reset counters ถ้าเป็นวันใหม่
        $this->resetDailyCountersIfNeeded();

        // อัพเดท counters
        $this->increment('tokens_used_today', $totalTokens);
        $this->increment('tokens_used_month', $totalTokens);
        $this->increment('tokens_used_total', $totalTokens);
        $this->increment('requests_today');

        // 🩹 (2026-05-05) Sync cache RPM → DB column requests_minute
        //   เดิม: requests_minute ใน DB never incremented (dead column) — admin เห็น 0 เสมอ
        //   ใหม่: อ่าน cache RPM (real-time) แล้ว mirror ลง DB เพื่อให้ admin query/dashboard เห็นค่าจริง
        $cachedRpm = (int) Cache::get("pool:rpm:{$this->provider}:{$this->id}", 0);

        $this->update([
            'last_used_at' => now(),
            'requests_minute' => $cachedRpm,   // 🩹 sync จาก cache (TTL 60s)
            'last_rate_limit_reset' => now(),  // 🩹 บันทึกเวลาที่ sync
            'consecutive_errors' => 0,         // reset errors เมื่อใช้งานสำเร็จ
            'error_check_attempts' => 0,       // 🩺 (2026-05-01) reset health-check counter
            'disabled_until' => null,          // ปลด temporary disable
        ]);

        // บันทึก log
        $this->usageLogs()->create(array_merge([
            'model' => $model,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'total_tokens' => $totalTokens,
            'response_time_ms' => $responseTimeMs,
            'is_success' => true,
            'request_type' => $requestType,
        ], $this->customerContextColumns($context)));

        // 🪙 (2026-05-23 Phase 5) Accumulate TPM (Tokens Per Minute) — กัน Groq llama-3.3-70b ทะลุ 6000
        //   ทุก call ที่สำเร็จ → บวก tokens ลง 'pool:tpm:{provider}:{key_id}' (TTL 60s)
        //   AiApiKeyPoolService.isTpmSaturated() ใช้ filter ก่อน acquire ครั้งถัดไป
        if ($totalTokens > 0) {
            $tpmKey = "pool:tpm:{$this->provider}:{$this->id}";
            if (Cache::has($tpmKey)) {
                Cache::increment($tpmKey, $totalTokens);
            } else {
                Cache::put($tpmKey, $totalTokens, 60);
            }
        }
    }

    /**
     * บันทึก error
     */
    public function recordError(string $errorMessage, ?string $model = null, ?array $context = null): void
    {
        // 🪙 (2026-05-24) Route ผ่าน recordSmartError เพื่อให้ auto-detect ทุกประเภท error
        //    - 429/413 rate-limit → cooldown only, no strike
        //    - 5xx/timeout transient → cooldown 90s no strike
        //    - 402/billing → mark critical immediately + alert admin
        //    legacy callers ที่เรียก recordError() จะได้ smart handling ฟรี ไม่ต้องแก้
        $msgLower = mb_strtolower($errorMessage);
        $needsSmartRouting = str_contains($errorMessage, '429')
            || str_contains($errorMessage, '413')
            || str_contains($errorMessage, '402')
            || str_contains($errorMessage, '500')
            || str_contains($errorMessage, '502')
            || str_contains($errorMessage, '503')
            || str_contains($errorMessage, '504')
            || str_contains($msgLower, 'rate limit')
            || str_contains($msgLower, 'request too large')
            || str_contains($msgLower, 'rate_limit_exceeded')
            || str_contains($msgLower, 'tokens per minute')
            || str_contains($msgLower, 'timeout')
            || str_contains($msgLower, 'timed out')
            || str_contains($msgLower, 'connection refused')
            || str_contains($msgLower, 'connection reset')
            || str_contains($msgLower, 'service unavailable')
            || str_contains($msgLower, 'insufficient credit')
            || str_contains($msgLower, 'requires more credits')
            || str_contains($msgLower, 'out of credits');

        if ($needsSmartRouting) {
            // recordSmartError จะแยกเอง: rate-limit / transient / payment / regular
            $this->recordSmartError($errorMessage, $model, false, null, $context);

            return;
        }

        $this->increment('consecutive_errors');

        $this->update([
            'last_error' => $errorMessage,
            'last_error_at' => now(),
        ]);

        // บันทึก log
        $this->usageLogs()->create(array_merge([
            'model' => $model,
            'is_success' => false,
            'error_message' => $errorMessage,
        ], $this->customerContextColumns($context)));

        // Disable ชั่วคราวถ้า error มากเกินไป
        $settings = AiApiKeySetting::forProvider($this->provider);
        $maxErrors = $settings->max_consecutive_errors ?? 3;
        $disableDuration = $settings->disable_duration_minutes ?? 5;

        if ($this->consecutive_errors >= $maxErrors) {
            $this->update([
                'disabled_until' => now()->addMinutes($disableDuration),
            ]);
        }
    }

    /**
     * Reset daily counters ถ้าเป็นวันใหม่
     */
    protected function resetDailyCountersIfNeeded(): void
    {
        $today = now()->toDateString();

        if ($this->tokens_reset_date != $today) {
            $this->update([
                'tokens_used_today' => 0,
                'requests_today' => 0,
                'tokens_reset_date' => $today,
            ]);

            // Reset monthly ถ้าเป็นเดือนใหม่
            if (now()->day === 1) {
                $this->update(['tokens_used_month' => 0]);
            }
        }
    }

    /**
     * Enable key กลับมาใช้งาน
     */
    public function enable(): void
    {
        $this->update([
            'is_active' => true,
            'disabled_until' => null,
            'consecutive_errors' => 0,
        ]);
    }

    /**
     * Disable key ชั่วคราว
     */
    public function disable(?int $minutes = null): void
    {
        $this->update([
            'disabled_until' => $minutes ? now()->addMinutes($minutes) : null,
            'is_active' => $minutes ? $this->is_active : false,
        ]);
    }

    // ============================================================
    // Static Methods
    // ============================================================

    /**
     * ดึง key ที่พร้อมใช้งานสำหรับ provider
     */
    public static function getAvailableForProvider(string $provider): ?self
    {
        return self::forProvider($provider)
            ->available()
            ->orderByPriority()
            ->first();
    }

    /**
     * ดึงสถิติรวมของ provider
     */
    public static function getProviderStats(string $provider): array
    {
        $keys = self::forProvider($provider)->get();

        return [
            'total_keys' => $keys->count(),
            'active_keys' => $keys->where('is_active', true)->count(),
            'available_keys' => $keys->filter(fn ($k) => $k->isAvailable())->count(),
            'tokens_used_today' => $keys->sum('tokens_used_today'),
            'tokens_used_month' => $keys->sum('tokens_used_month'),
            'tokens_used_total' => $keys->sum('tokens_used_total'),
            'requests_today' => $keys->sum('requests_today'),
        ];
    }
}
