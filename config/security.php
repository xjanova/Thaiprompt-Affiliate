<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    |
    | Configure Content Security Policy for enhanced XSS protection
    |
    */
    'csp' => [
        'enabled' => env('CSP_ENABLED', true),
        'policy' => env('CSP_POLICY', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed HTML Tags
    |--------------------------------------------------------------------------
    |
    | Tags that are allowed in user input after sanitization
    |
    */
    'allowed_tags' => env('ALLOWED_HTML_TAGS', '<b><i><u><strong><em><p><br><ul><ol><li>'),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for different endpoints
    |
    */
    'rate_limiting' => [
        'enabled' => env('RATE_LIMITING_ENABLED', true),

        // API rate limits
        'api' => [
            'max_attempts' => env('API_RATE_LIMIT', 60),
            'decay_minutes' => env('API_RATE_DECAY', 1),
        ],

        // Login rate limits
        'login' => [
            'max_attempts' => env('LOGIN_RATE_LIMIT', 5),
            'decay_minutes' => env('LOGIN_RATE_DECAY', 15),
        ],

        // Registration rate limits
        'registration' => [
            'max_attempts' => env('REGISTRATION_RATE_LIMIT', 3),
            'decay_minutes' => env('REGISTRATION_RATE_DECAY', 60),
        ],

        // Order creation rate limits
        'order' => [
            'max_attempts' => env('ORDER_RATE_LIMIT', 10),
            'decay_minutes' => env('ORDER_RATE_DECAY', 60),
        ],

        // Review/Comment rate limits
        'review' => [
            'max_attempts' => env('REVIEW_RATE_LIMIT', 5),
            'decay_minutes' => env('REVIEW_RATE_DECAY', 60),
        ],

        // Contact form rate limits
        'contact' => [
            'max_attempts' => env('CONTACT_RATE_LIMIT', 3),
            'decay_minutes' => env('CONTACT_RATE_DECAY', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Requirements
    |--------------------------------------------------------------------------
    |
    | Configure password strength requirements
    |
    */
    'password' => [
        'min_length' => env('PASSWORD_MIN_LENGTH', 8),
        'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
        'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
        'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
        'require_special_chars' => env('PASSWORD_REQUIRE_SPECIAL', true),
        'max_age_days' => env('PASSWORD_MAX_AGE_DAYS', 90), // 0 = no expiry
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    |
    | Enhanced session security settings
    |
    */
    'session' => [
        'secure_cookie' => env('SESSION_SECURE_COOKIE', true),
        'http_only' => env('SESSION_HTTP_ONLY', true),
        'same_site' => env('SESSION_SAME_SITE', 'lax'),
        'regenerate_on_login' => true,
        'timeout_minutes' => env('SESSION_TIMEOUT', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    |
    | Configure Cross-Origin Resource Sharing
    |
    */
    'cors' => [
        'enabled' => env('CORS_ENABLED', true),
        'allowed_origins' => env('CORS_ALLOWED_ORIGINS', '*'),
        'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept'],
        'exposed_headers' => [],
        'max_age' => 0,
        'supports_credentials' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Blocking
    |--------------------------------------------------------------------------
    |
    | IP addresses to block from accessing the application
    |
    */
    'blocked_ips' => env('BLOCKED_IPS', ''),
    'blocked_user_agents' => env('BLOCKED_USER_AGENTS', ''),

    /*
    |--------------------------------------------------------------------------
    | 2FA Configuration
    |--------------------------------------------------------------------------
    |
    | Two-factor authentication settings
    |
    */
    '2fa' => [
        'enabled' => env('2FA_ENABLED', false),
        'required_for_admin' => env('2FA_REQUIRED_ADMIN', true),
        'required_for_vendor' => env('2FA_REQUIRED_VENDOR', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Security
    |--------------------------------------------------------------------------
    |
    | Security settings for file uploads
    |
    */
    'uploads' => [
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'],
        'max_size_mb' => env('MAX_UPLOAD_SIZE_MB', 10),
        'scan_for_viruses' => env('SCAN_UPLOADS', false),
        'validate_mime_type' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Security
    |--------------------------------------------------------------------------
    |
    | Database-related security settings
    |
    */
    'database' => [
        'encrypt_sensitive_data' => env('DB_ENCRYPT_SENSITIVE', true),
        'log_queries' => env('DB_LOG_QUERIES', false),
        'prepared_statements_only' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | Configure what actions to log for security auditing
    |
    */
    'audit' => [
        'enabled' => env('AUDIT_LOGGING_ENABLED', true),
        'log_login_attempts' => true,
        'log_admin_actions' => true,
        'log_data_changes' => true,
        'log_permission_changes' => true,
        'retention_days' => env('AUDIT_RETENTION_DAYS', 365),
    ],
];
