<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Page Builder Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for Page Builder data
    |
    */
    'cache' => [
        'enabled' => env('PAGE_BUILDER_CACHE_ENABLED', true),
        'ttl' => env('PAGE_BUILDER_CACHE_TTL', 1440), // 24 hours in minutes
        'prefix' => 'page_builder',
    ],

    /*
    |--------------------------------------------------------------------------
    | Page Types
    |--------------------------------------------------------------------------
    |
    | Define available page types for the Page Builder
    |
    */
    'page_types' => [
        'homepage' => 'Homepage',
        'landing' => 'Landing Page',
        'about' => 'About Page',
        'contact' => 'Contact Page',
        'custom' => 'Custom Page',
    ],

    /*
    |--------------------------------------------------------------------------
    | Section Types
    |--------------------------------------------------------------------------
    |
    | Define all available section types and their display labels
    |
    */
    'section_types' => [
        // Hero Sections
        'hero' => 'Hero Section',
        'hero_gradient' => 'Hero Section (Gradient)',
        'hero_video' => 'Hero Section (Video Background)',
        'hero_premium' => 'Hero Section (Premium)',

        // Feature Sections
        'features' => 'Features Grid',
        'features_list' => 'Features List',

        // Statistics
        'statistics' => 'Statistics',
        'statistics_realtime' => 'Statistics (Real-time)',

        // Content Sections
        'content_block' => 'Content Block',
        'image_text' => 'Image + Text',
        'testimonials' => 'Testimonials',
        'cta' => 'Call to Action',

        // Interactive Sections
        'gallery' => 'Image Gallery',
        'pricing' => 'Pricing Table',
        'team' => 'Team Members',
        'faq' => 'FAQ (Accordion)',
        'contact_form' => 'Contact Form',

        // Utility
        'spacer' => 'Spacer',
        'custom_html' => 'Custom HTML',
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Categories
    |--------------------------------------------------------------------------
    |
    | Categories for organizing templates
    |
    */
    'template_categories' => [
        'modern' => 'Modern',
        'classic' => 'Classic',
        'minimal' => 'Minimal',
        'gradient' => 'Gradient',
        'animated' => 'Animated',
        'business' => 'Business',
        'creative' => 'Creative',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | Default configuration for new sections
    |
    */
    'defaults' => [
        'section' => [
            'is_active' => true,
            'is_visible' => true,
            'padding_y' => 'py-20',
            'bg_color' => '#ffffff',
        ],
        'hero' => [
            'gradient_from' => '#667eea',
            'gradient_to' => '#764ba2',
            'text_align' => 'center',
        ],
        'features' => [
            'columns' => 3,
            'icon_color' => '#667eea',
        ],
        'testimonials' => [
            'columns' => 3,
            'show_rating' => true,
        ],
        'pricing' => [
            'billing_cycle' => 'monthly',
            'highlight_popular' => true,
        ],
        'team' => [
            'columns' => 4,
            'show_social' => true,
        ],
        'gallery' => [
            'layout' => 'grid',
            'columns' => 3,
            'enable_lightbox' => true,
        ],
        'faq' => [
            'layout' => 'single',
            'accordion_style' => 'default',
        ],
        'contact_form' => [
            'layout' => 'split',
            'show_contact_info' => true,
            'enable_privacy_checkbox' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    |
    | Permission requirements for Page Builder operations
    |
    */
    'permissions' => [
        'view' => 'page-builder.view',
        'create' => 'page-builder.create',
        'edit' => 'page-builder.edit',
        'delete' => 'page-builder.delete',
        'publish' => 'page-builder.publish',
    ],

    /*
    |--------------------------------------------------------------------------
    | Editor Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the visual editor interface
    |
    */
    'editor' => [
        'preview_devices' => ['desktop', 'tablet', 'mobile'],
        'auto_save_interval' => 30, // seconds
        'enable_version_history' => false,
        'max_versions' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Settings
    |--------------------------------------------------------------------------
    |
    | CDN and asset loading configuration
    |
    */
    'assets' => [
        'use_cdn' => env('PAGE_BUILDER_USE_CDN', true),
        'cdn' => [
            'tailwind' => 'https://cdn.tailwindcss.com',
            'alpinejs' => 'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js',
            'fontawesome' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
            'sortablejs' => 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Validation rules for content structure
    |
    */
    'validation' => [
        'enabled' => true,
        'strict_mode' => false, // If true, reject invalid content structure
        'required_fields' => [
            'hero' => ['title'],
            'features' => ['title', 'features'],
            'testimonials' => ['testimonials'],
            'pricing' => ['plans'],
            'team' => ['members'],
            'faq' => ['faqs'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SEO Settings
    |--------------------------------------------------------------------------
    |
    | SEO-related configuration
    |
    */
    'seo' => [
        'auto_generate_meta' => true,
        'default_meta_image' => '/images/default-og-image.jpg',
        'sitemap_include' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Settings
    |--------------------------------------------------------------------------
    |
    | Media and file storage configuration
    |
    */
    'storage' => [
        'disk' => 'public',
        'media_path' => 'page-builder/media',
        'max_upload_size' => 10240, // KB
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'video/mp4',
            'video/webm',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Settings
    |--------------------------------------------------------------------------
    |
    | Settings for development and debugging
    |
    */
    'development' => [
        'debug_mode' => env('PAGE_BUILDER_DEBUG', false),
        'show_template_info' => env('PAGE_BUILDER_SHOW_TEMPLATE_INFO', false),
        'log_queries' => env('PAGE_BUILDER_LOG_QUERIES', false),
    ],
];
