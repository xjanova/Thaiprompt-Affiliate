<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Performance Optimization Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all performance-related configurations for the
    | multi-million dollar level system optimization.
    |
    | Phase 1: Database Indexes + Redis Cache (+50% capacity)
    | Phase 2: Laravel Octane/Swoole (+200-300% performance)
    | Phase 3: Horizontal Scaling (+10,000 concurrent users)
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Phase 1: Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Redis caching is enabled for maximum performance. This provides
    | significant speed improvements over file-based caching.
    |
    */

    'cache' => [
        // Enable Redis caching
        'driver' => env('CACHE_DRIVER', 'redis'),

        // Cache TTL in seconds
        'ttl' => [
            'analytics' => 300, // 5 minutes
            'store' => 3600, // 1 hour
            'products' => 1800, // 30 minutes
            'user' => 7200, // 2 hours
            'session' => 1800, // 30 minutes
        ],

        // Cache prefixes
        'prefixes' => [
            'analytics' => 'analytics:',
            'store' => 'store:',
            'product' => 'product:',
            'user' => 'user:',
            'system' => 'system:',
        ],

        // Cache tags (for Redis cache tagging)
        'tags' => [
            'analytics' => 'analytics',
            'stores' => 'stores',
            'products' => 'products',
            'users' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Phase 1: Database Optimization
    |--------------------------------------------------------------------------
    |
    | Database indexing and query optimization settings
    |
    */

    'database' => [
        // Enable query caching
        'query_cache' => true,

        // Query cache TTL in seconds
        'query_cache_ttl' => 600,

        // Enable slow query logging
        'slow_query_log' => env('DB_SLOW_QUERY_LOG', true),

        // Slow query threshold in milliseconds
        'slow_query_threshold' => env('DB_SLOW_QUERY_THRESHOLD', 1000),

        // Connection pool settings
        'pool' => [
            'min' => 2,
            'max' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Phase 2: Laravel Octane Configuration
    |--------------------------------------------------------------------------
    |
    | Laravel Octane with Swoole/RoadRunner for 2-3x performance boost
    |
    */

    'octane' => [
        // Server type: swoole or roadrunner
        'server' => env('OCTANE_SERVER', 'swoole'),

        // Swoole configuration
        'swoole' => [
            'host' => env('OCTANE_HOST', '127.0.0.1'),
            'port' => env('OCTANE_PORT', 8000),
            'workers' => env('OCTANE_WORKERS', 4),
            'task_workers' => env('OCTANE_TASK_WORKERS', 4),
            'max_request' => env('OCTANE_MAX_REQUEST', 10000),
            'options' => [
                'enable_coroutine' => true,
                'max_coroutine' => 100000,
                'package_max_length' => 10 * 1024 * 1024, // 10MB
                'buffer_output_size' => 10 * 1024 * 1024, // 10MB
            ],
        ],

        // Warm services on boot
        'warm' => [
            'config',
            'routes',
            'views',
        ],

        // Tables to warm (cache entire tables in memory)
        'warm_tables' => [
            // Small, frequently accessed tables
            'roles',
            'permissions',
            'settings',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Phase 3: Horizontal Scaling Configuration
    |--------------------------------------------------------------------------
    |
    | Load balancing and horizontal scaling for 10,000+ concurrent users
    |
    */

    'scaling' => [
        // Enable horizontal scaling
        'enabled' => env('HORIZONTAL_SCALING_ENABLED', false),

        // Load balancer configuration
        'load_balancer' => [
            'algorithm' => env('LB_ALGORITHM', 'round-robin'), // round-robin, least-connections, ip-hash
            'health_check' => [
                'enabled' => true,
                'interval' => 30, // seconds
                'timeout' => 5, // seconds
                'path' => '/health',
            ],
        ],

        // Read replicas
        'read_replicas' => [
            'enabled' => env('READ_REPLICAS_ENABLED', false),
            'connections' => [
                // Add read replica connections here
                // 'replica1' => [...],
                // 'replica2' => [...],
            ],
        ],

        // Session management for distributed systems
        'session' => [
            'driver' => 'redis', // Must use Redis for distributed sessions
            'sticky_sessions' => true,
        ],

        // Distributed caching
        'distributed_cache' => [
            'driver' => 'redis',
            'cluster' => env('REDIS_CLUSTER', false),
            'prefix' => env('REDIS_PREFIX', 'thaiprompt_'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Real-time System Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for real-time system monitoring
    |
    */

    'monitoring' => [
        // Enable system monitoring
        'enabled' => env('SYSTEM_MONITORING_ENABLED', true),

        // Metrics collection interval (seconds)
        'interval' => env('MONITORING_INTERVAL', 2),

        // History retention (number of data points)
        'history_points' => env('MONITORING_HISTORY_POINTS', 60),

        // Alert thresholds
        'thresholds' => [
            'cpu' => [
                'warning' => 70,
                'critical' => 85,
                'danger' => 95,
            ],
            'memory' => [
                'warning' => 70,
                'critical' => 85,
                'danger' => 95,
            ],
            'disk' => [
                'warning' => 75,
                'critical' => 85,
                'danger' => 95,
            ],
        ],

        // Enable email alerts
        'email_alerts' => env('MONITORING_EMAIL_ALERTS', false),
        'alert_email' => env('MONITORING_ALERT_EMAIL', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | CDN Configuration
    |--------------------------------------------------------------------------
    |
    | Content Delivery Network for static assets
    |
    */

    'cdn' => [
        'enabled' => env('CDN_ENABLED', false),
        'url' => env('CDN_URL', ''),
        'assets' => [
            'css' => true,
            'js' => true,
            'images' => true,
            'fonts' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Optimization
    |--------------------------------------------------------------------------
    */

    'images' => [
        // Enable lazy loading
        'lazy_load' => true,

        // Enable WebP conversion
        'webp_enabled' => env('IMAGE_WEBP_ENABLED', true),

        // Image compression quality (1-100)
        'compression_quality' => 85,

        // Generate responsive images
        'responsive' => true,
        'breakpoints' => [320, 640, 768, 1024, 1280, 1920],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limiting' => [
        'enabled' => true,
        'limits' => [
            'api' => 60, // requests per minute
            'auth' => 5, // login attempts per minute
            'search' => 30, // search queries per minute
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */

    'queue' => [
        'default' => env('QUEUE_CONNECTION', 'redis'),
        'workers' => env('QUEUE_WORKERS', 3),
        'retry_after' => 90,
        'max_exceptions' => 3,
    ],

];
