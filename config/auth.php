<?php

return [
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'api' => [
            'driver' => 'sanctum',
            'provider' => 'users',
        ],

        // 🔐 (2026-07-17) OAuth2 SSO server (Passport) สำหรับ juntraweb (จันทรา.online)
        //    guard แยกต่างหาก — ไม่แตะ 'api'=sanctum (mobile + JuntraMlm API ใช้อยู่)
        //    provider oauth_users ชี้ OAuthUser (User + Passport trait) เพื่อให้
        //    middleware scopes เรียก ->token()/->tokenCan() ได้ (User หลัก = Sanctum-only)
        'api-oauth' => [
            'driver' => 'passport',
            'provider' => 'oauth_users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        // ใช้ตาราง users เดียวกัน แต่ผูก OAuthUser (มี Passport HasApiTokens)
        // เฉพาะ guard 'api-oauth' — ไม่กระทบ provider 'users' ของ web/sanctum
        'oauth_users' => [
            'driver' => 'eloquent',
            'model' => App\Models\OAuthUser::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];
