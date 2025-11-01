<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IpIntelligenceService
{
    /**
     * Get IP geolocation and intelligence data
     */
    public static function getIpInfo(string $ip): array
    {
        // Skip for local IPs
        if (self::isLocalIp($ip)) {
            return [
                'country_code' => 'XX',
                'country_name' => 'Local',
                'city' => 'localhost',
                'latitude' => null,
                'longitude' => null,
                'is_vpn' => false,
                'is_proxy' => false,
                'is_tor' => false,
            ];
        }

        // Check cache first (cache for 24 hours)
        $cacheKey = "ip_info:{$ip}";

        return Cache::remember($cacheKey, 86400, function () use ($ip) {
            return self::fetchIpInfo($ip);
        });
    }

    /**
     * Fetch IP info from API
     */
    protected static function fetchIpInfo(string $ip): array
    {
        try {
            // Using ip-api.com (free, no API key required)
            // Limit: 45 requests per minute
            $response = Http::timeout(5)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'status,message,country,countryCode,city,lat,lon,proxy,hosting',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'success') {
                    return [
                        'country_code' => $data['countryCode'] ?? null,
                        'country_name' => $data['country'] ?? null,
                        'city' => $data['city'] ?? null,
                        'latitude' => $data['lat'] ?? null,
                        'longitude' => $data['lon'] ?? null,
                        'is_vpn' => false, // ip-api.com free version doesn't provide this
                        'is_proxy' => $data['proxy'] ?? false,
                        'is_tor' => false,
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning("Failed to fetch IP info for {$ip}: " . $e->getMessage());
        }

        // Fallback data
        return [
            'country_code' => null,
            'country_name' => null,
            'city' => null,
            'latitude' => null,
            'longitude' => null,
            'is_vpn' => false,
            'is_proxy' => false,
            'is_tor' => false,
        ];
    }

    /**
     * Check if IP is local
     */
    protected static function isLocalIp(string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', '::1', 'localhost']) ||
               str_starts_with($ip, '192.168.') ||
               str_starts_with($ip, '10.') ||
               str_starts_with($ip, '172.16.');
    }

    /**
     * Get country flag emoji
     */
    public static function getCountryFlag(?string $countryCode): string
    {
        if (!$countryCode || strlen($countryCode) !== 2) {
            return '🏴';
        }

        // Convert country code to flag emoji
        $countryCode = strtoupper($countryCode);
        $firstLetter = mb_chr(ord($countryCode[0]) + 127397);
        $secondLetter = mb_chr(ord($countryCode[1]) + 127397);

        return $firstLetter . $secondLetter;
    }

    /**
     * Get VPN/Proxy detection (enhanced version with multiple services)
     */
    public static function detectVpnProxy(string $ip): array
    {
        if (self::isLocalIp($ip)) {
            return ['is_vpn' => false, 'is_proxy' => false, 'is_tor' => false];
        }

        try {
            // Using proxycheck.io (free tier: 1000 requests/day)
            $apiKey = env('PROXYCHECK_API_KEY');

            if ($apiKey) {
                $response = Http::timeout(5)->get("https://proxycheck.io/v2/{$ip}", [
                    'key' => $apiKey,
                    'vpn' => 1,
                    'asn' => 1,
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data[$ip])) {
                        return [
                            'is_vpn' => $data[$ip]['proxy'] === 'yes',
                            'is_proxy' => $data[$ip]['proxy'] === 'yes',
                            'is_tor' => $data[$ip]['type'] === 'TOR' ?? false,
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug("VPN detection failed for {$ip}: " . $e->getMessage());
        }

        return ['is_vpn' => false, 'is_proxy' => false, 'is_tor' => false];
    }
}
