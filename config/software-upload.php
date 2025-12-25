<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Software Upload Configuration
    |--------------------------------------------------------------------------
    |
    | การตั้งค่าสำหรับระบบอัพโหลดไฟล์ซอฟต์แวร์
    | รองรับ 4 วิธีการ: URL, AWS S3, Google Drive, Local Server
    |
    */

    /**
     * Storage Methods ที่เปิดใช้งาน
     *
     * url: URL Link (Google Drive Share, Dropbox, etc.)
     * s3: AWS S3 Object Storage
     * google_drive: Google Drive API
     * local: Local Server Upload
     */
    'enabled_methods' => [
        'url' => true,
        's3' => env('UPLOAD_S3_ENABLED', false),
        'google_drive' => env('UPLOAD_GOOGLE_DRIVE_ENABLED', false),
        'local' => true,
    ],

    /**
     * Default Storage Method
     */
    'default_method' => env('UPLOAD_DEFAULT_METHOD', 'local'),

    /*
    |--------------------------------------------------------------------------
    | File Validation
    |--------------------------------------------------------------------------
    */

    /**
     * Allowed File Extensions
     */
    'allowed_extensions' => ['zip'],

    /**
     * Maximum File Size (bytes)
     * Default: 10MB
     * Admin can override this in admin panel
     */
    'max_file_size' => env('UPLOAD_MAX_FILE_SIZE', 10 * 1024 * 1024), // 10MB

    /**
     * Allowed MIME Types
     */
    'allowed_mime_types' => [
        'application/zip',
        'application/x-zip-compressed',
        'application/octet-stream',
    ],

    /*
    |--------------------------------------------------------------------------
    | Local Storage Configuration
    |--------------------------------------------------------------------------
    */

    'local' => [
        /**
         * Storage Disk (defined in config/filesystems.php)
         */
        'disk' => 'local',

        /**
         * Upload Directory (relative to storage/app/)
         */
        'directory' => 'software',

        /**
         * Generate Unique Filenames
         */
        'unique_filenames' => true,

        /**
         * File Name Pattern
         * {timestamp}: Unix timestamp
         * {random}: Random string (8 chars)
         * {original}: Original filename (sanitized)
         */
        'filename_pattern' => '{timestamp}_{random}_{original}',
    ],

    /*
    |--------------------------------------------------------------------------
    | AWS S3 Configuration
    |--------------------------------------------------------------------------
    */

    's3' => [
        /**
         * AWS Credentials
         */
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'ap-southeast-1'),

        /**
         * S3 Bucket
         */
        'bucket' => env('AWS_BUCKET'),

        /**
         * Upload Directory in Bucket
         */
        'directory' => 'software',

        /**
         * ACL (Private or Public)
         */
        'visibility' => 'private',

        /**
         * Presigned URL Expiration (seconds)
         * Used for generating download links
         */
        'presigned_url_expiration' => 3600, // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Drive Configuration
    |--------------------------------------------------------------------------
    */

    'google_drive' => [
        /**
         * Google Cloud Project Credentials
         * Path to JSON credentials file
         */
        'credentials_path' => env('GOOGLE_DRIVE_CREDENTIALS_PATH', storage_path('app/google-drive-credentials.json')),

        /**
         * Google Drive Folder ID
         * All uploads will be stored in this folder
         */
        'folder_id' => env('GOOGLE_DRIVE_FOLDER_ID'),

        /**
         * Shared Drive ID (optional)
         * Leave null for regular Drive
         */
        'shared_drive_id' => env('GOOGLE_DRIVE_SHARED_DRIVE_ID'),

        /**
         * File Permission
         * anyone: Anyone with the link can access
         * restricted: Only specific users
         */
        'permission' => env('GOOGLE_DRIVE_PERMISSION', 'anyone'),
    ],

    /*
    |--------------------------------------------------------------------------
    | URL Link Configuration
    |--------------------------------------------------------------------------
    */

    'url' => [
        /**
         * Allowed URL Patterns
         * Regex patterns for allowed URL domains
         */
        'allowed_domains' => [
            'drive.google.com',
            'docs.google.com',
            'dropbox.com',
            'www.dropbox.com',
            'onedrive.live.com',
            '1drv.ms',
            'mega.nz',
            'mediafire.com',
        ],

        /**
         * Validate URL Accessibility
         * Send HEAD request to verify URL is accessible
         */
        'validate_accessibility' => env('UPLOAD_URL_VALIDATE', true),

        /**
         * URL Validation Timeout (seconds)
         */
        'validation_timeout' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Download Configuration
    |--------------------------------------------------------------------------
    */

    'download' => [
        /**
         * Track Downloads
         */
        'track_downloads' => true,

        /**
         * Download Rate Limiting
         * Maximum downloads per user per hour
         */
        'rate_limit_per_hour' => env('DOWNLOAD_RATE_LIMIT', 10),

        /**
         * Generate Temporary Download Links
         * For security, generate one-time or expiring links
         */
        'use_temporary_links' => true,

        /**
         * Temporary Link Expiration (seconds)
         */
        'temp_link_expiration' => 3600, // 1 hour

        /**
         * Require Authentication for Downloads
         */
        'require_auth' => true,

        /**
         * Verify Purchase Before Download
         */
        'verify_purchase' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */

    'security' => [
        /**
         * Scan Uploaded Files for Malware
         * Requires ClamAV or similar
         */
        'virus_scan' => env('UPLOAD_VIRUS_SCAN', false),

        /**
         * Verify ZIP File Integrity
         */
        'verify_zip_integrity' => true,

        /**
         * Maximum Files in ZIP
         */
        'max_files_in_zip' => 1000,

        /**
         * Disallowed File Extensions in ZIP
         */
        'zip_disallowed_extensions' => [
            'exe', 'bat', 'cmd', 'sh', 'ps1',
            'scr', 'pif', 'app', 'deb', 'rpm',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Override
    |--------------------------------------------------------------------------
    */

    'admin' => [
        /**
         * Allow Admin to Override File Size Limit
         */
        'can_override_size_limit' => true,

        /**
         * Admin Maximum File Size (bytes)
         */
        'max_file_size' => 100 * 1024 * 1024, // 100MB

        /**
         * Admin Can Upload Any Extension
         */
        'allow_any_extension' => false,
    ],

];
