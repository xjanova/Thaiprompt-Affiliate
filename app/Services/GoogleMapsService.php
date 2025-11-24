<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * Google Maps Service
 *
 * บริการจัดการ Google Maps API สำหรับระบบ
 * รองรับการตั้งค่าจาก database และ .env file
 *
 * @version 2.0 - เพิ่มการรองรับ database settings
 */
class GoogleMapsService
{
    protected ?string $apiKey = null;
    protected string $baseUrl = 'https://maps.googleapis.com/maps/api';
    protected array $settings = [];

    /**
     * Constructor - โหลดการตั้งค่าจาก database และ config
     */
    public function __construct()
    {
        // โหลดการตั้งค่าจาก database ก่อน ถ้าไม่มีใช้ค่าจาก config
        $this->apiKey = Setting::get('google_maps_api_key')
                        ?: config('services.google_maps.api_key')
                        ?: null;

        // โหลดการตั้งค่าอื่นๆ จาก database
        $this->loadSettings();
    }

    /**
     * โหลดการตั้งค่าทั้งหมดจาก database
     *
     * @return void
     */
    protected function loadSettings(): void
    {
        $this->settings = [
            'enabled' => Setting::get('google_maps_enabled', true),
            'geocoding_enabled' => Setting::get('google_maps_geocoding_enabled', true),
            'directions_enabled' => Setting::get('google_maps_directions_enabled', true),
            'distance_matrix_enabled' => Setting::get('google_maps_distance_matrix_enabled', true),
            'places_enabled' => Setting::get('google_maps_places_enabled', true),
            'cache_enabled' => Setting::get('google_maps_cache_enabled', true),
            'cache_ttl' => Setting::get('google_maps_cache_ttl', 86400),
            'language' => Setting::get('google_maps_geocoding_language', 'th'),
            'region' => Setting::get('google_maps_geocoding_region', 'TH'),
            'default_mode' => Setting::get('google_maps_default_mode', 'driving'),
            'avoid_tolls' => Setting::get('google_maps_avoid_tolls', false),
            'avoid_highways' => Setting::get('google_maps_avoid_highways', false),
            'avoid_ferries' => Setting::get('google_maps_avoid_ferries', false),
            'distance_unit' => Setting::get('google_maps_distance_unit', 'metric'),
            'places_default_radius' => Setting::get('google_maps_places_default_radius', 1000),
            'places_max_results' => Setting::get('google_maps_places_max_results', 20),
            'fallback_enabled' => Setting::get('google_maps_fallback_enabled', true),
        ];
    }

    /**
     * ตรวจสอบว่าฟีเจอร์เปิดใช้งานหรือไม่
     *
     * @param string $feature ชื่อฟีเจอร์ (geocoding, directions, distance_matrix, places)
     * @return bool
     */
    protected function isFeatureEnabled(string $feature): bool
    {
        if (!$this->settings['enabled']) {
            return false;
        }

        $key = "{$feature}_enabled";
        return $this->settings[$key] ?? true;
    }

    /**
     * ตรวจสอบว่ามี API key หรือไม่
     *
     * @throws \Exception
     */
    protected function ensureApiKey(): void
    {
        // ตรวจสอบว่าระบบเปิดใช้งานหรือไม่
        if (!$this->settings['enabled']) {
            throw new \Exception('Google Maps service is disabled. Please enable it in settings.');
        }

        if (empty($this->apiKey)) {
            throw new \Exception('Google Maps API key is not configured. Please set it in Admin > Settings > Google Maps or in your .env file.');
        }
    }

    /**
     * ตรวจสอบว่า cache เปิดใช้งานหรือไม่
     *
     * @return bool
     */
    protected function isCacheEnabled(): bool
    {
        return $this->settings['cache_enabled'] ?? true;
    }

    /**
     * รับค่า cache TTL
     *
     * @return int จำนวนวินาที
     */
    protected function getCacheTTL(): int
    {
        return $this->settings['cache_ttl'] ?? 86400;
    }

    /**
     * Reverse geocode: Convert coordinates to address
     *
     * @param float $lat ละติจูด
     * @param float $lng ลองจิจูด
     * @return array ข้อมูลที่อยู่และพิกัด
     * @throws \Exception
     */
    public function reverseGeocode(float $lat, float $lng): array
    {
        $this->ensureApiKey();

        // ตรวจสอบว่าฟีเจอร์เปิดใช้งานหรือไม่
        if (!$this->isFeatureEnabled('geocoding')) {
            throw new \Exception('Geocoding feature is disabled. Please enable it in settings.');
        }

        $cacheKey = "geocode:reverse:{$lat}:{$lng}";
        $cacheTTL = $this->isCacheEnabled() ? $this->getCacheTTL() : 0;

        $callback = function () use ($lat, $lng) {
            $response = Http::get("{$this->baseUrl}/geocode/json", [
                'latlng' => "{$lat},{$lng}",
                'key' => $this->apiKey,
                'language' => $this->settings['language'] ?? 'th',
                'region' => $this->settings['region'] ?? 'TH',
            ]);

            if (!$response->successful()) {
                throw new \Exception('Google Maps API request failed');
            }

            $data = $response->json();

            if ($data['status'] !== 'OK') {
                throw new \Exception("Geocoding failed: {$data['status']}");
            }

            $result = $data['results'][0] ?? null;

            if (!$result) {
                throw new \Exception('No results found');
            }

            return [
                'formatted_address' => $result['formatted_address'],
                'address_components' => $this->parseAddressComponents($result['address_components']),
                'place_id' => $result['place_id'],
                'location' => [
                    'lat' => $result['geometry']['location']['lat'],
                    'lng' => $result['geometry']['location']['lng'],
                ],
            ];
        });
    }

    /**
     * Geocode: Convert address to coordinates
     *
     * @param string $address ที่อยู่
     * @return array ข้อมูลพิกัดและที่อยู่
     * @throws \Exception
     */
    public function geocode(string $address): array
    {
        $this->ensureApiKey();

        // ตรวจสอบว่าฟีเจอร์เปิดใช้งานหรือไม่
        if (!$this->isFeatureEnabled('geocoding')) {
            throw new \Exception('Geocoding feature is disabled. Please enable it in settings.');
        }

        $cacheKey = "geocode:forward:" . md5($address);
        $cacheTTL = $this->isCacheEnabled() ? $this->getCacheTTL() : 0;

        $callback = function () use ($address) {
            $response = Http::get("{$this->baseUrl}/geocode/json", [
                'address' => $address,
                'key' => $this->apiKey,
                'language' => $this->settings['language'] ?? 'th',
                'region' => $this->settings['region'] ?? 'TH',
            ]);

            if (!$response->successful()) {
                throw new \Exception('Google Maps API request failed');
            }

            $data = $response->json();

            if ($data['status'] !== 'OK') {
                throw new \Exception("Geocoding failed: {$data['status']}");
            }

            $result = $data['results'][0] ?? null;

            if (!$result) {
                throw new \Exception('No results found');
            }

            return [
                'formatted_address' => $result['formatted_address'],
                'address_components' => $this->parseAddressComponents($result['address_components']),
                'place_id' => $result['place_id'],
                'location' => [
                    'lat' => $result['geometry']['location']['lat'],
                    'lng' => $result['geometry']['location']['lng'],
                ],
            ];
        };

        // ใช้ cache ถ้าเปิดใช้งาน
        return $this->isCacheEnabled()
            ? Cache::remember($cacheKey, $cacheTTL, $callback)
            : $callback();
    }

    /**
     * Get distance and duration between two points
     *
     * @param array $origin จุดเริ่มต้น ['lat' => float, 'lng' => float]
     * @param array $destination จุดหมาย ['lat' => float, 'lng' => float]
     * @param string $mode โหมดการเดินทาง (driving, walking, bicycling, transit)
     * @return array ข้อมูลระยะทาง เวลา และเส้นทาง
     * @throws \Exception
     */
    public function getDirections(array $origin, array $destination, string $mode = null): array
    {
        $this->ensureApiKey();

        // ตรวจสอบว่าฟีเจอร์เปิดใช้งานหรือไม่
        if (!$this->isFeatureEnabled('directions')) {
            throw new \Exception('Directions feature is disabled. Please enable it in settings.');
        }

        // ใช้ mode จากการตั้งค่าถ้าไม่ได้ระบุ
        $mode = $mode ?? $this->settings['default_mode'] ?? 'driving';

        $originStr = "{$origin['lat']},{$origin['lng']}";
        $destStr = "{$destination['lat']},{$destination['lng']}";

        $cacheKey = "directions:{$originStr}:{$destStr}:{$mode}";

        return Cache::remember($cacheKey, 3600, function () use ($originStr, $destStr, $mode) {
            $response = Http::get("{$this->baseUrl}/directions/json", [
                'origin' => $originStr,
                'destination' => $destStr,
                'mode' => $mode,
                'key' => $this->apiKey,
                'language' => 'th',
            ]);

            if (!$response->successful()) {
                throw new \Exception('Google Maps API request failed');
            }

            $data = $response->json();

            if ($data['status'] !== 'OK') {
                throw new \Exception("Directions failed: {$data['status']}");
            }

            $route = $data['routes'][0] ?? null;
            $leg = $route['legs'][0] ?? null;

            if (!$leg) {
                throw new \Exception('No route found');
            }

            return [
                'distance' => [
                    'value' => $leg['distance']['value'], // meters
                    'text' => $leg['distance']['text'],
                ],
                'duration' => [
                    'value' => $leg['duration']['value'], // seconds
                    'text' => $leg['duration']['text'],
                ],
                'start_address' => $leg['start_address'],
                'end_address' => $leg['end_address'],
                'steps' => collect($leg['steps'])->map(fn($step) => [
                    'distance' => $step['distance'],
                    'duration' => $step['duration'],
                    'instruction' => $step['html_instructions'],
                    'start_location' => $step['start_location'],
                    'end_location' => $step['end_location'],
                ]),
            ];
        });
    }

    /**
     * Calculate distance matrix for multiple origins/destinations
     */
    public function getDistanceMatrix(array $origins, array $destinations, string $mode = 'driving'): array
    {
        $this->ensureApiKey();

        $originsStr = collect($origins)->map(fn($o) => "{$o['lat']},{$o['lng']}")->implode('|');
        $destsStr = collect($destinations)->map(fn($d) => "{$d['lat']},{$d['lng']}")->implode('|');

        $response = Http::get("{$this->baseUrl}/distancematrix/json", [
            'origins' => $originsStr,
            'destinations' => $destsStr,
            'mode' => $mode,
            'key' => $this->apiKey,
            'language' => 'th',
        ]);

        if (!$response->successful()) {
            throw new \Exception('Google Maps API request failed');
        }

        $data = $response->json();

        if ($data['status'] !== 'OK') {
            throw new \Exception("Distance matrix failed: {$data['status']}");
        }

        return [
            'origin_addresses' => $data['origin_addresses'],
            'destination_addresses' => $data['destination_addresses'],
            'rows' => collect($data['rows'])->map(fn($row) => [
                'elements' => collect($row['elements'])->map(fn($el) => [
                    'status' => $el['status'],
                    'distance' => $el['distance'] ?? null,
                    'duration' => $el['duration'] ?? null,
                ]),
            ]),
        ];
    }

    /**
     * Get place details by place ID
     */
    public function getPlaceDetails(string $placeId): array
    {
        $this->ensureApiKey();

        $cacheKey = "place:details:{$placeId}";

        return Cache::remember($cacheKey, 86400, function () use ($placeId) {
            $response = Http::get("{$this->baseUrl}/place/details/json", [
                'place_id' => $placeId,
                'key' => $this->apiKey,
                'language' => 'th',
                'fields' => 'name,formatted_address,geometry,address_components,type,rating,user_ratings_total,photos',
            ]);

            if (!$response->successful()) {
                throw new \Exception('Google Maps API request failed');
            }

            $data = $response->json();

            if ($data['status'] !== 'OK') {
                throw new \Exception("Place details failed: {$data['status']}");
            }

            return $data['result'];
        });
    }

    /**
     * Parse address components into structured format
     */
    protected function parseAddressComponents(array $components): array
    {
        $parsed = [
            'street_number' => null,
            'route' => null,
            'sublocality' => null,
            'locality' => null,
            'administrative_area_level_1' => null,
            'administrative_area_level_2' => null,
            'country' => null,
            'postal_code' => null,
        ];

        foreach ($components as $component) {
            foreach ($component['types'] as $type) {
                if (isset($parsed[$type])) {
                    $parsed[$type] = $component['long_name'];
                }
            }
        }

        return [
            'street_number' => $parsed['street_number'],
            'street' => $parsed['route'],
            'subdistrict' => $parsed['sublocality'],
            'district' => $parsed['locality'] ?? $parsed['administrative_area_level_2'],
            'province' => $parsed['administrative_area_level_1'],
            'country' => $parsed['country'],
            'postal_code' => $parsed['postal_code'],
        ];
    }

    /**
     * Search nearby places
     */
    public function searchNearby(float $lat, float $lng, string $type, int $radius = 1000): array
    {
        $this->ensureApiKey();

        $response = Http::get("{$this->baseUrl}/place/nearbysearch/json", [
            'location' => "{$lat},{$lng}",
            'radius' => $radius,
            'type' => $type,
            'key' => $this->apiKey,
            'language' => 'th',
        ]);

        if (!$response->successful()) {
            throw new \Exception('Google Maps API request failed');
        }

        $data = $response->json();

        if ($data['status'] !== 'OK') {
            throw new \Exception("Nearby search failed: {$data['status']}");
        }

        return collect($data['results'])->map(fn($place) => [
            'place_id' => $place['place_id'],
            'name' => $place['name'],
            'vicinity' => $place['vicinity'],
            'location' => $place['geometry']['location'],
            'rating' => $place['rating'] ?? null,
            'user_ratings_total' => $place['user_ratings_total'] ?? null,
        ])->toArray();
    }

    /**
     * ตรวจสอบ API capabilities - ว่า API Key นี้เปิดใช้งาน APIs อะไรบ้าง
     *
     * @return array รายการ APIs ที่พร้อมใช้งานและสถานะ
     */
    public function checkApiCapabilities(): array
    {
        if (empty($this->apiKey)) {
            return [
                'has_key' => false,
                'message' => 'ไม่พบ API Key',
                'apis' => [],
            ];
        }

        $apis = [
            'geocoding' => [
                'name' => 'Geocoding API',
                'description' => 'แปลงที่อยู่ ⇔ พิกัด GPS',
                'icon' => 'fa-map-marker-alt',
                'category' => 'core',
                'status' => 'unknown',
                'used_by' => ['Delivery Module', 'Service Booking', 'Store Locator'],
            ],
            'directions' => [
                'name' => 'Directions API',
                'description' => 'คำนวณเส้นทางและระยะทาง',
                'icon' => 'fa-route',
                'category' => 'core',
                'status' => 'unknown',
                'used_by' => ['Delivery Module', 'Service Booking', 'Route Planning'],
            ],
            'distance_matrix' => [
                'name' => 'Distance Matrix API',
                'description' => 'คำนวณระยะทางหลายจุดพร้อมกัน',
                'icon' => 'fa-table',
                'category' => 'core',
                'status' => 'unknown',
                'used_by' => ['Delivery Optimization', 'Multi-point Route'],
            ],
            'places' => [
                'name' => 'Places API',
                'description' => 'ค้นหาสถานที่และรายละเอียด',
                'icon' => 'fa-search-location',
                'category' => 'core',
                'status' => 'unknown',
                'used_by' => ['Store Locator', 'POI Search'],
            ],
            'maps_javascript' => [
                'name' => 'Maps JavaScript API',
                'description' => 'แสดงแผนที่แบบ Interactive',
                'icon' => 'fa-map',
                'category' => 'display',
                'status' => 'available',
                'used_by' => ['Frontend Map Display'],
            ],
            'static_maps' => [
                'name' => 'Maps Static API',
                'description' => 'สร้างรูปภาพแผนที่',
                'icon' => 'fa-image',
                'category' => 'display',
                'status' => 'available',
                'used_by' => ['Email Templates', 'PDF Reports'],
            ],
            'street_view' => [
                'name' => 'Street View Static API',
                'description' => 'แสดง Street View แบบรูปภาพ',
                'icon' => 'fa-street-view',
                'category' => 'display',
                'status' => 'available',
                'used_by' => ['Property Listings'],
            ],
            'elevation' => [
                'name' => 'Elevation API',
                'description' => 'ดึงข้อมูลความสูงจากพิกัด',
                'icon' => 'fa-mountain',
                'category' => 'advanced',
                'status' => 'available',
                'used_by' => ['Future: Terrain Analysis'],
            ],
            'timezone' => [
                'name' => 'Time Zone API',
                'description' => 'ดึงข้อมูล timezone จากพิกัด',
                'icon' => 'fa-clock',
                'category' => 'advanced',
                'status' => 'available',
                'used_by' => ['Future: Multi-timezone Booking'],
            ],
            'roads' => [
                'name' => 'Roads API',
                'description' => 'Snap to roads, Speed limits',
                'icon' => 'fa-road',
                'category' => 'advanced',
                'status' => 'available',
                'used_by' => ['Future: GPS Tracking'],
            ],
        ];

        // ทดสอบ APIs หลักที่ใช้งานจริง
        $testResults = $this->testMainApis();

        foreach ($testResults as $apiKey => $result) {
            if (isset($apis[$apiKey])) {
                $apis[$apiKey]['status'] = $result['status'];
                $apis[$apiKey]['error'] = $result['error'] ?? null;
            }
        }

        return [
            'has_key' => true,
            'api_key_preview' => substr($this->apiKey, 0, 10) . '...',
            'apis' => $apis,
            'summary' => [
                'total' => count($apis),
                'enabled' => collect($apis)->where('status', 'enabled')->count(),
                'available' => collect($apis)->where('status', 'available')->count(),
                'disabled' => collect($apis)->where('status', 'disabled')->count(),
            ],
        ];
    }

    /**
     * ทดสอบ APIs หลักที่ใช้งานจริง
     *
     * @return array ผลการทดสอบแต่ละ API
     */
    protected function testMainApis(): array
    {
        $results = [];

        // Test Geocoding API
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/geocode/json", [
                'address' => 'Bangkok',
                'key' => $this->apiKey,
            ]);
            $data = $response->json();
            $results['geocoding'] = [
                'status' => $data['status'] === 'OK' ? 'enabled' : 'disabled',
                'error' => $data['status'] !== 'OK' ? $data['error_message'] ?? $data['status'] : null,
            ];
        } catch (\Exception $e) {
            $results['geocoding'] = ['status' => 'error', 'error' => $e->getMessage()];
        }

        // Test Directions API
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/directions/json", [
                'origin' => '13.7563,100.5018',
                'destination' => '13.7308,100.5418',
                'key' => $this->apiKey,
            ]);
            $data = $response->json();
            $results['directions'] = [
                'status' => $data['status'] === 'OK' ? 'enabled' : 'disabled',
                'error' => $data['status'] !== 'OK' ? $data['error_message'] ?? $data['status'] : null,
            ];
        } catch (\Exception $e) {
            $results['directions'] = ['status' => 'error', 'error' => $e->getMessage()];
        }

        // Test Distance Matrix API
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/distancematrix/json", [
                'origins' => '13.7563,100.5018',
                'destinations' => '13.7308,100.5418',
                'key' => $this->apiKey,
            ]);
            $data = $response->json();
            $results['distance_matrix'] = [
                'status' => $data['status'] === 'OK' ? 'enabled' : 'disabled',
                'error' => $data['status'] !== 'OK' ? $data['error_message'] ?? $data['status'] : null,
            ];
        } catch (\Exception $e) {
            $results['distance_matrix'] = ['status' => 'error', 'error' => $e->getMessage()];
        }

        // Test Places API
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/place/nearbysearch/json", [
                'location' => '13.7563,100.5018',
                'radius' => 1000,
                'key' => $this->apiKey,
            ]);
            $data = $response->json();
            $results['places'] = [
                'status' => $data['status'] === 'OK' || $data['status'] === 'ZERO_RESULTS' ? 'enabled' : 'disabled',
                'error' => !in_array($data['status'], ['OK', 'ZERO_RESULTS']) ? $data['error_message'] ?? $data['status'] : null,
            ];
        } catch (\Exception $e) {
            $results['places'] = ['status' => 'error', 'error' => $e->getMessage()];
        }

        return $results;
    }

    /**
     * รับรายการ APIs ทั้งหมดที่รองรับ
     *
     * @return array รายการ APIs แยกตามหมวดหมู่
     */
    public static function getSupportedApis(): array
    {
        return [
            'core' => [
                'label' => 'Core APIs (ใช้งานหลัก)',
                'apis' => [
                    'geocoding' => 'Geocoding API - แปลงที่อยู่ ⇔ พิกัด',
                    'directions' => 'Directions API - คำนวณเส้นทาง',
                    'distance_matrix' => 'Distance Matrix API - ระยะทางหลายจุด',
                    'places' => 'Places API - ค้นหาสถานที่',
                ],
            ],
            'display' => [
                'label' => 'Display APIs (แสดงผล)',
                'apis' => [
                    'maps_javascript' => 'Maps JavaScript API - แผนที่แบบ Interactive',
                    'static_maps' => 'Maps Static API - รูปภาพแผนที่',
                    'street_view' => 'Street View Static API - Street View',
                ],
            ],
            'advanced' => [
                'label' => 'Advanced APIs (ขั้นสูง - เตรียมไว้ใช้อนาคต)',
                'apis' => [
                    'elevation' => 'Elevation API - ข้อมูลความสูง',
                    'timezone' => 'Time Zone API - เขตเวลา',
                    'roads' => 'Roads API - Snap to roads',
                    'geolocation' => 'Geolocation API - หาตำแหน่งจาก IP/WiFi',
                ],
            ],
        ];
    }
}
