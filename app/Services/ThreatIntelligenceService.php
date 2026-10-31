<?php

namespace App\Services;

use App\Models\ThreatIp;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ThreatIntelligenceService
{
    /**
     * จำนวน IP ต่อหนึ่งคำสั่ง upsert — ทั้งเพดานหน่วยความจำของงานนี้และขนาดของหนึ่ง transaction
     */
    protected const UPSERT_BATCH_SIZE = 1000;

    /**
     * จำนวนแถวต่อรอบที่ลบของหมดอายุ — ไม่ให้ DELETE ก้อนเดียวถือ lock ทั้งตารางหลายล้านแถว
     */
    protected const DELETE_BATCH_SIZE = 10000;

    /**
     * เวลาสูงสุดในการโหลดหนึ่งฟีด (วินาที)
     *
     * ฟีด proxies 41 MB ตอนเที่ยงคืนบน production เคยได้แค่ 14.6 MB ใน 30 วินาที
     * (ราว 85 วินาทีต่อไฟล์) จึงเผื่อไว้สองเท่า
     */
    protected const FEED_TIMEOUT_SECONDS = 180;

    /**
     * รายงานความคืบหน้าทุกกี่ batch — ให้ TTL ของ progress ไม่หมดระหว่างบันทึกฟีดใหญ่
     */
    protected const PROGRESS_EVERY_BATCHES = 100;

    /**
     * อายุ cache ของสถิติ (วินาที) — ข้อมูลเปลี่ยนวันละครั้ง และ threat:update สร้าง cache ใหม่ให้ทุกรอบ
     * จึงเผื่อให้อยู่ข้ามรอบรายวัน เพื่อไม่ให้หน้า admin ต้องนับหลายล้านแถวเอง
     */
    protected const STATS_CACHE_SECONDS = 93600;

    /**
     * ธงว่าแอดมินกดอัปเดตจากหน้าเว็บ รอ scheduler มารับไปรัน
     */
    protected const UPDATE_REQUEST_KEY = 'threat_update_requested';

    /**
     * อายุของ progress และธง (วินาที) — ถ้าไม่มีใครมารับงาน ทั้งคู่หายพร้อมกันแล้วกดใหม่ได้
     */
    protected const PROGRESS_TTL_SECONDS = 300;

    /**
     * Update threat intelligence from all sources
     */
    public function updateAllSources(): array
    {
        // Initialize progress
        $this->updateProgress(0, 'เริ่มต้นการอัปเดต Threat Intelligence...');

        $results = [
            'firehol' => $this->updateFromFirehol(),
            'total_updated' => 0,
            'total_errors' => 0,
        ];

        $results['total_updated'] = array_sum(array_column(array_filter($results['firehol'], 'is_array'), 'updated'));
        $results['total_errors'] = count(array_filter($results['firehol'], fn ($r) => isset($r['error'])));

        // Clear cache
        Cache::forget('threat_intelligence_stats');

        // Mark as complete
        $this->updateProgress(100, 'อัปเดตเสร็จสิ้น! อัปเดตทั้งหมด '.number_format($results['total_updated']).' รายการ');

        return $results;
    }

    /**
     * Update progress
     */
    protected function updateProgress(int $percentage, string $message, array $details = []): void
    {
        Cache::put('threat_update_progress', [
            'percentage' => $percentage,
            'message' => $message,
            'details' => $details,
            'updated_at' => now()->toIso8601String(),
        ], self::PROGRESS_TTL_SECONDS);
    }

    /**
     * ขอให้ scheduler รันอัปเดตเบื้องหลัง
     *
     * @return bool false = มีงานรออยู่หรือกำลังรันอยู่แล้ว กดซ้ำจึงไม่เกิดงานซ้อน
     */
    public function requestBackgroundUpdate(): bool
    {
        $progress = $this->getProgress();

        if ($progress !== null && $progress['percentage'] < 100) {
            return false;
        }

        if (! Cache::add(self::UPDATE_REQUEST_KEY, true, self::PROGRESS_TTL_SECONDS)) {
            return false;
        }

        $this->updateProgress(0, 'รอเริ่มอัปเดตเบื้องหลัง... (ไม่เกิน 1 นาที)');

        return true;
    }

    /**
     * scheduler รับธงไปรัน — ลบทันทีเพื่อให้กดหนึ่งครั้งได้งานหนึ่งรอบ
     */
    public function takeBackgroundUpdateRequest(): bool
    {
        return (bool) Cache::pull(self::UPDATE_REQUEST_KEY);
    }

    /**
     * Get current progress
     */
    public function getProgress(): ?array
    {
        return Cache::get('threat_update_progress');
    }

    /**
     * Clear progress
     */
    public function clearProgress(): void
    {
        Cache::forget('threat_update_progress');
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
                'name' => 'Firehol Level 1 (Abuse IPs)',
            ],
            'proxies' => [
                'url' => 'https://iplists.firehol.org/files/firehol_proxies.netset',
                'type' => 'proxy',
                'confidence' => 80,
                'name' => 'Firehol Proxies',
            ],
            'webserver' => [
                'url' => 'https://iplists.firehol.org/files/firehol_webserver.netset',
                'type' => 'scanner',
                'confidence' => 75,
                'name' => 'Firehol Webserver Attacks',
            ],
        ];

        $results = [];
        $totalLists = count($lists);
        $currentList = 0;

        foreach ($lists as $name => $config) {
            $currentList++;
            $percentage = intval(($currentList / $totalLists) * 90); // Reserve 0-10% for initialization, 90-100% for completion

            $this->updateProgress(
                $percentage,
                "กำลังดาวน์โหลด {$config['name']}... ({$currentList}/{$totalLists})"
            );

            $feedPath = null;
            $updated = 0;

            try {
                // โหลดลงไฟล์ก่อนแตะ DB: โหลดไม่ครบ = รายการเดิมอยู่ครบ และไม่ต้องถือฟีดทั้งก้อนในหน่วยความจำ
                $feedPath = tempnam(sys_get_temp_dir(), 'firehol_');
                $response = $this->downloadFeed($config['url'], $feedPath);

                if ($response->successful()) {
                    $this->updateProgress(
                        $percentage,
                        "กำลังประมวลผล {$config['name']}... ({$currentList}/{$totalLists})"
                    );

                    // ไม่ครอบ transaction ทั้งลิสต์: ล้มกลางทาง = แถวที่ทำแล้วสดใหม่ ที่เหลือคงอายุเดิม
                    // ไม่มีแถวหาย และรอบถัดไปเขียนทับต่อได้เลยเพราะ upsert ซ้ำได้
                    $batches = 0;
                    foreach ($this->readFeedBatches($feedPath) as $ips) {
                        $updated += $this->storeThreatBatch($ips, $config['type'], $config['confidence'], 'firehol');

                        if (++$batches % self::PROGRESS_EVERY_BATCHES === 0) {
                            $this->updateProgress(
                                $percentage,
                                "กำลังบันทึก {$config['name']}... ".number_format($updated).' รายการ'
                            );
                        }
                    }

                    if ($updated === 0) {
                        throw new \RuntimeException('ฟีดไม่มีรายการ IP ที่ใช้ได้');
                    }

                    $results[$name] = [
                        'success' => true,
                        'updated' => $updated,
                    ];

                    Log::info("Firehol {$name} list updated successfully", [
                        'count' => $updated,
                    ]);
                } else {
                    $results[$name] = [
                        'error' => 'HTTP '.$response->status(),
                    ];

                    Log::error("Failed to fetch Firehol {$name} list", [
                        'status' => $response->status(),
                    ]);
                }
            } catch (\Throwable $e) {
                // ข้อความ QueryException มี SQL พร้อม IP ทั้ง batch — ตัดให้สั้นก่อนลง log และส่งกลับหน้า admin
                $error = Str::limit($e->getMessage(), 300);

                // เก็บ updated ไว้ด้วย: ยอดนี้ถูกเขียนลง DB จริงก่อนล้ม
                $results[$name] = [
                    'error' => $error,
                    'updated' => $updated,
                ];

                Log::error("Error fetching Firehol {$name} list", [
                    'error' => $error,
                    'updated' => $updated,
                ]);
            } finally {
                if (is_string($feedPath) && file_exists($feedPath)) {
                    unlink($feedPath);
                }
            }
        }

        return $results;
    }

    /**
     * โหลดฟีดลงไฟล์โดยไม่ผ่านหน่วยความจำ
     */
    protected function downloadFeed(string $url, string $path): Response
    {
        return Http::connectTimeout(15)
            ->timeout(self::FEED_TIMEOUT_SECONDS)
            ->sink($path)
            ->get($url);
    }

    /**
     * อ่านไฟล์ฟีดทีละบรรทัดแล้วคืน IP/CIDR ที่ใช้ได้เป็นชุด ๆ
     *
     * fgets จำกัดความยาวบรรทัดไว้ ถ้าปลายทางส่งไฟล์ที่ไม่มีขึ้นบรรทัดมาจะได้ไม่อ่านทั้งก้อนเข้าหน่วยความจำ
     *
     * @return \Generator<int, array<int, string>>
     */
    protected function readFeedBatches(string $path): \Generator
    {
        $handle = fopen($path, 'r');
        $batch = [];

        try {
            while (($line = fgets($handle, 256)) !== false) {
                $line = trim($line);

                // ข้ามบรรทัดว่างและ comment
                if ($line === '' || str_starts_with($line, '#') || ! $this->isValidIpOrCidr($line)) {
                    continue;
                }

                // CIDR เก็บตามที่ฟีดส่งมา
                $batch[] = $line;

                if (count($batch) >= self::UPSERT_BATCH_SIZE) {
                    yield $batch;
                    $batch = [];
                }
            }

            if ($batch !== []) {
                yield $batch;
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * บันทึก IP หนึ่งชุดด้วยคำสั่งเดียว
     *
     * ต้องอาศัย unique index (ip_address, source) — ถ้าไม่มี MySQL จะ insert แถวซ้ำทุกรอบ
     *
     * @param  array<int, string>  $ips
     * @return int จำนวน IP ที่บันทึก
     */
    protected function storeThreatBatch(array $ips, string $type, int $confidence, string $source): int
    {
        $lastSeen = now();
        $expiresAt = $lastSeen->copy()->addDays(7); // Refresh weekly

        $rows = array_map(fn (string $ip) => [
            'ip_address' => $ip,
            'source' => $source,
            'threat_type' => $type,
            'confidence_score' => $confidence,
            'last_seen' => $lastSeen,
            'expires_at' => $expiresAt,
        ], $ips);

        ThreatIp::upsert(
            $rows,
            ['ip_address', 'source'],
            ['threat_type', 'confidence_score', 'last_seen', 'expires_at']
        );

        return count($rows);
    }

    /**
     * Check IP with AbuseIPDB (requires API key)
     */
    public function checkAbuseIPDB(string $ip): ?array
    {
        $apiKey = config('services.abuseipdb.api_key');

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
        $apiKey = config('services.ipqualityscore.api_key');

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
                    if ($data['proxy']) {
                        $threatType = 'proxy';
                    } elseif ($data['vpn']) {
                        $threatType = 'vpn';
                    } elseif ($data['tor']) {
                        $threatType = 'tor';
                    }

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
            [$ip, $mask] = explode('/', $value);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $mask = (int) $mask;
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
        return Cache::remember('threat_intelligence_stats', self::STATS_CACHE_SECONDS, function () {
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
        $deleted = 0;

        do {
            $batch = ThreatIp::where('expires_at', '<', now())
                ->limit(self::DELETE_BATCH_SIZE)
                ->delete();

            $deleted += $batch;
        } while ($batch === self::DELETE_BATCH_SIZE);

        return $deleted;
    }
}
