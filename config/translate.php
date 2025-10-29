<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Cloud Translation API Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your Google Cloud Translation API credentials here.
    | You need to create a service account in Google Cloud Console and
    | download the credentials JSON file.
    |
    */

    'google' => [
        'enabled' => env('GOOGLE_TRANSLATE_ENABLED', false),
        'api_key' => env('GOOGLE_TRANSLATE_API_KEY', ''),
        'project_id' => env('GOOGLE_TRANSLATE_PROJECT_ID', ''),
        'credentials_path' => env('GOOGLE_TRANSLATE_CREDENTIALS', storage_path('app/google-credentials.json')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Languages
    |--------------------------------------------------------------------------
    |
    | List of languages that can be translated to.
    | Add or remove languages as needed.
    |
    */

    'supported_languages' => [
        'en' => [
            'name' => 'English',
            'native' => 'English',
            'flag' => '🇬🇧',
            'code' => 'en',
        ],
        'th' => [
            'name' => 'Thai',
            'native' => 'ไทย',
            'flag' => '🇹🇭',
            'code' => 'th',
        ],
        'zh' => [
            'name' => 'Chinese',
            'native' => '中文',
            'flag' => '🇨🇳',
            'code' => 'zh',
        ],
        'ja' => [
            'name' => 'Japanese',
            'native' => '日本語',
            'flag' => '🇯🇵',
            'code' => 'ja',
        ],
        'ko' => [
            'name' => 'Korean',
            'native' => '한국어',
            'flag' => '🇰🇷',
            'code' => 'ko',
        ],
        'vi' => [
            'name' => 'Vietnamese',
            'native' => 'Tiếng Việt',
            'flag' => '🇻🇳',
            'code' => 'vi',
        ],
        'es' => [
            'name' => 'Spanish',
            'native' => 'Español',
            'flag' => '🇪🇸',
            'code' => 'es',
        ],
        'fr' => [
            'name' => 'French',
            'native' => 'Français',
            'flag' => '🇫🇷',
            'code' => 'fr',
        ],
        'de' => [
            'name' => 'German',
            'native' => 'Deutsch',
            'flag' => '🇩🇪',
            'code' => 'de',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Cache translated content to reduce API calls and improve performance.
    |
    */

    'cache' => [
        'enabled' => env('TRANSLATE_CACHE_ENABLED', true),
        'ttl' => env('TRANSLATE_CACHE_TTL', 86400), // 24 hours in seconds
        'prefix' => 'translate:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Source Language
    |--------------------------------------------------------------------------
    |
    | The default language of your content.
    |
    */

    'source_language' => env('TRANSLATE_SOURCE_LANGUAGE', 'th'),

];
