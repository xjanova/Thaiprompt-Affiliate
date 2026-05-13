<?php

/**
 * AI Pool + Provider Configuration
 *
 * 🎯 (2026-05-13) Cross-provider Pool routing — overrides
 *
 * ค่าส่วนใหญ่ใน AI flow มาจาก:
 *   - DB: ai_api_keys + ai_api_key_settings (per-provider)
 *   - Model: FortuneTellingSetting (per-feature toggles)
 *
 * ไฟล์นี้เก็บ "global override" ที่ไม่ควรอยู่ใน DB:
 *   - cross_provider_rotation_mode: mode สำหรับ Pool tier ที่ priority เท่ากัน
 *   - per_key_inflight_cap: limit จำนวน concurrent requests ต่อ key
 *   - deep_total_budget_sec: total time ก่อน fallback loop หยุด
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Provider Rotation Mode
    |--------------------------------------------------------------------------
    |
    | ใช้เมื่อ Pool มี keys หลาย provider ที่ priority เท่ากัน
    | (acquireKeyAnyProvider ใน AiApiKeyPoolService)
    |
    | Modes รองรับ:
    |   'smart'       — เลือก key ที่ load น้อยสุด (errors + tokens + inflight)
    |   'round_robin' — rotate pointer ภายใน tier (cache-based, TTL 5min)
    |   'least_used'  — เลือก tokens_used_today ASC
    |   'random'      — สุ่ม
    |   'failover'    — ใช้ id ASC (primary key, สำรองเมื่อ fail)
    |   'priority'    — ใน tier เดียวกัน → ใช้ id ASC (stable)
    |
    | Default = 'smart' (แนะนำ — กระจาย load + หลีก keys ที่ error บ่อย)
    |
    */

    'cross_provider_rotation_mode' => env('AI_CROSS_PROVIDER_ROTATION', 'smart'),

    /*
    |--------------------------------------------------------------------------
    | Per-Key In-flight Cap
    |--------------------------------------------------------------------------
    |
    | จำนวน concurrent requests สูงสุดต่อ key (กัน hammer key เดียว)
    | ใช้ใน Pool eligibility filter — skip key ที่ inflight ≥ cap
    |
    */

    'per_key_inflight_cap' => env('AI_PER_KEY_INFLIGHT_CAP', 10),

    /*
    |--------------------------------------------------------------------------
    | Deep Prediction Total Budget (วินาที)
    |--------------------------------------------------------------------------
    |
    | Loop fallback จะหยุดหลังเกินเวลานี้ — กันลูกค้ารอนานเกิน
    | Default 150s = รองรับ OpenAI Responses 1 attempt (120s) + Flash fallback
    |
    */

    'deep_total_budget_sec' => env('AI_DEEP_TOTAL_BUDGET_SEC', 150),

    /*
    |--------------------------------------------------------------------------
    | Provider Timeouts (วินาที)
    |--------------------------------------------------------------------------
    |
    | Default timeouts per provider category — caller override ได้
    |
    */

    'timeouts' => [
        'deep_provider' => env('AI_DEEP_PROVIDER_TIMEOUT', 30),
        'chat_provider' => env('AI_CHAT_PROVIDER_TIMEOUT', 15),
        'gemini_pro' => env('AI_GEMINI_PRO_TIMEOUT', 60),
        'openai_responses' => env('AI_OPENAI_RESPONSES_TIMEOUT', 120),
    ],

];
