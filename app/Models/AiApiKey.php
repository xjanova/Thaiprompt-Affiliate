<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

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
     * ⚠️ Maintenance: เมื่อ provider ออกโมเดลใหม่ ให้เพิ่มที่นี่ + MODEL_PRICING — ลำดับแสดง = ลำดับใน array
     *    ตัวแรกของแต่ละ provider = default model ของ resolveModel() เมื่อ key ไม่ได้ตั้ง model
     *    → วาง "ตัวคุ้มราคา" ไว้แรกเสมอ กัน key ใหม่เผลอ default เป็นตัวแพงสุด
     *
     * 🔄 (2026-10-30) อัปเดตทุกค่ายตามหน้า docs/pricing ทางการ (เช็ค 2026-10-30)
     *   โมเดลที่ปิดแล้วถูกลบออก — migration 2026_10_30_120000 รีแมป DB rows ที่ค้างค่าเก่าให้อัตโนมัติ
     */
    public const MODELS_BY_PROVIDER = [
        'grok' => [
            // docs.x.ai/docs/models — ไม่มี grok-5
            // 🚫 grok-4 / grok-4-latest / grok-4-fast* / grok-3* ปลดแล้ว 2026-05-15
            //    (ยังยิงได้แต่ถูก redirect ไป grok-4.3 และคิดราคา grok-4.3)
            'grok-4.3',                       // ⭐ DEFAULT — ถูกสุดของรุ่นปัจจุบัน ($1.25/$2.50) 1M context
            'grok-4.7',                       // รุ่นใหม่สุด ($2/$6) 500K context — prompt ≥200K คิด 2 เท่า
            'grok-4.6',                       // ($2/$6)
            'grok-4.5',                       // ($2/$6)
            'grok-4.20-0309-non-reasoning',   // ($1.25/$2.50) ไม่ใช้ reasoning — ตอบเร็ว
            'grok-4.20-0309-reasoning',       // ($1.25/$2.50)
            'grok-build-0.1',                 // งานเขียนโค้ด ($1/$2) แทน grok-code-fast-1
        ],
        'groq' => [
            // console.groq.com/docs/deprecations
            // 🚫 (2026-10-30) Groq ปิด Llama ทั้งหมดสำหรับ free/dev tier แล้ว:
            //   - llama-3.3-70b-versatile / llama-3.1-8b-instant      (ปิด 2026-08-16 — เหลือเฉพาะ enterprise)
            //   - meta-llama/llama-4-scout-17b-16e-instruct / qwen/qwen3-32b (ปิด 2026-07-17)
            //   - qwen/qwen3.6-27b                                      (ปิด 2026-09-14)
            //   → key free tier เหลือแค่ gpt-oss 2 ตัว (+ qwen3.8 preview)
            'openai/gpt-oss-120b',            // ⭐ DEFAULT — production ($0.15/$0.60) แทน llama-3.3-70b
            'openai/gpt-oss-20b',             // production — เร็ว+ถูกสุด ($0.075/$0.30) แทน llama-3.1-8b
            'qwen/qwen3.8-27b',               // preview ($0.80/$4.00) — preview อาจถูกปิดโดยแจ้งล่วงหน้าสั้น
        ],
        'openai' => [
            // 🔬 (2026-08-17) Verified live กับ key prod (id 37) บน /v1/responses: gpt-5.6-* / 5.5 / 5.4 / gpt-4o*
            //   ❌ 400 model_not_found : gpt-5.5-mini ← ไม่มีจริง (migration 2026_05_29_120000)
            // 🔄 (2026-10-30) developers.openai.com/api/docs/pricing + /deprecations
            //   ราคา 5.6 ปรับใหม่: sol $4/$20 (เดิม $5/$30) · terra $2/$12 · luna $0.20/$1.20
            //   ⚠️ input เกิน 272K คิด input 2× / output 1.5× "ทั้งคำขอ" (ของเราสูงสุด ~12K → ปลอดภัย)
            //   ⚠️ ตัวแรก = default ของ resolveModel() — คง 5.6-luna (ยิงจริงบน prod แล้ว) ไว้แรก
            //
            // GPT-5.6 family — ⭐ ที่ใช้บน prod อยู่ (verified)
            'gpt-5.6-luna',                   // ⭐ DEFAULT — ($0.20/$1.20)
            'gpt-5.6-sol',                    // flagship 5.6 ($4/$20) — ใช้กับ sensitive/งานยากจริง
            'gpt-5.6-terra',                  // ($2/$12) — Artificial Analysis: แพ้ luna+sol ทุกจุด ไม่แนะนำ
            // GPT-6 family (DevDay 2026-09-29) — 🆕 ยังไม่ได้ยิงจริงกับ key prod: กด "ทดสอบ" ก่อนลงสนาม
            //   Responses API: gpt-6-astra ไม่รับ effort 'none' / gpt-6.1-sol ไม่รับ 'none','minimal'
            //   (โค้ดเราส่ง 'low' → ใช้ได้)
            'gpt-6-luna',                     // ถูกสุดของ gen 6 ($0.10/$0.50)
            'gpt-6.1-terra',                  // ($1/$5)
            'gpt-6.1-sol',                    // ($2/$10)
            'gpt-6-sol',                      // ($2/$10)
            'gpt-6-astra',                    // flagship ($10/$50 — แพงมาก)
            // GPT-5.5 / 5.4 — gen ก่อนหน้า (ยังใช้ได้)
            'gpt-5.5',                        // ($5/$30)
            'gpt-5.5-pro',                    // ($30/$180 — แพงมาก)
            'gpt-5.4',                        // ($2.50/$15)
            'gpt-5.4-mini',                   // ($0.75/$4.50)
            'gpt-5.4-pro',                    // 5.4 ตัวฉลาดสุด
            'gpt-5.4-nano',                   // ⚠️ deprecated — ปิด 2027-04-01 ($0.20/$1.25)
            // ⚠️ Deprecated — ยังยิงได้ แต่มีวันปิดแล้ว
            'gpt-5',                          // ปิด 2026-12-11
            'gpt-5-mini',                     // ปิด 2026-12-11
            'gpt-4o',                         // ปิด 2027-01-14 ($2.50/$10)
            'gpt-4o-mini',                    // ปิด 2027-01-14 ($0.15/$0.60)
            // 🚫 (2026-10-30) ลบ gpt-4 / gpt-4-turbo / gpt-3.5-turbo / o3-mini (ปิด 2026-10-23)
            //    และ o1-preview / o1-mini (ปิดตั้งแต่ 2025) — migration รีแมปเป็น gpt-5.6-luna
            // 🎙️ TTS models — เลือกได้ถ้า key purpose='tts' — ⚠️ ทั้ง 3 ตัวปิด 2027-01-06
            //    ตัวแทน (gpt-realtime-2.1-mini) เป็น Realtime API ไม่ใช่ /v1/audio/speech — ต้องเขียน provider ใหม่
            'gpt-4o-mini-tts',
            'tts-1',
            'tts-1-hd',
        ],
        'gemini' => [
            // ai.google.dev/gemini-api/docs/models + /deprecations (หน้า 2026-10-28)
            // 🚫 (2026-05-26 v2) admin directive "ใช้ได้จริงเท่านั้น" — stable เท่านั้น ห้าม preview
            // 🔄 (2026-10-30) gemini-3.5-flash ถูก supersede → Google route ไป 3.6-flash เอง
            //    migration รีแมป DB → gemini-3.6-flash ให้ราคาในตารางตรงกับที่ถูกคิดจริง
            //    ยังไม่มี gemini-3.x Pro แบบ stable — ตัว Pro-class stable คือ gemini-4-argon
            'gemini-3.6-flash',               // ⭐ DEFAULT — frontier flash ($0.75/$3.75) มี free tier
            'gemini-3.5-flash-lite',          // ⭐ ถูก+มี free tier ($0.30/$2.50) — แทน 3.1-flash-lite
            'gemini-3.8-flash',               // flash ใหม่สุด ($0.75/$3.75) — ⚠️ ขึ้นเป็น $1.50/$7.50 ตั้งแต่ 2027-01-01
            'gemini-4-argon',                 // flagship ($2/$10 → $4/$20 ตั้งแต่ 2026-12-01) ❌ ไม่มี free tier
            'gemini-3.1-flash-lite',          // ถูกสุด ($0.25/$1.50) — ⚠️ ปิดเร็วสุด 2027-05-07
            // Gemini 2.5 stable — ยังไม่มีวันปิด แต่ "เฉพาะ project ที่เคยเรียกช่วงหลัง" เท่านั้นที่ยังใช้ได้
            'gemini-2.5-pro',
            'gemini-2.5-flash',
            'gemini-2.5-flash-lite',
        ],
        'qwen' => [
            // alibabacloud.com/help/en/model-studio/model-pricing (Singapore, หน้า 2026-10-29)
            // 🚫 qwen-turbo / qwen2.5-* ไม่อยู่ในรายการ international แล้ว
            'qwen3.7-plus',                   // ⭐ DEFAULT — recommended ($0.40/$1.60 ช่วง 0-256K)
            'qwen3.8-flash',                  // recommended ถูกสุด ($0.15/$0.47)
            'qwen3.8-max',                    // recommended flagship ($2/$6)
            'qwen-plus',
            'qwen-max',
            'qwen-flash',
            'qwen3-coder-flash',
        ],
        'openrouter' => [
            // 🔄 (2026-10-30) เช็คจาก GET openrouter.ai/api/v1/models จริง
            //   slug เก่า anthropic/claude-sonnet-4, google/gemini-2.0-flash-001, x-ai/grok-2-1212 หายแล้ว (404)
            'anthropic/claude-haiku-5.5',     // ⭐ DEFAULT — ถูกสุด ($0.10/$0.50)
            'anthropic/claude-sonnet-5.5',
            'anthropic/claude-opus-5.5',
            'openai/gpt-6-luna',
            'openai/gpt-6.1-sol',
            'google/gemini-3.5-flash-lite',
            'google/gemini-3.8-flash',
            'x-ai/grok-4.3',
            'deepseek/deepseek-v4-pro-0813',
            'qwen/qwen3.8-flash',
            'mistralai/mistral-large-4-0',
            'meta/muse-spark-1.3',
        ],
        'anthropic' => [
            // platform.claude.com/docs models overview + model-deprecations
            // 🤖 (2026-10-30) Claude 4.7+ / 5.x ไม่รับ temperature — request สร้างผ่าน App\Support\AnthropicMessages
            // 🚫 retired: claude-opus-4 / claude-sonnet-4 (2026-06-15), claude-3-5-sonnet / 3-5-haiku / 3-opus
            //    claude-sonnet-4-5 ปิด 2026-11-30 → ไม่ใส่ใน dropdown แล้ว
            'claude-haiku-5-5',               // ⭐ DEFAULT — ถูกสุด ($0.10/$0.50 เมื่อ prompt ≤100K)
            'claude-sonnet-5-5',              // งานทั่วไปคุณภาพสูง ($2/$10)
            'claude-opus-5-5',                // Opus ปัจจุบัน ($4/$20) — คิด (thinking) ตลอด ปิดไม่ได้
            'claude-fable-5-1',               // ฉลาดสุด ($10/$50 — แพงมาก) — คิดตลอด
            // legacy — ยังใช้ได้
            'claude-opus-5',
            'claude-sonnet-5',
            'claude-opus-4-8',
            'claude-opus-4-7',
            'claude-opus-4-6',
            'claude-sonnet-4-6',
            'claude-haiku-4-5',
        ],
        'deepseek' => [
            // api-docs.deepseek.com/quick_start/pricing
            // 🚫 deepseek-chat / deepseek-reasoner ประกาศยุติ 2026-07-24 · deepseek-coder ไม่อยู่ในรายการแล้ว
            'deepseek-flash',                 // ⭐ DEFAULT — V4.1-Flash ($0.30/$1.20 ช่วง peak) รับรูปได้
            'deepseek-v4-pro',                // V4-Pro ($1.32/$3.96 ช่วง peak)
        ],
        'typhoon' => [
            // docs.opentyphoon.ai/en/models — ฟรี (5 req/s, 200 req/min)
            'typhoon-v2.5-30b-a3b-instruct',  // ⭐ DEFAULT — flagship ปัจจุบัน 128K context
            'typhoon-v2.1-12b-instruct',      // flagship รุ่นก่อน 56K context
        ],
        'xiaomi' => [
            // mimo.mi.com pricing (อัปเดต 2026-10-21)
            // 🚫 mimo-v2.5-pro / mimo-v2.5 ปิด 2026-10-21 (ยิงแล้ว fail) · mimo-v2-* ไม่อยู่ในรายการ
            'mimo-v2.6-pro',                  // ⭐ DEFAULT ($0.435/$0.87)
            'mimo-v2.6-flash',                // ถูกสุด ($0.14/$0.28)
            'mimo-v2.6-pro-ultraspeed',       // เร็วพิเศษ ($4.35/$8.70)
        ],
        // 🎙️ (2026-05-08) MiniMax TTS — text-to-audio
        //   ใช้กับ purpose='tts' — สังเคราะห์เสียงสรุปคำทำนาย Celtic 99฿
        //   📚 https://platform.minimax.io/docs/api-reference/speech-t2a-http
        //   v2 endpoint = https://api.minimax.io/v1/t2a_v2 (ไม่ต้อง group_id ใน URL แล้ว)
        //   ราคาคิดต่อจำนวนตัวอักษร (ไม่ใช่ token) — hd $100 / turbo $60 ต่อ 1M ตัวอักษร · ไม่มี speech-3
        'minimax' => [
            'speech-2.8-hd',          // ⭐ ใหม่สุด — HD quality — interjection tag ใช้ได้เฉพาะ 2.8
            'speech-2.8-turbo',       // ⭐ ใหม่สุด — turbo (เร็ว+ถูก)
            'speech-2.6-hd',          // legacy — emotion 'whisper' ใช้ได้เฉพาะ 2.6
            'speech-2.6-turbo',
            'speech-02-hd',           // legacy
            'speech-02-turbo',        // legacy
            'speech-01-hd',           // legacy — ไม่อยู่ในหน้าราคาแล้ว
            'speech-01-turbo',        // legacy — ไม่อยู่ในหน้าราคาแล้ว
        ],
    ];

    /**
     * 💰 (2026-10-30) ราคาต่อโมเดล — USD ต่อ 1M tokens [input, output, cached_input|null]
     *
     * ใช้แสดงในตารางหน้า admin + คำนวณค่าใช้จ่ายโดยประมาณจาก usage logs (ดู pricingFor / estimateCostUsd)
     *   null ช่องที่ 3     = ค่ายไม่ประกาศราคา cache
     *   ['unit' => 'chars', 'price' => N] = คิด N USD ต่อ 1M ตัวอักษร (TTS) — ไม่คำนวณจาก token
     *   ['free' => true]   = ไม่มีค่าใช้จ่าย (เช่น Typhoon)
     *
     * ⚠️ ราคาเปลี่ยนบ่อย — อัปเดตพร้อม MODELS_BY_PROVIDER ทุกครั้ง (เช็คล่าสุด 2026-10-30)
     *    เป็น "ประมาณการ" — ไม่รวมส่วนลด batch / ราคาตามความยาว prompt / ช่วง off-peak / cache hit
     */
    public const MODEL_PRICING = [
        'grok' => [
            'grok-4.3' => [1.25, 2.50, 0.20],
            'grok-4.7' => [2.00, 6.00, 0.50],
            'grok-4.6' => [2.00, 6.00, 0.50],
            'grok-4.5' => [2.00, 6.00, 0.30],
            'grok-4.20-0309-non-reasoning' => [1.25, 2.50, 0.20],
            'grok-4.20-0309-reasoning' => [1.25, 2.50, 0.20],
            'grok-build-0.1' => [1.00, 2.00, 0.20],
        ],
        'groq' => [
            'openai/gpt-oss-120b' => [0.15, 0.60, null],
            'openai/gpt-oss-20b' => [0.075, 0.30, null],
            'qwen/qwen3.8-27b' => [0.80, 4.00, null],
        ],
        'openai' => [
            'gpt-5.6-luna' => [0.20, 1.20, 0.02],
            'gpt-5.6-sol' => [4.00, 20.00, 0.40],
            'gpt-5.6-terra' => [2.00, 12.00, 0.20],
            'gpt-6-luna' => [0.10, 0.50, 0.01],
            'gpt-6.1-terra' => [1.00, 5.00, 0.05],
            'gpt-6.1-sol' => [2.00, 10.00, 0.10],
            'gpt-6-sol' => [2.00, 10.00, 0.20],
            'gpt-6-astra' => [10.00, 50.00, 1.00],
            'gpt-5.5' => [5.00, 30.00, 0.50],
            'gpt-5.5-pro' => [30.00, 180.00, null],
            'gpt-5.4' => [2.50, 15.00, 0.25],
            'gpt-5.4-mini' => [0.75, 4.50, 0.075],
            'gpt-5.4-nano' => [0.20, 1.25, 0.02],
            'gpt-5' => [1.25, 10.00, 0.125],
            'gpt-5-mini' => [0.25, 2.00, 0.025],
            'gpt-4o' => [2.50, 10.00, 1.25],
            'gpt-4o-mini' => [0.15, 0.60, 0.075],
            // gpt-4o-mini-tts: input = text tokens, output = audio tokens
            'gpt-4o-mini-tts' => [0.60, 12.00, null],
            'tts-1' => ['unit' => 'chars', 'price' => 15.00],
            'tts-1-hd' => ['unit' => 'chars', 'price' => 30.00],
        ],
        'gemini' => [
            'gemini-3.6-flash' => [0.75, 3.75, 0.075],
            'gemini-3.5-flash-lite' => [0.30, 2.50, 0.03],
            'gemini-3.8-flash' => [0.75, 3.75, 0.075],
            'gemini-4-argon' => [2.00, 10.00, 0.10],
            'gemini-3.1-flash-lite' => [0.25, 1.50, 0.025],
            'gemini-2.5-pro' => [1.25, 10.00, 0.125],
            'gemini-2.5-flash' => [0.30, 2.50, 0.03],
            'gemini-2.5-flash-lite' => [0.10, 0.40, 0.01],
        ],
        'qwen' => [
            'qwen3.7-plus' => [0.40, 1.60, null],
            'qwen3.8-flash' => [0.15, 0.47, null],
            'qwen3.8-max' => [2.00, 6.00, null],
            'qwen-plus' => [0.40, 1.20, null],
            'qwen-max' => [1.60, 6.40, null],
            'qwen-flash' => [0.05, 0.40, null],
            'qwen3-coder-flash' => [0.30, 1.50, null],
        ],
        'openrouter' => [
            'anthropic/claude-haiku-5.5' => [0.10, 0.50, 0.01],
            'anthropic/claude-sonnet-5.5' => [2.00, 10.00, 0.10],
            'anthropic/claude-opus-5.5' => [4.00, 20.00, 0.20],
            'openai/gpt-6-luna' => [0.10, 0.50, 0.01],
            'openai/gpt-6.1-sol' => [2.00, 10.00, 0.10],
            'google/gemini-3.5-flash-lite' => [0.30, 2.50, 0.03],
            'google/gemini-3.8-flash' => [0.825, 4.125, 0.0825],
            'x-ai/grok-4.3' => [1.25, 2.50, 0.20],
            'deepseek/deepseek-v4-pro-0813' => [1.32, 3.96, 0.044],
            'qwen/qwen3.8-flash' => [0.15, 0.47, 0.016],
            'mistralai/mistral-large-4-0' => [0.70, 2.10, 0.07],
            'meta/muse-spark-1.3' => [1.25, 4.25, null],
        ],
        'anthropic' => [
            // Haiku 5.5: ราคานี้สำหรับ prompt ≤100K tokens (เกินคิด $0.50/$2.50)
            'claude-haiku-5-5' => [0.10, 0.50, 0.01],
            'claude-sonnet-5-5' => [2.00, 10.00, 0.20],
            'claude-opus-5-5' => [4.00, 20.00, 0.20],
            'claude-fable-5-1' => [10.00, 50.00, 0.25],
            'claude-opus-5' => [5.00, 25.00, 0.50],
            'claude-sonnet-5' => [2.00, 10.00, 0.20],
            'claude-opus-4-8' => [5.00, 25.00, 0.50],
            'claude-opus-4-7' => [5.00, 25.00, 0.50],
            'claude-opus-4-6' => [5.00, 25.00, 0.50],
            'claude-sonnet-4-6' => [3.00, 15.00, 0.30],
            'claude-haiku-4-5' => [1.00, 5.00, 0.10],
        ],
        'deepseek' => [
            // ราคาช่วง peak (จ-ศ 01-04, 06-10 UTC) — off-peak ถูกลงครึ่งหนึ่ง
            'deepseek-flash' => [0.30, 1.20, 0.006],
            'deepseek-v4-pro' => [1.32, 3.96, 0.044],
        ],
        'typhoon' => [
            'typhoon-v2.5-30b-a3b-instruct' => ['free' => true],
            'typhoon-v2.1-12b-instruct' => ['free' => true],
        ],
        'xiaomi' => [
            'mimo-v2.6-pro' => [0.435, 0.87, 0.0036],
            'mimo-v2.6-flash' => [0.14, 0.28, 0.0028],
            'mimo-v2.6-pro-ultraspeed' => [4.35, 8.70, 0.036],
        ],
        'minimax' => [
            'speech-2.8-hd' => ['unit' => 'chars', 'price' => 100.00],
            'speech-2.8-turbo' => ['unit' => 'chars', 'price' => 60.00],
            'speech-2.6-hd' => ['unit' => 'chars', 'price' => 100.00],
            'speech-2.6-turbo' => ['unit' => 'chars', 'price' => 60.00],
            'speech-02-hd' => ['unit' => 'chars', 'price' => 100.00],
            'speech-02-turbo' => ['unit' => 'chars', 'price' => 60.00],
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
            // 🔄 (2026-10-30) Llama/Qwen3 ปิดสำหรับ free/dev tier แล้ว — เหลือ gpt-oss (ดู MODELS_BY_PROVIDER)
            //    console.groq.com/docs/rate-limits: gpt-oss 8K TPM · qwen3.8-27b 8K TPM (ตาราง base ของ Developer plan)
            '_default' => 8000,
            'openai/gpt-oss-120b' => 8000,
            'openai/gpt-oss-20b' => 8000,
            'qwen/qwen3.8-27b' => 8000,
        ],
        'gemini' => [
            // Gemini ใช้ Project quota เป็นหลัก (RPD/TPD) — TPM ไม่ใช่ bottleneck หลัก ใส่ไว้กันเหนียว
            // 🔄 (2026-10-30) ตัวเลข free tier ต่อโมเดลดูได้เฉพาะใน AI Studio — ใช้ 250K ทุกรุ่นตามเดิม
            '_default' => 250000,
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
            // 🔄 (2026-10-30) gpt-oss / qwen3.8-27b = 30 RPM → ใช้ 28 (safety 5%)
            '_default' => 28,
            'openai/gpt-oss-120b' => 28,
            'openai/gpt-oss-20b' => 28,
            'qwen/qwen3.8-27b' => 28,
        ],
        'gemini' => [
            // 🔄 (2026-10-30) Google ไม่ประกาศ RPM free tier บนเว็บแล้ว (ดูใน AI Studio) — ค่าประมาณเดิมต่อรุ่น
            '_default' => 9,
            'gemini-3.6-flash' => 9,
            'gemini-3.8-flash' => 9,
            'gemini-3.5-flash-lite' => 14,
            'gemini-3.1-flash-lite' => 14,
            // gemini-4-argon ไม่มี free tier → ทุก key เป็น paid (Tier 1 ~150 RPM)
            'gemini-4-argon' => 140,
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
    /**
     * 🔄 (2026-10-30) default model ของ provider = ตัวแรกใน MODELS_BY_PROVIDER
     * ให้ fallback ทุกจุดในระบบชี้ที่เดียว — ไม่ต้องตามแก้ hardcode เวลาค่ายปิดโมเดล
     */
    public static function defaultModelFor(string $provider): ?string
    {
        return self::MODELS_BY_PROVIDER[$provider][0] ?? null;
    }

    public function resolveModel(): ?string
    {
        if (! empty($this->model)) {
            return $this->model;
        }

        $list = self::MODELS_BY_PROVIDER[$this->provider] ?? [];

        return $list[0] ?? null;
    }

    /**
     * 💰 (2026-10-30) ราคาของ provider + model — normalize จาก MODEL_PRICING
     *
     * Anthropic: id ที่มีวันที่ต่อท้าย (claude-haiku-4-5-20251001) ใช้ราคาของ alias
     *
     * @return array{unit: string, input: float|null, output: float|null, cached: float|null, free: bool}|null
     *   unit 'tokens' = USD/1M tokens · 'chars' = USD/1M ตัวอักษร (input = ราคา) · null = ไม่รู้ราคา
     */
    public static function pricingFor(string $provider, ?string $model): ?array
    {
        if (empty($model)) {
            return null;
        }

        $table = self::MODEL_PRICING[$provider] ?? [];
        $row = $table[$model] ?? null;
        if ($row === null && $provider === 'anthropic') {
            $row = $table[preg_replace('/-\d{8}$/', '', $model)] ?? null;
        }
        if ($row === null) {
            return null;
        }

        if (! empty($row['free'])) {
            return ['unit' => 'tokens', 'input' => 0.0, 'output' => 0.0, 'cached' => 0.0, 'free' => true];
        }

        if (($row['unit'] ?? null) === 'chars') {
            return ['unit' => 'chars', 'input' => (float) $row['price'], 'output' => null, 'cached' => null, 'free' => false];
        }

        return [
            'unit' => 'tokens',
            'input' => (float) $row[0],
            'output' => (float) $row[1],
            'cached' => isset($row[2]) ? (float) $row[2] : null,
            'free' => false,
        ];
    }

    /**
     * 💰 (2026-10-30) ประมาณค่าใช้จ่าย (USD) จากจำนวน token — ไม่รวมส่วนลด cache
     *
     * @return float|null null = ไม่รู้ราคา หรือโมเดลคิดต่อตัวอักษร (TTS)
     */
    public static function estimateCostUsd(string $provider, ?string $model, int $inputTokens, int $outputTokens): ?float
    {
        $price = self::pricingFor($provider, $model);
        if ($price === null || $price['unit'] !== 'tokens') {
            return null;
        }

        return ($inputTokens * $price['input'] + $outputTokens * $price['output']) / 1_000_000;
    }

    /**
     * 💰 (2026-10-30) ข้อความใน dropdown เลือกโมเดล — ชื่อโมเดล + ราคา ให้ admin เห็นก่อนเลือก
     *
     * @return array<string, array<string, string>> [provider => [model => label]]
     */
    public static function modelOptionLabels(): array
    {
        $fmt = fn (float $v) => '$'.rtrim(rtrim(number_format($v, 4), '0'), '.');

        $labels = [];
        foreach (self::MODELS_BY_PROVIDER as $provider => $models) {
            foreach ($models as $model) {
                $p = self::pricingFor($provider, $model);
                $labels[$provider][$model] = match (true) {
                    $p === null => "{$model} — ไม่ทราบราคา",
                    $p['free'] => "{$model} — ฟรี",
                    $p['unit'] === 'chars' => "{$model} — {$fmt($p['input'])} / 1M ตัวอักษร",
                    default => "{$model} — เข้า {$fmt($p['input'])} / ออก {$fmt($p['output'])} ต่อ 1M tokens",
                };
            }
        }

        return $labels;
    }

    /**
     * ราคาของโมเดลที่ key นี้ใช้จริง (รวมกรณีไม่ได้ตั้ง model → default ของ provider)
     */
    public function getPricingAttribute(): ?array
    {
        return self::pricingFor((string) $this->provider, $this->resolveModel());
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
        // 🔐 (2026-09-27) ข้อความมักมาจาก $e->getMessage() ของ Gemini ที่ส่ง ?key=<คีย์นี้เอง> ใน URL
        //    → ห้ามเก็บลง last_error / error_message (แสดงเต็มในหน้าแอดมิน) หรือส่งแจ้งเตือนทั้งดอก
        $errorMessage = \App\Support\SafeLog::redactSecrets($errorMessage);

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
        // 🔐 (2026-09-27) ปิดบัง ?key= ก่อนเก็บ — เหตุผลเดียวกับ recordSmartError()
        $errorMessage = \App\Support\SafeLog::redactSecrets($errorMessage);
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
