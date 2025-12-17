<?php

/**
 * PostXAgent Configuration
 *
 * การตั้งค่าสำหรับเชื่อมต่อกับ PostXAgent AI Manager Core
 * PostXAgent เป็นระบบ AI ที่รันบน C# .NET 8.0 รองรับหลาย AI providers
 *
 * @see https://github.com/xjanova/PostXAgent
 */

return [

    /*
    |--------------------------------------------------------------------------
    | PostXAgent Connection Settings
    |--------------------------------------------------------------------------
    |
    | การตั้งค่าการเชื่อมต่อกับ PostXAgent AI Manager
    | - api_url: REST API endpoint
    | - signalr_url: SignalR WebSocket endpoint สำหรับ real-time
    | - timeout: Timeout ในวินาที
    |
    */

    'enabled' => env('POSTXAGENT_ENABLED', false),

    'api_url' => env('POSTXAGENT_API_URL', 'http://localhost:8000/api/v1'),

    'signalr_url' => env('POSTXAGENT_SIGNALR_URL', 'http://localhost:8000/hubs/ai'),

    'timeout' => env('POSTXAGENT_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | การตั้งค่า authentication สำหรับเชื่อมต่อกับ PostXAgent
    | รองรับทั้ง API Key และ JWT Token
    |
    */

    'auth' => [
        'type' => env('POSTXAGENT_AUTH_TYPE', 'api_key'), // api_key, jwt
        'api_key' => env('POSTXAGENT_API_KEY', ''),
        'jwt_token' => env('POSTXAGENT_JWT_TOKEN', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check Settings
    |--------------------------------------------------------------------------
    |
    | การตั้งค่าสำหรับตรวจสอบสถานะ PostXAgent
    | - interval: ตรวจสอบทุกๆ กี่วินาที
    | - cache_ttl: เก็บ cache สถานะไว้กี่วินาที
    |
    */

    'health_check' => [
        'enabled' => env('POSTXAGENT_HEALTH_CHECK_ENABLED', true),
        'interval' => env('POSTXAGENT_HEALTH_CHECK_INTERVAL', 60),
        'cache_ttl' => env('POSTXAGENT_HEALTH_CHECK_CACHE_TTL', 30),
        'endpoint' => '/status',
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Settings
    |--------------------------------------------------------------------------
    |
    | การตั้งค่าการ retry เมื่อเกิดข้อผิดพลาด
    |
    */

    'retry' => [
        'max_attempts' => env('POSTXAGENT_RETRY_MAX_ATTEMPTS', 3),
        'delay_ms' => env('POSTXAGENT_RETRY_DELAY_MS', 1000),
        'multiplier' => env('POSTXAGENT_RETRY_MULTIPLIER', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default AI Settings
    |--------------------------------------------------------------------------
    |
    | ค่าเริ่มต้นสำหรับการใช้งาน AI ผ่าน PostXAgent
    |
    */

    'defaults' => [
        'provider' => env('POSTXAGENT_DEFAULT_PROVIDER', 'ollama'),
        'model' => env('POSTXAGENT_DEFAULT_MODEL', 'llama3.2:3b'),
        'temperature' => env('POSTXAGENT_DEFAULT_TEMPERATURE', 0.7),
        'max_tokens' => env('POSTXAGENT_DEFAULT_MAX_TOKENS', 2048),
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Providers on PostXAgent
    |--------------------------------------------------------------------------
    |
    | รายชื่อ AI providers ที่ PostXAgent รองรับ
    | ใช้สำหรับแสดงใน dropdown และตรวจสอบความถูกต้อง
    |
    */

    'available_providers' => [
        'ollama' => [
            'name' => 'Ollama (Local)',
            'description' => 'AI รันบน server ของ PostXAgent - ฟรี',
            'is_free' => true,
            'requires_api_key' => false,
            'models' => ['llama3.2:3b', 'llama3.1:8b', 'llama3.1:70b', 'codellama:7b'],
        ],
        'openai' => [
            'name' => 'OpenAI GPT',
            'description' => 'GPT-4, GPT-3.5 จาก OpenAI',
            'is_free' => false,
            'requires_api_key' => true,
            'models' => ['gpt-4-turbo', 'gpt-4', 'gpt-3.5-turbo'],
        ],
        'anthropic' => [
            'name' => 'Anthropic Claude',
            'description' => 'Claude 3 Opus, Sonnet, Haiku',
            'is_free' => false,
            'requires_api_key' => true,
            'models' => ['claude-3-opus', 'claude-3-sonnet', 'claude-3-haiku'],
        ],
        'gemini' => [
            'name' => 'Google Gemini',
            'description' => 'Gemini Pro, Gemini Pro Vision',
            'is_free' => false,
            'requires_api_key' => true,
            'models' => ['gemini-pro', 'gemini-pro-vision'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | LINE Signup Integration
    |--------------------------------------------------------------------------
    |
    | การตั้งค่าสำหรับใช้ PostXAgent กับระบบสมัครสมาชิกผ่าน LINE
    |
    */

    'line_signup' => [
        'enabled' => env('POSTXAGENT_LINE_SIGNUP_ENABLED', true),
        'fallback_to_local' => env('POSTXAGENT_LINE_SIGNUP_FALLBACK', true),
        'preferred_provider' => env('POSTXAGENT_LINE_SIGNUP_PROVIDER', 'ollama'),
        'preferred_model' => env('POSTXAGENT_LINE_SIGNUP_MODEL', 'llama3.2:3b'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | การตั้งค่า logging สำหรับ debugging
    |
    */

    'logging' => [
        'enabled' => env('POSTXAGENT_LOGGING_ENABLED', true),
        'channel' => env('POSTXAGENT_LOGGING_CHANNEL', 'postxagent'),
        'level' => env('POSTXAGENT_LOGGING_LEVEL', 'info'),
    ],

];
