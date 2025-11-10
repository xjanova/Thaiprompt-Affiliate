# 🔌 ระบบจัดการ API - API Access Control System

ระบบจัดการ API ที่ครบครันสำหรับ Thaiprompt-Affiliate พร้อมระบบควบคุมการเข้าถึง โควต้า และการตรวจสอบการใช้งานแบบเรียลไทม์

## 📋 สารบัญ

- [คุณสมบัติหลัก](#คุณสมบัติหลัก)
- [การติดตั้ง](#การติดตั้ง)
- [การใช้งาน](#การใช้งาน)
- [API Endpoints Management](#api-endpoints-management)
- [API Keys Management](#api-keys-management)
- [การใช้งาน Middleware](#การใช้งาน-middleware)
- [การตรวจสอบการใช้งาน](#การตรวจสอบการใช้งาน)
- [ตัวอย่างการเรียกใช้ API](#ตัวอย่างการเรียกใช้-api)

---

## ✨ คุณสมบัติหลัก

### 1. จัดการ API Endpoints
- ✅ เปิด/ปิดการใช้งาน API endpoints แต่ละตัว
- 📝 อธิบายรายละเอียดว่าแต่ละ API ใช้ทำอะไร
- 🎯 จัดหมวดหมู่ API ตามประเภท (Users, Products, Orders, etc.)
- 🔍 ฟิลเตอร์และค้นหา API endpoints
- 📊 ดูสถิติการใช้งานแต่ละ endpoint

### 2. จัดการ API Keys
- 🔑 สร้าง API keys สำหรับแต่ละผู้ใช้หรือแอพพลิเคชัน
- 🛡️ กำหนดสิทธิ์การเข้าถึง (scopes: read, write, delete)
- 🌐 จำกัด IP addresses ที่สามารถใช้ API key ได้
- ⏰ กำหนดวันหมดอายุของ API key
- 📈 ดูประวัติการใช้งาน API key

### 3. ระบบโควต้า (Quota System)
- ⏱️ จำกัดจำนวนคำขอต่อนาที
- 📅 จำกัดจำนวนคำขอต่อชั่วโมง
- 📆 จำกัดจำนวนคำขอต่อวัน
- 📊 จำกัดจำนวนคำขอต่อเดือน
- 🎯 กำหนดโควต้าแยกตาม API key หรือ endpoint

### 4. การตรวจสอบการใช้งาน (Usage Tracking)
- 📝 บันทึกทุกการเรียกใช้ API
- ⚡ เก็บเวลาตอบสนอง (response time)
- 🚦 บันทึก response code (200, 404, 500, etc.)
- 🔍 ตรวจสอบ request/response payload
- 📊 สร้างรายงานการใช้งาน

### 5. UI Management Dashboard
- 🎨 หน้าจัดการสวยงามด้วย Tailwind CSS
- 🌓 รองรับ Dark Mode
- 📊 แสดงสถิติแบบเรียลไทม์
- 🔍 ฟิลเตอร์และค้นหาขั้นสูง
- 📈 กราฟแสดงการใช้งาน

---

## 🚀 การติดตั้ง

### 1. รัน Migrations

```bash
php artisan migrate
```

Migrations ที่จะถูกสร้าง:
- `api_endpoints` - เก็บข้อมูล API endpoints
- `api_keys` - เก็บ API keys
- `api_usage_logs` - เก็บ log การใช้งาน
- `api_quotas` - เก็บข้อมูลโควต้า

### 2. รัน Seeder (ถ้าต้องการข้อมูลตัวอย่าง)

```bash
php artisan db:seed --class=ApiEndpointSeeder
```

Seeder จะสร้างตัวอย่าง API endpoints ประมาณ 17 endpoints ครอบคลุม:
- User Management
- Products
- Orders
- Analytics
- Affiliate
- Hotels
- Wallet
- AI/Chatbot

### 3. ตรวจสอบ Middleware

Middleware `ApiAccessControl` ได้ถูกลงทะเบียนใน `bootstrap/app.php` แล้วในชื่อ `api.access`

---

## 💻 การใช้งาน

### เข้าถึงหน้าจัดการ

#### จัดการ API Endpoints
```
URL: /admin/api-management/endpoints
```

หน้านี้ให้คุณ:
- ดูรายการ API endpoints ทั้งหมด
- เพิ่ม/แก้ไข/ลบ endpoints
- เปิด/ปิดการใช้งาน endpoints
- กำหนด rate limits
- ดูสถิติการใช้งาน

#### จัดการ API Keys
```
URL: /admin/api-management/keys
```

หน้านี้ให้คุณ:
- สร้าง API keys ใหม่
- กำหนดสิทธิ์การเข้าถึง
- จำกัด IP addresses
- กำหนดโควต้า
- ดู usage dashboard
- รีเซ็ตการใช้งานรายเดือน

---

## 🔧 API Endpoints Management

### สร้าง Endpoint ใหม่

1. ไปที่ `/admin/api-management/endpoints`
2. คลิก "เพิ่ม Endpoint ใหม่"
3. กรอกข้อมูล:
   - **ชื่อ**: ชื่อของ endpoint (เช่น "รายการผู้ใช้")
   - **Path**: เส้นทาง API (เช่น `api/v1/users`)
   - **Method**: HTTP method (GET, POST, PUT, PATCH, DELETE)
   - **Category**: หมวดหมู่ (เช่น "Users", "Products")
   - **คำอธิบาย**: อธิบายว่า API นี้ใช้ทำอะไร
   - **Rate Limits**:
     - ต่อนาที (เช่น 60)
     - ต่อชั่วโมง (เช่น 1000)
     - ต่อวัน (เช่น 10000)
   - **Allowed Roles**: บทบาทที่สามารถเข้าถึง (admin, user, seller)

### แก้ไข Endpoint

1. ไปที่รายการ endpoints
2. คลิกปุ่ม "แก้ไข" (ไอคอนดินสอ)
3. แก้ไขข้อมูลตามต้องการ
4. บันทึก

### เปิด/ปิดการใช้งาน

คลิกปุ่มสถานะ (สีเขียว/แดง) เพื่อเปิดหรือปิดการใช้งาน endpoint ทันที

---

## 🔑 API Keys Management

### สร้าง API Key ใหม่

1. ไปที่ `/admin/api-management/keys`
2. คลิก "สร้าง API Key ใหม่"
3. กรอกข้อมูล:
   - **ชื่อ**: ชื่อของ API key (เช่น "Mobile App Production")
   - **ผู้ใช้**: เลือกผู้ใช้ที่เป็นเจ้าของ key (optional)
   - **คำอธิบาย**: อธิบายวัตถุประสงค์
   - **Allowed Endpoints**: เลือก endpoints ที่อนุญาต (ถ้าไม่เลือก = อนุญาตทั้งหมด)
   - **Allowed IPs**: ใส่ IP addresses ที่อนุญาต (หนึ่งบรรทัดต่อ IP)
   - **Scopes**: เลือกสิทธิ์ (read, write, delete)
   - **Rate Limits**: กำหนดโควต้า (override endpoint limits)
   - **Monthly Quota**: จำนวนสูงสุดต่อเดือน
   - **วันหมดอายุ**: กำหนดวันหมดอายุ (optional)

4. คลิก "สร้าง"
5. **สำคัญ**: API key แบบเต็มจะแสดงเพียงครั้งเดียว! คัดลอกและเก็บไว้อย่างปลอดภัย

### ตัวอย่าง API Key Format

```
sk_test_xJ9KpL2mN4oP5qR6sT7uV8wX9yZ0aB1cD2eF3gH4iJ5k...
sk_live_aB1cD2eF3gH4iJ5kxJ9KpL2mN4oP5qR6sT7uV8wX9yZ0...
```

- `sk_test_` = Test environment
- `sk_live_` = Production environment

### จัดการ API Key

#### ดูรายละเอียด
คลิกปุ่ม "ดูรายละเอียด" เพื่อดู:
- สถิติการใช้งาน
- ประวัติ requests
- Top endpoints ที่ใช้บ่อย
- กราฟการใช้งาน

#### รีเซ็ตการใช้งาน
คลิกปุ่ม "รีเซ็ต" เพื่อรีเซ็ตการนับการใช้งานรายเดือน

#### เปิด/ปิดการใช้งาน
คลิกปุ่มสถานะเพื่อเปิดหรือปิดการใช้งาน key ทันที

---

## 🛡️ การใช้งาน Middleware

### วิธีที่ 1: ใช้กับ Route ทั้งกลุ่ม

```php
// routes/api.php
Route::middleware(['api.access'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    // ... more routes
});
```

### วิธีที่ 2: ใช้กับ Route เดียว

```php
Route::get('/users', [UserController::class, 'index'])
    ->middleware('api.access');
```

### วิธีที่ 3: ใช้ใน Controller

```php
class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('api.access');
    }
}
```

---

## 📊 การตรวจสอบการใช้งาน

### ดูสถิติแบบเรียลไทม์

#### Dashboard Metrics
หน้าแรกแสดง:
- จำนวน API keys ทั้งหมด
- จำนวน requests วันนี้
- จำนวน failed requests
- จำนวน active endpoints

#### รายละเอียด API Key
แสดง:
- การใช้งานในเดือนนี้
- โควต้าที่เหลือ
- อัตราการใช้งาน (%)
- ใช้ครั้งล่าสุดเมื่อไหร่

#### รายละเอียด Endpoint
แสดง:
- จำนวน requests ทั้งหมด
- Success rate (%)
- Average response time
- กราฟการใช้งาน

### ดาวน์โหลดรายงาน

```php
// ใช้ Controller method
public function downloadReport(Request $request)
{
    $logs = ApiUsageLog::dateRange($startDate, $endDate)->get();
    // Export to CSV or Excel
}
```

---

## 🌐 ตัวอย่างการเรียกใช้ API

### 1. เรียกใช้ด้วย Header

```bash
curl -X GET "https://yourdomain.com/api/v1/users" \
  -H "X-API-Key: sk_live_your_api_key_here" \
  -H "Content-Type: application/json"
```

### 2. เรียกใช้ด้วย Bearer Token

```bash
curl -X GET "https://yourdomain.com/api/v1/users" \
  -H "Authorization: Bearer sk_live_your_api_key_here" \
  -H "Content-Type: application/json"
```

### 3. เรียกใช้ด้วย JavaScript (Fetch API)

```javascript
fetch('https://yourdomain.com/api/v1/users', {
  method: 'GET',
  headers: {
    'X-API-Key': 'sk_live_your_api_key_here',
    'Content-Type': 'application/json'
  }
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));
```

### 4. เรียกใช้ด้วย PHP (cURL)

```php
$apiKey = 'sk_live_your_api_key_here';
$url = 'https://yourdomain.com/api/v1/users';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-Key: ' . $apiKey,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);
```

---

## ⚠️ Response Codes

### Success
- `200` OK - สำเร็จ
- `201` Created - สร้างข้อมูลสำเร็จ

### Client Errors
- `400` Bad Request - ข้อมูลไม่ถูกต้อง
- `401` Unauthorized - ไม่มี API key หรือ key ไม่ถูกต้อง
- `403` Forbidden - ไม่มีสิทธิ์เข้าถึง endpoint นี้
- `404` Not Found - ไม่พบข้อมูล
- `429` Too Many Requests - เกินโควต้าที่กำหนด

### Server Errors
- `500` Internal Server Error - เกิดข้อผิดพลาดในระบบ

---

## 🔒 Security Best Practices

### 1. เก็บ API Keys อย่างปลอดภัย
- ❌ **ห้าม** commit API keys ลง Git
- ✅ ใช้ Environment Variables (`.env`)
- ✅ เก็บใน Secret Manager (AWS Secrets, Azure Key Vault)

### 2. จำกัด IP Addresses
- กำหนด IP whitelist สำหรับ production keys
- ใช้ VPN หรือ Fixed IP สำหรับการเข้าถึง

### 3. กำหนด Scopes ที่เหมาะสม
- ใช้ `read` สำหรับ read-only applications
- ใช้ `write` เฉพาะเมื่อจำเป็น
- ระวัง `delete` scope

### 4. กำหนดวันหมดอายุ
- ตั้งวันหมดอายุสำหรับ temporary keys
- Rotate keys เป็นประจำ (ทุก 90 วัน)

### 5. ตรวจสอบ Usage Logs
- ตรวจสอบ failed requests
- หา unusual patterns
- Alert เมื่อมี suspicious activity

---

## 📈 Rate Limiting Guidelines

### แนะนำสำหรับ Endpoint Types

| Endpoint Type | Per Minute | Per Hour | Per Day |
|---------------|------------|----------|---------|
| Public Read (GET) | 100 | 2000 | 20000 |
| Authenticated Read | 60 | 1000 | 10000 |
| Write (POST/PUT) | 20 | 200 | 1000 |
| Delete | 10 | 100 | 500 |
| Analytics | 30 | 500 | 5000 |
| AI/Heavy Processing | 10 | 100 | 500 |

---

## 🔄 Maintenance

### ล้างข้อมูล Logs เก่า

```bash
# ใน Console Command หรือ Scheduler
php artisan api:cleanup-logs --days=30
```

หรือเพิ่มใน `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // ลบ logs เก่ากว่า 30 วัน
    $schedule->call(function () {
        \App\Models\ApiQuota::cleanup(30);
    })->daily();

    // รีเซ็ตโควต้ารายเดือนทุกต้นเดือน
    $schedule->call(function () {
        \App\Models\ApiKey::resetMonthlyUsage();
    })->monthlyOn(1, '00:00');
}
```

---

## 🐛 Troubleshooting

### ปัญหา: "API key is required"
**แก้ไข**: ตรวจสอบว่าส่ง header `X-API-Key` หรือ `Authorization: Bearer` ไปด้วย

### ปัญหา: "Invalid or expired API key"
**แก้ไข**:
- ตรวจสอบว่า key ถูกต้อง
- ตรวจสอบว่า key ยังไม่หมดอายุ
- ตรวจสอบว่า key ยัง active อยู่

### ปัญหา: "IP address not allowed"
**แก้ไข**: ตรวจสอบ IP whitelist ของ API key หรือลบข้อจำกัด IP

### ปัญหา: "Rate limit exceeded"
**แก้ไข**:
- รอให้หมดช่วงเวลา rate limit
- ขอเพิ่มโควต้า
- ใช้ API key อื่น

### ปัญหา: "Access denied to this endpoint"
**แก้ไข**: ตรวจสอบว่า API key มีสิทธิ์เข้าถึง endpoint นี้หรือไม่

---

## 📞 Support

หากมีปัญหาหรือข้อสงสัย สามารถติดต่อได้ที่:
- Email: support@thaiprompt.com
- GitHub Issues: [Report Issue](https://github.com/yourusername/thaiprompt-affiliate/issues)

---

## 📝 License

ระบบนี้เป็นส่วนหนึ่งของ Thaiprompt-Affiliate Platform

---

**สร้างโดย**: Thaiprompt Development Team
**อัพเดทล่าสุด**: 2025-11-10
**เวอร์ชั่น**: 1.0.0
