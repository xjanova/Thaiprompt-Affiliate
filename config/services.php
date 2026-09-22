<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Cloudflare Turnstile, Google APIs, etc.
    |
    */

    'cloudflare' => [
        // Turnstile (CAPTCHA)
        'turnstile' => [
            'site_key' => env('CLOUDFLARE_TURNSTILE_SITE_KEY', ''),
            'secret_key' => env('CLOUDFLARE_TURNSTILE_SECRET_KEY', ''),
            'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
        ],
        // Cloudflare API (Cache Purge, DNS, Security, etc.)
        'zone_id' => env('CLOUDFLARE_ZONE_ID', 'd552b4a77bf4783bf6cbfd6a07d3f349'),
        'api_token' => env('CLOUDFLARE_API_TOKEN', '3fc13fcba9b6add1ee59f2504f092bddec540'),

        // Cloudflare Account ID (สำหรับ Workers AI - เจนภาพ FLUX)
        // หาได้จาก dash.cloudflare.com → sidebar ขวา → Account ID
        // หรือดูใน URL: dash.cloudflare.com/<account_id>/...
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID', ''),
    ],

    'google' => [
        'api_key' => env('GOOGLE_API_KEY', ''),
        // คีย์เฉพาะ Cloud Text-to-Speech (ว่าง = ใช้คีย์ Translate/ทั่วไปแทน ดู GoogleCloudTtsProvider)
        'tts_api_key' => env('GOOGLE_TTS_API_KEY', ''),
        'cloud_project_id' => env('GOOGLE_CLOUD_PROJECT_ID', ''),
        'credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS', storage_path('app/google-credentials.json')),
        'translate' => [
            'enabled' => env('GOOGLE_TRANSLATE_ENABLED', false),
            'api_key' => env('GOOGLE_TRANSLATE_API_KEY', ''),
            'project_id' => env('GOOGLE_TRANSLATE_PROJECT_ID', ''),
            'credentials' => env('GOOGLE_TRANSLATE_CREDENTIALS', storage_path('app/google-credentials.json')),
        ],
    ],

    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | LINE Configuration
    |--------------------------------------------------------------------------
    */

    'line' => [
        'channel_id' => env('LINE_CHANNEL_ID'),
        'channel_secret' => env('LINE_CHANNEL_SECRET'),
        'channel_access_token' => env('LINE_CHANNEL_ACCESS_TOKEN'),
        'fresh_market_add_friend_url' => env('LINE_FRESH_MARKET_ADD_FRIEND_URL'),
        'thaiprompt_add_friend_url' => env('LINE_THAIPROMPT_ADD_FRIEND_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Facebook OAuth (Laravel Socialite)
    |--------------------------------------------------------------------------
    |
    | สำหรับ "Login with Facebook" — ลูกค้าที่จ่ายดูดวงผ่าน Messenger
    | สามารถ login เข้าเว็บเพื่อดู wallet/รายได้/ถอนเงิน
    |
    | App setup: https://developers.facebook.com/apps/
    | Required scopes: email, public_profile
    | Callback URL: https://main.thaiprompt.online/auth/facebook/callback
    */

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI', '/auth/facebook/callback'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configurations
    |--------------------------------------------------------------------------
    */

    'stripe' => [
        'api_key' => env('STRIPE_API_KEY'),
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox or live
    ],

    'omise' => [
        'public_key' => env('OMISE_PUBLIC_KEY'),
        'secret_key' => env('OMISE_SECRET_KEY'),
    ],

    'promptpay' => [
        'merchant_id' => env('PROMPTPAY_MERCHANT_ID'),
        'webhook_secret' => env('PROMPTPAY_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging (FCM) - สำหรับ SMS Checker Push Notifications
    |--------------------------------------------------------------------------
    */

    'firebase' => [
        'server_key' => env('FCM_SERVER_KEY'),
        'project_id' => env('FCM_PROJECT_ID'),
        'credentials' => env('FIREBASE_CREDENTIALS_PATH', storage_path('app/firebase-credentials.json')),
    ],

    'paysolutions' => [
        'merchant_id' => env('PAYSOLUTIONS_MERCHANT_ID'),
        'api_key' => env('PAYSOLUTIONS_API_KEY'),
        'secret_key' => env('PAYSOLUTIONS_SECRET_KEY'),
        'webhook_secret' => env('PAYSOLUTIONS_WEBHOOK_SECRET'),
        'api_url' => env('PAYSOLUTIONS_API_URL', 'https://api.paysolutions.asia'),
        'sandbox_url' => env('PAYSOLUTIONS_SANDBOX_URL', 'https://sandbox-api.paysolutions.asia'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracking / Shipping Provider API Keys
    |--------------------------------------------------------------------------
    |
    | API keys สำหรับเชื่อมต่อระบบติดตามพัสดุกับขนส่งชั้นนำของไทย
    | ตั้งค่าใน .env เมื่อต้องการเปิดใช้งาน realtime tracking
    |
    */

    /*
    |--------------------------------------------------------------------------
    | YouTube API
    |--------------------------------------------------------------------------
    */

    'youtube' => [
        'api_key' => env('YOUTUBE_API_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Service API Keys
    |--------------------------------------------------------------------------
    */

    'deepseek' => [
        'api_key' => env('DEEPSEEK_API_KEY', ''),
        'api_endpoint' => env('DEEPSEEK_API_ENDPOINT', 'https://api.deepseek.com/v1'),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY', ''),
        'model' => env('AI_MODEL_CLAUDE', 'claude-haiku-4-5-20251001'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY', ''),
        'model' => env('AI_MODEL_OPENAI', 'gpt-4o-mini'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY', ''),
        'model' => env('AI_MODEL_GEMINI', 'gemini-2.5-flash'),
        'tts_model' => env('AI_TTS_MODEL', 'gemini-2.5-flash-preview-tts'),
    ],

    // แชทน้องหญิงในแอพ (Api\V1\AiChatApiController) — gemini / claude / openai
    'nongying_chat' => [
        'provider' => env('AI_PROVIDER', 'gemini'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Web search (เครื่องมือค้นเว็บของ AI)
    |--------------------------------------------------------------------------
    |
    | ค่าใน FortuneTellingSetting (หลังบ้าน) มาก่อนเสมอ ค่าตรงนี้เป็นแค่ fallback
    |
    */

    'tavily' => [
        'api_key' => env('TAVILY_API_KEY', ''),
    ],

    'brave_search' => [
        'api_key' => env('BRAVE_SEARCH_API_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | GitHub
    |--------------------------------------------------------------------------
    |
    | token: อ่าน release ของ repo (app:backfill-releases) — ว่างได้ถ้า repo เป็น public
    | webhook_secret: ตรวจลายเซ็น webhook — ว่าง = ปฏิเสธ webhook ทั้งหมด
    |
    */

    'github' => [
        'token' => env('GITHUB_TOKEN', ''),
        'webhook_secret' => env('GITHUB_WEBHOOK_SECRET', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security / Threat Intelligence API Keys
    |--------------------------------------------------------------------------
    */

    'proxycheck' => [
        'api_key' => env('PROXYCHECK_API_KEY', ''),
    ],

    'abuseipdb' => [
        'api_key' => env('ABUSEIPDB_API_KEY', ''),
    ],

    'ipqualityscore' => [
        'api_key' => env('IPQUALITYSCORE_API_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Huggingface
    |--------------------------------------------------------------------------
    |
    | token ดาวน์โหลดโมเดล AI ของแอพ thaiapp — รับได้ 3 ชื่อ env ตามที่เคยใช้กันมา
    |
    */

    'huggingface' => [
        'token' => env('HF_TOKEN') ?: env('HUGGING_FACE_TOKEN') ?: env('HUGGINGFACE_TOKEN') ?: '',
    ],

    'google_cloud' => [
        'translate_api_key' => env('GOOGLE_CLOUD_TRANSLATE_API_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | ที่เก็บไฟล์เสียงคำทำนาย (FortuneVoiceStorageService)
    |--------------------------------------------------------------------------
    |
    | เป็นค่าตั้งต้นเท่านั้น — ค่าที่แอดมินบันทึกในหลังบ้าน (DB) ทับค่าตรงนี้เสมอ
    |
    */

    'fortune_voice_storage' => [
        'r2' => [
            'account_id' => env('FORTUNE_VOICE_R2_ACCOUNT_ID'),
            'access_key_id' => env('FORTUNE_VOICE_R2_ACCESS_KEY_ID'),
            'secret_access_key' => env('FORTUNE_VOICE_R2_SECRET_ACCESS_KEY'),
            'bucket' => env('FORTUNE_VOICE_R2_BUCKET'),
            'public_url' => env('FORTUNE_VOICE_R2_PUBLIC_URL'),
        ],
        's3' => [
            'access_key_id' => env('FORTUNE_VOICE_S3_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID')),
            'secret_access_key' => env('FORTUNE_VOICE_S3_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY')),
            'region' => env('FORTUNE_VOICE_S3_REGION', env('AWS_DEFAULT_REGION', 'ap-southeast-1')),
            'bucket' => env('FORTUNE_VOICE_S3_BUCKET', env('AWS_BUCKET')),
            'endpoint' => env('FORTUNE_VOICE_S3_ENDPOINT', env('AWS_ENDPOINT')),
            'public_url' => env('FORTUNE_VOICE_S3_PUBLIC_URL', env('AWS_URL')),
        ],
        'gcs' => [
            'credentials_path' => env('FORTUNE_VOICE_GCS_CREDENTIALS', env('GOOGLE_APPLICATION_CREDENTIALS', storage_path('app/firebase-credentials.json'))),
            'bucket' => env('FORTUNE_VOICE_GCS_BUCKET'),
            'public_url' => env('FORTUNE_VOICE_GCS_PUBLIC_URL'),
        ],
        'firebase' => [
            'credentials_path' => env('FORTUNE_VOICE_FIREBASE_CREDENTIALS', env('GOOGLE_APPLICATION_CREDENTIALS', storage_path('app/firebase-credentials.json'))),
            'bucket' => env('FORTUNE_VOICE_FIREBASE_BUCKET'),
            'public_url' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracking / Shipping Provider API Keys
    |--------------------------------------------------------------------------
    |
    | API keys สำหรับเชื่อมต่อระบบติดตามพัสดุกับขนส่งชั้นนำของไทย
    | ตั้งค่าใน .env เมื่อต้องการเปิดใช้งาน realtime tracking
    |
    */

    'tracking' => [
        'thaipost_token' => env('TRACKING_THAIPOST_TOKEN', ''),
        'kerry_api_key' => env('TRACKING_KERRY_API_KEY', ''),
        'flash_api_key' => env('TRACKING_FLASH_API_KEY', ''),
        'jt_api_key' => env('TRACKING_JT_API_KEY', ''),
        'ninjavan_key' => env('TRACKING_NINJAVAN_KEY', ''),
        'scg_api_key' => env('TRACKING_SCG_API_KEY', ''),
        'best_api_key' => env('TRACKING_BEST_API_KEY', ''),
        'dhl_key' => env('TRACKING_DHL_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Juntra (จันทรา.online) server-to-server API
    |--------------------------------------------------------------------------
    |
    | server_client_ids: Passport client ids allowed to call /api/v1/juntra/server/*
    | with a client_credentials token (comma-separated env). Empty = accept only a
    | client whose redirect URI points at จันทรา.online (the SSO client).
    |
    */

    'juntra' => [
        'server_client_ids' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('JUNTRA_SERVER_CLIENT_IDS', ''))
        ), fn ($id) => $id !== '')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Telegram — แจ้งเตือนแอดมิน (ไม่ใช่บอทคุยลูกค้า)
    |--------------------------------------------------------------------------
    |
    | ใช้โดย App\Services\TelegramAlertService สำหรับเตือนเรื่องระบบ เช่น
    | รูปดวงรายวันสร้างไม่ได้ / เครดิต provider หมด
    |
    | token: ว่างไว้ได้ → ตกไปใช้ token ของบอทแม่หมอใน DB ให้อัตโนมัติ
    | chat_id: **จำเป็นเสมอ** — บอท Telegram ส่งหาคนที่ไม่เคยทักมันไม่ได้
    |          หา chat id: ทักบอท 1 ครั้ง แล้วเปิด
    |          https://api.telegram.org/bot<TOKEN>/getUpdates
    |
    */

    'telegram_alert' => [
        'token' => env('TELEGRAM_ALERT_BOT_TOKEN', ''),
        'chat_id' => env('TELEGRAM_ALERT_CHAT_ID', ''),
    ],

];
