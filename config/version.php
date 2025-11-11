<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Version
    |--------------------------------------------------------------------------
    |
    | This value is the version of your application. This value is used when
    | the framework needs to display the version string of the application.
    | You can update this by running: php artisan app:bump-version
    |
    */

    'current' => env('APP_VERSION', (function() {
        // Try VERSION file first
        $versionFile = base_path('VERSION');
        if (file_exists($versionFile)) {
            $version = trim(file_get_contents($versionFile));
            if (!empty($version)) {
                return $version;
            }
        }

        // Fallback to package.json
        $packageJson = base_path('package.json');
        if (file_exists($packageJson)) {
            $package = json_decode(file_get_contents($packageJson), true);
            if (isset($package['version'])) {
                return $package['version'];
            }
        }

        // Final fallback
        return '1.0.0';
    })()),

    /*
    |--------------------------------------------------------------------------
    | Version Name (Codename)
    |--------------------------------------------------------------------------
    |
    | Optional codename for this version
    |
    */

    'name' => 'Phoenix',

    /*
    |--------------------------------------------------------------------------
    | Release Date
    |--------------------------------------------------------------------------
    |
    | The date this version was released
    |
    */

    'released_at' => '2025-11-07',

    /*
    |--------------------------------------------------------------------------
    | Minimum PHP Version
    |--------------------------------------------------------------------------
    |
    | The minimum PHP version required for this version
    |
    */

    'min_php_version' => '8.1.0',

    /*
    |--------------------------------------------------------------------------
    | Repository Settings
    |--------------------------------------------------------------------------
    |
    | GitHub repository information for update checks
    |
    */

    'repository' => [
        'owner' => 'xjanova',
        'name' => 'Thaiprompt-Affiliate',
        'branch' => 'main',
        'api_url' => 'https://api.github.com/repos/xjanova/Thaiprompt-Affiliate',
        // Token will be loaded dynamically from database or .env when needed
        // See VersionService and UpdateService for token loading logic
        'token' => env('GITHUB_TOKEN'), // Fallback only
    ],

    /*
    |--------------------------------------------------------------------------
    | Update Check Settings
    |--------------------------------------------------------------------------
    |
    | Configure how the application checks for updates
    |
    */

    'update' => [
        'enabled' => env('VERSION_CHECK_ENABLED', true),
        'cache_ttl' => env('VERSION_CHECK_CACHE_TTL', 60), // 1 minute for immediate feedback
        'auto_check' => env('VERSION_AUTO_CHECK', true),
        'allow_prerelease' => env('VERSION_ALLOW_PRERELEASE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Changelog URL
    |--------------------------------------------------------------------------
    |
    | URL to the changelog file
    |
    */

    'changelog_url' => 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/CHANGELOG.md',

    /*
    |--------------------------------------------------------------------------
    | Documentation URL
    |--------------------------------------------------------------------------
    |
    | URL to the versioning documentation
    |
    */

    'docs_url' => 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/VERSIONING.md',
];
