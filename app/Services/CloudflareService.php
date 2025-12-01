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
     */
    public function __construct()
    {
        $this->apiToken = config('services.cloudflare.api_token');
        $this->zoneId = config('services.cloudflare.zone_id');
    }

    /**
     * ตรวจสอบว่า configured หรือยัง
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiToken) && !empty($this->zoneId);
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
