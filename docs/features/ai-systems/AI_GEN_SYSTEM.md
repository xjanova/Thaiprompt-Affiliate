# AI Gen - Image & Video Generation System

## ภาพรวม

ระบบให้เช่าการใช้งาน AI สำหรับสร้างภาพและวีดีโอ พร้อมระบบจัดการ quota, subscription และรองรับหลาย AI providers

## คุณสมบัติหลัก

### 1. Multi-Provider Support
- รองรับหลาย AI providers (Freepik, Vidu, Pixverse, ฯลฯ)
- สามารถเพิ่ม provider ใหม่ได้ง่าย
- แต่ละ provider มี configuration แยกกัน

### 2. Package & Subscription System
- แพคเกจแบบ one-time purchase และ recurring subscription
- กำหนด credits สำหรับภาพและวีดีโอแยกกัน
- ระบบหมดอายุอัตโนมัติ

### 3. Quota Management
- Free quota รายวัน/รายเดือน
- Admin มี unlimited access
- User ต้องซื้อ package หรือใช้ free quota

### 4. Usage Tracking
- บันทึกทุกการใช้งาน
- ติดตาม status การ generate
- สถิติและ analytics

## โครงสร้างฐานข้อมูล

### Tables

#### `ai_gen_providers`
เก็บข้อมูล AI providers

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | ชื่อ provider |
| slug | string | URL-friendly name |
| type | enum | image, video, both |
| description | text | คำอธิบาย |
| supported_features | json | ฟีเจอร์ที่รองรับ |
| is_active | boolean | สถานะเปิด/ปิด |

#### `ai_gen_provider_configs`
เก็บ configuration ของแต่ละ provider

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| provider_id | bigint | FK to providers |
| config_key | string | ชื่อ config (api_key, endpoint, etc.) |
| config_value | text | ค่า config |
| is_encrypted | boolean | เข้ารหัสหรือไม่ |

#### `ai_gen_packages`
แพคเกจสำหรับขาย

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | ชื่อแพคเกจ |
| price | decimal | ราคา |
| image_credits | integer | จำนวน credits สำหรับภาพ |
| video_credits | integer | จำนวน credits สำหรับวีดีโอ |
| duration_days | integer | ระยะเวลา (วัน) |
| is_recurring | boolean | ต่ออายุอัตโนมัติหรือไม่ |

#### `ai_gen_subscriptions`
Subscription ของ user

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| user_id | bigint | FK to users |
| package_id | bigint | FK to packages |
| image_credits_total | integer | Credits ทั้งหมด (ภาพ) |
| image_credits_used | integer | Credits ที่ใช้แล้ว (ภาพ) |
| video_credits_total | integer | Credits ทั้งหมด (วีดีโอ) |
| video_credits_used | integer | Credits ที่ใช้แล้ว (วีดีโอ) |
| status | enum | active, expired, cancelled |
| expires_at | timestamp | วันหมดอายุ |

#### `ai_gen_quotas`
ตั้งค่า free quota

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| free_image_daily | integer | Free quota ภาพต่อวัน |
| free_image_monthly | integer | Free quota ภาพต่อเดือน |
| free_video_daily | integer | Free quota วีดีโอต่อวัน |
| free_video_monthly | integer | Free quota วีดีโอต่อเดือน |
| role | string | user, admin หรือ null (ทั้งหมด) |

#### `ai_gen_usage_logs`
บันทึกการใช้งาน

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| user_id | bigint | FK to users |
| provider_id | bigint | FK to providers |
| subscription_id | bigint | FK to subscriptions (nullable) |
| generation_type | string | image หรือ video |
| prompt | text | คำสั่งที่ใช้ |
| credits_used | integer | Credits ที่ใช้ |
| is_free_quota | boolean | ใช้ free quota หรือไม่ |
| status | string | pending, processing, completed, failed |

#### `ai_gen_generations`
ผลลัพธ์ที่ generate

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| user_id | bigint | FK to users |
| provider_id | bigint | FK to providers |
| type | string | image หรือ video |
| prompt | text | คำสั่งที่ใช้ |
| file_url | string | URL ของไฟล์ผลลัพธ์ |
| status | enum | pending, processing, completed, failed |
| is_favorite | boolean | เป็น favorite หรือไม่ |

## API Endpoints

### User APIs

#### GET `/api/v1/ai-gen/dashboard`
ดึงข้อมูล dashboard ของ user (quota, subscription, stats)

**Response:**
```json
{
  "success": true,
  "data": {
    "quota": {
      "image": {
        "daily": 3,
        "monthly": 20,
        "daily_limit": 3,
        "monthly_limit": 20
      },
      "video": {...}
    },
    "subscription": {
      "has_subscription": true,
      "package_name": "Professional",
      "image_credits": {
        "total": 200,
        "used": 50,
        "remaining": 150
      }
    },
    "stats": {...},
    "providers": [...]
  }
}
```

#### POST `/api/v1/ai-gen/generate`
สร้างภาพหรือวีดีโอ

**Request:**
```json
{
  "provider": "freepik",
  "type": "image",
  "prompt": "A beautiful sunset over the ocean",
  "parameters": {
    "size": "1024x1024",
    "style": "realistic"
  }
}
```

**Response:**
```json
{
  "success": true,
  "generation_id": 123,
  "external_id": "abc-123",
  "status": "pending",
  "data": {...}
}
```

#### GET `/api/v1/ai-gen/generations/{id}/status`
ตรวจสอบสถานะการ generate

#### GET `/api/v1/ai-gen/generations`
ดึงรายการ generations ของ user

**Query Parameters:**
- `type` - image หรือ video
- `status` - pending, processing, completed, failed
- `is_favorite` - true/false
- `per_page` - จำนวนต่อหน้า (default: 20)

#### GET `/api/v1/ai-gen/packages`
ดึงรายการแพคเกจทั้งหมด

#### POST `/api/v1/ai-gen/packages/{id}/purchase`
ซื้อแพคเกจ (ต้อง integrate กับ payment gateway)

### Admin APIs

#### GET `/admin/ai-gen/dashboard`
ดึงสถิติระบบ

#### GET `/admin/ai-gen/providers`
ดึงรายการ providers ทั้งหมด

#### POST `/admin/ai-gen/providers`
สร้าง provider ใหม่

**Request:**
```json
{
  "name": "New Provider",
  "slug": "new-provider",
  "type": "image",
  "description": "Description",
  "supported_features": ["text-to-image"],
  "is_active": true
}
```

#### POST `/admin/ai-gen/providers/{id}/config`
อัปเดต configuration

**Request:**
```json
{
  "configs": [
    {
      "key": "api_key",
      "value": "your-api-key",
      "is_encrypted": true
    },
    {
      "key": "api_endpoint",
      "value": "https://api.example.com",
      "is_encrypted": false
    }
  ]
}
```

#### POST `/admin/ai-gen/providers/{id}/test`
ทดสอบการเชื่อมต่อ provider

#### GET/POST/PUT `/admin/ai-gen/packages`
จัดการแพคเกจ

#### GET/POST/PUT `/admin/ai-gen/quotas`
จัดการ quota

#### GET `/admin/ai-gen/usage-logs`
ดึง usage logs พร้อม filter

## การเพิ่ม Provider ใหม่

### ขั้นตอน:

1. สร้าง class ใหม่ใน `app/Services/AiGen/` ที่ extend `BaseAiGenProvider`

```php
<?php

namespace App\Services\AiGen;

class ViduProvider extends BaseAiGenProvider
{
    public function generateImage(string $prompt, array $parameters = []): array
    {
        // Implementation
    }

    public function generateVideo(string $prompt, array $parameters = []): array
    {
        // Implementation
    }

    public function checkStatus(string $generationId): array
    {
        // Implementation
    }

    public function getResult(string $generationId): array
    {
        // Implementation
    }

    public function isConfigured(): bool
    {
        // Check if API key exists
        return !empty($this->getConfig('api_key'));
    }

    public function testConnection(): array
    {
        // Test API connection
    }
}
```

2. เพิ่ม provider ในฐานข้อมูลผ่าน Admin Panel หรือ Seeder

3. ตั้งค่า API credentials ผ่าน Admin Panel

4. ทดสอบการเชื่อมต่อ

## การตั้งค่าระบบ

### 1. Run Migrations

```bash
php artisan migrate
```

### 2. Run Seeder

```bash
php artisan db:seed --class=AiGenSeeder
```

### 3. ตั้งค่า Freepik API Key

1. ไปที่ Admin Panel -> AI Gen -> Providers
2. เลือก Freepik
3. กรอก API Key
4. ทดสอบการเชื่อมต่อ

### 4. ปรับแต่ง Quotas

1. ไปที่ Admin Panel -> AI Gen -> Quotas
2. แก้ไขค่า free quota ตามต้องการ

### 5. จัดการ Packages

1. ไปที่ Admin Panel -> AI Gen -> Packages
2. สร้าง/แก้ไขแพคเกจ
3. ตั้งราคา, credits และระยะเวลา

## การทำงานของระบบ

### สำหรับ Admin:
- เข้าใช้ได้ทันที (unlimited)
- ไม่ถูกหัก credits
- ทุกการ generate จะถูกบันทึกเป็น free quota

### สำหรับ User:
1. User พยายาม generate
2. ระบบตรวจสอบ:
   - มี active subscription และมี credits เหลือ? → ใช้ subscription credits
   - ไม่มี subscription? → ตรวจสอบ free quota
   - Free quota หมด? → แจ้งให้ซื้อแพคเกจ
3. บันทึก usage log
4. เรียก API ของ provider
5. บันทึกผลลัพธ์

## Environment Variables

เพิ่มใน `.env`:

```env
# Freepik API
FREEPIK_API_KEY=your-api-key
FREEPIK_API_ENDPOINT=https://api.freepik.com/v1

# Vidu API (future)
VIDU_API_KEY=
VIDU_API_ENDPOINT=

# Pixverse API (future)
PIXVERSE_API_KEY=
PIXVERSE_API_ENDPOINT=
```

## Security

- API keys ถูกเข้ารหัสในฐานข้อมูล
- Rate limiting ป้องกันการใช้งานเกินจำกัด
- Authorization middleware ตรวจสอบสิทธิ์
- Usage logs เพื่อ audit trail

## Roadmap

- [ ] Payment gateway integration
- [ ] Webhook support สำหรับ async generation
- [ ] Image editing features
- [ ] Video editing features
- [ ] Batch generation
- [ ] API rate limiting per user
- [ ] Credit sharing system
- [ ] Team accounts
