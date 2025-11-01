<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email Delivery System Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for the multi-provider email delivery
    | system. You can configure multiple email providers and the system will
    | automatically handle failover and load balancing.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Email Provider
    |--------------------------------------------------------------------------
    |
    | This option defines the default email provider that will be used
    | when sending emails. You can override this on a per-email basis.
    |
    */

    'default_provider' => env('EMAIL_DEFAULT_PROVIDER', 'smtp'),

    /*
    |--------------------------------------------------------------------------
    | Email Tracking
    |--------------------------------------------------------------------------
    |
    | Enable or disable email tracking features like opens and clicks.
    |
    */

    'tracking' => [
        'enabled' => env('EMAIL_TRACKING_ENABLED', true),
        'track_opens' => env('EMAIL_TRACK_OPENS', true),
        'track_clicks' => env('EMAIL_TRACK_CLICKS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configure retry behavior for failed email deliveries.
    |
    */

    'retry' => [
        'enabled' => env('EMAIL_RETRY_ENABLED', true),
        'max_attempts' => env('EMAIL_MAX_RETRY_ATTEMPTS', 3),
        'delay' => env('EMAIL_RETRY_DELAY', 60), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for email sending to prevent abuse.
    |
    */

    'rate_limit' => [
        'enabled' => env('EMAIL_RATE_LIMIT_ENABLED', true),
        'max_per_hour' => env('EMAIL_MAX_PER_HOUR', 100),
        'max_per_day' => env('EMAIL_MAX_PER_DAY', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Preferences
    |--------------------------------------------------------------------------
    |
    | Default preferences for new users.
    |
    */

    'default_preferences' => [
        'marketing_emails' => true,
        'security_alerts' => true,
        'commission_notifications' => true,
        'withdrawal_notifications' => true,
        'system_announcements' => true,
        'weekly_reports' => true,
        'all_emails' => true,
        'preferred_language' => 'th',
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Gmail API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Gmail API provider.
    |
    */

    'gmail_api' => [
        'enabled' => env('GMAIL_API_ENABLED', false),
        'credentials_path' => env('GMAIL_API_CREDENTIALS_PATH'),
        'credentials_json' => env('GMAIL_API_CREDENTIALS_JSON'),
        'user_email' => env('GMAIL_API_USER_EMAIL'),
        'access_token' => env('GMAIL_API_ACCESS_TOKEN'),
        'refresh_token' => env('GMAIL_API_REFRESH_TOKEN'),
        'redirect_uri' => env('GMAIL_API_REDIRECT_URI', url('/admin/email/gmail/callback')),
        'from_email' => env('GMAIL_API_FROM_EMAIL'),
        'from_name' => env('GMAIL_API_FROM_NAME', config('app.name')),
        'daily_limit' => env('GMAIL_API_DAILY_LIMIT', 500), // Gmail API free tier limit
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Gmail SMTP Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Gmail SMTP provider.
    | Note: Requires App Password when 2FA is enabled.
    |
    */

    'gmail_smtp' => [
        'enabled' => env('GMAIL_SMTP_ENABLED', false),
        'host' => env('GMAIL_SMTP_HOST', 'smtp.gmail.com'),
        'port' => env('GMAIL_SMTP_PORT', 587),
        'username' => env('GMAIL_SMTP_USERNAME'),
        'password' => env('GMAIL_SMTP_PASSWORD'), // Use App Password
        'encryption' => env('GMAIL_SMTP_ENCRYPTION', 'tls'),
        'from_email' => env('GMAIL_SMTP_FROM_EMAIL'),
        'from_name' => env('GMAIL_SMTP_FROM_NAME', config('app.name')),
        'daily_limit' => env('GMAIL_SMTP_DAILY_LIMIT', 500), // Gmail SMTP daily limit
        'hourly_limit' => env('GMAIL_SMTP_HOURLY_LIMIT', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Generic SMTP Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for generic SMTP provider.
    |
    */

    'smtp' => [
        'enabled' => env('SMTP_ENABLED', true),
        'host' => env('MAIL_HOST', 'smtp.mailtrap.io'),
        'port' => env('MAIL_PORT', 2525),
        'username' => env('MAIL_USERNAME'),
        'password' => env('MAIL_PASSWORD'),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
        'from_email' => env('MAIL_FROM_ADDRESS'),
        'from_name' => env('MAIL_FROM_NAME', config('app.name')),
        'smtp_auth' => env('SMTP_AUTH', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Priority
    |--------------------------------------------------------------------------
    |
    | Define the priority order for email providers. Higher number = higher priority.
    | The system will try providers in order of priority when the default fails.
    |
    */

    'provider_priority' => [
        'gmail_api' => 100,
        'gmail_smtp' => 80,
        'smtp' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Templates
    |--------------------------------------------------------------------------
    |
    | Default template settings.
    |
    */

    'templates' => [
        'default_language' => 'th',
        'fallback_language' => 'en',
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check
    |--------------------------------------------------------------------------
    |
    | Configure automatic health checks for email providers.
    |
    */

    'health_check' => [
        'enabled' => env('EMAIL_HEALTH_CHECK_ENABLED', true),
        'interval' => env('EMAIL_HEALTH_CHECK_INTERVAL', 3600), // seconds (1 hour)
    ],

];
