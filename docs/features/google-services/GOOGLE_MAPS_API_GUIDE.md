# 🗺️ Google Maps API Integration Guide

**คู่มือการใช้งาน Google Maps API ในระบบ TP-Affiliate**

---

## 📋 สารบัญ

1. [การตั้งค่า Google Maps API](#การตั้งค่า-google-maps-api)
2. [การ Config ในระบบ](#การ-config-ในระบบ)
3. [GoogleMapsService - API Reference](#googlemapsservice---api-reference)
4. [ตัวอย่างการใช้งาน](#ตัวอย่างการใช้งาน)
5. [การใช้งานกับ Hotel Management](#การใช้งานกับ-hotel-management)
6. [Best Practices](#best-practices)
7. [Troubleshooting](#troubleshooting)

---

## 🔧 การตั้งค่า Google Maps API

### 1. สร้าง Google Cloud Project

1. ไปที่ [Google Cloud Console](https://console.cloud.google.com/)
2. สร้าง Project ใหม่หรือเลือก Project ที่มีอยู่
3. เปิดใช้งาน APIs ที่จำเป็น:
   - **Maps JavaScript API** - สำหรับแสดงแผนที่บนเว็บ
   - **Geocoding API** - สำหรับแปลงที่อยู่เป็นพิกัด
   - **Directions API** - สำหรับคำนวณเส้นทาง
   - **Distance Matrix API** - สำหรับคำนวณระยะทาง
   - **Places API** - สำหรับค้นหาสถานที่

### 2. สร้าง API Key

```bash
# ใน Google Cloud Console
1. ไปที่ "APIs & Services" > "Credentials"
2. คลิก "Create Credentials" > "API Key"
3. จดบันทึก API Key
4. (แนะนำ) จำกัดการใช้งาน API Key:
   - Application restrictions: HTTP referrers (websites)
   - เพิ่ม domains ที่อนุญาต (เช่น *.yourdomain.com/*)
   - API restrictions: เลือกเฉพาะ APIs ที่ต้องการใช้
```

### 3. ตั้งค่า Billing

⚠️ **สำคัญ:** Google Maps APIs ต้องเปิดใช้งาน Billing Account

- ผู้ใช้ใหม่จะได้ Free Credit $300 ใช้ได้ 90 วัน
- หลังจากนั้นจะคิดค่าใช้จ่ายตามการใช้งานจริง
- [ดูราคา Google Maps Platform](https://mapsplatform.google.com/pricing/)

---

## 🔐 การ Config ในระบบ

### 1. เพิ่ม Environment Variables

แก้ไขไฟล์ `.env`:

```env
# Google Maps Configuration
GOOGLE_MAPS_API_KEY=your_google_maps_api_key_here
GOOGLE_MAPS_GEOCODING_ENABLED=true
GOOGLE_MAPS_DIRECTIONS_ENABLED=true
GOOGLE_MAPS_CACHE_TTL=86400
```

**คำอธิบาย:**
- `GOOGLE_MAPS_API_KEY` - API Key จาก Google Cloud Console
- `GOOGLE_MAPS_GEOCODING_ENABLED` - เปิด/ปิด Geocoding features
- `GOOGLE_MAPS_DIRECTIONS_ENABLED` - เปิด/ปิด Directions features
- `GOOGLE_MAPS_CACHE_TTL` - เวลา cache (วินาที), default = 86400 (1 วัน)

### 2. ตรวจสอบ Config

```bash
# ตรวจสอบว่า config ถูกโหลดแล้ว
php artisan tinker
>>> config('services.google_maps.api_key')
=> "your_api_key_here"
```

### 3. Clear Cache (ถ้าจำเป็น)

```bash
php artisan config:clear
php artisan cache:clear
```

---

## 📚 GoogleMapsService - API Reference

### Location: `app/Services/GoogleMapsService.php`

ระบบมี Service สำเร็จรูปสำหรับเรียกใช้ Google Maps APIs:

### 1. **Reverse Geocoding** - แปลงพิกัดเป็นที่อยู่

```php
use App\Services\GoogleMapsService;

$mapsService = app(GoogleMapsService::class);

// แปลง Latitude, Longitude เป็นที่อยู่
$result = $mapsService->reverseGeocode(13.7563, 100.5018);

// ผลลัพธ์:
[
    'formatted_address' => 'ถนนราชดำเนินกลาง พระนคร กรุงเทพมหานคร 10200',
    'address_components' => [
        'street_number' => null,
        'street' => 'ถนนราชดำเนินกลาง',
        'subdistrict' => 'พระบรมมหาราชวัง',
        'district' => 'พระนคร',
        'province' => 'กรุงเทพมหานคร',
        'country' => 'ประเทศไทย',
        'postal_code' => '10200',
    ],
    'place_id' => 'ChIJ...',
    'location' => [
        'lat' => 13.7563,
        'lng' => 100.5018,
    ],
]
```

### 2. **Geocoding** - แปลงที่อยู่เป็นพิกัด

```php
// แปลงที่อยู่เป็น Latitude, Longitude
$result = $mapsService->geocode('วัดพระแก้ว กรุงเทพฯ');

// ผลลัพธ์:
[
    'formatted_address' => 'Na Phra Lan Rd, Phra Borom Maha Ratchawang, ...',
    'location' => [
        'lat' => 13.7519,
        'lng' => 100.4925,
    ],
    'place_id' => 'ChIJ...',
    // ...
]
```

### 3. **Get Directions** - คำนวณเส้นทาง

```php
$origin = ['lat' => 13.7563, 'lng' => 100.5018];
$destination = ['lat' => 13.7519, 'lng' => 100.4925];

$result = $mapsService->getDirections($origin, $destination, 'driving');

// ผลลัพธ์:
[
    'distance' => [
        'value' => 1500,      // เมตร
        'text' => '1.5 กม.',
    ],
    'duration' => [
        'value' => 300,       // วินาที
        'text' => '5 นาที',
    ],
    'start_address' => '...',
    'end_address' => '...',
    'steps' => [...],        // ขั้นตอนการเดินทาง
]
```

**Modes:** `driving`, `walking`, `bicycling`, `transit`

### 4. **Distance Matrix** - คำนวณระยะทางหลายจุด

```php
$origins = [
    ['lat' => 13.7563, 'lng' => 100.5018],
    ['lat' => 13.7519, 'lng' => 100.4925],
];

$destinations = [
    ['lat' => 13.7465, 'lng' => 100.5345],
    ['lat' => 13.8065, 'lng' => 100.5602],
];

$result = $mapsService->getDistanceMatrix($origins, $destinations);

// คำนวณระยะทางจาก origins ทุกจุด ไป destinations ทุกจุด
```

### 5. **Get Place Details** - ดูรายละเอียดสถานที่

```php
$placeId = 'ChIJ...'; // จาก Geocoding result

$result = $mapsService->getPlaceDetails($placeId);

// ผลลัพธ์:
[
    'name' => 'วัดพระแก้ว',
    'formatted_address' => '...',
    'rating' => 4.7,
    'user_ratings_total' => 50000,
    'photos' => [...],
    // ...
]
```

### 6. **Search Nearby** - ค้นหาสถานที่ใกล้เคียง

```php
$lat = 13.7563;
$lng = 100.5018;
$type = 'restaurant';  // ร้านอาหาร
$radius = 1000;        // รัศมี 1 กม.

$results = $mapsService->searchNearby($lat, $lng, $type, $radius);

// ผลลัพธ์: Array ของสถานที่ใกล้เคียง
[
    [
        'place_id' => 'ChIJ...',
        'name' => 'ร้านอาหาร ABC',
        'vicinity' => 'ถนนราชดำเนิน',
        'location' => ['lat' => 13.75, 'lng' => 100.50],
        'rating' => 4.5,
        'user_ratings_total' => 250,
    ],
    // ...
]
```

**Types:** `restaurant`, `hotel`, `hospital`, `atm`, `bank`, `airport`, etc.
[ดู Place Types ทั้งหมด](https://developers.google.com/maps/documentation/places/web-service/supported_types)

---

## 💡 ตัวอย่างการใช้งาน

### Example 1: ใน Controller

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GoogleMapsService;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function __construct(
        protected GoogleMapsService $mapsService
    ) {}

    /**
     * แปลงที่อยู่เป็นพิกัด
     */
    public function geocodeAddress(Request $request)
    {
        $address = $request->input('address');

        try {
            $result = $this->mapsService->geocode($address);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * แปลงพิกัดเป็นที่อยู่
     */
    public function reverseGeocode(Request $request)
    {
        $lat = $request->input('lat');
        $lng = $request->input('lng');

        try {
            $result = $this->mapsService->reverseGeocode($lat, $lng);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
```

### Example 2: ใน Blade Template (Frontend)

```blade
{{-- resources/views/admin/hotels/create.blade.php --}}

<div class="mb-4">
    <label>ที่อยู่โรงแรม</label>
    <textarea id="hotel-address" name="address" rows="3"></textarea>
    <button type="button" onclick="geocodeAddress()">
        🗺️ แปลงเป็นพิกัด
    </button>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label>Latitude</label>
        <input type="number" id="latitude" name="latitude" step="0.000001">
    </div>
    <div>
        <label>Longitude</label>
        <input type="number" id="longitude" name="longitude" step="0.000001">
    </div>
</div>

<div id="map" style="width: 100%; height: 400px;"></div>

@push('scripts')
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&libraries=places"></script>
<script>
let map;
let marker;

// เริ่มต้น Google Map
function initMap() {
    const lat = parseFloat(document.getElementById('latitude').value) || 13.7563;
    const lng = parseFloat(document.getElementById('longitude').value) || 100.5018;

    map = new google.maps.Map(document.getElementById('map'), {
        center: { lat, lng },
        zoom: 15
    });

    marker = new google.maps.Marker({
        position: { lat, lng },
        map: map,
        draggable: true
    });

    // เมื่อลากหมุด อัพเดทพิกัด
    marker.addListener('dragend', function() {
        const position = marker.getPosition();
        document.getElementById('latitude').value = position.lat();
        document.getElementById('longitude').value = position.lng();

        // Reverse geocode เพื่อดึงที่อยู่
        reverseGeocode(position.lat(), position.lng());
    });
}

// แปลงที่อยู่เป็นพิกัด
async function geocodeAddress() {
    const address = document.getElementById('hotel-address').value;

    if (!address) {
        alert('กรุณากรอกที่อยู่');
        return;
    }

    try {
        const response = await fetch('/api/geocode', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ address })
        });

        const result = await response.json();

        if (result.success) {
            const { lat, lng } = result.data.location;

            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;

            // อัพเดทแผนที่
            const position = { lat, lng };
            map.setCenter(position);
            marker.setPosition(position);
        } else {
            alert('ไม่พบที่อยู่: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาด');
    }
}

// แปลงพิกัดเป็นที่อยู่
async function reverseGeocode(lat, lng) {
    try {
        const response = await fetch('/api/reverse-geocode', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ lat, lng })
        });

        const result = await response.json();

        if (result.success) {
            document.getElementById('hotel-address').value = result.data.formatted_address;
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// เริ่มต้นแผนที่เมื่อโหลดหน้า
document.addEventListener('DOMContentLoaded', initMap);
</script>
@endpush
```

---

## 🏨 การใช้งานกับ Hotel Management

### Use Cases สำหรับระบบโรงแรม

#### 1. **เพิ่มโรงแรม - Auto-fill ที่อยู่จากพิกัด**

```php
// ใน HotelController::store()
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required',
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
        // ...
    ]);

    // Auto-complete address จาก coordinates
    if (empty($validated['address']) && $validated['latitude'] && $validated['longitude']) {
        $mapsService = app(GoogleMapsService::class);

        try {
            $location = $mapsService->reverseGeocode(
                $validated['latitude'],
                $validated['longitude']
            );

            $validated['address'] = $location['formatted_address'];
            $validated['city'] = $location['address_components']['district'] ?? '';
            $validated['state'] = $location['address_components']['province'] ?? '';
            $validated['country'] = $location['address_components']['country'] ?? 'Thailand';
            $validated['postal_code'] = $location['address_components']['postal_code'] ?? '';
        } catch (\Exception $e) {
            \Log::warning('Failed to reverse geocode', ['error' => $e->getMessage()]);
        }
    }

    $hotel = Hotel::create($validated);

    return redirect()->route('admin.hotels.index')
        ->with('success', 'เพิ่มโรงแรมสำเร็จ');
}
```

#### 2. **ค้นหาโรงแรมในรัศมี X กม.**

```php
// ใน HotelController::nearbyHotels()
public function nearbyHotels(Request $request)
{
    $userLat = $request->input('lat');
    $userLng = $request->input('lng');
    $radius = $request->input('radius', 5); // km

    // ใช้ Haversine formula สำหรับคำนวณระยะทาง
    $hotels = Hotel::selectRaw("
        *,
        (6371 * acos(cos(radians(?))
        * cos(radians(latitude))
        * cos(radians(longitude) - radians(?))
        + sin(radians(?))
        * sin(radians(latitude)))) AS distance
    ", [$userLat, $userLng, $userLat])
        ->having('distance', '<', $radius)
        ->orderBy('distance')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $hotels,
    ]);
}
```

#### 3. **คำนวณระยะทางจากโรงแรมไปสถานที่สำคัญ**

```php
// ใน Hotel Model
public function calculateDistanceToPlace($placeId)
{
    $mapsService = app(GoogleMapsService::class);

    $placeDetails = $mapsService->getPlaceDetails($placeId);
    $destination = $placeDetails['geometry']['location'];

    $origin = [
        'lat' => $this->latitude,
        'lng' => $this->longitude,
    ];

    return $mapsService->getDirections($origin, $destination);
}
```

#### 4. **แสดงแผนที่โรงแรมในหน้า Show**

```blade
{{-- resources/views/admin/hotels/show.blade.php --}}

<div class="card">
    <div class="card-header">📍 ตำแหน่งโรงแรม</div>
    <div class="card-body">
        <div id="hotel-map" style="width: 100%; height: 400px;"></div>

        <div class="mt-3">
            <p><strong>ที่อยู่:</strong> {{ $hotel->address }}</p>
            <p><strong>พิกัด:</strong> {{ $hotel->latitude }}, {{ $hotel->longitude }}</p>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}"></script>
<script>
function initHotelMap() {
    const lat = {{ $hotel->latitude ?? 13.7563 }};
    const lng = {{ $hotel->longitude ?? 100.5018 }};

    const map = new google.maps.Map(document.getElementById('hotel-map'), {
        center: { lat, lng },
        zoom: 16
    });

    new google.maps.Marker({
        position: { lat, lng },
        map: map,
        title: '{{ $hotel->name }}'
    });
}

document.addEventListener('DOMContentLoaded', initHotelMap);
</script>
@endpush
```

---

## ✅ Best Practices

### 1. **ใช้ Cache เพื่อประหยัด API Quota**

```php
// GoogleMapsService มี cache อยู่แล้ว (24 ชั่วโมง)
// แต่คุณสามารถปรับได้ผ่าน .env
GOOGLE_MAPS_CACHE_TTL=86400  # 24 hours
```

### 2. **Error Handling**

```php
try {
    $result = $mapsService->geocode($address);
} catch (\Exception $e) {
    // Google Maps API key ไม่ถูกต้อง
    if (str_contains($e->getMessage(), 'API key')) {
        return response()->json([
            'error' => 'Google Maps API is not configured',
        ], 500);
    }

    // ไม่พบที่อยู่
    if (str_contains($e->getMessage(), 'ZERO_RESULTS')) {
        return response()->json([
            'error' => 'ไม่พบที่อยู่ที่ระบุ',
        ], 404);
    }

    // อื่นๆ
    \Log::error('Google Maps API Error', ['error' => $e->getMessage()]);
    return response()->json(['error' => 'เกิดข้อผิดพลาด'], 500);
}
```

### 3. **Rate Limiting**

```php
// ใน RouteServiceProvider.php หรือ middleware
RateLimiter::for('maps-api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

### 4. **Lazy Loading Maps**

```blade
{{-- โหลด Google Maps API เฉพาะหน้าที่ต้องการใช้ --}}
@push('scripts')
    @once
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&libraries=places" defer></script>
    @endonce
@endpush
```

---

## 🐛 Troubleshooting

### ปัญหาที่พบบ่อย

#### 1. **Error: "API key is not configured"**

```bash
# ตรวจสอบว่าตั้งค่า .env แล้ว
php artisan config:clear
php artisan cache:clear

# ตรวจสอบค่า
php artisan tinker
>>> config('services.google_maps.api_key')
```

#### 2. **Error: "REQUEST_DENIED"**

- ✅ ตรวจสอบว่าเปิดใช้งาน APIs ที่จำเป็นใน Google Cloud Console
- ✅ ตรวจสอบว่า API Key มีสิทธิ์เรียก API นั้นๆ
- ✅ ตรวจสอบว่าตั้งค่า Billing Account แล้ว

#### 3. **Error: "OVER_QUERY_LIMIT"**

- Google Maps APIs มี Free tier limit:
  - Geocoding API: 40,000 requests/month
  - Directions API: 40,000 requests/month
  - Distance Matrix API: 40,000 elements/month

**แก้ไข:**
- เพิ่ม cache TTL
- ใช้ Database caching แทน API calls บ่อยๆ
- Upgrade billing plan

#### 4. **แผนที่ไม่แสดง (สีเทา)**

```javascript
// ตรวจสอบ console errors
// ถ้าเป็น "Google Maps JavaScript API error: InvalidKeyMapError"
// แปลว่า API Key ไม่ถูกต้องหรือไม่ได้เปิดใช้งาน Maps JavaScript API
```

---

## 📖 เอกสารเพิ่มเติม

- [Google Maps Platform Documentation](https://developers.google.com/maps/documentation)
- [Geocoding API](https://developers.google.com/maps/documentation/geocoding)
- [Directions API](https://developers.google.com/maps/documentation/directions)
- [Places API](https://developers.google.com/maps/documentation/places)
- [Google Maps Pricing](https://mapsplatform.google.com/pricing/)

---

## 🎯 สรุป

### ขั้นตอนการใช้งาน Google Maps ในระบบ

1. ✅ **Setup Google Cloud Project** - สร้าง project และ enable APIs
2. ✅ **Get API Key** - สร้างและจำกัดสิทธิ์ API key
3. ✅ **Config .env** - เพิ่ม `GOOGLE_MAPS_API_KEY=...`
4. ✅ **Use GoogleMapsService** - เรียกใช้ methods ต่างๆ
5. ✅ **Frontend Integration** - เพิ่ม Google Maps JavaScript API
6. ✅ **Test & Monitor** - ทดสอบและตรวจสอบ usage

### Features ที่พร้อมใช้งาน

- ✅ Geocoding (ที่อยู่ → พิกัด)
- ✅ Reverse Geocoding (พิกัด → ที่อยู่)
- ✅ Directions (คำนวณเส้นทาง)
- ✅ Distance Matrix (ระยะทางหลายจุด)
- ✅ Place Details (รายละเอียดสถานที่)
- ✅ Nearby Search (ค้นหาใกล้เคียง)
- ✅ Caching (ประหยัด API quota)
- ✅ Error Handling (จัดการ errors)

---

**🎊 Happy Mapping! 🗺️**

ถ้ามีคำถามหรือต้องการความช่วยเหลือเพิ่มเติม สามารถดูเอกสารเพิ่มเติมได้จาก:
- `app/Services/GoogleMapsService.php` - Source code
- [Google Maps Platform Documentation](https://developers.google.com/maps)
