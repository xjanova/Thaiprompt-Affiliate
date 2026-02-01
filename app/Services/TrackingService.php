<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderTrackingHistory;
use App\Models\ShippingProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TrackingService - บริการติดตามพัสดุแบบ Realtime
 *
 * เชื่อมต่อกับบริษัทขนส่งชั้นนำของไทย:
 * - Thailand Post (ไปรษณีย์ไทย)
 * - Kerry Express
 * - Flash Express
 * - J&T Express
 * - Ninja Van
 * - SCG Express (BEST Express เดิม)
 * - Best Express
 * - DHL Express
 *
 * รองรับการ query สถานะพัสดุแบบ realtime ผ่าน API/Web scraping
 * พร้อม caching เพื่อลด request ซ้ำ
 */
class TrackingService
{
    /**
     * ระยะเวลา cache (วินาที) - 10 นาที
     */
    const CACHE_TTL = 600;

    /**
     * Timeout สำหรับ HTTP request (วินาที)
     */
    const HTTP_TIMEOUT = 15;

    /**
     * รหัสขนส่งที่รองรับ พร้อมข้อมูลเบื้องต้น
     */
    const SUPPORTED_CARRIERS = [
        'thaipost' => [
            'name' => 'ไปรษณีย์ไทย',
            'name_en' => 'Thailand Post',
            'tracking_url' => 'https://track.thailandpost.co.th/?trackNumber={tracking_number}',
            'api_url' => 'https://track.thailandpost.co.th/post/api/v1/track',
            'logo' => '/images/carriers/thaipost.png',
            'hotline' => '1545',
            'website' => 'https://www.thailandpost.co.th',
        ],
        'kerry' => [
            'name' => 'Kerry Express',
            'name_en' => 'Kerry Express',
            'tracking_url' => 'https://th.kerryexpress.com/th/track/?track={tracking_number}',
            'api_url' => 'https://th.kerryexpress.com/api/shipment/tracking',
            'logo' => '/images/carriers/kerry.png',
            'hotline' => '1217',
            'website' => 'https://th.kerryexpress.com',
        ],
        'flash' => [
            'name' => 'Flash Express',
            'name_en' => 'Flash Express',
            'tracking_url' => 'https://www.flashexpress.co.th/tracking/?se={tracking_number}',
            'api_url' => 'https://flashexpress.com/fle/tracking/all',
            'logo' => '/images/carriers/flash.png',
            'hotline' => '1247',
            'website' => 'https://www.flashexpress.co.th',
        ],
        'jt' => [
            'name' => 'J&T Express',
            'name_en' => 'J&T Express',
            'tracking_url' => 'https://www.jtexpress.co.th/service/track?bills={tracking_number}',
            'api_url' => 'https://www.jtexpress.co.th/api/tracker/track',
            'logo' => '/images/carriers/jt.png',
            'hotline' => '021-017-017',
            'website' => 'https://www.jtexpress.co.th',
        ],
        'ninja' => [
            'name' => 'Ninja Van',
            'name_en' => 'Ninja Van',
            'tracking_url' => 'https://www.ninjavan.co/th-th/tracking?id={tracking_number}',
            'api_url' => 'https://api.ninjavan.co/th/v2/tracking',
            'logo' => '/images/carriers/ninja.png',
            'hotline' => '02-092-9000',
            'website' => 'https://www.ninjavan.co',
        ],
        'scg' => [
            'name' => 'SCG Express',
            'name_en' => 'SCG Express',
            'tracking_url' => 'https://www.scgexpress.co.th/tracking/?refNo={tracking_number}',
            'api_url' => 'https://www.scgexpress.co.th/api/tracking',
            'logo' => '/images/carriers/scg.png',
            'hotline' => '02-271-0999',
            'website' => 'https://www.scgexpress.co.th',
        ],
        'best' => [
            'name' => 'Best Express',
            'name_en' => 'Best Express',
            'tracking_url' => 'https://www.best-inc.co.th/track?bills={tracking_number}',
            'api_url' => 'https://www.best-inc.co.th/api/tracking',
            'logo' => '/images/carriers/best.png',
            'hotline' => '02-106-8900',
            'website' => 'https://www.best-inc.co.th',
        ],
        'dhl' => [
            'name' => 'DHL Express',
            'name_en' => 'DHL Express',
            'tracking_url' => 'https://www.dhl.com/th-th/home/tracking.html?tracking-id={tracking_number}',
            'api_url' => 'https://api-eu.dhl.com/track/shipments',
            'logo' => '/images/carriers/dhl.png',
            'hotline' => '02-345-5000',
            'website' => 'https://www.dhl.com',
        ],
    ];

    /**
     * Mapping สถานะจากขนส่งต่างๆ ให้เป็นสถานะมาตรฐาน
     */
    const STATUS_MAP = [
        // สถานะมาตรฐาน
        'pending' => 'รอรับพัสดุ',
        'picked_up' => 'รับพัสดุแล้ว',
        'in_transit' => 'อยู่ระหว่างขนส่ง',
        'at_sorting_center' => 'ถึงศูนย์คัดแยก',
        'out_for_delivery' => 'กำลังนำส่ง',
        'delivered' => 'จัดส่งสำเร็จ',
        'failed_delivery' => 'นำส่งไม่สำเร็จ',
        'returned' => 'ส่งคืนผู้ส่ง',
        'exception' => 'มีปัญหา',
    ];

    /**
     * ติดตามพัสดุ realtime - ดึงข้อมูลล่าสุดจากขนส่ง
     *
     * @param string $trackingNumber เลขพัสดุ
     * @param string $providerCode รหัสขนส่ง (thaipost, kerry, flash, etc.)
     * @param bool $forceRefresh บังคับดึงข้อมูลใหม่ (ไม่ใช้ cache)
     * @return array ข้อมูลติดตามพัสดุ
     */
    public function track(string $trackingNumber, string $providerCode, bool $forceRefresh = false): array
    {
        $providerCode = $this->normalizeProviderCode($providerCode);

        // ตรวจสอบว่ารองรับขนส่งนี้หรือไม่
        if (!isset(self::SUPPORTED_CARRIERS[$providerCode])) {
            return $this->buildResponse(
                trackingNumber: $trackingNumber,
                provider: $providerCode,
                success: false,
                message: 'ไม่รองรับบริษัทขนส่งนี้',
            );
        }

        // ตรวจสอบ cache (ถ้าไม่บังคับ refresh)
        $cacheKey = "tracking:{$providerCode}:{$trackingNumber}";
        if (!$forceRefresh && Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            $cached['from_cache'] = true;
            return $cached;
        }

        // ดึงข้อมูลจาก API ของขนส่ง
        try {
            $result = $this->fetchFromCarrier($trackingNumber, $providerCode);

            // Cache ผลลัพธ์
            if ($result['success']) {
                Cache::put($cacheKey, $result, self::CACHE_TTL);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("TrackingService: ไม่สามารถดึงข้อมูลติดตามได้", [
                'tracking_number' => $trackingNumber,
                'provider' => $providerCode,
                'error' => $e->getMessage(),
            ]);

            // ถ้ามี cache เก่า ใช้ cache เก่าไปก่อน
            if (Cache::has($cacheKey)) {
                $cached = Cache::get($cacheKey);
                $cached['from_cache'] = true;
                $cached['cache_note'] = 'ใช้ข้อมูล cache เนื่องจากไม่สามารถเชื่อมต่อขนส่งได้';
                return $cached;
            }

            return $this->buildResponse(
                trackingNumber: $trackingNumber,
                provider: $providerCode,
                success: false,
                message: 'ไม่สามารถเชื่อมต่อกับระบบขนส่งได้ กรุณาลองใหม่อีกครั้ง',
            );
        }
    }

    /**
     * ติดตามพัสดุจาก Order โดยตรง
     *
     * @param Order $order คำสั่งซื้อ
     * @param bool $forceRefresh บังคับดึงข้อมูลใหม่
     * @return array ข้อมูลติดตามพร้อม tracking history ในระบบ
     */
    public function trackOrder(Order $order, bool $forceRefresh = false): array
    {
        if (!$order->tracking_number) {
            return [
                'success' => false,
                'message' => 'คำสั่งซื้อนี้ยังไม่มีเลขพัสดุ',
                'order_status' => $order->status,
                'order_status_label' => $order->status_label,
            ];
        }

        // ดึงข้อมูล provider
        $providerCode = $this->resolveProviderCode($order);

        // ดึงข้อมูลติดตามจากขนส่ง
        $trackingResult = $this->track($order->tracking_number, $providerCode, $forceRefresh);

        // รวมกับ tracking history ในระบบ
        $internalHistory = $order->trackingHistory()
            ->orderBy('tracked_at', 'desc')
            ->get()
            ->map(fn($h) => [
                'status' => $h->status,
                'title' => $h->title,
                'description' => $h->description,
                'location' => $h->location,
                'timestamp' => $h->tracked_at->format('Y-m-d H:i:s'),
                'timestamp_display' => $h->tracked_at->format('d/m/Y H:i'),
                'source' => $h->created_by_type,
                'icon' => $h->status_icon,
                'color' => $h->status_color,
            ])
            ->toArray();

        // รวม timeline จากขนส่งกับระบบภายใน
        $mergedTimeline = $this->mergeTimelines(
            $trackingResult['timeline'] ?? [],
            $internalHistory
        );

        return [
            'success' => true,
            'tracking_number' => $order->tracking_number,
            'provider' => [
                'code' => $providerCode,
                'name' => self::SUPPORTED_CARRIERS[$providerCode]['name'] ?? $order->shipping_provider ?? 'ไม่ทราบ',
                'tracking_url' => $this->getTrackingUrl($order->tracking_number, $providerCode),
                'hotline' => self::SUPPORTED_CARRIERS[$providerCode]['hotline'] ?? null,
                'logo' => self::SUPPORTED_CARRIERS[$providerCode]['logo'] ?? null,
            ],
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'status_label' => $order->status_label,
                'shipped_at' => $order->shipped_at?->format('d/m/Y H:i'),
                'estimated_delivery_at' => $order->estimated_delivery_at?->format('d/m/Y H:i'),
                'delivered_at' => $order->delivered_at?->format('d/m/Y H:i'),
            ],
            'current_status' => $trackingResult['current_status'] ?? $order->status,
            'current_status_label' => $trackingResult['current_status_label'] ?? $order->status_label,
            'timeline' => $mergedTimeline,
            'carrier_data' => $trackingResult,
            'from_cache' => $trackingResult['from_cache'] ?? false,
        ];
    }

    /**
     * ดึงข้อมูลจาก API ของขนส่ง
     *
     * @param string $trackingNumber
     * @param string $providerCode
     * @return array
     */
    protected function fetchFromCarrier(string $trackingNumber, string $providerCode): array
    {
        return match ($providerCode) {
            'thaipost' => $this->fetchThaiPost($trackingNumber),
            'kerry' => $this->fetchKerry($trackingNumber),
            'flash' => $this->fetchFlash($trackingNumber),
            'jt' => $this->fetchJT($trackingNumber),
            'ninja' => $this->fetchNinjaVan($trackingNumber),
            'scg' => $this->fetchSCG($trackingNumber),
            'best' => $this->fetchBest($trackingNumber),
            'dhl' => $this->fetchDHL($trackingNumber),
            default => $this->fetchGeneric($trackingNumber, $providerCode),
        };
    }

    /**
     * ดึงข้อมูลติดตามจากไปรษณีย์ไทย
     */
    protected function fetchThaiPost(string $trackingNumber): array
    {
        try {
            // Thailand Post ใช้ API token สำหรับ developer
            $apiToken = config('services.tracking.thaipost_token');

            if ($apiToken) {
                // ใช้ Official API
                $response = Http::timeout(self::HTTP_TIMEOUT)
                    ->withHeaders([
                        'Authorization' => "Token {$apiToken}",
                        'Content-Type' => 'application/json',
                    ])
                    ->post('https://trackapi.thailandpost.co.th/post/api/v1/track', [
                        'status' => 'all',
                        'language' => 'TH',
                        'barcode' => [$trackingNumber],
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $this->parseThaiPostResponse($data, $trackingNumber);
                }
            }

            // Fallback: ส่ง link ติดตาม
            return $this->buildResponse(
                trackingNumber: $trackingNumber,
                provider: 'thaipost',
                success: true,
                message: 'กรุณาตรวจสอบสถานะที่เว็บไซต์ไปรษณีย์ไทย',
                currentStatus: 'in_transit',
                currentStatusLabel: 'อยู่ระหว่างขนส่ง',
                timeline: [],
                trackingUrl: str_replace('{tracking_number}', $trackingNumber, self::SUPPORTED_CARRIERS['thaipost']['tracking_url']),
            );
        } catch (\Exception $e) {
            Log::warning("TrackingService: ThaiPost API error", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * ดึงข้อมูลติดตามจาก Kerry Express
     */
    protected function fetchKerry(string $trackingNumber): array
    {
        try {
            $response = Http::timeout(self::HTTP_TIMEOUT)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post('https://th.kerryexpress.com/api/shipment/tracking', [
                    'tracking_number' => $trackingNumber,
                    'lang' => 'th',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $this->parseKerryResponse($data, $trackingNumber);
            }

            return $this->buildFallbackResponse($trackingNumber, 'kerry');
        } catch (\Exception $e) {
            Log::warning("TrackingService: Kerry API error", ['error' => $e->getMessage()]);
            return $this->buildFallbackResponse($trackingNumber, 'kerry');
        }
    }

    /**
     * ดึงข้อมูลติดตามจาก Flash Express
     */
    protected function fetchFlash(string $trackingNumber): array
    {
        try {
            $response = Http::timeout(self::HTTP_TIMEOUT)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post('https://flashexpress.com/fle/tracking/all', [
                    'pno' => $trackingNumber,
                    'language' => 1, // Thai
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $this->parseFlashResponse($data, $trackingNumber);
            }

            return $this->buildFallbackResponse($trackingNumber, 'flash');
        } catch (\Exception $e) {
            Log::warning("TrackingService: Flash API error", ['error' => $e->getMessage()]);
            return $this->buildFallbackResponse($trackingNumber, 'flash');
        }
    }

    /**
     * ดึงข้อมูลติดตามจาก J&T Express
     */
    protected function fetchJT(string $trackingNumber): array
    {
        try {
            $response = Http::timeout(self::HTTP_TIMEOUT)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post('https://www.jtexpress.co.th/api/tracker/track', [
                    'billcode' => $trackingNumber,
                    'lang' => 'th',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $this->parseJTResponse($data, $trackingNumber);
            }

            return $this->buildFallbackResponse($trackingNumber, 'jt');
        } catch (\Exception $e) {
            Log::warning("TrackingService: J&T API error", ['error' => $e->getMessage()]);
            return $this->buildFallbackResponse($trackingNumber, 'jt');
        }
    }

    /**
     * ดึงข้อมูลติดตามจาก Ninja Van
     */
    protected function fetchNinjaVan(string $trackingNumber): array
    {
        try {
            $apiKey = config('services.tracking.ninjavan_key');

            if ($apiKey) {
                $response = Http::timeout(self::HTTP_TIMEOUT)
                    ->withHeaders([
                        'Authorization' => "Bearer {$apiKey}",
                        'Accept' => 'application/json',
                    ])
                    ->get("https://api.ninjavan.co/th/v2/tracking/{$trackingNumber}");

                if ($response->successful()) {
                    $data = $response->json();
                    return $this->parseNinjaVanResponse($data, $trackingNumber);
                }
            }

            return $this->buildFallbackResponse($trackingNumber, 'ninja');
        } catch (\Exception $e) {
            Log::warning("TrackingService: NinjaVan API error", ['error' => $e->getMessage()]);
            return $this->buildFallbackResponse($trackingNumber, 'ninja');
        }
    }

    /**
     * ดึงข้อมูลติดตามจาก SCG Express
     */
    protected function fetchSCG(string $trackingNumber): array
    {
        return $this->buildFallbackResponse($trackingNumber, 'scg');
    }

    /**
     * ดึงข้อมูลติดตามจาก Best Express
     */
    protected function fetchBest(string $trackingNumber): array
    {
        return $this->buildFallbackResponse($trackingNumber, 'best');
    }

    /**
     * ดึงข้อมูลติดตามจาก DHL Express
     */
    protected function fetchDHL(string $trackingNumber): array
    {
        try {
            $apiKey = config('services.tracking.dhl_key');

            if ($apiKey) {
                $response = Http::timeout(self::HTTP_TIMEOUT)
                    ->withHeaders([
                        'DHL-API-Key' => $apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get('https://api-eu.dhl.com/track/shipments', [
                        'trackingNumber' => $trackingNumber,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $this->parseDHLResponse($data, $trackingNumber);
                }
            }

            return $this->buildFallbackResponse($trackingNumber, 'dhl');
        } catch (\Exception $e) {
            Log::warning("TrackingService: DHL API error", ['error' => $e->getMessage()]);
            return $this->buildFallbackResponse($trackingNumber, 'dhl');
        }
    }

    /**
     * Fallback สำหรับขนส่งที่ไม่มี API หรือ API ใช้ไม่ได้
     */
    protected function fetchGeneric(string $trackingNumber, string $providerCode): array
    {
        return $this->buildFallbackResponse($trackingNumber, $providerCode);
    }

    // ========================================
    // Parser methods สำหรับแต่ละขนส่ง
    // ========================================

    /**
     * แปลงข้อมูลจาก Thailand Post API
     */
    protected function parseThaiPostResponse(array $data, string $trackingNumber): array
    {
        $timeline = [];
        $currentStatus = 'in_transit';
        $currentStatusLabel = 'อยู่ระหว่างขนส่ง';

        if (isset($data['response']['items'][$trackingNumber])) {
            $items = $data['response']['items'][$trackingNumber];

            foreach ($items as $item) {
                $status = $this->mapThaiPostStatus($item['status'] ?? '');
                $timeline[] = [
                    'status' => $status,
                    'title' => $item['status_description'] ?? $item['status'] ?? '',
                    'description' => $item['location'] ?? '',
                    'location' => $item['postcode'] ?? '',
                    'timestamp' => $item['status_date'] ?? '',
                    'source' => 'thaipost',
                ];
            }

            // สถานะล่าสุดจาก timeline
            if (!empty($timeline)) {
                $latest = $timeline[0];
                $currentStatus = $latest['status'];
                $currentStatusLabel = self::STATUS_MAP[$currentStatus] ?? $latest['title'];
            }
        }

        return $this->buildResponse(
            trackingNumber: $trackingNumber,
            provider: 'thaipost',
            success: true,
            currentStatus: $currentStatus,
            currentStatusLabel: $currentStatusLabel,
            timeline: $timeline,
        );
    }

    /**
     * แปลงข้อมูลจาก Kerry Express API
     */
    protected function parseKerryResponse(array $data, string $trackingNumber): array
    {
        $timeline = [];
        $currentStatus = 'in_transit';
        $currentStatusLabel = 'อยู่ระหว่างขนส่ง';

        $shipment = $data['shipment'] ?? $data['data'] ?? null;
        if ($shipment) {
            $scans = $shipment['scans'] ?? $shipment['scan'] ?? [];
            foreach ($scans as $scan) {
                $status = $this->mapKerryStatus($scan['status_code'] ?? $scan['scanType'] ?? '');
                $timeline[] = [
                    'status' => $status,
                    'title' => $scan['description'] ?? $scan['status'] ?? '',
                    'description' => $scan['location'] ?? '',
                    'location' => $scan['hub_name'] ?? '',
                    'timestamp' => $scan['date'] ?? $scan['scanDate'] ?? '',
                    'source' => 'kerry',
                ];
            }

            if (!empty($timeline)) {
                $latest = $timeline[0];
                $currentStatus = $latest['status'];
                $currentStatusLabel = self::STATUS_MAP[$currentStatus] ?? $latest['title'];
            }
        }

        return $this->buildResponse(
            trackingNumber: $trackingNumber,
            provider: 'kerry',
            success: true,
            currentStatus: $currentStatus,
            currentStatusLabel: $currentStatusLabel,
            timeline: $timeline,
        );
    }

    /**
     * แปลงข้อมูลจาก Flash Express API
     */
    protected function parseFlashResponse(array $data, string $trackingNumber): array
    {
        $timeline = [];
        $currentStatus = 'in_transit';
        $currentStatusLabel = 'อยู่ระหว่างขนส่ง';

        $routes = $data['data']['routes'] ?? $data['routes'] ?? [];
        foreach ($routes as $route) {
            $status = $this->mapFlashStatus($route['routeStatus'] ?? $route['status'] ?? '');
            $timeline[] = [
                'status' => $status,
                'title' => $route['routeStatusDesc'] ?? $route['description'] ?? '',
                'description' => $route['routeRemark'] ?? '',
                'location' => $route['routeLocation'] ?? '',
                'timestamp' => $route['routeDate'] ?? '',
                'source' => 'flash',
            ];
        }

        if (!empty($timeline)) {
            $latest = $timeline[0];
            $currentStatus = $latest['status'];
            $currentStatusLabel = self::STATUS_MAP[$currentStatus] ?? $latest['title'];
        }

        return $this->buildResponse(
            trackingNumber: $trackingNumber,
            provider: 'flash',
            success: true,
            currentStatus: $currentStatus,
            currentStatusLabel: $currentStatusLabel,
            timeline: $timeline,
        );
    }

    /**
     * แปลงข้อมูลจาก J&T Express API
     */
    protected function parseJTResponse(array $data, string $trackingNumber): array
    {
        $timeline = [];
        $currentStatus = 'in_transit';
        $currentStatusLabel = 'อยู่ระหว่างขนส่ง';

        $details = $data['data']['details'] ?? $data['details'] ?? [];
        foreach ($details as $detail) {
            $status = $this->mapJTStatus($detail['scanType'] ?? '');
            $timeline[] = [
                'status' => $status,
                'title' => $detail['desc'] ?? '',
                'description' => $detail['remark'] ?? '',
                'location' => $detail['city'] ?? '',
                'timestamp' => $detail['scanTime'] ?? '',
                'source' => 'jt',
            ];
        }

        if (!empty($timeline)) {
            $latest = $timeline[0];
            $currentStatus = $latest['status'];
            $currentStatusLabel = self::STATUS_MAP[$currentStatus] ?? $latest['title'];
        }

        return $this->buildResponse(
            trackingNumber: $trackingNumber,
            provider: 'jt',
            success: true,
            currentStatus: $currentStatus,
            currentStatusLabel: $currentStatusLabel,
            timeline: $timeline,
        );
    }

    /**
     * แปลงข้อมูลจาก Ninja Van API
     */
    protected function parseNinjaVanResponse(array $data, string $trackingNumber): array
    {
        $timeline = [];
        $currentStatus = 'in_transit';
        $currentStatusLabel = 'อยู่ระหว่างขนส่ง';

        $events = $data['events'] ?? [];
        foreach ($events as $event) {
            $status = $this->mapNinjaVanStatus($event['status'] ?? '');
            $timeline[] = [
                'status' => $status,
                'title' => $event['description'] ?? '',
                'description' => $event['comment'] ?? '',
                'location' => $event['address1'] ?? '',
                'timestamp' => $event['time'] ?? '',
                'source' => 'ninja',
            ];
        }

        if (!empty($timeline)) {
            $latest = $timeline[0];
            $currentStatus = $latest['status'];
            $currentStatusLabel = self::STATUS_MAP[$currentStatus] ?? $latest['title'];
        }

        return $this->buildResponse(
            trackingNumber: $trackingNumber,
            provider: 'ninja',
            success: true,
            currentStatus: $currentStatus,
            currentStatusLabel: $currentStatusLabel,
            timeline: $timeline,
        );
    }

    /**
     * แปลงข้อมูลจาก DHL API
     */
    protected function parseDHLResponse(array $data, string $trackingNumber): array
    {
        $timeline = [];
        $currentStatus = 'in_transit';
        $currentStatusLabel = 'อยู่ระหว่างขนส่ง';

        $shipments = $data['shipments'] ?? [];
        if (!empty($shipments)) {
            $events = $shipments[0]['events'] ?? [];
            foreach ($events as $event) {
                $status = $this->mapDHLStatus($event['statusCode'] ?? '');
                $timeline[] = [
                    'status' => $status,
                    'title' => $event['description'] ?? $event['status'] ?? '',
                    'description' => $event['remark'] ?? '',
                    'location' => $event['location']['address']['addressLocality'] ?? '',
                    'timestamp' => $event['timestamp'] ?? '',
                    'source' => 'dhl',
                ];
            }

            if (!empty($timeline)) {
                $latest = $timeline[0];
                $currentStatus = $latest['status'];
                $currentStatusLabel = self::STATUS_MAP[$currentStatus] ?? $latest['title'];
            }
        }

        return $this->buildResponse(
            trackingNumber: $trackingNumber,
            provider: 'dhl',
            success: true,
            currentStatus: $currentStatus,
            currentStatusLabel: $currentStatusLabel,
            timeline: $timeline,
        );
    }

    // ========================================
    // Status Mapping Methods
    // ========================================

    protected function mapThaiPostStatus(string $status): string
    {
        return match (strtolower($status)) {
            'posting/collection', 'collection' => 'picked_up',
            'in transit', 'in process' => 'in_transit',
            'arrival at sorting center', 'sorting' => 'at_sorting_center',
            'out for delivery' => 'out_for_delivery',
            'delivered', 'successful delivery' => 'delivered',
            'undelivered', 'unsuccessful delivery' => 'failed_delivery',
            'return', 'returned' => 'returned',
            default => 'in_transit',
        };
    }

    protected function mapKerryStatus(string $statusCode): string
    {
        return match (strtoupper($statusCode)) {
            'PU', 'PICKUP' => 'picked_up',
            'IT', 'IN_TRANSIT' => 'in_transit',
            'AR', 'ARRIVED' => 'at_sorting_center',
            'OD', 'OUT_FOR_DELIVERY' => 'out_for_delivery',
            'DL', 'DELIVERED' => 'delivered',
            'FD', 'FAILED' => 'failed_delivery',
            'RT', 'RETURNED' => 'returned',
            default => 'in_transit',
        };
    }

    protected function mapFlashStatus(string $status): string
    {
        return match ((int) $status) {
            1 => 'picked_up',
            2, 3, 4 => 'in_transit',
            5 => 'at_sorting_center',
            6 => 'out_for_delivery',
            7 => 'delivered',
            8 => 'failed_delivery',
            9 => 'returned',
            default => 'in_transit',
        };
    }

    protected function mapJTStatus(string $scanType): string
    {
        return match (strtoupper($scanType)) {
            'PICKUP' => 'picked_up',
            'GATEWAY_IN', 'GATEWAY_OUT', 'TRANSPORT' => 'in_transit',
            'SORTING' => 'at_sorting_center',
            'DELIVERING' => 'out_for_delivery',
            'SIGNED' => 'delivered',
            'UNSIGN' => 'failed_delivery',
            'RETURN' => 'returned',
            default => 'in_transit',
        };
    }

    protected function mapNinjaVanStatus(string $status): string
    {
        return match (strtolower($status)) {
            'pending_pickup', 'pickup_scheduled' => 'pending',
            'picked_up', 'pickup_completed' => 'picked_up',
            'in_transit', 'arrived_at_sorting_hub' => 'in_transit',
            'at_sorting_facility' => 'at_sorting_center',
            'on_vehicle_for_delivery' => 'out_for_delivery',
            'completed' => 'delivered',
            'delivery_failed' => 'failed_delivery',
            'returned_to_sender' => 'returned',
            default => 'in_transit',
        };
    }

    protected function mapDHLStatus(string $statusCode): string
    {
        return match (strtolower($statusCode)) {
            'pre-transit' => 'pending',
            'picked-up' => 'picked_up',
            'transit' => 'in_transit',
            'out-for-delivery' => 'out_for_delivery',
            'delivered' => 'delivered',
            'failure' => 'failed_delivery',
            default => 'in_transit',
        };
    }

    // ========================================
    // Helper Methods
    // ========================================

    /**
     * แปลงชื่อ provider เป็น code มาตรฐาน
     */
    protected function normalizeProviderCode(string $code): string
    {
        $code = strtolower(trim($code));

        // Mapping aliases
        $aliases = [
            'thailand_post' => 'thaipost',
            'thai_post' => 'thaipost',
            'thailandpost' => 'thaipost',
            'ไปรษณีย์ไทย' => 'thaipost',
            'kerry_express' => 'kerry',
            'kerryexpress' => 'kerry',
            'flash_express' => 'flash',
            'flashexpress' => 'flash',
            'j&t' => 'jt',
            'j_t' => 'jt',
            'jt_express' => 'jt',
            'jtexpress' => 'jt',
            'j&t express' => 'jt',
            'ninja_van' => 'ninja',
            'ninjavan' => 'ninja',
            'scg_express' => 'scg',
            'scgexpress' => 'scg',
            'best_express' => 'best',
            'bestexpress' => 'best',
            'dhl_express' => 'dhl',
            'dhlexpress' => 'dhl',
        ];

        return $aliases[$code] ?? $code;
    }

    /**
     * หา provider code จาก Order
     */
    protected function resolveProviderCode(Order $order): string
    {
        // ลองจาก ShippingProvider model ก่อน
        if ($order->shipping_provider_id && $order->shippingProviderRelation) {
            return $this->normalizeProviderCode($order->shippingProviderRelation->code);
        }

        // ลองจาก shipping_provider field
        if ($order->shipping_provider) {
            return $this->normalizeProviderCode($order->shipping_provider);
        }

        // ลองเดาจากรูปแบบเลขพัสดุ
        return $this->guessProviderFromTracking($order->tracking_number);
    }

    /**
     * เดาบริษัทขนส่งจากรูปแบบเลขพัสดุ
     */
    public function guessProviderFromTracking(string $trackingNumber): string
    {
        $tn = strtoupper(trim($trackingNumber));

        // Thailand Post: เริ่มด้วย E + 9 ตัวเลข + TH หรือ R + 9 ตัวเลข + TH
        if (preg_match('/^[A-Z]{2}\d{9}TH$/', $tn)) {
            return 'thaipost';
        }

        // Kerry Express: เริ่มด้วย KERDO/SLAH หรือ SMK
        if (preg_match('/^(KERDO|SLAH|SMK)/', $tn)) {
            return 'kerry';
        }

        // Flash Express: เริ่มด้วย TH
        if (preg_match('/^TH\d{13,}/', $tn)) {
            return 'flash';
        }

        // J&T Express: เริ่มด้วย JT หรือ 82
        if (preg_match('/^(JT|82)\d{10,}/', $tn)) {
            return 'jt';
        }

        // Ninja Van: เริ่มด้วย NV หรือ NVTH
        if (preg_match('/^(NV|NVTH)/', $tn)) {
            return 'ninja';
        }

        // Best Express: เริ่มด้วย TH + ตัวเลข 15 ตัว
        if (preg_match('/^[0-9]{15}$/', $tn)) {
            return 'best';
        }

        // DHL: เริ่มด้วย 10 ตัวเลข
        if (preg_match('/^\d{10}$/', $tn)) {
            return 'dhl';
        }

        return 'thaipost'; // default
    }

    /**
     * สร้าง tracking URL สำหรับเปิดดูที่เว็บขนส่ง
     */
    public function getTrackingUrl(string $trackingNumber, string $providerCode): ?string
    {
        $providerCode = $this->normalizeProviderCode($providerCode);
        $carrier = self::SUPPORTED_CARRIERS[$providerCode] ?? null;

        if (!$carrier || !isset($carrier['tracking_url'])) {
            return null;
        }

        return str_replace('{tracking_number}', $trackingNumber, $carrier['tracking_url']);
    }

    /**
     * ดึงรายการขนส่งที่รองรับ
     */
    public function getSupportedCarriers(): array
    {
        $carriers = [];
        foreach (self::SUPPORTED_CARRIERS as $code => $info) {
            $carriers[] = [
                'code' => $code,
                'name' => $info['name'],
                'name_en' => $info['name_en'],
                'hotline' => $info['hotline'],
                'logo' => $info['logo'],
            ];
        }
        return $carriers;
    }

    /**
     * สร้าง response มาตรฐาน
     */
    protected function buildResponse(
        string $trackingNumber,
        string $provider,
        bool $success = true,
        string $message = '',
        string $currentStatus = 'pending',
        string $currentStatusLabel = 'รอดำเนินการ',
        array $timeline = [],
        ?string $trackingUrl = null,
    ): array {
        $carrier = self::SUPPORTED_CARRIERS[$provider] ?? null;

        return [
            'success' => $success,
            'message' => $message,
            'tracking_number' => $trackingNumber,
            'provider' => [
                'code' => $provider,
                'name' => $carrier['name'] ?? $provider,
                'name_en' => $carrier['name_en'] ?? $provider,
                'hotline' => $carrier['hotline'] ?? null,
                'logo' => $carrier['logo'] ?? null,
            ],
            'current_status' => $currentStatus,
            'current_status_label' => $currentStatusLabel,
            'timeline' => $timeline,
            'tracking_url' => $trackingUrl ?? $this->getTrackingUrl($trackingNumber, $provider),
            'from_cache' => false,
            'checked_at' => now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * สร้าง response fallback (ส่ง link ติดตามให้)
     */
    protected function buildFallbackResponse(string $trackingNumber, string $providerCode): array
    {
        $carrier = self::SUPPORTED_CARRIERS[$providerCode] ?? null;

        return $this->buildResponse(
            trackingNumber: $trackingNumber,
            provider: $providerCode,
            success: true,
            message: 'กดลิงก์ด้านล่างเพื่อติดตามพัสดุที่เว็บไซต์ของ' . ($carrier['name'] ?? 'บริษัทขนส่ง'),
            currentStatus: 'in_transit',
            currentStatusLabel: 'อยู่ระหว่างขนส่ง',
            timeline: [],
        );
    }

    /**
     * รวม timeline จากขนส่งกับ tracking history ในระบบ
     */
    protected function mergeTimelines(array $carrierTimeline, array $internalTimeline): array
    {
        $merged = [];

        // เพิ่ม internal timeline (มีข้อมูลจากระบบแน่นอน)
        foreach ($internalTimeline as $item) {
            $item['source_type'] = 'internal';
            $merged[] = $item;
        }

        // เพิ่ม carrier timeline
        foreach ($carrierTimeline as $item) {
            $item['source_type'] = 'carrier';
            // เพิ่ม icon/color
            $item['icon'] = $this->getStatusIcon($item['status'] ?? 'in_transit');
            $item['color'] = $this->getStatusColor($item['status'] ?? 'in_transit');
            $merged[] = $item;
        }

        // เรียงตาม timestamp ล่าสุดก่อน
        usort($merged, function ($a, $b) {
            $timeA = strtotime($a['timestamp'] ?? '0');
            $timeB = strtotime($b['timestamp'] ?? '0');
            return $timeB - $timeA;
        });

        return $merged;
    }

    /**
     * ดึง icon สำหรับสถานะ
     */
    public function getStatusIcon(string $status): string
    {
        return match ($status) {
            'pending' => 'clock',
            'picked_up' => 'package',
            'in_transit' => 'truck',
            'at_sorting_center' => 'building',
            'out_for_delivery' => 'bike',
            'delivered' => 'check-circle',
            'failed_delivery' => 'x-circle',
            'returned' => 'arrow-left',
            'exception' => 'alert-triangle',
            default => 'circle',
        };
    }

    /**
     * ดึงสีสำหรับสถานะ
     */
    public function getStatusColor(string $status): string
    {
        return match ($status) {
            'pending' => '#F59E0B',
            'picked_up' => '#8B5CF6',
            'in_transit' => '#3B82F6',
            'at_sorting_center' => '#06B6D4',
            'out_for_delivery' => '#10B981',
            'delivered' => '#22C55E',
            'failed_delivery' => '#EF4444',
            'returned' => '#F97316',
            'exception' => '#EF4444',
            default => '#9CA3AF',
        };
    }

    /**
     * บันทึก tracking event ลงใน OrderTrackingHistory
     */
    public function syncTrackingToOrder(Order $order, array $trackingData): int
    {
        $synced = 0;

        foreach ($trackingData['timeline'] ?? [] as $event) {
            if (($event['source_type'] ?? '') !== 'carrier') {
                continue;
            }

            // ตรวจสอบว่า event นี้บันทึกแล้วหรือยัง
            $exists = OrderTrackingHistory::where('order_id', $order->id)
                ->where('status', $event['status'])
                ->where('title', $event['title'])
                ->where('tracked_at', $event['timestamp'])
                ->exists();

            if (!$exists && !empty($event['timestamp'])) {
                OrderTrackingHistory::create([
                    'order_id' => $order->id,
                    'status' => $event['status'],
                    'title' => $event['title'],
                    'description' => $event['description'] ?? null,
                    'location' => $event['location'] ?? null,
                    'tracking_number' => $trackingData['tracking_number'] ?? $order->tracking_number,
                    'shipping_provider' => $trackingData['provider']['name'] ?? $order->shipping_provider,
                    'created_by_type' => 'carrier_api',
                    'meta_data' => ['source' => $event['source'] ?? 'carrier'],
                    'tracked_at' => $event['timestamp'],
                ]);
                $synced++;
            }
        }

        return $synced;
    }
}
