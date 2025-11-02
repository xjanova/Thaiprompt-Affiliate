<?php

namespace App\Services;

use App\Models\ThreatIp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ThreatIntelligenceService
{
    /**
     * Update threat intelligence from all sources
     */
    public function updateAllSources(): array
    {
        $results = [
            'firehol' => $this->updateFromFirehol(),
            'total_updated' => 0,
            'total_errors' => 0,
        ];

        $results['total_updated'] = array_sum(array_column(array_filter($results['firehol'], 'is_array'), 'updated'));
        $results['total_errors'] = count(array_filter($results['firehol'], fn($r) => isset($r['error'])));

        // Clear cache
        Cache::forget('threat_intelligence_stats');

        return $results;
    }

    /**
     * Update from Firehol blocklists (Free, No API key needed)
     */
    public function updateFromFirehol(): array
    {
        $lists = [
            'level1' => [
                'url' => 'https://iplists.firehol.org/files/firehol_level1.netset',
                'type' => 'abuse',
                'confidence' => 90,
            ],
            'proxies' => [
                'url' => 'https://iplists.firehol.org/files/firehol_proxies.netset',
                'type' => 'proxy',
                'confidence' => 80,
            ],
            'webserver' => [
                'url' => 'https://iplists.firehol.org/files/firehol_webserver.netset',
                'type' => 'scanner',
                'confidence' => 75,
            ],
        ];

        $results = [];

        foreach ($lists as $name => $config) {
            try {
                $response = Http::timeout(30)->get($config['url']);

                if ($response->successful()) {
                    $updated = $this->parseAndStoreFireholList(
                        $response->body(),
                        $config['type'],
                        $config['confidence']
                    );

                    $results[$name] = [
                        'success' => true,
                        'updated' => $updated,
                    ];

                    Log::info("Firehol {$name} list updated successfully", [
                        'count' => $updated,
                    ]);
                } else {
                    $results[$name] = [
                        'error' => 'HTTP ' . $response->status(),
                    ];

                    Log::error("Failed to fetch Firehol {$name} list", [
                        'status' => $response->status(),
                    ]);
                }
            } catch (\Exception $e) {
                $results[$name] = [
                    'error' => $e->getMessage(),
                ];

                Log::error("Error fetching Firehol {$name} list", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Parse and store Firehol list
     */
    protected function parseAndStoreFireholList(string $content, string $type, int $confidence): int
    {
        $lines = explode("\n", $content);
        $updated = 0;

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Parse IP or CIDR
            if ($this->isValidIpOrCidr($line)) {
                // For CIDR, store as is for now (we'll handle checking later)
                $ip = $line;

                ThreatIp::updateOrCreate(
                    [
                        'ip_address' => $ip,
                        'source' => 'firehol',
                    ],
                    [
                        'threat_type' => $type,
                        'confidence_score' => $confidence,
                        'last_seen' => now(),
                        'expires_at' => now()->addDays(7), // Refresh weekly
                    ]
                );

                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Check IP with AbuseIPDB (requires API key)
     */
    public function checkAbuseIPDB(string $ip): ?array
    {
        $apiKey = env('ABUSEIPDB_API_KEY');

        if (empty($apiKey)) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Key' => $apiKey,
                'Accept' => 'application/json',
            ])->get('https://api.abuseipdb.com/api/v2/check', [
                'ipAddress' => $ip,
                'maxAgeInDays' => 90,
            ]);

            if ($response->successful()) {
                $data = $response->json()['data'] ?? null;

                if ($data && $data['abuseConfidenceScore'] > 0) {
                    // Store in database
                    ThreatIp::updateOrCreate(
                        [
                            'ip_address' => $ip,
                            'source' => 'abuseipdb',
                        ],
                        [
                            'threat_type' => 'abuse',
                            'confidence_score' => $data['abuseConfidenceScore'],
                            'details' => [
                                'total_reports' => $data['totalReports'],
                                'country_code' => $data['countryCode'],
                                'usage_type' => $data['usageType'] ?? null,
                                'isp' => $data['isp'] ?? null,
                            ],
                            'last_seen' => now(),
                            'expires_at' => now()->addDays(30),
                        ]
                    );

                    return $data;
                }
            }
        } catch (\Exception $e) {
            Log::error('AbuseIPDB check failed', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Check IP with IPQualityScore (requires API key)
     */
    public function checkIPQualityScore(string $ip): ?array
    {
        $apiKey = env('IPQUALITYSCORE_API_KEY');

        if (empty($apiKey)) {
            return null;
        }

        try {
            $response = Http::get("https://www.ipqualityscore.com/api/json/ip/{$apiKey}/{$ip}", [
                'strictness' => 1,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['success']) {
                    // Determine threat type
                    $threatType = 'other';
                    if ($data['proxy']) $threatType = 'proxy';
                    elseif ($data['vpn']) $threatType = 'vpn';
                    elseif ($data['tor']) $threatType = 'tor';

                    // Store in database
                    ThreatIp::updateOrCreate(
                        [
                            'ip_address' => $ip,
                            'source' => 'ipqualityscore',
                        ],
                        [
                            'threat_type' => $threatType,
                            'confidence_score' => $data['fraud_score'] ?? 0,
                            'details' => [
                                'proxy' => $data['proxy'],
                                'vpn' => $data['vpn'],
                                'tor' => $data['tor'],
                                'country_code' => $data['country_code'],
                                'isp' => $data['ISP'] ?? null,
                                'organization' => $data['organization'] ?? null,
                            ],
                            'last_seen' => now(),
                            'expires_at' => now()->addDays(30),
                        ]
                    );

                    return $data;
                }
            }
        } catch (\Exception $e) {
            Log::error('IPQualityScore check failed', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Check if IP is valid IP or CIDR
     */
    protected function isValidIpOrCidr(string $value): bool
    {
        // Check if it's a valid IP
        if (filter_var($value, FILTER_VALIDATE_IP)) {
            return true;
        }

        // Check if it's a valid CIDR
        if (str_contains($value, '/')) {
            list($ip, $mask) = explode('/', $value);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $mask = (int)$mask;
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    return $mask >= 0 && $mask <= 32;
                } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    return $mask >= 0 && $mask <= 128;
                }
            }
        }

        return false;
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        return Cache::remember('threat_intelligence_stats', 3600, function () {
            return [
                'total_threats' => ThreatIp::active()->count(),
                'by_type' => ThreatIp::active()
                    ->selectRaw('threat_type, COUNT(*) as count')
                    ->groupBy('threat_type')
                    ->pluck('count', 'threat_type')
                    ->toArray(),
                'by_source' => ThreatIp::active()
                    ->selectRaw('source, COUNT(*) as count')
                    ->groupBy('source')
                    ->pluck('count', 'source')
                    ->toArray(),
                'high_confidence' => ThreatIp::active()->highConfidence(80)->count(),
                'last_update' => ThreatIp::latest('updated_at')->value('updated_at'),
            ];
        });
    }

    /**
     * Clean expired threats
     */
    public function cleanExpired(): int
    {
        return ThreatIp::where('expires_at', '<', now())->delete();
    }
}
