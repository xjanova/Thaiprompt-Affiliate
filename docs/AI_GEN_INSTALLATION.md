# AI Gen System - Installation Guide

## การติดตั้งและตั้งค่าระบบ

### Prerequisites

- PHP >= 8.1
- Composer
- MySQL >= 5.7 หรือ MariaDB
- Node.js & NPM (สำหรับ frontend)

### ขั้นตอนการติดตั้ง

#### 1. Clone Repository และติดตั้ง Dependencies

```bash
cd /path/to/Thaiprompt-Affiliate
composer install
npm install
```

#### 2. สร้างและตั้งค่า .env

```bash
cp .env.example .env
php artisan key:generate
```

แก้ไขการตั้งค่าฐานข้อมูลใน `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thaiprompt_affiliate
DB_USERNAME=root
DB_PASSWORD=your_password
```

#### 3. สร้างฐานข้อมูล

```sql
CREATE DATABASE thaiprompt_affiliate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### 4. Run Migrations

```bash
php artisan migrate
```

ระบบจะสร้าง tables ทั้งหมด รวมถึง:
- `ai_gen_providers` - ข้อมูล AI providers
- `ai_gen_provider_configs` - Configuration ของ providers
- `ai_gen_packages` - แพคเกจสำหรับขาย
- `ai_gen_subscriptions` - Subscription ของ users
- `ai_gen_quotas` - ตั้งค่า free quota
- `ai_gen_usage_logs` - บันทึกการใช้งาน
- `ai_gen_generations` - ผลลัพธ์ที่ generate

#### 5. Run Seeder เพื่อสร้างข้อมูลเริ่มต้น

```bash
php artisan db:seed --class=AiGenSeeder
```

Seeder จะสร้าง:
- ✅ Freepik Provider (พร้อม placeholder config)
- ✅ Vidu Provider (ปิดการใช้งาน - รอ implementation)
- ✅ Pixverse Provider (ปิดการใช้งาน - รอ implementation)
- ✅ 3 แพคเกจ: Starter, Professional, Enterprise
- ✅ Free quota settings (Default และ Admin)

#### 6. ตั้งค่า Freepik API

1. เข้า Freepik และสมัคร API key
2. เข้า Admin Panel: `/admin/ai-gen/providers`
3. เลือก Freepik provider
4. กรอก API key:

```json
[
  {
    "key": "api_key",
    "value": "your-freepik-api-key",
    "is_encrypted": true
  },
  {
    "key": "api_endpoint",
    "value": "https://api.freepik.com/v1",
    "is_encrypted": false
  }
]
```

5. คลิก "Test Connection" เพื่อทดสอบ

#### 7. Compile Assets (ถ้ามี frontend)

```bash
npm run dev
# หรือ
npm run build
```

### การตั้งค่าเพิ่มเติม

#### ปรับแต่ง Free Quota

1. เข้า Admin Panel: `/admin/ai-gen/quotas`
2. แก้ไข "Default Free Quota":
   - `free_image_daily`: จำนวนภาพฟรีต่อวัน
   - `free_image_monthly`: จำนวนภาพฟรีต่อเดือน
   - `free_video_daily`: จำนวนวีดีโอฟรีต่อวัน
   - `free_video_monthly`: จำนวนวีดีโอฟรีต่อเดือน

#### จัดการแพคเกจ

1. เข้า Admin Panel: `/admin/ai-gen/packages`
2. สามารถ:
   - แก้ไขราคา
   - เพิ่ม/ลด credits
   - เปลี่ยนระยะเวลา
   - ตั้งค่า recurring subscription

### การทดสอบระบบ

#### ทดสอบ API โดยตรง

```bash
# Get dashboard data
curl -X GET http://localhost/api/v1/ai-gen/dashboard \
  -H "Authorization: Bearer YOUR_TOKEN"

# Generate image
curl -X POST http://localhost/api/v1/ai-gen/generate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "provider": "freepik",
    "type": "image",
    "prompt": "A beautiful sunset over the ocean",
    "parameters": {
      "size": "1024x1024",
      "style": "realistic"
    }
  }'
```

#### ทดสอบผ่าน Postman

Import collection จาก `/docs/postman_collection.json` (ถ้ามี)

### Troubleshooting

#### ปัญหา: "Provider not configured"

**แก้ไข:**
1. ตรวจสอบว่าได้ตั้งค่า API key แล้ว
2. ทดสอบการเชื่อมต่อผ่าน Admin Panel
3. ตรวจสอบ logs: `storage/logs/laravel.log`

#### ปัญหา: "No available credits or quota"

**แก้ไข:**
1. ตรวจสอบ free quota settings
2. หรือซื้อแพคเกจ
3. หรือเปลี่ยน user เป็น admin (unlimited)

#### ปัญหา: Migration failed

**แก้ไข:**
1. ตรวจสอบ MySQL/MariaDB เปิดอยู่
2. ตรวจสอบ credentials ใน `.env`
3. ตรวจสอบว่าฐานข้อมูลมีอยู่
4. Run: `php artisan migrate:fresh` (⚠️ ลบข้อมูลเก่าทั้งหมด)

### Environment Variables

เพิ่มใน `.env` สำหรับ providers:

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

### การอัปเดตระบบ

```bash
# Pull latest code
git pull origin main

# Update dependencies
composer install
npm install

# Run new migrations
php artisan migrate

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild assets
npm run build
```

### Performance Optimization

```bash
# Cache config
php artisan config:cache

# Cache routes
php artisan route:cache

# Optimize autoloader
composer dump-autoload -o

# Cache views
php artisan view:cache
```

### การ Deploy สู่ Production

1. ตั้งค่า `.env`:
```env
APP_ENV=production
APP_DEBUG=false
```

2. Run optimizations:
```bash
php artisan optimize
```

3. ตั้งค่า Queue worker สำหรับ async processing (แนะนำ):
```bash
php artisan queue:work --daemon
```

4. ตั้งค่า Cron job สำหรับ scheduled tasks:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### การ Monitor ระบบ

- Usage logs: `/admin/ai-gen/usage-logs`
- Dashboard statistics: `/admin/ai-gen/dashboard`
- Laravel logs: `storage/logs/laravel.log`

### ติดต่อและรายงานปัญหา

หากพบปัญหาการใช้งาน:
1. ตรวจสอบ logs
2. ตรวจสอบ documentation
3. ติดต่อทีมพัฒนา

---

สร้างโดย: Thai Prompt Affiliate Team
อัปเดตล่าสุด: 2024-11-11
