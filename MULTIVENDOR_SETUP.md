# Multi-Vendor Marketplace - Setup Guide

## 📦 ภาพรวม

ระบบ Multi-vendor Marketplace ที่ครบวงจร ออกแบบมาเพื่อรองรับร้านค้าหลายพันร้าน พร้อมระบบ Package/Subscription และ Feature Management แบบยืดหยุ่น

---

## 🗃️ Database Schema

### ตารางที่สร้างใหม่

1. **vendor_packages** - แพ็คเกจร้านค้า (Free, Basic, Premium, Enterprise)
2. **vendor_stores** - ข้อมูลร้านค้าแต่ละร้าน
3. **vendor_package_features** - ฟีเจอร์พิเศษที่ซื้อเพิ่มได้
4. **vendor_subscriptions** - ประวัติการสมัครแพ็คเกจ
5. **vendor_features_usage** - ฟีเจอร์ที่ร้านค้าเปิดใช้งาน
6. **vendor_public_products** - สินค้าที่ส่งขออนุมัติไปหน้าหลัก
7. **vendor_marketing_campaigns** - แคมเปญการตลาด
8. **vendor_analytics** - สถิติและ Analytics รายวัน

### ตารางที่อัพเดต

- **products** - เพิ่ม `store_id`, `is_public_approved`, `public_approved_at`, `public_approved_by`
- **orders** - เพิ่ม `store_id`, `store_commission`, `store_earning`

---

## 🚀 การติดตั้ง

### 1. Run Migrations

```bash
php artisan migrate
```

### 2. Run Seeders

```bash
# สร้างแพ็คเกจเริ่มต้น
php artisan db:seed --class=VendorPackageSeeder

# สร้างฟีเจอร์เสริม
php artisan db:seed --class=VendorPackageFeatureSeeder
```

### 3. Verify Installation

```bash
# ตรวจสอบแพ็คเกจ
php artisan tinker
>>> App\Models\VendorPackage::count()
=> 4

# ตรวจสอบฟีเจอร์
>>> App\Models\VendorPackageFeature::count()
=> 12
```

---

## 📋 Models ที่สร้างใหม่

```
app/Models/
├── VendorPackage.php              - แพ็คเกจ
├── VendorStore.php                - ร้านค้า
├── VendorPackageFeature.php       - ฟีเจอร์พิเศษ
├── VendorSubscription.php         - การสมัครแพ็คเกจ
├── VendorFeatureUsage.php         - การใช้ฟีเจอร์
├── VendorPublicProduct.php        - สินค้า Public
├── VendorMarketingCampaign.php    - แคมเปญการตลาด
└── VendorAnalytics.php            - Analytics
```

---

## 💡 การใช้งาน Models

### สร้างร้านค้าใหม่

```php
use App\Models\VendorStore;
use App\Models\VendorPackage;

// Get free package
$freePackage = VendorPackage::where('package_slug', 'free')->first();

// Create store
$store = VendorStore::create([
    'user_id' => auth()->id(),
    'package_id' => $freePackage->id,
    'store_name' => 'ร้านของฉัน',
    'store_slug' => 'my-store',
    'store_description' => 'ร้านขายของดีมีคุณภาพ',
    'commission_rate' => $freePackage->commission_rate,
    'subscription_status' => 'trial',
    'trial_ends_at' => now()->addDays($freePackage->trial_days),
]);
```

### ตรวจสอบ Limitations

```php
// ตรวจสอบว่าเพิ่มสินค้าได้หรือไม่
if (!$store->canAddProducts()) {
    return response()->json([
        'error' => 'คุณถึงขีดจำกัดจำนวนสินค้าแล้ว กรุณาอัพเกรดแพ็คเกจ'
    ], 403);
}

// ตรวจสอบว่าร้านมีฟีเจอร์หรือไม่
if ($store->hasFeature('ai_bot')) {
    // Enable AI Bot features
}
```

### Subscribe Package

```php
use App\Models\VendorSubscription;

$subscription = VendorSubscription::create([
    'store_id' => $store->id,
    'package_id' => $premiumPackage->id,
    'subscription_type' => 'monthly',
    'amount' => $premiumPackage->price,
    'started_at' => now(),
    'expires_at' => now()->addMonth(),
    'payment_status' => 'paid',
    'status' => 'active',
]);

// Update store
$store->update([
    'package_id' => $premiumPackage->id,
    'subscription_status' => 'active',
    'subscription_started_at' => now(),
    'subscription_expires_at' => now()->addMonth(),
]);
```

### ซื้อฟีเจอร์เสริม

```php
use App\Models\VendorFeatureUsage;

$aiBot = VendorPackageFeature::where('feature_slug', 'ai-chatbot')->first();

VendorFeatureUsage::create([
    'store_id' => $store->id,
    'feature_id' => $aiBot->id,
    'activated_at' => now(),
    'expires_at' => now()->addMonth(),
    'is_active' => true,
]);
```

### ส่งสินค้าขออนุมัติ Public

```php
use App\Models\VendorPublicProduct;

$publicRequest = VendorPublicProduct::create([
    'store_id' => $store->id,
    'product_id' => $product->id,
    'requested_by' => auth()->id(),
    'request_note' => 'สินค้าคุณภาพดี ราคาถูก',
    'status' => 'pending',
]);
```

### อนุมัติสินค้า (Admin)

```php
$publicRequest->approve(
    reviewerId: auth()->id(),
    note: 'สินค้าผ่านการตรวจสอบ'
);

// หรือ ปฏิเสธ
$publicRequest->reject(
    reviewerId: auth()->id(),
    reason: 'สินค้าไม่เหมาะสม'
);
```

---

## 📊 Package Features

### Free Package
- 🆓 ฟรี
- 📦 สินค้า 10 รายการ
- 🖼️ รูปภาพ 5 รูป/สินค้า
- 💾 พื้นที่ 100 MB
- 📋 ออเดอร์ 50/เดือน
- 💰 Commission 15%

### Basic Package (฿999/เดือน)
- ✅ ทุกอย่างใน Free
- 📦 สินค้า 100 รายการ
- 🖼️ รูปภาพ 10 รูป/สินค้า
- 💾 พื้นที่ 1 GB
- 📋 ออเดอร์ 500/เดือน
- 🎨 ปรับแต่ง Theme
- 📢 เครื่องมือการตลาด
- 💰 Commission 10%
- 🆓 ทดลองใช้ 14 วัน

### Premium Package (฿2,999/เดือน)
- ✅ ทุกอย่างใน Basic
- 📦 สินค้าไม่จำกัด
- 🖼️ รูปภาพ 20 รูป/สินค้า
- 💾 พื้นที่ 5 GB
- 📋 ออเดอร์ไม่จำกัด
- 🌐 Custom Domain
- 🤖 AI Bot
- 📊 รายงานขั้นสูง
- 🚀 Priority Support
- 💰 Commission 7%
- 🆓 ทดลองใช้ 30 วัน

### Enterprise Package (Custom Price)
- ✅ ทุกอย่างใน Premium
- ♾️ ไม่จำกัดทั้งหมด
- 👨‍💼 Account Manager
- 🛠️ Custom Development
- 🔒 ความปลอดภัยขั้นสูง
- 💰 Commission 5%

---

## 🎯 Add-on Features

### Marketing
- 🤖 **AI ChatBot** - ฿499/เดือน
- 💬 **LINE Broadcast** - ฿299/เดือน
- 📧 **Email Marketing** - ฿199/เดือน
- 👥 **MLM System** - ฿1,999/เดือน

### Analytics
- 📊 **Advanced Analytics** - ฿399/เดือน
- 🔥 **Heatmap & Behavior** - ฿299/เดือน

### Storage
- 💾 **Extra Storage 5GB** - ฿99/เดือน
- 💾 **Extra Storage 10GB** - ฿179/เดือน

### Integration
- 📘 **Facebook Shop** - ฿249/เดือน
- 🎵 **TikTok Shop** - ฿249/เดือน

### Special
- 🏷️ **White Label** - ฿2,999/เดือน
- 🌐 **Custom Domain + SSL** - ฿299/ปี

---

## 🔐 Authorization

### ตรวจสอบสิทธิ์การเข้าถึง

```php
// Middleware
Route::middleware(['auth', 'store.owner'])->group(function () {
    // Seller routes
});

// Check if user owns store
if ($store->user_id !== auth()->id()) {
    abort(403);
}

// Check package permission
if (!$store->package->allow_ai_bot) {
    return redirect()->back()->with('error', 'กรุณาอัพเกรดแพ็คเกจ');
}
```

---

## 📈 Analytics

### บันทึก Analytics รายวัน

```php
use App\Models\VendorAnalytics;

// บันทึกอัตโนมัติ (ใน Scheduler)
VendorAnalytics::recordDailyAnalytics($store, today());

// Query Analytics
$monthlyStats = $store->analytics()
    ->thisMonth()
    ->get();

$totalRevenue = $monthlyStats->sum('gross_revenue');
$totalOrders = $monthlyStats->sum('orders_count');
```

---

## 🎨 UI Components (Coming Soon)

ระบบ UI/UX Dashboard สำหรับ Seller จะถูกพัฒนาในขั้นตอนถัดไป:

- ✅ Database Schema - **เสร็จสมบูรณ์**
- ✅ Models & Relationships - **เสร็จสมบูรณ์**
- ✅ Package Seeders - **เสร็จสมบูรณ์**
- ⏳ Layout Template - **Coming Soon**
- ⏳ Dashboard Pages - **Coming Soon**
- ⏳ Store Settings UI - **Coming Soon**
- ⏳ Product Management - **Coming Soon**
- ⏳ Order Management - **Coming Soon**
- ⏳ Marketing Tools UI - **Coming Soon**

---

## 📝 ขั้นตอนถัดไป

1. สร้าง Controllers สำหรับ Seller Dashboard
2. สร้าง Middleware สำหรับตรวจสอบ Package Limitations
3. สร้าง Views สำหรับ Seller Dashboard
4. พัฒนาระบบ Store Customization (Logo, Theme)
5. สร้างระบบ Public Product Approval
6. พัฒนา Marketing Tools UI
7. สร้างระบบ Analytics Dashboard

---

## 🤝 Support

หากมีคำถามหรือต้องการความช่วยเหลือ กรุณาติดต่อทีมพัฒนา

---

**Version:** 1.0.0
**Last Updated:** 2025-11-03
**Author:** Development Team
