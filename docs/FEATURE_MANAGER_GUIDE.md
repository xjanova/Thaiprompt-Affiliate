# 🚀 คู่มือระบบ Feature Manager สำหรับร้านค้า

## สารบัญ

1. [ภาพรวม](#ภาพรวม)
2. [คุณสมบัติหลัก](#คุณสมบัติหลัก)
3. [โครงสร้างระบบ](#โครงสร้างระบบ)
4. [การใช้งานสำหรับร้านค้า](#การใช้งานสำหรับร้านค้า)
5. [การจัดการสำหรับ Admin](#การจัดการสำหรับ-admin)
6. [API Reference](#api-reference)
7. [Database Schema](#database-schema)

---

## ภาพรวม

ระบบ Feature Manager เป็นระบบที่ช่วยให้ร้านค้าสามารถซื้อและจัดการฟีเจอร์เสริมต่างๆ ได้ตามต้องการ โดยมีทั้งแบบซื้อครั้งเดียว (One-time) และแบบสมัครรายเดือน/รายปี (Subscription)

### จุดเด่นของระบบ

✅ **ซื้อได้ทุกเวลา** - ร้านค้าสามารถซื้อฟีเจอร์เพิ่มเติมได้ตลอด 24/7
✅ **Versioning** - ติดตามเวอร์ชันของแต่ละฟีเจอร์
✅ **Changelog** - ดูประวัติการพัฒนาและอัพเดทของฟีเจอร์
✅ **Multiple Payment Methods** - รองรับการชำระเงินหลายช่องทาง (Wallet, Stripe, PromptPay)
✅ **Subscription Management** - จัดการการต่ออายุอัตโนมัติ
✅ **Usage Tracking** - บันทึกการใช้งานฟีเจอร์แต่ละตัว

---

## คุณสมบัติหลัก

### 1. ประเภทฟีเจอร์

#### Sales & Marketing
- **Advanced Analytics** - ระบบวิเคราะห์ข้อมูลขั้นสูง
- **Email Marketing** - ส่งอีเมลการตลาดอัตโนมัติ
- **Multi-Channel Selling** - ขายข้ามแพลตฟอร์ม
- **Loyalty Program** - ระบบสะสมแต้มลูกค้า
- **SEO Boost** - เพิ่มการมองเห็นบน Google

#### Inventory & Operations
- **Inventory Pro** - จัดการสต็อกสินค้าขั้นสูง
- **Barcode System** - ระบบบาร์โค้ด
- **Warehouse Management** - จัดการคลังสินค้า

#### Branding & Design
- **Custom Domain** - ใช้โดเมนของคุณเอง
- **Theme Customization** - ปรับแต่งธีมร้านค้า
- **Logo Generator** - สร้างโลโก้อัตโนมัติ

### 2. รูปแบบการชำระเงิน

- **One-time Purchase** - ซื้อครั้งเดียว ใช้งานตลอด
- **Monthly Subscription** - สมัครรายเดือน
- **Yearly Subscription** - สมัครรายปี (ประหยัดกว่า)

### 3. ช่องทางการชำระเงิน

- **Wallet** - ใช้เงินในกระเป๋า (แนะนำ - ไม่มีค่าธรรมเนียม)
- **Stripe** - บัตรเครดิต/เดบิต
- **PromptPay** - QR Code ชำระเงิน

---

## โครงสร้างระบบ

### Database Tables

```
vendor_features
├── id
├── name
├── slug
├── description
├── price
├── price_type (one_time, monthly, yearly)
├── current_version
├── features_list (JSON)
└── is_active

vendor_feature_versions
├── id
├── vendor_feature_id
├── version
├── release_notes
├── changes (JSON)
└── released_at

vendor_feature_purchases
├── id
├── vendor_id
├── vendor_feature_id
├── purchase_number
├── price_paid
├── payment_method
├── status (active, expired, cancelled)
├── activated_at
└── expires_at

vendor_feature_changelogs
├── id
├── vendor_feature_id
├── type (added, changed, fixed, removed)
├── title
├── description
└── published_at

vendor_feature_usage_logs
├── id
├── vendor_id
├── vendor_feature_id
├── action (purchased, activated, used)
└── metadata (JSON)
```

---

## การใช้งานสำหรับร้านค้า

### 1. ดูฟีเจอร์ที่มีให้เลือก

```php
// ใน Controller
use App\Services\VendorFeature\VendorFeatureService;

$featureService = app(VendorFeatureService::class);
$features = $featureService->getAvailableFeatures();
$featuredFeatures = $featureService->getFeaturedFeatures();
```

### 2. ซื้อฟีเจอร์

```php
$vendor = auth()->user()->vendor;
$feature = VendorFeature::find($featureId);

try {
    $purchase = $featureService->purchaseFeature(
        vendor: $vendor,
        feature: $feature,
        paymentMethod: 'wallet', // or 'stripe', 'promptpay'
        transactionId: null // optional for stripe/promptpay
    );

    return redirect()->route('vendor.features.index')
        ->with('success', 'Feature purchased successfully!');
} catch (\Exception $e) {
    return back()->with('error', $e->getMessage());
}
```

### 3. ดูฟีเจอร์ที่ซื้อแล้ว

```php
$myFeatures = $featureService->getVendorFeatures($vendor);

// ตรวจสอบว่ามีฟีเจอร์หรือไม่
if ($featureService->hasActiveFeature($vendor, $featureId)) {
    // มีฟีเจอร์นี้
}
```

### 4. ต่ออายุฟีเจอร์ (สำหรับ Subscription)

```php
$purchase = VendorFeaturePurchase::find($purchaseId);

$featureService->renewFeature(
    purchase: $purchase,
    paymentMethod: 'wallet'
);
```

### 5. ยกเลิกฟีเจอร์

```php
$featureService->cancelFeature(
    purchase: $purchase,
    reason: 'No longer needed'
);
```

---

## การจัดการสำหรับ Admin

### 1. สร้างฟีเจอร์ใหม่

```php
VendorFeature::create([
    'name' => 'New Feature',
    'slug' => 'new-feature',
    'description' => 'Full description here...',
    'short_description' => 'Short description',
    'price' => 299.00,
    'price_type' => 'monthly',
    'current_version' => '1.0.0',
    'features_list' => [
        'Feature 1',
        'Feature 2',
        'Feature 3',
    ],
    'icon' => 'star',
    'category' => 'marketing',
    'is_active' => true,
    'is_featured' => false,
]);
```

### 2. เพิ่มเวอร์ชันใหม่

```php
VendorFeatureVersion::create([
    'vendor_feature_id' => $feature->id,
    'version' => '1.1.0',
    'release_notes' => 'What's new in this version',
    'changes' => [
        'Added new dashboard',
        'Fixed bugs',
        'Improved performance',
    ],
    'change_type' => 'feature', // feature, bugfix, enhancement, breaking
    'released_at' => now(),
    'is_major' => false,
]);

// อัพเดท current version
$feature->update(['current_version' => '1.1.0']);
```

### 3. เพิ่ม Changelog

```php
VendorFeatureChangelog::create([
    'vendor_feature_id' => $feature->id,
    'type' => 'added', // added, changed, deprecated, removed, fixed, security
    'title' => 'New Export Feature',
    'description' => 'You can now export reports to PDF and Excel',
    'priority' => 'high', // low, medium, high, critical
    'published_at' => now(),
    'is_visible' => true,
]);
```

### 4. ดูสถิติฟีเจอร์

```php
$stats = $featureService->getFeatureStatistics($feature);
/*
[
    'total_purchases' => 150,
    'active_purchases' => 120,
    'total_revenue' => 44850.00,
    'average_price' => 299.00,
    'latest_version' => '1.1.0',
    'total_versions' => 3,
    'total_changelogs' => 8,
]
*/
```

### 5. ดูสถิติร้านค้า

```php
$vendorStats = $featureService->getVendorStatistics($vendor);
/*
[
    'total_features' => 5,
    'active_features' => 4,
    'total_spent' => 1495.00,
    'expiring_soon' => 1,
]
*/
```

---

## API Reference

### GET /api/v1/vendor/features

ดูฟีเจอร์ทั้งหมดที่มีให้เลือก

**Query Parameters:**
- `category` (optional) - กรองตามหมวดหมู่
- `featured` (optional) - แสดงเฉพาะฟีเจอร์แนะนำ

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Advanced Analytics",
            "slug": "advanced-analytics",
            "description": "...",
            "price": 299.00,
            "price_type": "monthly",
            "current_version": "1.0.0",
            "features_list": ["..."],
            "is_featured": true
        }
    ]
}
```

### POST /api/v1/vendor/features/purchase

ซื้อฟีเจอร์

**Request Body:**
```json
{
    "feature_id": 1,
    "payment_method": "wallet"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Feature purchased successfully",
    "data": {
        "purchase_id": 123,
        "purchase_number": "FP-20250124-ABC12",
        "feature": {
            "name": "Advanced Analytics"
        },
        "price_paid": 299.00,
        "status": "active",
        "activated_at": "2025-01-24T10:00:00Z"
    }
}
```

### GET /api/v1/vendor/features/my-features

ดูฟีเจอร์ที่ซื้อแล้ว

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 123,
            "purchase_number": "FP-20250124-ABC12",
            "feature": {
                "name": "Advanced Analytics",
                "current_version": "1.0.0"
            },
            "price_paid": 299.00,
            "status": "active",
            "expires_at": "2025-02-24T10:00:00Z"
        }
    ]
}
```

### POST /api/v1/vendor/features/{id}/renew

ต่ออายุฟีเจอร์

**Request Body:**
```json
{
    "payment_method": "wallet"
}
```

### POST /api/v1/vendor/features/{id}/cancel

ยกเลิกฟีเจอร์

**Request Body:**
```json
{
    "reason": "No longer needed"
}
```

---

## ตัวอย่างการใช้งานใน Blade

### แสดงรายการฟีเจอร์

```blade
<div class="features-grid">
    @foreach($features as $feature)
    <div class="feature-card">
        <div class="feature-icon">
            <i class="fas fa-{{ $feature->icon }}"></i>
        </div>
        <h3>{{ $feature->name }}</h3>
        <p>{{ $feature->short_description }}</p>

        <div class="price">
            ฿{{ number_format($feature->price, 2) }}
            @if($feature->price_type === 'monthly')
                /เดือน
            @elseif($feature->price_type === 'yearly')
                /ปี
            @endif
        </div>

        <ul class="feature-list">
            @foreach($feature->features_list as $item)
            <li>{{ $item }}</li>
            @endforeach
        </ul>

        @if($vendor->hasFeature($feature->id))
            <button class="btn btn-success" disabled>
                <i class="fas fa-check"></i> เปิดใช้งานแล้ว
            </button>
        @else
            <a href="{{ route('vendor.features.purchase', $feature->id) }}"
               class="btn btn-primary">
                ซื้อเลย
            </a>
        @endif
    </div>
    @endforeach
</div>
```

### แสดง Changelog

```blade
<div class="changelog">
    <h2>ประวัติการอัพเดท</h2>

    @foreach($feature->publishedChangelogs as $log)
    <div class="changelog-item">
        <span class="badge badge-{{ $log->getTypeBadgeColor() }}">
            {{ $log->type }}
        </span>
        <span class="badge badge-{{ $log->getPriorityBadgeColor() }}">
            {{ $log->priority }}
        </span>

        <h4>{{ $log->title }}</h4>
        <p>{{ $log->description }}</p>

        <small class="text-muted">
            {{ $log->published_at->diffForHumans() }}
        </small>
    </div>
    @endforeach
</div>
```

---

## Best Practices

### 1. ตรวจสอบก่อนใช้ฟีเจอร์

```php
// ในทุก Controller ที่ต้องการใช้ฟีเจอร์
public function index()
{
    $vendor = auth()->user()->vendor;

    if (!$vendor->hasFeatureBySlug('advanced-analytics')) {
        return redirect()->route('vendor.features.index')
            ->with('warning', 'Please purchase Advanced Analytics feature first');
    }

    // โค้ดที่ใช้ฟีเจอร์...
}
```

### 2. Auto-renewal สำหรับ Subscription

สร้าง Scheduled Task:

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        $service = app(VendorFeatureService::class);
        $result = $service->autoRenewExpiringFeatures();

        Log::info('Auto-renewal completed', $result);
    })->daily();
}
```

### 3. แจ้งเตือนฟีเจอร์ใกล้หมดอายุ

```php
public function checkExpiringFeatures()
{
    $vendor = auth()->user()->vendor;
    $expiring = app(VendorFeatureService::class)
        ->getExpiringFeatures($vendor, 7); // 7 days

    if ($expiring->count() > 0) {
        // ส่งการแจ้งเตือน
        event(new FeaturesExpiringSoon($vendor, $expiring));
    }
}
```

---

## การทดสอบ

### Unit Tests

```php
public function test_can_purchase_feature()
{
    $vendor = Vendor::factory()->create();
    $feature = VendorFeature::factory()->create(['price' => 299]);

    $service = app(VendorFeatureService::class);
    $purchase = $service->purchaseFeature($vendor, $feature, 'wallet');

    $this->assertDatabaseHas('vendor_feature_purchases', [
        'vendor_id' => $vendor->id,
        'vendor_feature_id' => $feature->id,
        'status' => 'active',
    ]);
}
```

---

Made with ❤️ by ThaiPrompt Team
