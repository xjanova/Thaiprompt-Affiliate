<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CloudflareService
 *
 * บริการจัดการ Cloudflare API
 * รองรับ: Cache Purge, DNS, Analytics, Firewall, Security และอื่นๆ
 *
 * @see https://developers.cloudflare.com/api/
 */
class CloudflareService
{
    /**
     * Cloudflare API Base URL
     */
    protected const API_BASE = 'https://api.cloudflare.com/client/v4';

    /**
     * API Token
     */
    protected string $apiToken;

    /**
     * Zone ID
     */
    protected ?string $zoneId;

    /**
     * Constructor
     *
     * ลำดับการดึงค่า:
     * 1. Database (Setting model) - ถ้ามี
     * 2. Config (services.cloudflare) - fallback
     */
    public function __construct()
    {
        // ลองดึงจาก database ก่อน
        $this->apiToken = $this->getSettingValue('cloudflare_api_token')
            ?? config('services.cloudflare.api_token');

        $this->zoneId = $this->getSettingValue('cloudflare_zone_id')
            ?? config('services.cloudflare.zone_id');
    }

    /**
     * ดึงค่า setting จาก database
     */
    protected function getSettingValue(string $key): ?string
    {
        try {
            if (class_exists(\App\Models\Setting::class)) {
                $value = \App\Models\Setting::get($key);
                return !empty($value) ? $value : null;
            }
        } catch (\Exception $e) {
            // Database อาจยังไม่พร้อม (เช่น ตอน migrate)
        }
        return null;
    }

    /**
     * บันทึก Cloudflare settings ลง database
     */
    public static function saveSettings(string $zoneId, string $apiToken): array
    {
        try {
            \App\Models\Setting::set('cloudflare_zone_id', $zoneId, 'string', 'cloudflare');
            \App\Models\Setting::set('cloudflare_api_token', $apiToken, 'string', 'cloudflare');

            return [
                'success' => true,
                'message' => 'บันทึกการตั้งค่า Cloudflare สำเร็จ',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * ดึง Cloudflare settings จาก database
     */
    public static function getStoredSettings(): array
    {
        return [
            'zone_id' => \App\Models\Setting::get('cloudflare_zone_id', ''),
            'api_token' => \App\Models\Setting::get('cloudflare_api_token', ''),
        ];
    }

    /**
     * ตรวจสอบว่า configured หรือยัง
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiToken) && !empty($this->zoneId);
    }

    /**
     * ทดสอบการเชื่อมต่อ API
     *
     * @return array
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Cloudflare ยังไม่ได้ตั้งค่า Zone ID หรือ API Token',
                'details' => [
                    'zone_id_set' => !empty($this->zoneId),
                    'api_token_set' => !empty($this->apiToken),
                ],
            ];
        }

        try {
            // ทดสอบ API Token ด้วย /user/tokens/verify
            $tokenResponse = $this->client()->get(self::API_BASE . '/user/tokens/verify');

            if (!$tokenResponse->successful()) {
                return [
                    'success' => false,
                    'message' => 'API Token ไม่ถูกต้องหรือหมดอายุ',
                    'details' => [
                        'status' => $tokenResponse->status(),
                        'errors' => $tokenResponse->json('errors', []),
                    ],
                ];
            }

            $tokenData = $tokenResponse->json('result', []);

            // ทดสอบ Zone ID
            $zoneResponse = $this->client()->get(self::API_BASE . "/zones/{$this->zoneId}");

            if (!$zoneResponse->successful()) {
                return [
                    'success' => false,
                    'message' => 'Zone ID ไม่ถูกต้องหรือ Token ไม่มีสิทธิ์เข้าถึง Zone นี้',
                    'details' => [
                        'status' => $zoneResponse->status(),
                        'errors' => $zoneResponse->json('errors', []),
                        'token_status' => $tokenData['status'] ?? 'unknown',
                    ],
                ];
            }

            $zoneData = $zoneResponse->json('result', []);

            return [
                'success' => true,
                'message' => 'เชื่อมต่อ Cloudflare สำเร็จ!',
                'details' => [
                    'token_status' => $tokenData['status'] ?? 'active',
                    'zone_name' => $zoneData['name'] ?? 'unknown',
                    'zone_status' => $zoneData['status'] ?? 'unknown',
                    'plan' => $zoneData['plan']['name'] ?? 'Free',
                    'plan_id' => $zoneData['plan']['legacy_id'] ?? 'free',
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' . $e->getMessage(),
                'details' => [
                    'exception' => get_class($e),
                    'error' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * ดึง HTTP client พร้อม headers
     */
    protected function client()
    {
        return Http::withToken($this->apiToken)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->timeout(30);
    }

    // ========================================
    // ZONE MANAGEMENT
    // ========================================

    /**
     * ดึงข้อมูล Zone ทั้งหมด
     */
    public function getZones(): array
    {
        try {
            $response = $this->client()->get(self::API_BASE . '/zones');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('result', []),
                ];
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * ดึงข้อมูล Zone เดียว
     */
    public function getZone(?string $zoneId = null): array
    {
        $zoneId = $zoneId ?? $this->zoneId;

        try {
            $response = $this->client()->get(self::API_BASE . "/zones/{$zoneId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('result'),
                ];
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    // ========================================
    // CACHE MANAGEMENT
    // ========================================

    /**
     * Purge cache ทั้งหมด
     */
    public function purgeEverything(?string $zoneId = null): array
    {
        $zoneId = $zoneId ?? $this->zoneId;

        try {
            $response = $this->client()->post(
                self::API_BASE . "/zones/{$zoneId}/purge_cache",
                ['purge_everything' => true]
            );

            if ($response->successful() && $response->json('success')) {
                Log::info('Cloudflare: Purged all cache', ['zone_id' => $zoneId]);
                return ['success' => true, 'message' => 'Purge cache ทั้งหมดสำเร็จ'];
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Purge cache เฉพาะ URLs
     */
    public function purgeUrls(array $urls, ?string $zoneId = null): array
    {
        $zoneId = $zoneId ?? $this->zoneId;

        try {
            $response = $this->client()->post(
                self::API_BASE . "/zones/{$zoneId}/purge_cache",
                ['files' => $urls]
            );

            if ($response->successful() && $response->json('success')) {
                Log::info('Cloudflare: Purged specific URLs', ['zone_id' => $zoneId, 'urls' => $urls]);
                return ['success' => true, 'message' => 'Purge URLs สำเร็จ', 'count' => count($urls)];
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Purge cache ตาม tags
     */
    public function purgeTags(array $tags, ?string $zoneId = null): array
    {
        $zoneId = $zoneId ?? $this->zoneId;

        try {
            $response = $this->client()->post(
                self::API_BASE . "/zones/{$zoneId}/purge_cache",
                ['tags' => $tags]
            );

            if ($response->successful() && $response->json('success')) {
                return ['success' => true, 'message' => 'Purge tags สำเร็จ', 'count' => count($tags)];
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Purge cache ตาม prefixes
     */
    public function purgePrefixes(array $prefixes, ?string $zoneId = null): array
    {
        $zoneId = $zoneId ?? $this->zoneId;

        try {
            $response = $this->client()->post(
                self::API_BASE . "/zones/{$zoneId}/purge_cache",
                ['prefixes' => $prefixes]
            );

            if ($response->successful() && $response->json('success')) {
                return ['success' => true, 'message' => 'Purge prefixes สำเร็จ', 'count' => count($prefixes)];
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    // ========================================
    // DNS MANAGEMENT
    // ========================================

    /**
     * ดึง DNS Records ทั้งหมด
     */
    public function getDnsRecords(?string $zoneId = null): array
    {
        $zoneId = $zoneId ?? $this->zoneId;

        try {
            $response = $this->client()->get(
                self::API_BASE . "/zones/{$zoneId}/dns_records",
                ['per_page' => 100]
            );

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('result', []),
                    'total' => $response->json('result_info.total_count', 0),
                ];
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * สร้าง DNS Record
     */
    public function createDnsRecord(array $data, ?string $zoneId = null): array
    {
        $zoneId = $zoneId ?? $this->zoneId;

        try {
            $response = $this->client()->post(
                self::API_BASE . "/zones/{$zoneId}/dns_records",
                $data
            );

            if ($response->successful()) {
                Log::info('Cloudflare: Created DNS record', ['zone_id' => $zoneId, 'data' => $data]);
                return [
                    'success' => true,
                    'data' => $response->json('result'),
                    'message' => 'สร้าง DNS Record สำเร็จ',
                ];
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * อัพเดท DNS Record
     */
    public function updateDnsRecord(string $recordId, array $data, ?string $zoneId = null): array
    {
        $zoneId = $zoneId ?? $this->zoneId;

        try {
            $response = $this->client()->put(
                self::API_BASE . "/zones/{$zoneId}/dns_records/{$recordId}",
                $data
            );

            if ($response->successful()) {
                Log::info('Cloudflare: Updated DNS record', ['record_id' => $recordId]);
                return [
                    'success' => true,
                    'data' => $response->json('result'),
                    'message' => 'อัพเดท DNS Record สำเร็จ',
                ];
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * ลบ DNS Record
     */
    public function deleteDnsRecord(string $recordId, ?string $zoneId = null): array
    {
        $zoneId = $zoneId ?? $this->zoneId;

        try {
            $response = $this->client()->delete(
                self::API_BASE . "/zones/{$zoneId}/dns_records/{$recordId}"
            );

            if ($response->successful()) {
                Log::info('Cloudflare: Deleted DNS record', ['record_id' => $recordId]);
                return ['success' => true, 'message' => 'ลบ DNS Record สำเร็จ'];
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    // ========================================
    // ANALYTICS
    // ========================================

    /**
     * ดึงข้อมูล Analytics Dashboard
     */
    public function getAnalyticsDashboard(?string $zoneId = null, string $since = '-1440'): array
    {
        $zoneId = $zoneId ?? $this->zoneId;

        try {
            $response = $this->client()->get(
                self::API_BASE . "/zones/{$zoneId}/analytics/dashboard",
                ['since' => $since, 'continuous' => true]
            );

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('result'),
                ];
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    // ========================================
    // SECURITY / FIREWALL
    // ========================================

    /**
     * ดึง Firewall Rules
     */
    public function getFirewallRules(?string $zoneId = null): array
    {
        $zoneId = $zoneId ?? $this->zoneId;

        try {
            $response = $this->client()->get(
                self::API_BASE . "/zones/{$zoneId}/firewall/rules"
            );

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('result', []),
                ];
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * ดึง Security Level
     */
    public function getSecurityLevel(?string $zoneId = null): array
    {
        $zoneId = $zoneId ?? $this->zoneId;

        try {
            $response = $this->client()->get(
                self::API_BASE . "/zones/{$zoneId}/settings/security_level"
            );

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('result'),
                ];
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * ตั้งค่า Security Level
     * Options: off, essentially_off, low, medium, high, under_attack
     */
    public function setSecurityLevel(string $level, ?string $zoneId = null): array
    {
        $zoneId = $zoneId ?? $this->zoneId;
        $validLevels = ['off', 'essentially_off', 'low', 'medium', 'high', 'under_attack'];

        if (!in_array($level, $validLevels)) {
            return ['success' => false, 'message' => 'Invalid security level'];
        }

        try {
            $response = $this->client()->patch(
                self::API_BASE . "/zones/{$zoneId}/settings/security_level",
                ['value' => $level]
            );

            if ($response->successful()) {
                Log::info('Cloudflare: Security level changed', ['level' => $level]);
                return [
                    'success' => true,
                    'message' => "Security level เปลี่ยนเป็น {$level} สำเร็จ",
                ];
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * เปิด Under Attack Mode
     */
    public function enableUnderAttackMode(?string $zoneId = null): array
    {
        return $this->setSecurityLevel('under_attack', $zoneId);
    }

    /**
     * ปิด Under Attack Mode (กลับไป high)
     */
    public function disableUnderAttackMode(?string $zoneId = null): array
    {
        return $this->setSecurityLevel('high', $zoneId);
    }

    // ========================================
    // SSL/TLS
    // ========================================

    /**
     * ดึงการตั้งค่า SSL
     */
    public function getSslSettings(?string $zoneId = null): array
    {
        $zoneId = $zoneId ?? $this->zoneId;

        try {
            $response = $this->client()->get(
                self::API_BASE . "/zones/{$zoneId}/settings/ssl"
            );

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('result'),
                ];
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    // ========================================
    // DEVELOPMENT MODE
    // ========================================

    /**
     * ดึงสถานะ Development Mode
     */
    public function getDevelopmentMode(?string $zoneId = null): array
    {
        $zoneId = $zoneId ?? $this->zoneId;

        try {
            $response = $this->client()->get(
                self::API_BASE . "/zones/{$zoneId}/settings/development_mode"
            );

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('result'),
                ];
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * เปิด/ปิด Development Mode
     */
    public function setDevelopmentMode(bool $enabled, ?string $zoneId = null): array
    {
        $zoneId = $zoneId ?? $this->zoneId;

        try {
            $response = $this->client()->patch(
                self::API_BASE . "/zones/{$zoneId}/settings/development_mode",
                ['value' => $enabled ? 'on' : 'off']
            );

            if ($response->successful()) {
                $status = $enabled ? 'เปิด' : 'ปิด';
                Log::info("Cloudflare: Development mode {$status}");
                return [
                    'success' => true,
                    'message' => "{$status} Development Mode สำเร็จ",
                ];
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    // ========================================
    // PAGE RULES
    // ========================================

    /**
     * ดึง Page Rules
     */
    public function getPageRules(?string $zoneId = null): array
    {
        $zoneId = $zoneId ?? $this->zoneId;

        try {
            $response = $this->client()->get(
                self::API_BASE . "/zones/{$zoneId}/pagerules"
            );

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('result', []),
                ];
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    // ========================================
    // ONE-CLICK OPTIMIZATION
    // ========================================

    /**
     * One-Click Optimization - ตั้งค่าทั้งหมดให้เหมาะสมที่สุด
     *
     * รวมการตั้งค่า:
     * - Performance (Speed)
     * - SEO (Search Engine Optimization)
     * - Security
     * - Caching
     * - SSL/TLS
     *
     * @param string|null $zoneId
     * @return array
     */
    public function oneClickOptimization(?string $zoneId = null): array
    {
        $zoneId = $zoneId ?? $this->zoneId;
        $results = [];
        $successCount = 0;
        $failCount = 0;

        // รายการ settings ที่จะตั้งค่า
        $optimizations = [
            // ========================================
            // PERFORMANCE SETTINGS
            // ========================================
            [
                'name' => 'Auto Minify (JS)',
                'category' => 'performance',
                'setting' => 'minify',
                'value' => ['js' => 'on', 'css' => 'on', 'html' => 'on'],
                'description' => 'บีบอัด JavaScript, CSS, HTML เพื่อลดขนาดไฟล์',
            ],
            [
                'name' => 'Brotli Compression',
                'category' => 'performance',
                'setting' => 'brotli',
                'value' => 'on',
                'description' => 'เปิด Brotli compression สำหรับ response ที่เร็วขึ้น',
            ],
            [
                'name' => 'Early Hints',
                'category' => 'performance',
                'setting' => 'early_hints',
                'value' => 'on',
                'description' => 'ส่ง hints ล่วงหน้าเพื่อให้ browser preload resources',
            ],
            [
                'name' => 'HTTP/2',
                'category' => 'performance',
                'setting' => 'http2',
                'value' => 'on',
                'description' => 'เปิดใช้ HTTP/2 protocol',
            ],
            [
                'name' => 'HTTP/3 (QUIC)',
                'category' => 'performance',
                'setting' => 'http3',
                'value' => 'on',
                'description' => 'เปิดใช้ HTTP/3 สำหรับ performance ที่ดีขึ้น',
            ],
            [
                'name' => '0-RTT Connection Resumption',
                'category' => 'performance',
                'setting' => '0rtt',
                'value' => 'on',
                'description' => 'ลด latency สำหรับผู้เยี่ยมชมที่กลับมา',
            ],
            [
                'name' => 'Rocket Loader',
                'category' => 'performance',
                'setting' => 'rocket_loader',
                'value' => 'on',
                'description' => 'เร่งความเร็ว paint time โดย async load JavaScript',
            ],

            // ========================================
            // SEO / CRAWLER SETTINGS
            // ========================================
            [
                'name' => 'Crawler Hints',
                'category' => 'seo',
                'setting' => 'crawler_hints',
                'value' => 'on',
                'description' => 'ส่ง IndexNow เพื่อแจ้ง search engines เมื่อเนื้อหาเปลี่ยนแปลง',
            ],
            [
                'name' => 'Always Online',
                'category' => 'seo',
                'setting' => 'always_online',
                'value' => 'on',
                'description' => 'แสดงหน้าจาก cache เมื่อ origin server ล่ม',
            ],

            // ========================================
            // SECURITY SETTINGS
            // ========================================
            [
                'name' => 'Security Level',
                'category' => 'security',
                'setting' => 'security_level',
                'value' => 'medium',
                'description' => 'ระดับ security ที่สมดุล (ไม่รบกวนผู้ใช้จริง)',
            ],
            [
                'name' => 'Browser Integrity Check',
                'category' => 'security',
                'setting' => 'browser_check',
                'value' => 'on',
                'description' => 'ตรวจสอบ HTTP headers เพื่อกัน bots ไม่ดี',
            ],
            [
                'name' => 'Email Obfuscation',
                'category' => 'security',
                'setting' => 'email_obfuscation',
                'value' => 'on',
                'description' => 'ซ่อน email จาก spammers/scrapers',
            ],
            [
                'name' => 'Hotlink Protection',
                'category' => 'security',
                'setting' => 'hotlink_protection',
                'value' => 'on',
                'description' => 'ป้องกันเว็บอื่นขโมย bandwidth ของคุณ',
            ],
            [
                'name' => 'Opportunistic Encryption',
                'category' => 'security',
                'setting' => 'opportunistic_encryption',
                'value' => 'on',
                'description' => 'เข้ารหัส HTTP requests โดยไม่ต้อง redirect',
            ],

            // ========================================
            // SSL/TLS SETTINGS
            // ========================================
            [
                'name' => 'SSL Mode',
                'category' => 'ssl',
                'setting' => 'ssl',
                'value' => 'full',
                'description' => 'Full SSL encryption ระหว่าง Cloudflare และ Origin',
            ],
            [
                'name' => 'Always Use HTTPS',
                'category' => 'ssl',
                'setting' => 'always_use_https',
                'value' => 'on',
                'description' => 'Redirect HTTP → HTTPS อัตโนมัติ',
            ],
            [
                'name' => 'Automatic HTTPS Rewrites',
                'category' => 'ssl',
                'setting' => 'automatic_https_rewrites',
                'value' => 'on',
                'description' => 'แก้ mixed content โดยเปลี่ยน http:// เป็น https://',
            ],
            [
                'name' => 'TLS 1.3',
                'category' => 'ssl',
                'setting' => 'min_tls_version',
                'value' => '1.2',
                'description' => 'ใช้ TLS 1.2+ สำหรับ security ที่ดีขึ้น',
            ],

            // ========================================
            // CACHING SETTINGS
            // ========================================
            [
                'name' => 'Cache Level',
                'category' => 'cache',
                'setting' => 'cache_level',
                'value' => 'aggressive',
                'description' => 'Cache แบบ aggressive เพื่อประสิทธิภาพสูงสุด',
            ],
            [
                'name' => 'Browser Cache TTL',
                'category' => 'cache',
                'setting' => 'browser_cache_ttl',
                'value' => 14400, // 4 ชั่วโมง
                'description' => 'Cache ในเบราว์เซอร์ 4 ชั่วโมง',
            ],
        ];

        // วนลูปตั้งค่าทั้งหมด
        foreach ($optimizations as $opt) {
            $result = $this->setSetting($opt['setting'], $opt['value'], $zoneId);

            $results[] = [
                'name' => $opt['name'],
                'category' => $opt['category'],
                'description' => $opt['description'],
                'success' => $result['success'],
                'message' => $result['message'] ?? ($result['success'] ? 'สำเร็จ' : 'ล้มเหลว'),
            ];

            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }

            // หน่วงเวลาเล็กน้อยเพื่อหลีกเลี่ยง rate limit
            usleep(100000); // 0.1 วินาที
        }

        // Purge cache หลังจากตั้งค่าเสร็จ
        $purgeResult = $this->purgeEverything($zoneId);
        $results[] = [
            'name' => 'Purge Cache',
            'category' => 'cache',
            'description' => 'ล้าง cache ทั้งหมดเพื่อให้ settings ใหม่มีผล',
            'success' => $purgeResult['success'],
            'message' => $purgeResult['message'] ?? 'Cache cleared',
        ];

        if ($purgeResult['success']) {
            $successCount++;
        } else {
            $failCount++;
        }

        Log::info('Cloudflare: One-Click Optimization completed', [
            'success' => $successCount,
            'failed' => $failCount,
            'zone_id' => $zoneId,
        ]);

        return [
            'success' => $failCount === 0,
            'message' => "Optimization เสร็จสิ้น: {$successCount} สำเร็จ, {$failCount} ล้มเหลว",
            'results' => $results,
            'summary' => [
                'total' => count($results),
                'success' => $successCount,
                'failed' => $failCount,
            ],
        ];
    }

    /**
     * ตั้งค่า setting เดี่ยว
     *
     * @param string $setting
     * @param mixed $value
     * @param string|null $zoneId
     * @return array
     */
    public function setSetting(string $setting, $value, ?string $zoneId = null): array
    {
        $zoneId = $zoneId ?? $this->zoneId;

        try {
            $response = $this->client()->patch(
                self::API_BASE . "/zones/{$zoneId}/settings/{$setting}",
                ['value' => $value]
            );

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => "ตั้งค่า {$setting} สำเร็จ",
                    'data' => $response->json('result'),
                ];
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * ดึงค่า setting เดี่ยว
     *
     * @param string $setting
     * @param string|null $zoneId
     * @return array
     */
    public function getSetting(string $setting, ?string $zoneId = null): array
    {
        $zoneId = $zoneId ?? $this->zoneId;

        try {
            $response = $this->client()->get(
                self::API_BASE . "/zones/{$zoneId}/settings/{$setting}"
            );

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('result'),
                ];
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * ดึง settings ทั้งหมดพร้อมกัน
     *
     * @param string|null $zoneId
     * @return array
     */
    public function getAllSettings(?string $zoneId = null): array
    {
        $zoneId = $zoneId ?? $this->zoneId;

        try {
            $response = $this->client()->get(
                self::API_BASE . "/zones/{$zoneId}/settings"
            );

            if ($response->successful()) {
                $settings = $response->json('result', []);

                // แปลงเป็น key-value array
                $settingsMap = [];
                foreach ($settings as $setting) {
                    $settingsMap[$setting['id']] = $setting['value'];
                }

                return [
                    'success' => true,
                    'data' => $settingsMap,
                    'raw' => $settings,
                ];
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * ตรวจสอบสถานะ optimization ปัจจุบัน
     *
     * @param string|null $zoneId
     * @return array
     */
    public function checkOptimizationStatus(?string $zoneId = null): array
    {
        $zoneId = $zoneId ?? $this->zoneId;

        $settingsResult = $this->getAllSettings($zoneId);

        if (!$settingsResult['success']) {
            return $settingsResult;
        }

        $current = $settingsResult['data'];

        // รายการ settings พร้อมข้อมูลละเอียด
        $optimizedSettings = [
            'brotli' => [
                'name' => 'Brotli Compression',
                'category' => 'performance',
                'optimal' => 'on',
                'description' => 'บีบอัดข้อมูลให้เล็กลง',
                'free_plan' => true,
            ],
            'early_hints' => [
                'name' => 'Early Hints',
                'category' => 'performance',
                'optimal' => 'on',
                'description' => 'ส่ง hints ล่วงหน้าให้ browser',
                'free_plan' => true,
            ],
            'http2' => [
                'name' => 'HTTP/2',
                'category' => 'performance',
                'optimal' => 'on',
                'description' => 'HTTP/2 protocol',
                'free_plan' => true,
            ],
            'http3' => [
                'name' => 'HTTP/3 (QUIC)',
                'category' => 'performance',
                'optimal' => 'on',
                'description' => 'HTTP/3 protocol',
                'free_plan' => true,
            ],
            '0rtt' => [
                'name' => '0-RTT',
                'category' => 'performance',
                'optimal' => 'on',
                'description' => 'ลด latency',
                'free_plan' => true,
            ],
            'rocket_loader' => [
                'name' => 'Rocket Loader',
                'category' => 'performance',
                'optimal' => 'on',
                'description' => 'Async load JavaScript',
                'free_plan' => true,
            ],
            'always_online' => [
                'name' => 'Always Online',
                'category' => 'seo',
                'optimal' => 'on',
                'description' => 'แสดงหน้าจาก cache เมื่อ server ล่ม',
                'free_plan' => true,
            ],
            'security_level' => [
                'name' => 'Security Level',
                'category' => 'security',
                'optimal' => 'medium',
                'description' => 'ระดับ security',
                'free_plan' => true,
            ],
            'browser_check' => [
                'name' => 'Browser Integrity Check',
                'category' => 'security',
                'optimal' => 'on',
                'description' => 'ตรวจสอบ browser headers',
                'free_plan' => true,
            ],
            'email_obfuscation' => [
                'name' => 'Email Obfuscation',
                'category' => 'security',
                'optimal' => 'on',
                'description' => 'ซ่อน email จาก bots',
                'free_plan' => true,
            ],
            'hotlink_protection' => [
                'name' => 'Hotlink Protection',
                'category' => 'security',
                'optimal' => 'on',
                'description' => 'ป้องกันขโมย bandwidth',
                'free_plan' => true,
            ],
            'opportunistic_encryption' => [
                'name' => 'Opportunistic Encryption',
                'category' => 'security',
                'optimal' => 'on',
                'description' => 'เข้ารหัส HTTP',
                'free_plan' => true,
            ],
            'ssl' => [
                'name' => 'SSL Mode',
                'category' => 'ssl',
                'optimal' => 'full',
                'description' => 'Full SSL encryption',
                'free_plan' => true,
            ],
            'always_use_https' => [
                'name' => 'Always Use HTTPS',
                'category' => 'ssl',
                'optimal' => 'on',
                'description' => 'Redirect HTTP → HTTPS',
                'free_plan' => true,
            ],
            'automatic_https_rewrites' => [
                'name' => 'Automatic HTTPS Rewrites',
                'category' => 'ssl',
                'optimal' => 'on',
                'description' => 'แก้ mixed content',
                'free_plan' => true,
            ],
            'cache_level' => [
                'name' => 'Cache Level',
                'category' => 'cache',
                'optimal' => 'aggressive',
                'description' => 'ระดับ caching',
                'free_plan' => true,
            ],
        ];

        $optimizedCount = 0;
        $status = [];

        foreach ($optimizedSettings as $key => $setting) {
            $currentValue = $current[$key] ?? null;
            $expectedValue = $setting['optimal'];
            $isOptimized = ($currentValue === $expectedValue);

            if ($isOptimized) {
                $optimizedCount++;
            }

            $status[$key] = [
                'name' => $setting['name'],
                'category' => $setting['category'],
                'description' => $setting['description'],
                'current' => $currentValue,
                'current_display' => $this->formatSettingValue($currentValue),
                'optimal' => $expectedValue,
                'optimal_display' => $this->formatSettingValue($expectedValue),
                'optimized' => $isOptimized,
                'free_plan' => $setting['free_plan'],
            ];
        }

        $totalSettings = count($optimizedSettings);
        $percentage = round(($optimizedCount / $totalSettings) * 100);

        return [
            'success' => true,
            'optimized_percentage' => $percentage,
            'optimized_count' => $optimizedCount,
            'total_count' => $totalSettings,
            'status' => $status,
            'message' => "ระดับ Optimization: {$percentage}% ({$optimizedCount}/{$totalSettings})",
        ];
    }

    // ========================================
    // AUTO UNDER ATTACK MODE
    // ========================================

    /**
     * ตรวจสอบว่าควรเปิด Under Attack Mode อัตโนมัติหรือไม่
     *
     * ตรวจสอบจาก:
     * - จำนวน failed logins ใน X นาที
     * - จำนวน rate limit exceeded ใน X นาที
     * - จำนวน turnstile failures ใน X นาที
     * - จำนวน threat IPs detected ใน X นาที
     *
     * @return array
     */
    public function checkAutoUnderAttackStatus(): array
    {
        $settings = $this->getAutoUnderAttackSettings();

        if (!$settings['enabled']) {
            return [
                'should_activate' => false,
                'reason' => 'Auto Under Attack Mode ถูกปิดใช้งาน',
                'metrics' => [],
            ];
        }

        $timeWindow = $settings['time_window']; // นาที
        $since = now()->subMinutes($timeWindow);

        $metrics = [
            'failed_logins' => 0,
            'rate_limit_exceeded' => 0,
            'turnstile_failures' => 0,
            'unique_attack_ips' => 0,
        ];

        // ตรวจสอบจาก SecurityLog (ถ้ามี)
        if (class_exists(\App\Models\SecurityLog::class)) {
            try {
                $metrics['failed_logins'] = \App\Models\SecurityLog::where('event_type', 'login_failed')
                    ->where('created_at', '>=', $since)
                    ->count();

                $metrics['rate_limit_exceeded'] = \App\Models\SecurityLog::where('event_type', 'rate_limit_exceeded')
                    ->where('created_at', '>=', $since)
                    ->count();

                $metrics['turnstile_failures'] = \App\Models\SecurityLog::where('event_type', 'turnstile_failed')
                    ->where('created_at', '>=', $since)
                    ->count();

                $metrics['unique_attack_ips'] = \App\Models\SecurityLog::whereIn('event_type', [
                    'login_failed', 'rate_limit_exceeded', 'turnstile_failed', 'blocked_by_threat_intelligence'
                ])
                    ->where('created_at', '>=', $since)
                    ->distinct('ip_address')
                    ->count('ip_address');
            } catch (\Exception $e) {
                Log::warning('CloudflareService: ไม่สามารถดึงข้อมูล SecurityLog', ['error' => $e->getMessage()]);
            }
        }

        // คำนวณ total threat score
        $threatScore = 0;
        $threatScore += $metrics['failed_logins'] * ($settings['weight_failed_login'] ?? 1);
        $threatScore += $metrics['rate_limit_exceeded'] * ($settings['weight_rate_limit'] ?? 2);
        $threatScore += $metrics['turnstile_failures'] * ($settings['weight_turnstile'] ?? 1);
        $threatScore += $metrics['unique_attack_ips'] * ($settings['weight_unique_ips'] ?? 3);

        $shouldActivate = $threatScore >= $settings['threshold'];

        $reasons = [];
        if ($metrics['failed_logins'] >= ($settings['max_failed_logins'] ?? 100)) {
            $reasons[] = "Failed logins สูง: {$metrics['failed_logins']}";
        }
        if ($metrics['rate_limit_exceeded'] >= ($settings['max_rate_limit'] ?? 50)) {
            $reasons[] = "Rate limit exceeded สูง: {$metrics['rate_limit_exceeded']}";
        }
        if ($metrics['unique_attack_ips'] >= ($settings['max_unique_ips'] ?? 30)) {
            $reasons[] = "Unique attack IPs สูง: {$metrics['unique_attack_ips']}";
        }

        return [
            'should_activate' => $shouldActivate,
            'reason' => $shouldActivate
                ? 'ตรวจพบการโจมตี! ' . implode(', ', $reasons)
                : 'ยังไม่ถึง threshold การโจมตี',
            'threat_score' => $threatScore,
            'threshold' => $settings['threshold'],
            'metrics' => $metrics,
            'time_window' => $timeWindow,
        ];
    }

    /**
     * เปิด Under Attack Mode อัตโนมัติ พร้อม log
     *
     * @param string $reason เหตุผลที่เปิด
     * @return array
     */
    public function autoEnableUnderAttackMode(string $reason = ''): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Cloudflare ยังไม่ได้ตั้งค่า',
            ];
        }

        // เปิด Under Attack Mode
        $result = $this->enableUnderAttackMode();

        if ($result['success']) {
            // บันทึกการเปิด Under Attack Mode
            \App\Models\Setting::set('cloudflare_under_attack_auto_enabled', true, 'boolean', 'cloudflare');
            \App\Models\Setting::set('cloudflare_under_attack_auto_enabled_at', now()->toIso8601String(), 'string', 'cloudflare');
            \App\Models\Setting::set('cloudflare_under_attack_auto_reason', $reason, 'string', 'cloudflare');

            // Log to SecurityLog ถ้ามี
            if (class_exists(\App\Models\SecurityLog::class)) {
                try {
                    \App\Models\SecurityLog::create([
                        'event_type' => 'cloudflare_under_attack_enabled',
                        'ip_address' => request()->ip() ?? '127.0.0.1',
                        'severity' => 'critical',
                        'description' => 'Auto-enabled Under Attack Mode: ' . $reason,
                        'user_agent' => 'System',
                    ]);
                } catch (\Exception $e) {
                    Log::warning('ไม่สามารถ log Under Attack Mode activation', ['error' => $e->getMessage()]);
                }
            }

            Log::critical('Cloudflare: AUTO ENABLED Under Attack Mode', ['reason' => $reason]);

            return [
                'success' => true,
                'message' => 'เปิด Under Attack Mode อัตโนมัติสำเร็จ',
                'reason' => $reason,
            ];
        }

        return $result;
    }

    /**
     * ปิด Under Attack Mode อัตโนมัติ เมื่อหมดเวลาที่กำหนด
     *
     * @return array
     */
    public function autoDisableUnderAttackMode(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Cloudflare ยังไม่ได้ตั้งค่า',
            ];
        }

        $settings = $this->getAutoUnderAttackSettings();

        // ตรวจสอบว่าเปิดอัตโนมัติอยู่หรือไม่
        $autoEnabled = \App\Models\Setting::get('cloudflare_under_attack_auto_enabled', false);

        if (!$autoEnabled) {
            return [
                'success' => false,
                'message' => 'Under Attack Mode ไม่ได้เปิดอัตโนมัติ',
            ];
        }

        $enabledAt = \App\Models\Setting::get('cloudflare_under_attack_auto_enabled_at');

        if (!$enabledAt) {
            return [
                'success' => false,
                'message' => 'ไม่พบเวลาที่เปิด Under Attack Mode',
            ];
        }

        $enabledAtTime = \Carbon\Carbon::parse($enabledAt);
        $duration = $settings['auto_disable_after'] ?? 30; // นาที

        // ถ้ายังไม่ถึงเวลาปิด
        if (now()->diffInMinutes($enabledAtTime) < $duration) {
            $remainingMinutes = $duration - now()->diffInMinutes($enabledAtTime);
            return [
                'success' => false,
                'message' => "ยังไม่ถึงเวลาปิด Under Attack Mode อีก {$remainingMinutes} นาที",
                'remaining_minutes' => $remainingMinutes,
            ];
        }

        // ปิด Under Attack Mode (กลับไป medium)
        $result = $this->setSecurityLevel('medium');

        if ($result['success']) {
            // ล้าง settings
            \App\Models\Setting::set('cloudflare_under_attack_auto_enabled', false, 'boolean', 'cloudflare');

            // Log to SecurityLog ถ้ามี
            if (class_exists(\App\Models\SecurityLog::class)) {
                try {
                    \App\Models\SecurityLog::create([
                        'event_type' => 'cloudflare_under_attack_disabled',
                        'ip_address' => '127.0.0.1',
                        'severity' => 'medium',
                        'description' => 'Auto-disabled Under Attack Mode หลังจากเปิดมา ' . $duration . ' นาที',
                        'user_agent' => 'System',
                    ]);
                } catch (\Exception $e) {
                    // Ignore
                }
            }

            Log::info('Cloudflare: AUTO DISABLED Under Attack Mode', ['duration' => $duration]);

            return [
                'success' => true,
                'message' => 'ปิด Under Attack Mode อัตโนมัติสำเร็จ',
            ];
        }

        return $result;
    }

    /**
     * ดึงการตั้งค่า Auto Under Attack Mode
     *
     * @return array
     */
    public function getAutoUnderAttackSettings(): array
    {
        return [
            'enabled' => (bool) \App\Models\Setting::get('cloudflare_auto_under_attack_enabled', false),
            'threshold' => (int) \App\Models\Setting::get('cloudflare_auto_under_attack_threshold', 150),
            'time_window' => (int) \App\Models\Setting::get('cloudflare_auto_under_attack_time_window', 5), // นาที
            'auto_disable_after' => (int) \App\Models\Setting::get('cloudflare_auto_under_attack_auto_disable', 30), // นาที
            'max_failed_logins' => (int) \App\Models\Setting::get('cloudflare_auto_under_attack_max_failed_logins', 100),
            'max_rate_limit' => (int) \App\Models\Setting::get('cloudflare_auto_under_attack_max_rate_limit', 50),
            'max_unique_ips' => (int) \App\Models\Setting::get('cloudflare_auto_under_attack_max_unique_ips', 30),
            'weight_failed_login' => (int) \App\Models\Setting::get('cloudflare_auto_under_attack_weight_failed_login', 1),
            'weight_rate_limit' => (int) \App\Models\Setting::get('cloudflare_auto_under_attack_weight_rate_limit', 2),
            'weight_turnstile' => (int) \App\Models\Setting::get('cloudflare_auto_under_attack_weight_turnstile', 1),
            'weight_unique_ips' => (int) \App\Models\Setting::get('cloudflare_auto_under_attack_weight_unique_ips', 3),
        ];
    }

    /**
     * บันทึกการตั้งค่า Auto Under Attack Mode
     *
     * @param array $settings
     * @return array
     */
    public static function saveAutoUnderAttackSettings(array $settings): array
    {
        try {
            $settingsMap = [
                'enabled' => ['key' => 'cloudflare_auto_under_attack_enabled', 'type' => 'boolean'],
                'threshold' => ['key' => 'cloudflare_auto_under_attack_threshold', 'type' => 'integer'],
                'time_window' => ['key' => 'cloudflare_auto_under_attack_time_window', 'type' => 'integer'],
                'auto_disable_after' => ['key' => 'cloudflare_auto_under_attack_auto_disable', 'type' => 'integer'],
                'max_failed_logins' => ['key' => 'cloudflare_auto_under_attack_max_failed_logins', 'type' => 'integer'],
                'max_rate_limit' => ['key' => 'cloudflare_auto_under_attack_max_rate_limit', 'type' => 'integer'],
                'max_unique_ips' => ['key' => 'cloudflare_auto_under_attack_max_unique_ips', 'type' => 'integer'],
            ];

            foreach ($settings as $key => $value) {
                if (isset($settingsMap[$key])) {
                    \App\Models\Setting::set(
                        $settingsMap[$key]['key'],
                        $value,
                        $settingsMap[$key]['type'],
                        'cloudflare'
                    );
                }
            }

            return [
                'success' => true,
                'message' => 'บันทึกการตั้งค่า Auto Under Attack Mode สำเร็จ',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * ดึงสถานะ Under Attack Mode ปัจจุบัน
     *
     * @return array
     */
    public function getUnderAttackStatus(): array
    {
        $autoEnabled = \App\Models\Setting::get('cloudflare_under_attack_auto_enabled', false);
        $autoEnabledAt = \App\Models\Setting::get('cloudflare_under_attack_auto_enabled_at');
        $autoReason = \App\Models\Setting::get('cloudflare_under_attack_auto_reason', '');

        // ดึง security level ปัจจุบัน
        $currentLevel = null;
        if ($this->isConfigured()) {
            $levelResult = $this->getSecurityLevel();
            if ($levelResult['success']) {
                $currentLevel = $levelResult['data']['value'] ?? 'unknown';
            }
        }

        $isUnderAttack = $currentLevel === 'under_attack';

        return [
            'is_under_attack' => $isUnderAttack,
            'current_security_level' => $currentLevel,
            'auto_enabled' => $autoEnabled,
            'auto_enabled_at' => $autoEnabledAt,
            'auto_reason' => $autoReason,
        ];
    }

    /**
     * แปลงค่า setting ให้อ่านง่าย
     */
    protected function formatSettingValue($value): string
    {
        if ($value === null) {
            return 'ไม่พบ';
        }
        if ($value === 'on') {
            return '✅ เปิด';
        }
        if ($value === 'off') {
            return '❌ ปิด';
        }
        if (is_array($value)) {
            return json_encode($value);
        }
        return (string) $value;
    }

    // ========================================
    // ERROR HANDLING
    // ========================================

    /**
     * จัดการ API Error
     */
    protected function handleError($response): array
    {
        $errors = $response->json('errors', []);
        $message = !empty($errors) ? $errors[0]['message'] ?? 'Unknown error' : 'API request failed';

        Log::error('Cloudflare API Error', [
            'status' => $response->status(),
            'errors' => $errors,
        ]);

        return [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ];
    }

    /**
     * จัดการ Exception
     */
    protected function handleException(\Exception $e): array
    {
        Log::error('Cloudflare Exception', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return [
            'success' => false,
            'message' => $e->getMessage(),
        ];
    }
}
