<?php

/**
 * ไฟล์ตั้งค่าระบบ Email สำหรับ Laravel
 *
 * ไฟล์นี้กำหนดค่าสำหรับการส่ง Email ผ่าน Laravel Mail facade
 * และ Notification system
 *
 * @see https://laravel.com/docs/mail
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | กำหนด mailer เริ่มต้นที่จะใช้ในการส่ง email
    | สามารถเปลี่ยนได้ที่ .env โดยใช้ MAIL_MAILER
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | กำหนดค่า mailers ที่สามารถใช้ในการส่ง email
    | Laravel รองรับหลาย driver: smtp, sendmail, mailgun, ses, postmark, log, array
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | กำหนดที่อยู่ "From" เริ่มต้นสำหรับทุก email ที่ส่งออก
    | สามารถเปลี่ยนได้เมื่อส่ง email แต่ละครั้ง
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@tp-affiliate.local'),
        'name' => env('MAIL_FROM_NAME', 'TP-Affiliate'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Markdown Mail Settings
    |--------------------------------------------------------------------------
    |
    | กำหนด theme และ paths สำหรับ Markdown mail messages
    |
    */

    'markdown' => [
        'theme' => env('MAIL_MARKDOWN_THEME', 'default'),

        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],

];
