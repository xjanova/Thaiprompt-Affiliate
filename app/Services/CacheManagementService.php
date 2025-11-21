<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Cache Management Service
 *
 * จัดการระบบแคชทั้งหมด รองรับ File, Database, Redis, และ Memcached
 * ตรวจสอบสถานะ, ทดสอบการเชื่อมต่อ, และให้คำแนะนำการติดตั้ง
 */
class CacheManagementService
{
    /**
     * รายการ Cache Drivers ที่รองรับ
     */
    const SUPPORTED_DRIVERS = [
        'file' => [
            'name' => 'File Cache',
            'description' => 'เก็บแคชในไฟล์ (storage/framework/cache) - ใช้งานได้ทันทีโดยไม่ต้องติดตั้งอะไรเพิ่ม',
            'pros' => [
                '✅ ไม่ต้องติดตั้งอะไรเพิ่ม',
                '✅ ใช้งานง่าย',
                '✅ เหมาะสำหรับเว็บไซต์ขนาดเล็ก-กลาง',
            ],
            'cons' => [
                '⚠️ ช้ากว่า Redis/Memcached',
                '⚠️ ใช้ disk I/O',
                '⚠️ ไม่เหมาะกับ high traffic',
            ],
            'recommended' => 'เหมาะสำหรับเว็บไซต์ทั่วไป Traffic ไม่สูงมาก',
        ],
        'database' => [
            'name' => 'Database Cache',
            'description' => 'เก็บแคชในฐานข้อมูล MySQL - ต้องมีตาราง cache',
            'pros' => [
                '✅ ใช้ database ที่มีอยู่แล้ว',
                '✅ จัดการง่าย',
                '✅ Persistent cache',
            ],
            'cons' => [
                '⚠️ ช้ากว่า File cache',
                '⚠️ เพิ่มโหลด database',
                '⚠️ ไม่เหมาะกับ high traffic',
            ],
            'recommended' => 'เหมาะสำหรับระบบที่ต้องการ persistent cache แต่ไม่แนะนำ',
        ],
        'redis' => [
            'name' => 'Redis Cache',
            'description' => 'เก็บแคชใน Redis (in-memory) - เร็วมาก เหมาะสำหรับ production',
            'pros' => [
                '🚀 เร็วที่สุด (in-memory)',
                '✅ รองรับ clustering',
                '✅ รองรับ data structures มากมาย',
                '✅ เหมาะสำหรับ high traffic',
            ],
            'cons' => [
                '⚠️ ต้องติดตั้ง Redis server',
                '⚠️ ใช้ RAM',
                '⚠️ ต้องมีความรู้ในการดูแล',
            ],
            'recommended' => '⭐ แนะนำสำหรับ Production (high traffic)',
        ],
        'memcached' => [
            'name' => 'Memcached',
            'description' => 'เก็บแคชใน Memcached (in-memory) - เร็วมาก',
            'pros' => [
                '🚀 เร็วมาก (in-memory)',
                '✅ Simple and fast',
                '✅ รองรับ distributed caching',
                '✅ น้อย overhead กว่า Redis',
            ],
            'cons' => [
                '⚠️ ต้องติดตั้ง Memcached server',
                '⚠️ ใช้ RAM',
                '⚠️ Feature น้อยกว่า Redis',
            ],
            'recommended' => '⭐ แนะนำสำหรับ Production (high traffic)',
        ],
    ];

    /**
     * ทดสอบการเชื่อมต่อ cache driver
     *
     * @param string $driver ชื่อ driver (file, database, redis, memcached)
     * @return array ผลการทดสอบ ['status' => true/false, 'message' => '...', 'details' => [...]]
     */
    public function testConnection(string $driver): array
    {
        try {
            switch ($driver) {
                case 'file':
                    return $this->testFileCache();

                case 'database':
                    return $this->testDatabaseCache();

                case 'redis':
                    return $this->testRedisCache();

                case 'memcached':
                    return $this->testMemcachedCache();

                default:
                    return [
                        'status' => false,
                        'message' => "Driver '{$driver}' ไม่รองรับ",
                        'details' => [],
                    ];
            }
        } catch (Exception $e) {
            Log::error("Cache test failed for {$driver}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
                'details' => [
                    'error' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * ทดสอบ File Cache
     */
    protected function testFileCache(): array
    {
        $path = storage_path('framework/cache/data');

        // เช็คว่า directory มีอยู่และ writable
        if (!File::isDirectory($path)) {
            return [
                'status' => false,
                'message' => "ไม่พบ directory: {$path}",
                'details' => [],
            ];
        }

        if (!File::isWritable($path)) {
            return [
                'status' => false,
                'message' => "ไม่สามารถเขียนไฟล์ได้: {$path}",
                'details' => [
                    'path' => $path,
                    'writable' => false,
                ],
            ];
        }

        // ทดสอบเขียน/อ่าน cache
        try {
            $testKey = 'cache_test_' . time();
            $testValue = 'test_value_' . rand(1000, 9999);

            Cache::store('file')->put($testKey, $testValue, 60);
            $retrieved = Cache::store('file')->get($testKey);

            if ($retrieved !== $testValue) {
                return [
                    'status' => false,
                    'message' => 'ไม่สามารถอ่าน/เขียน cache ได้ถูกต้อง',
                    'details' => [],
                ];
            }

            Cache::store('file')->forget($testKey);

            return [
                'status' => true,
                'message' => '✅ File Cache ใช้งานได้ปกติ',
                'details' => [
                    'path' => $path,
                    'writable' => true,
                    'size' => $this->getDirectorySize($path),
                ],
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => 'เกิดข้อผิดพลาดในการทดสอบ: ' . $e->getMessage(),
                'details' => [],
            ];
        }
    }

    /**
     * ทดสอบ Database Cache
     */
    protected function testDatabaseCache(): array
    {
        try {
            // เช็คว่ามีตาราง cache
            if (!DB::getSchemaBuilder()->hasTable('cache')) {
                return [
                    'status' => false,
                    'message' => 'ไม่พบตาราง cache - กรุณารัน: php artisan migrate',
                    'details' => [
                        'migration_needed' => true,
                    ],
                ];
            }

            // ทดสอบเขียน/อ่าน cache
            $testKey = 'cache_test_' . time();
            $testValue = 'test_value_' . rand(1000, 9999);

            Cache::store('database')->put($testKey, $testValue, 60);
            $retrieved = Cache::store('database')->get($testKey);

            if ($retrieved !== $testValue) {
                return [
                    'status' => false,
                    'message' => 'ไม่สามารถอ่าน/เขียน cache ได้ถูกต้อง',
                    'details' => [],
                ];
            }

            Cache::store('database')->forget($testKey);

            // นับจำนวน cache entries
            $count = DB::table('cache')->count();

            return [
                'status' => true,
                'message' => '✅ Database Cache ใช้งานได้ปกติ',
                'details' => [
                    'table' => 'cache',
                    'entries_count' => $count,
                ],
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
                'details' => [],
            ];
        }
    }

    /**
     * ทดสอบ Redis Cache
     */
    protected function testRedisCache(): array
    {
        try {
            // เช็คว่า Redis extension ติดตั้งแล้ว
            if (!extension_loaded('redis') && !extension_loaded('phpredis')) {
                return [
                    'status' => false,
                    'message' => '❌ Redis PHP extension ยังไม่ได้ติดตั้ง',
                    'details' => [
                        'extension_missing' => true,
                        'install_command' => 'sudo apt-get install php-redis',
                    ],
                ];
            }

            // ทดสอบเชื่อมต่อ Redis
            Redis::connection('cache')->ping();

            // ทดสอบเขียน/อ่าน cache
            $testKey = 'cache_test_' . time();
            $testValue = 'test_value_' . rand(1000, 9999);

            Cache::store('redis')->put($testKey, $testValue, 60);
            $retrieved = Cache::store('redis')->get($testKey);

            if ($retrieved !== $testValue) {
                return [
                    'status' => false,
                    'message' => 'ไม่สามารถอ่าน/เขียน cache ได้ถูกต้อง',
                    'details' => [],
                ];
            }

            Cache::store('redis')->forget($testKey);

            // ดึงข้อมูล Redis
            $info = Redis::connection('cache')->info();

            return [
                'status' => true,
                'message' => '✅ Redis Cache ใช้งานได้ปกติ',
                'details' => [
                    'version' => $info['redis_version'] ?? 'N/A',
                    'uptime_days' => isset($info['uptime_in_days']) ? $info['uptime_in_days'] . ' วัน' : 'N/A',
                    'used_memory' => isset($info['used_memory_human']) ? $info['used_memory_human'] : 'N/A',
                    'connected_clients' => $info['connected_clients'] ?? 'N/A',
                ],
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => '❌ ไม่สามารถเชื่อมต่อ Redis: ' . $e->getMessage(),
                'details' => [
                    'error' => $e->getMessage(),
                    'host' => config('database.redis.cache.host', '127.0.0.1'),
                    'port' => config('database.redis.cache.port', 6379),
                ],
            ];
        }
    }

    /**
     * ทดสอบ Memcached Cache
     */
    protected function testMemcachedCache(): array
    {
        try {
            // เช็คว่า Memcached extension ติดตั้งแล้ว
            if (!extension_loaded('memcached')) {
                return [
                    'status' => false,
                    'message' => '❌ Memcached PHP extension ยังไม่ได้ติดตั้ง',
                    'details' => [
                        'extension_missing' => true,
                        'install_command' => 'sudo apt-get install php-memcached',
                    ],
                ];
            }

            // ทดสอบเขียน/อ่าน cache
            $testKey = 'cache_test_' . time();
            $testValue = 'test_value_' . rand(1000, 9999);

            Cache::store('memcached')->put($testKey, $testValue, 60);
            $retrieved = Cache::store('memcached')->get($testKey);

            if ($retrieved !== $testValue) {
                return [
                    'status' => false,
                    'message' => 'ไม่สามารถอ่าน/เขียน cache ได้ถูกต้อง',
                    'details' => [],
                ];
            }

            Cache::store('memcached')->forget($testKey);

            // ดึงข้อมูล Memcached stats
            $memcached = new \Memcached();
            $servers = config('cache.stores.memcached.servers', []);

            if (!empty($servers)) {
                $server = $servers[0];
                $memcached->addServer($server['host'], $server['port']);
                $stats = $memcached->getStats();

                $serverKey = $server['host'] . ':' . $server['port'];
                $serverStats = $stats[$serverKey] ?? [];

                return [
                    'status' => true,
                    'message' => '✅ Memcached ใช้งานได้ปกติ',
                    'details' => [
                        'version' => $serverStats['version'] ?? 'N/A',
                        'uptime' => isset($serverStats['uptime']) ? round($serverStats['uptime'] / 86400, 1) . ' วัน' : 'N/A',
                        'curr_items' => $serverStats['curr_items'] ?? 'N/A',
                        'bytes_used' => isset($serverStats['bytes']) ? $this->formatBytes($serverStats['bytes']) : 'N/A',
                    ],
                ];
            }

            return [
                'status' => true,
                'message' => '✅ Memcached ใช้งานได้ปกติ',
                'details' => [],
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => '❌ ไม่สามารถเชื่อมต่อ Memcached: ' . $e->getMessage(),
                'details' => [
                    'error' => $e->getMessage(),
                    'host' => config('cache.stores.memcached.servers.0.host', '127.0.0.1'),
                    'port' => config('cache.stores.memcached.servers.0.port', 11211),
                ],
            ];
        }
    }

    /**
     * ดึงสถานะของทุก cache drivers
     *
     * @return array
     */
    public function getAllDriversStatus(): array
    {
        $currentDriver = config('cache.default');
        $statuses = [];

        foreach (self::SUPPORTED_DRIVERS as $driver => $info) {
            $testResult = $this->testConnection($driver);

            $statuses[$driver] = [
                'name' => $info['name'],
                'description' => $info['description'],
                'is_current' => $driver === $currentDriver,
                'status' => $testResult['status'],
                'message' => $testResult['message'],
                'details' => $testResult['details'],
                'pros' => $info['pros'],
                'cons' => $info['cons'],
                'recommended' => $info['recommended'],
            ];
        }

        return $statuses;
    }

    /**
     * ดึงข้อมูลสถิติ cache ปัจจุบัน
     *
     * @return array
     */
    public function getCurrentCacheStats(): array
    {
        $driver = config('cache.default');

        $stats = [
            'current_driver' => $driver,
            'current_driver_name' => self::SUPPORTED_DRIVERS[$driver]['name'] ?? $driver,
        ];

        try {
            switch ($driver) {
                case 'file':
                    $path = storage_path('framework/cache/data');
                    $stats['cache_path'] = $path;
                    $stats['cache_size'] = $this->getDirectorySize($path);
                    $stats['file_count'] = count(File::allFiles($path));
                    break;

                case 'database':
                    $stats['table'] = 'cache';
                    $stats['entries_count'] = DB::table('cache')->count();
                    break;

                case 'redis':
                    $info = Redis::connection('cache')->info();
                    $stats['version'] = $info['redis_version'] ?? 'N/A';
                    $stats['used_memory'] = $info['used_memory_human'] ?? 'N/A';
                    $stats['uptime_days'] = $info['uptime_in_days'] ?? 'N/A';
                    break;

                case 'memcached':
                    $memcached = new \Memcached();
                    $servers = config('cache.stores.memcached.servers', []);
                    if (!empty($servers)) {
                        $server = $servers[0];
                        $memcached->addServer($server['host'], $server['port']);
                        $serverStats = $memcached->getStats();
                        $serverKey = $server['host'] . ':' . $server['port'];
                        $s = $serverStats[$serverKey] ?? [];
                        $stats['version'] = $s['version'] ?? 'N/A';
                        $stats['curr_items'] = $s['curr_items'] ?? 'N/A';
                    }
                    break;
            }
        } catch (Exception $e) {
            $stats['error'] = $e->getMessage();
        }

        return $stats;
    }

    /**
     * ล้างแคชทั้งหมด
     *
     * @return bool
     */
    public function clearAllCache(): bool
    {
        try {
            Cache::flush();

            Log::info('Cache cleared successfully', [
                'driver' => config('cache.default'),
                'cleared_at' => now(),
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to clear cache', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * คำแนะนำการติดตั้งสำหรับแต่ละ driver
     *
     * @param string $driver
     * @return array
     */
    public function getInstallationGuide(string $driver): array
    {
        $guides = [
            'file' => [
                'title' => 'File Cache',
                'requirements' => [
                    'ไม่ต้องติดตั้งอะไรเพิ่ม',
                    'directory storage/framework/cache/data ต้อง writable',
                ],
                'installation_steps' => [
                    '1. ตรวจสอบ permissions:',
                    '   chmod -R 775 storage/framework/cache',
                    '   chown -R www-data:www-data storage',
                    '',
                    '2. ตั้งค่าใน .env:',
                    '   CACHE_DRIVER=file',
                ],
                'env_example' => "CACHE_DRIVER=file\nCACHE_PREFIX=tp_affiliate_cache",
            ],
            'database' => [
                'title' => 'Database Cache',
                'requirements' => [
                    'MySQL/MariaDB ต้องติดตั้งแล้ว',
                    'ตาราง cache ต้องมีอยู่ในฐานข้อมูล',
                ],
                'installation_steps' => [
                    '1. รัน migration:',
                    '   php artisan migrate',
                    '',
                    '2. ตั้งค่าใน .env:',
                    '   CACHE_DRIVER=database',
                ],
                'env_example' => "CACHE_DRIVER=database\nCACHE_PREFIX=tp_affiliate_cache",
            ],
            'redis' => [
                'title' => 'Redis Cache',
                'requirements' => [
                    'Redis Server 5.0+',
                    'PHP Redis extension (phpredis)',
                ],
                'installation_steps' => [
                    '1. ติดตั้ง Redis Server (Ubuntu/Debian):',
                    '   sudo apt-get update',
                    '   sudo apt-get install redis-server',
                    '   sudo systemctl enable redis-server',
                    '   sudo systemctl start redis-server',
                    '',
                    '2. ติดตั้ง PHP Redis extension:',
                    '   sudo apt-get install php-redis',
                    '   sudo systemctl restart php8.1-fpm  # หรือ php-fpm',
                    '',
                    '3. ตั้งค่าใน .env:',
                    '   CACHE_DRIVER=redis',
                    '   REDIS_HOST=127.0.0.1',
                    '   REDIS_PASSWORD=null',
                    '   REDIS_PORT=6379',
                    '   REDIS_CACHE_DB=1',
                    '',
                    '4. ทดสอบ Redis:',
                    '   redis-cli ping',
                    '   # ควรได้ PONG',
                ],
                'env_example' => "CACHE_DRIVER=redis\nREDIS_HOST=127.0.0.1\nREDIS_PASSWORD=null\nREDIS_PORT=6379\nREDIS_CACHE_DB=1\nCACHE_PREFIX=tp_affiliate_cache",
            ],
            'memcached' => [
                'title' => 'Memcached',
                'requirements' => [
                    'Memcached Server 1.4+',
                    'PHP Memcached extension',
                ],
                'installation_steps' => [
                    '1. ติดตั้ง Memcached Server (Ubuntu/Debian):',
                    '   sudo apt-get update',
                    '   sudo apt-get install memcached',
                    '   sudo systemctl enable memcached',
                    '   sudo systemctl start memcached',
                    '',
                    '2. ติดตั้ง PHP Memcached extension:',
                    '   sudo apt-get install php-memcached',
                    '   sudo systemctl restart php8.1-fpm  # หรือ php-fpm',
                    '',
                    '3. ตั้งค่าใน .env:',
                    '   CACHE_DRIVER=memcached',
                    '   MEMCACHED_HOST=127.0.0.1',
                    '   MEMCACHED_PORT=11211',
                    '',
                    '4. ทดสอบ Memcached:',
                    '   echo "stats" | nc 127.0.0.1 11211',
                    '   # ควรเห็นสถิติต่างๆ',
                ],
                'env_example' => "CACHE_DRIVER=memcached\nMEMCACHED_HOST=127.0.0.1\nMEMCACHED_PORT=11211\nMEMCACHED_USERNAME=null\nMEMCACHED_PASSWORD=null\nCACHE_PREFIX=tp_affiliate_cache",
            ],
        ];

        return $guides[$driver] ?? [
            'title' => 'Unknown Driver',
            'requirements' => [],
            'installation_steps' => [],
            'env_example' => '',
        ];
    }

    /**
     * คำนวณขนาด directory (recursive)
     *
     * @param string $path
     * @return string
     */
    protected function getDirectorySize(string $path): string
    {
        try {
            $size = 0;
            $files = File::allFiles($path);

            foreach ($files as $file) {
                $size += $file->getSize();
            }

            return $this->formatBytes($size);
        } catch (Exception $e) {
            return 'N/A';
        }
    }

    /**
     * แปลง bytes เป็น human-readable format
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
