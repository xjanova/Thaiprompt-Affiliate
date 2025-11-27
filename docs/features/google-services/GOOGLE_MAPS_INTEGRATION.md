# Google Maps Integration Guide

> **คู่มือการใช้งาน Google Maps API สำหรับระบบ Thaiprompt-Affiliate**
>
> 📍 **Centralized Configuration** - ทุกระบบใช้ API Key เดียวกันจาก Admin Settings
>
> Version: 2.0 | Last Updated: 2025-11-24

---

## 🎯 Overview

ระบบ Google Maps ถูกรวมศูนย์การตั้งค่าไว้ที่เดียว (**Admin > Settings > Google Maps**)
ทุกโมดูล/ฟีเจอร์ที่ต้องใช้ Google Maps API จะใช้การตั้งค่าจากที่เดียวกัน

### ✅ Benefits
- **Centralized** - จัดการ API Key ที่เดียว
- **Consistent** - การตั้งค่าเหมือนกันทุกระบบ
- **Flexible** - เปิด/ปิด API แต่ละตัวได้
- **Cost Control** - ควบคุม cache และ rate limiting ได้
- **Future Ready** - พร้อมสำหรับฟีเจอร์ใหม่

---

## 🔧 Configuration Location

### Admin Panel
```
URL: /admin/settings/google-maps
Location: Admin > Settings > Google Maps
```

### Database
```
Table: settings
Group: google_maps
Total Settings: 30+ settings
```

### Environment (.env)
```bash
# Fallback ถ้าไม่มีใน database
GOOGLE_MAPS_API_KEY=AIzaSy...
GOOGLE_MAPS_GEOCODING_ENABLED=true
GOOGLE_MAPS_DIRECTIONS_ENABLED=true
GOOGLE_MAPS_CACHE_TTL=86400
```

---

## 📦 API Capabilities

### 🌟 Core APIs (ใช้งานปัจจุบัน)
| API | คำอธิบาย | ใช้โดย |
|-----|----------|--------|
| **Geocoding API** | แปลงที่อยู่ ⇔ พิกัด GPS | Delivery Module, Service Booking, Store Locator |
| **Directions API** | คำนวณเส้นทางและระยะทาง | Delivery Module, Service Booking, Route Planning |
| **Distance Matrix API** | คำนวณระยะทางหลายจุดพร้อมกัน | Delivery Optimization, Multi-point Routes |
| **Places API** | ค้นหาสถานที่และรายละเอียด | Store Locator, POI Search |

### 🖥️ Display APIs (แสดงผล)
| API | คำอธิบาย | ใช้โดย |
|-----|----------|--------|
| **Maps JavaScript API** | แสดงแผนที่แบบ Interactive | Frontend Map Display |
| **Maps Static API** | สร้างรูปภาพแผนที่ | Email Templates, PDF Reports |
| **Street View Static API** | แสดง Street View แบบรูปภาพ | Property Listings |

### 🚀 Advanced APIs (เตรียมไว้ใช้อนาคต)
| API | คำอธิบาย | Use Case อนาคต |
|-----|----------|----------------|
| **Elevation API** | ดึงข้อมูลความสูงจากพิกัด | Terrain Analysis, Hiking Routes |
| **Time Zone API** | ดึงข้อมูล timezone จากพิกัด | Multi-timezone Booking System |
| **Roads API** | Snap to roads, Speed limits | GPS Tracking, Fleet Management |
| **Geolocation API** | หาตำแหน่งจาก IP/WiFi | Auto-detect User Location |

---

## 💻 การใช้งานใน Code

### ✅ วิธีที่ถูกต้อง - ใช้ GoogleMapsService (Recommended)

```php
<?php

use App\Services\GoogleMapsService;

// 1. Inject service
class DeliveryController extends Controller
{
    protected GoogleMapsService $mapsService;

    public function __construct(GoogleMapsService $mapsService)
    {
        $this->mapsService = $mapsService;
    }

    public function calculateDeliveryFee(Request $request)
    {
        // Service จะโหลดการตั้งค่าจาก database อัตโนมัติ
        $result = $this->mapsService->getDirections(
            ['lat' => 13.7563, 'lng' => 100.5018],
            ['lat' => 13.7308, 'lng' => 100.5418],
            'driving'
        );

        $distance = $result['distance']['value'] / 1000; // km
        $baseFee = Setting::get('google_maps_delivery_base_fee', 30);
        $costPerKm = Setting::get('google_maps_delivery_cost_per_km', 10);

        return $baseFee + ($distance * $costPerKm);
    }
}

// 2. หรือใช้แบบ app() helper
$service = app(GoogleMapsService::class);
$result = $service->geocode('กรุงเทพมหานคร');
```

### ❌ วิธีที่ไม่ถูกต้อง - อย่าทำ!

```php
// ❌ ห้ามใช้ config() โดยตรง
$apiKey = config('services.google_maps.api_key'); // NO!

// ❌ ห้ามใช้ env() โดยตรง
$apiKey = env('GOOGLE_MAPS_API_KEY'); // NO!

// ❌ ห้ามฮาร์ดโค้ด API Key
$apiKey = 'AIzaSy...'; // NO! NEVER!
```

---

## 🔌 Integration Patterns

### Pattern 1: Geocoding - แปลงที่อยู่เป็นพิกัด

```php
use App\Services\GoogleMapsService;

$service = app(GoogleMapsService::class);

// Forward Geocoding: Address → Coordinates
$result = $service->geocode('1600 Amphitheatre Parkway, Mountain View, CA');

// ผลลัพธ์:
[
    'formatted_address' => '1600 Amphitheatre Pkwy, Mountain View, CA 94043, USA',
    'location' => [
        'lat' => 37.4224764,
        'lng' => -122.0842499,
    ],
    'place_id' => 'ChIJ...',
    'address_components' => [...],
]

// Reverse Geocoding: Coordinates → Address
$result = $service->reverseGeocode(13.7563, 100.5018);
```

### Pattern 2: Directions - คำนวณเส้นทาง

```php
$origin = ['lat' => 13.7563, 'lng' => 100.5018];
$destination = ['lat' => 13.7308, 'lng' => 100.5418];

$result = $service->getDirections($origin, $destination, 'driving');

// ผลลัพธ์:
[
    'distance' => [
        'value' => 5234,      // เมตร
        'text' => '5.2 กม.',
    ],
    'duration' => [
        'value' => 960,       // วินาที
        'text' => '16 นาที',
    ],
    'start_address' => 'Siam Square, Bangkok',
    'end_address' => 'Chatuchak Market, Bangkok',
    'steps' => [...],
]
```

### Pattern 3: Distance Matrix - คำนวณหลายจุด

```php
$origins = [
    ['lat' => 13.7563, 'lng' => 100.5018],
    ['lat' => 13.7308, 'lng' => 100.5418],
];

$destinations = [
    ['lat' => 13.7465, 'lng' => 100.5347],
    ['lat' => 13.7278, 'lng' => 100.5241],
];

$result = $service->getDistanceMatrix($origins, $destinations, 'driving');

// ใช้สำหรับ: Delivery route optimization
```

### Pattern 4: Places Search - ค้นหาสถานที่

```php
$places = $service->searchNearby(
    13.7563,     // latitude
    100.5018,    // longitude
    'restaurant', // type
    1000         // radius (เมตร)
);

// ผลลัพธ์: รายการร้านอาหารรอบๆ 1 กม.
[
    [
        'place_id' => 'ChIJ...',
        'name' => 'Sushi Bar Bangkok',
        'vicinity' => 'Siam Square',
        'location' => ['lat' => ..., 'lng' => ...],
        'rating' => 4.5,
        'user_ratings_total' => 1234,
    ],
    ...
]
```

---

## 🎨 Frontend Integration

### JavaScript API

```html
<!-- 1. โหลด Google Maps JavaScript API -->
<script src="https://maps.googleapis.com/maps/api/js?key={{ setting('google_maps_api_key') }}&language=th"></script>

<script>
// 2. สร้างแผนที่
function initMap() {
    const center = {
        lat: parseFloat('{{ setting("google_maps_default_center_lat", 13.7563) }}'),
        lng: parseFloat('{{ setting("google_maps_default_center_lng", 100.5018) }}')
    };

    const map = new google.maps.Map(document.getElementById('map'), {
        zoom: {{ setting('google_maps_default_zoom', 12) }},
        center: center,
        mapTypeId: '{{ setting("google_maps_map_type", "roadmap") }}'
    });

    // 3. เพิ่ม Marker
    const marker = new google.maps.Marker({
        position: center,
        map: map,
        title: 'สำนักงานของเรา'
    });
}
</script>

<div id="map" style="height: 400px;"></div>
```

### Blade Helper (สร้างถ้ายังไม่มี)

```php
// app/Helpers/GoogleMapsHelper.php

if (!function_exists('google_maps_enabled')) {
    function google_maps_enabled(): bool
    {
        return Setting::get('google_maps_enabled', false);
    }
}

if (!function_exists('google_maps_api_key')) {
    function google_maps_api_key(): ?string
    {
        return Setting::get('google_maps_api_key');
    }
}
```

---

## 🏗️ ระบบที่ใช้งาน Google Maps

### ปัจจุบัน (มีการใช้งานแล้ว)

| โมดูล | ใช้ API | คำอธิบาย |
|-------|---------|----------|
| **Delivery Module** | Geocoding, Directions, Distance Matrix | คำนวณค่าจัดส่ง, แสดงแผนที่ |
| **Service Booking** | Geocoding, Directions | จองบริการตามตำแหน่ง |
| **Store Locator** | Geocoding, Places | ค้นหาร้านค้าใกล้เคียง |
| **Food Passport** | Geocoding | ตรวจสอบตำแหน่งต้นทางอาหาร |

### อนาคต (เตรียมไว้แล้ว)

| โมดูล | API ที่จะใช้ | Use Case |
|-------|--------------|----------|
| **Fleet Management** | Roads API, Directions | ติดตาม GPS รถจัดส่ง |
| **Multi-timezone Booking** | Time Zone API | จองนัดหมายข้ามเขตเวลา |
| **Property Listings** | Street View API, Elevation | แสดงรูปอสังหาฯ |
| **Hiking/Tour Module** | Elevation API, Directions | วางแผนเส้นทางท่องเที่ยว |

---

## ⚙️ การตั้งค่าที่สำคัญ

### 1. API Configuration

```php
// เปิด/ปิด Google Maps ทั้งระบบ
Setting::get('google_maps_enabled', true);

// เปิด/ปิดแต่ละ API
Setting::get('google_maps_geocoding_enabled', true);
Setting::get('google_maps_directions_enabled', true);
Setting::get('google_maps_distance_matrix_enabled', true);
Setting::get('google_maps_places_enabled', true);
```

### 2. Cache Settings

```php
// เปิด/ปิด Cache
Setting::get('google_maps_cache_enabled', true);

// Cache TTL (วินาที)
Setting::get('google_maps_cache_ttl', 86400); // 24 ชั่วโมง
```

### 3. Delivery Settings

```php
// ระยะทางสูงสุด (กม.)
Setting::get('google_maps_delivery_max_distance', 50);

// ค่าบริการต่อ กม. (บาท)
Setting::get('google_maps_delivery_cost_per_km', 10);

// ค่าบริการพื้นฐาน (บาท)
Setting::get('google_maps_delivery_base_fee', 30);
```

### 4. Rate Limiting

```php
// เปิด/ปิด Rate Limiting
Setting::get('google_maps_rate_limit_enabled', true);

// จำนวนคำขอต่อนาที
Setting::get('google_maps_rate_limit_per_minute', 60);
```

---

## 🔒 Security Best Practices

### 1. API Key Restrictions (ตั้งค่าใน Google Cloud Console)

```
Application Restrictions:
- HTTP referrers (recommended for web)
- Add: https://yourdomain.com/*

API Restrictions:
- Restrict key to: Geocoding API, Directions API, Distance Matrix API, Places API
```

### 2. Never Expose API Key

```php
// ✅ CORRECT: ใช้ใน Backend
$service = app(GoogleMapsService::class);
$result = $service->geocode($address);

// ❌ WRONG: อย่าแสดงใน JavaScript ถ้าไม่จำเป็น
<script>
const API_KEY = '{{ config("services.google_maps.api_key") }}'; // ระวัง!
</script>

// ✅ BETTER: ใช้ Blade directive มี restriction
@if(google_maps_enabled())
<script src="https://maps.googleapis.com/maps/api/js?key={{ google_maps_api_key() }}"></script>
@endif
```

---

## 💰 Cost Optimization

### 1. Enable Caching

```php
// ตั้งค่า Cache TTL ให้เหมาะสม
// - Geocoding: 24-48 ชั่วโมง (ที่อยู่ไม่เปลี่ยนบ่อย)
// - Directions: 1-6 ชั่วโมง (ทราฟฟิกเปลี่ยนบ่อย)
Setting::set('google_maps_cache_ttl', 86400, 'integer', 'google_maps');
```

### 2. Use Haversine Fallback

```php
// ถ้า API ล่ม หรือเกิน quota ใช้การคำนวณระยะทางตรง
Setting::get('google_maps_fallback_enabled', true);
Setting::get('google_maps_fallback_calculation', 'haversine');
```

### 3. Monitor Usage

```php
// ตรวจสอบการใช้งานที่ Google Cloud Console
// https://console.cloud.google.com/google/maps-apis/metrics
```

---

## 🧪 Testing

### Unit Test Example

```php
use Tests\TestCase;
use App\Services\GoogleMapsService;

class GoogleMapsServiceTest extends TestCase
{
    public function test_geocoding_returns_coordinates()
    {
        $service = app(GoogleMapsService::class);
        $result = $service->geocode('Bangkok');

        $this->assertArrayHasKey('location', $result);
        $this->assertArrayHasKey('lat', $result['location']);
        $this->assertArrayHasKey('lng', $result['location']);
    }

    public function test_directions_calculates_distance()
    {
        $service = app(GoogleMapsService::class);
        $result = $service->getDirections(
            ['lat' => 13.7563, 'lng' => 100.5018],
            ['lat' => 13.7308, 'lng' => 100.5418]
        );

        $this->assertArrayHasKey('distance', $result);
        $this->assertGreaterThan(0, $result['distance']['value']);
    }
}
```

---

## 📚 Documentation Links

### Official Google Maps Documentation
- **Getting Started**: https://developers.google.com/maps/documentation
- **Geocoding API**: https://developers.google.com/maps/documentation/geocoding
- **Directions API**: https://developers.google.com/maps/documentation/directions
- **Distance Matrix API**: https://developers.google.com/maps/documentation/distance-matrix
- **Places API**: https://developers.google.com/maps/documentation/places
- **JavaScript API**: https://developers.google.com/maps/documentation/javascript

### Internal Documentation
- **Setup Guide**: `/admin/settings/google-maps/guide`
- **Settings Page**: `/admin/settings/google-maps`
- **API Capabilities**: Tab "API Capabilities" in settings page

---

## 🎓 สำหรับ Claude AI / Developers

### เมื่อพัฒนาฟีเจอร์ใหม่ที่ต้องใช้ Google Maps:

1. **อย่าสร้างการตั้งค่าใหม่!** ใช้ `GoogleMapsService` ที่มีอยู่แล้ว
2. **Inject service** ใน constructor: `public function __construct(GoogleMapsService $mapsService)`
3. **ใช้ methods ที่มี**:
   - `geocode($address)` - แปลงที่อยู่ → พิกัด
   - `reverseGeocode($lat, $lng)` - แปลงพิกัด → ที่อยู่
   - `getDirections($origin, $destination, $mode)` - คำนวณเส้นทาง
   - `getDistanceMatrix($origins, $destinations, $mode)` - คำนวณหลายจุด
   - `searchNearby($lat, $lng, $type, $radius)` - ค้นหาสถานที่
   - `checkApiCapabilities()` - เช็คว่า API Key รองรับอะไรบ้าง

4. **ถ้าต้องการ API ใหม่** (Elevation, Timezone, Roads):
   - เพิ่ม method ใน `GoogleMapsService`
   - อัปเดต `checkApiCapabilities()` ให้ทดสอบ API ใหม่
   - เพิ่มการตั้งค่าใน `GoogleMapsSettingsSeeder` ถ้าจำเป็น

5. **ต้องการแสดงแผนที่ใน Frontend**:
   - ใช้ `google_maps_api_key()` helper
   - โหลด JavaScript API: `<script src="https://maps.googleapis.com/maps/api/js?key={{ google_maps_api_key() }}"></script>`

### Example: เพิ่มฟีเจอร์ Elevation API

```php
// 1. เพิ่ม method ใน GoogleMapsService.php

/**
 * รับข้อมูลความสูงจากพิกัด
 *
 * @param float $lat ละติจูด
 * @param float $lng ลองจิจูด
 * @return array ข้อมูลความสูง
 */
public function getElevation(float $lat, float $lng): array
{
    $this->ensureApiKey();

    $response = Http::get("{$this->baseUrl}/elevation/json", [
        'locations' => "{$lat},{$lng}",
        'key' => $this->apiKey,
    ]);

    $data = $response->json();

    if ($data['status'] !== 'OK') {
        throw new \Exception("Elevation API failed: {$data['status']}");
    }

    return [
        'elevation' => $data['results'][0]['elevation'], // เมตร
        'resolution' => $data['results'][0]['resolution'],
        'location' => ['lat' => $lat, 'lng' => $lng],
    ];
}

// 2. ใช้งานใน Controller
$service = app(GoogleMapsService::class);
$elevation = $service->getElevation(13.7563, 100.5018);
// Result: ['elevation' => 12.5, 'resolution' => 4.771976, ...]
```

---

## 🚀 Quick Start Checklist

เมื่อเริ่มใช้งาน Google Maps ในโปรเจค:

- [ ] ตั้งค่า API Key: `/admin/settings/google-maps`
- [ ] เปิดใช้งาน APIs ที่ต้องการ: Geocoding, Directions, Distance Matrix, Places
- [ ] ทดสอบการเชื่อมต่อ: คลิก "ทดสอบการเชื่อมต่อ"
- [ ] ตรวจสอบ API Capabilities: Tab "API Capabilities"
- [ ] ตั้งค่า Cache: เปิดใช้งาน + ตั้ง TTL (แนะนำ 24 ชั่วโมง)
- [ ] ตั้งค่า Delivery (ถ้าใช้): ระยะทางสูงสุด, ค่าบริการ
- [ ] เพิ่ม API restrictions ใน Google Cloud Console
- [ ] Monitor usage: ดูที่ Google Cloud Console > Metrics

---

## 📞 Support

หากมีปัญหาหรือคำถาม:
1. ตรวจสอบ Setup Guide: `/admin/settings/google-maps/guide`
2. ดู API Capabilities: `/admin/settings/google-maps` > Tab "API Capabilities"
3. ตรวจสอบ logs: `storage/logs/laravel.log`
4. ดู Google Cloud Console: https://console.cloud.google.com/

---

**Last Updated**: 2025-11-24
**Version**: 2.0
**Maintained By**: Development Team
